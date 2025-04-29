<?php
// src/Controller/AyaOrderController.php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\WalletTransaction;
use App\Entity\GiftCard;
use App\Entity\FidelityPoint;
use App\Form\AyaOrderType;
use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use App\Repository\UserRepository;
use App\Repository\WalletTransactionRepository;
use App\Repository\GiftCardRepository;
use App\Repository\FidelityPointRepository;
use App\Repository\OrderRepository;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\ProductRepository;

class AyaOrderController extends AbstractController
{
    // Dans AyaOrderController.php, méthode new()

    #[Route('/aya/order/new', name: 'aya_order_new')]
    public function new(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        WalletTransactionRepository $walletTransactionRepository,
        GiftCardRepository $giftCardRepository,
        FidelityPointRepository $fidelityPointRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        // ✅ 1. Récupérer l'utilisateur connecté
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        $walletCredit = $walletTransactionRepository->calculateWalletBalance($user);
        // echo "Wallet credit for user {$userId}: {$walletCredit} TND\n";
        // Log du solde calculé
        error_log("Wallet credit for user {$userId}: {$walletCredit} TND");

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // ✅ Récupérer les points de fidélité de l'utilisateur en utilisant getUserPoints
        $points = $fidelityPointRepository->getUserPoints($user);

        // ✅ 2. Récupérer le panier
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $this->addFlash('warning', 'No cart found.');
            return $this->redirectToRoute('aya_cart');
        }

        // ✅ 3. Récupérer les produits du panier
        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        // ✅ 4. Calcul du total
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        $total = number_format($total, 2, '.', '');

        // ✅ 5. Initialiser la commande
        $order = new Order();
        $order->setUser($user);
        $order->setOrderedAt(new \DateTime());

        // ✅ 6. Appliquer les réductions
        $finalPrice = $total;

        // Coupon
        $couponCode = $request->get('coupon_code');
        if ($couponCode) {
            if ($couponCode == 'PROMO10') {
                $couponDiscount = 0.10 * $total;
                $finalPrice -= $couponDiscount;
            }
        }

        // Gift Card
        $giftCardCode = $request->get('gift_card_code');
        $giftCardPin = $request->get('gift_card_pin');
        if ($giftCardCode && $giftCardPin) {
            $giftCard = $giftCardRepository->findOneBy(['code' => $giftCardCode, 'pin' => $giftCardPin]);
            if ($giftCard && !$giftCard->isUsed()) {
                $finalPrice -= $giftCard->getBalance();
                $giftCard->setIsUsed(true);
                $em->persist($giftCard);
            }
        }

        // Wallet Credit
        $walletAmount = $request->get('wallet_credit');
        if ($walletAmount) {
            $walletAmount = floatval($walletAmount);
            if ($walletAmount > $walletCredit) {
                $this->addFlash('error', "Wallet credit amount exceeds available balance ({$walletCredit} TND).");
                $walletAmount = 0;
            } elseif ($walletAmount < 0) {
                $this->addFlash('error', "Wallet credit amount cannot be negative.");
                $walletAmount = 0;
            } else {
                $finalPrice -= $walletAmount;

                // Enregistrer une transaction de type 'payment' pour le montant utilisé
                if ($walletAmount > 0) {
                    $walletTransaction = new WalletTransaction();
                    $walletTransaction->setUser($user);
                    $walletTransaction->setAmount($walletAmount);
                    $walletTransaction->setType('payment');
                    $walletTransaction->setCreatedAt(new \DateTime());
                    $walletTransaction->setDescription('Payment via Wallet for order');
                    $em->persist($walletTransaction);
                }
            }
        }

        // Points de fidélité utilisés
        $pointsUsed = $request->get('points');
        $pointsMinimum = 100; // 📌 Seuil minimum de points

        if ($pointsUsed) {
            $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fidelityPoints && $fidelityPoints->getPoints() >= $pointsMinimum) {
                if ($fidelityPoints->getPoints() >= $pointsUsed) {
                    $discountAmount = $pointsUsed * 0.1;
                    $finalPrice -= $discountAmount;
                    $fidelityPoints->setPoints($fidelityPoints->getPoints() - $pointsUsed);
                    $em->persist($fidelityPoints);
                }
            }
        }

        // ✅ 7. Enregistrer le montant final
        $order->setTotalPrice($finalPrice);

        // ✅ 8. Formulaire de commande
        $form = $this->createForm(AyaOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $finalTotalFromForm = $request->request->get('aya_order')['final_total'] ?? null;
                if ($finalTotalFromForm !== null) {
                    $order->setTotalPrice(floatval($finalTotalFromForm));
                }

                $requestPaymentMethod = $request->request->get('aya_order')['payment_method'] ?? null;
                $hiddenPaymentMethod = $request->request->get('aya_order')['payment_method_hidden'] ?? null;

                if (empty($requestPaymentMethod) && $hiddenPaymentMethod === 'wallet_only') {
                    $order->setPaymentMethod('wallet_only');
                } else {
                    $order->setPaymentMethod($requestPaymentMethod);
                }

                if ($order->getTotalPrice() == 0.00) {
                    $order->setPaymentMethod('Wallet Only');
                }
                $order->setStatus('PENDING');

                // ✅ 9. Nouveau panier vide
                $newCart = new Cart();
                $newCart->setUser($user);
                $em->persist($newCart);

                $order->setCart($newCart);
                $em->persist($order);

                // ✅ 10. Supprimer les produits et panier actuel
                foreach ($cartProducts as $item) {
                    $em->remove($item);
                }
                $em->remove($cart);

                // ✅ 11. Ajouter les POINTS FIDÉLITÉ pour la commande
                $earnedPoints = floor($finalPrice / 10);
                if ($earnedPoints > 0) {
                    $fidelityPoint = new FidelityPoint();
                    $fidelityPoint->setUser($user);
                    $fidelityPoint->setPoints($earnedPoints);
                    $fidelityPoint->setType('earned');
                    $fidelityPoint->setDate(new \DateTimeImmutable());
                    $fidelityPoint->setReason('Commande validée');
                    $em->persist($fidelityPoint);
                }
                // ✅ 12. Tout sauvegarder
                $em->flush();
                $session->set('last_order_id', $order->getOrderId());

                // ✅ 13. Retourner une réponse JSON pour les requêtes AJAX
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'order_id' => $order->getOrderId(),
                        'message' => "Order placed successfully! You earned {$earnedPoints} points!"
                    ]);
                }

                // Pour les requêtes non-AJAX, rediriger normalement
                $this->addFlash('success', "Order placed successfully! You earned {$earnedPoints} points!");
                return $this->redirectToRoute('aya_order_confirm', [
                    'id' => $order->getOrderId()
                ]);
            } else {
                // Si le formulaire n'est pas valide, retourner les erreurs pour les requêtes AJAX
                if ($request->isXmlHttpRequest()) {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $errors
                    ], 400);
                }
            }
        }

        return $this->render('aya_order/aya_order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'total' => $total,
            'wallet_credit' => $walletCredit,
            'STRIPE_PUBLIC_KEY' => $_ENV['STRIPE_PUBLIC_KEY'],
            'points' => $points,
        ]);
    }

    #[Route('/api/order/new', name: 'api_aya_order_new', methods: ['POST'])]
    public function apiNew(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        ProductRepository $productRepository,    // ← ajouté
        WalletTransactionRepository $walletTransactionRepository,
        GiftCardRepository $giftCardRepository,
        FidelityPointRepository $fidelityPointRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Récupérer l'utilisateur…
        $userId = $session->get('user_id') ?? ($data['user_id'] ?? null);
        $user   = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // **1) Calcul du total à partir du JSON `items`**
        $total = 0;
        foreach ($data['items'] ?? [] as $entry) {
            $prod = $productRepository->find($entry['product_id']);
            if (!$prod) {
                return new JsonResponse(['error' => "Product {$entry['product_id']} not found"], 404);
            }
            $qty = max(1, (int)$entry['quantity']);
            $total += $prod->getPrice() * $qty;
        }

        $finalPrice = $total;

        // **2) Appliquer les points utilisés (si besoin)**
        $pointsUsed = $data['points'] ?? 0;
        if ($pointsUsed > 0) {
            $fpEntity = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fpEntity && $fpEntity->getPoints() >= $pointsUsed) {
                $finalPrice -= $pointsUsed * 0.1;
                $fpEntity->setPoints($fpEntity->getPoints() - $pointsUsed);
                $em->persist($fpEntity);
            }
        }

        // **3) Création de la commande**
        $order = new Order();
        $order->setUser($user)
            ->setExactAddress($data['exact_address'])
            ->setEventDate(new \DateTime($data['event_date']))
            ->setPaymentMethod($data['payment_method'])
            ->setOrderedAt(new \DateTime())
            ->setTotalPrice($finalPrice)
            ->setStatus('PENDING');
        $em->persist($order);

        // **4) Nouveau panier vide (optionnel)**
        $newCart = new Cart();
        $newCart->setUser($user);
        $em->persist($newCart);
        $order->setCart($newCart);

        // **5) Enregistrement des points gagnés**
        $earnedPoints = floor($finalPrice / 10);
        if ($earnedPoints > 0) {
            $fp = new FidelityPoint();
            $fp->setUser($user)
                ->setPoints($earnedPoints)
                ->setType('earned')
                ->setDate(new \DateTimeImmutable())
                ->setReason('Commande API');
            $em->persist($fp);
        }

        $em->flush();

        return new JsonResponse([
            'message'      => 'Order created successfully via API',
            'order_id'     => $order->getOrderId(),
            'total_price'  => $order->getTotalPrice(),
            'points_used'  => $pointsUsed,
            'earned_points' => $earnedPoints,
        ], 201);
    }






    // #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    // public function confirm(Order $order = null): Response
    // {
    //     if (!$order) {
    //         throw $this->createNotFoundException("Order not found.");
    //     }

    //     return $this->render('aya_order/ayaconfirmation.html.twig', [
    //         'order' => $order,
    //     ]);
    // }

    #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    public function confirm(OrderRepository $orderRepository, $id = null): Response
    {
        if (!$id) {
            $this->addFlash('error', 'Order ID is missing.');
            return $this->redirectToRoute('aya_cart');
        }

        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('aya_order/ayaconfirmation.html.twig', [
            'order' => $order,
        ]);
    }



    // #[Route('/api/order/new', name: 'api_order_new', methods: ['POST'])]
    // public function apiNewOrder(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     CartRepository $cartRepository,
    //     CartProductRepository $cartProductRepository,
    //     SessionInterface $session,
    //     WalletTransactionRepository $walletTransactionRepository,
    //     GiftCardRepository $giftCardRepository,
    //     FidelityPointRepository $fidelityPointRepository,
    //     UserRepository $userRepository
    // ): JsonResponse {
    //     $data = json_decode($request->getContent(), true);

    //     // ✅ Récupérer user
    //     $userId = $session->get('user_id') ?? ($data['user_id'] ?? null);
    //     $user = $userRepository->find($userId);
    //     if (!$user) {
    //         return new JsonResponse(['error' => 'Unauthorized'], 401);
    //     }

    //     // ✅ Récupérer panier
    //     $cart = $cartRepository->findOneBy(['user' => $user]);
    //     if (!$cart) {
    //         return new JsonResponse(['error' => 'No cart found'], 404);
    //     }

    //     $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

    //     $total = 0;
    //     foreach ($cartProducts as $item) {
    //         $total += $item->getTotalPrice();
    //     }

    //     $finalPrice = $total;

    //     // ✅ Appliquer la réduction des points fidélité si atteinte du minimum
    //     $pointsMinimum = 100; // 💬 Seuil minimum pour utiliser les points
    //     $pointsUsed = $data['points'] ?? 0; // Points envoyés depuis Postman

    //     if ($pointsUsed) {
    //         $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
    //         if ($fidelityPoints) {
    //             if ($fidelityPoints->getPoints() >= $pointsMinimum) {
    //                 if ($fidelityPoints->getPoints() >= $pointsUsed) {
    //                     $discountAmount = $pointsUsed * 0.1; // 1 point = 0.1 dinar
    //                     $finalPrice -= $discountAmount;
    //                     $fidelityPoints->setPoints($fidelityPoints->getPoints() - $pointsUsed);
    //                     $em->persist($fidelityPoints);
    //                     $em->flush();
    //                 }
    //             }
    //         }
    //     }

    //     // ✅ Créer la commande
    //     $order = new Order();
    //     $order->setUser($user);
    //     $order->setExactAddress($data['exact_address']);
    //     $order->setEventDate(new \DateTime($data['event_date']));
    //     $order->setPaymentMethod($data['payment_method']);
    //     $order->setOrderedAt(new \DateTime());
    //     $order->setTotalPrice($finalPrice);
    //     $order->setStatus('PENDING');

    //     // ✅ Nouveau panier vide
    //     $newCart = new Cart();
    //     $newCart->setUser($user);
    //     $em->persist($newCart);

    //     $order->setCart($newCart);
    //     $em->persist($order);

    //     // ✅ Supprimer ancien panier
    //     foreach ($cartProducts as $item) {
    //         $em->remove($item);
    //     }
    //     $em->remove($cart);

    //     // ✅ Ajouter les points fidélité EARNED
    //     $earnedPoints = floor($finalPrice / 10);
    //     if ($earnedPoints > 0) {
    //         $fidelityPointEarned = new FidelityPoint();
    //         $fidelityPointEarned->setUser($user);
    //         $fidelityPointEarned->setPoints($earnedPoints);
    //         $fidelityPointEarned->setType('earned');
    //         $fidelityPointEarned->setDate(new \DateTimeImmutable());
    //         $fidelityPointEarned->setReason('Commande API');
    //         $em->persist($fidelityPointEarned);
    //     }

    //     $em->flush();

    //     return new JsonResponse([
    //         'message' => 'Order created successfully via API',
    //         'order_id' => $order->getOrderId(),
    //         'total_price' => $order->getTotalPrice(),
    //         'points_used' => $pointsUsed,
    //         'earned_points' => $earnedPoints,
    //     ], 201);
    // }




    #[Route('/aya/order/confirm-wallet-stripe', name: 'aya_order_confirm_wallet_stripe', methods: ['POST'])]
    public function confirmWalletStripe(
        Request $request,
        UserRepository $userRepo,
        CartRepository $cartRepo,
        CartProductRepository $cpRepo,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        // 1) Récupération de l'utilisateur depuis la session
        $userId = $session->get('user_id');
        $user   = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // 2) On lit le body JSON
        $data = json_decode($request->getContent(), true);
        $useWallet    = $data['use_wallet']  ?? false;
        $amountToPay  = floatval($data['amount'] ?? 0);
        $address      = $data['address']     ?? '';
        $eventDateStr = $data['event_date']  ?? '';

        // 3) Validation basique
        if (!$address || !$eventDateStr) {
            return new JsonResponse(['success'=>false, 'message'=>'Address and event date required'], 400);
        }

        // 4) Récupérer le panier courant et calculer son total
        $oldCart      = $cartRepo->findOneBy(['user'=>$user]);
        $cartProducts = $cpRepo->findBy(['cart'=>$oldCart]);
        $total        = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        // 5) Déduction du wallet si demandé
        $walletDeducted = 0;
        if ($useWallet) {
            // Calcul de votre crédit sur mesure (exemple)
            $walletCredit = array_sum(array_map(fn($tx)=> $tx->getType()==='payment'? -$tx->getAmount(): $tx->getAmount(),
                                  $user->getWalletTransactions()->toArray()));
            $walletDeducted = min($amountToPay, $walletCredit);
            if ($walletDeducted > 0) {
                $wt = new WalletTransaction();
                $wt->setUser($user)
                   ->setAmount($walletDeducted)
                   ->setType('payment')
                   ->setCreatedAt(new \DateTime())
                   ->setDescription('Wallet payment for order');
                $em->persist($wt);
            }
        }

        // 6) Création d’un NOUVEAU panier vide
        $newCart = new Cart();
        $newCart->setUser($user);
        $em->persist($newCart);

        // 7) Création de la commande
        $order = new Order();
        $order->setUser($user)
              ->setCart($newCart)
              ->setExactAddress($address)
              ->setEventDate(new \DateTime($eventDateStr))
              ->setOrderedAt(new \DateTime())
              ->setPaymentMethod($useWallet ? 'Wallet + Stripe' : 'Stripe')
              ->setStatus('PENDING')
              ->setTotalPrice($amountToPay - $walletDeducted);
        $em->persist($order);

        // 8) On supprime l’ancien panier et ses produits
        foreach ($cartProducts as $item) {
            $em->remove($item);
        }
        $em->remove($oldCart);

        // 9) CALCUL et ENREGISTREMENT des points de fidélité
        $earnedPoints = floor($order->getTotalPrice() / 10);
        if ($earnedPoints > 0) {
            $fp = new FidelityPoint();
            $fp->setUser($user)
               ->setPoints($earnedPoints)
               ->setType('earned')
               ->setDate(new \DateTimeImmutable())
               ->setReason('Commande Wallet+Stripe');
            $em->persist($fp);
        }

        // 10) Tout persister
        $em->flush();

        // 11) Sauvegarde en session pour la confirmation
        $session->set('last_order_id', $order->getOrderId());

        // 12) Retour AJAX
        return new JsonResponse([
            'success'      => true,
            'order_id'     => $order->getOrderId(),
            'earnedPoints' => $earnedPoints,
        ]);
    }




    #[Route('/aya/order/cancelled', name: 'aya_order_cancelled')]
    public function cancelled(): Response
    {
        $this->addFlash('error', 'Payment was cancelled.');
        return $this->redirectToRoute('aya_cart');
    }
    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $data = $request->request->all();
        $amount = floatval($data['amount']) * 100; // Convert to cents for Stripe (TND)
        $orderId = $data['order_id'];

        // Validate order_id
        if (!$orderId) {
            return new JsonResponse(['error' => 'Order ID is required'], 400);
        }

        // Log the amount for debugging
        error_log("Creating Stripe session with amount: {$amount} TND (in cents), order_id: {$orderId}");

        // Set Stripe API key
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'tnd',
                        'product_data' => ['name' => 'Order #' . $orderId],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $urlGenerator->generate('aya_order_confirm', ['id' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $urlGenerator->generate('aya_order_cancelled', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'client_reference_id' => $orderId,
            ]);

            return new JsonResponse(['id' => $session->id]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        EntityManagerInterface $em,
        OrderRepository $orderRepository
    ): Response {
        // 1) Récupérer le contenu et l’en-tête de signature
        $payload    = $request->getContent();
        $sigHeader  = $request->headers->get('stripe-signature');
        $secret     = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            // 2) Construire l’événement Stripe à partir du payload
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            return new Response('Invalid signature', 400);
        }

        // 3) Traiter uniquement l’événement de paiement réussi
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->client_reference_id;

            // 4) Récupérer la commande et la passer en PAID
            $order = $orderRepository->find($orderId);
            if ($order) {
                $order->setStatus('PAID');
                $em->persist($order);

                // 5) Calculer et enregistrer les points de fidélité
                $totalTND    = $order->getTotalPrice();          // en TND
                $earnedPoints = floor($totalTND / 10);           // 1 point / 10 TND

                if ($earnedPoints > 0) {
                    $fp = new FidelityPoint();
                    $fp->setUser($order->getUser());
                    $fp->setPoints($earnedPoints);
                    $fp->setType('earned');
                    $fp->setDate(new \DateTimeImmutable());
                    $fp->setReason('Paiement Stripe');
                    $em->persist($fp);
                }

                // 6) Tout flush d’un coup
                $em->flush();
            }
        }

        // Répondre 200 à Stripe
        return new Response('Webhook processed', 200);
    }
}
