<?php

namespace App\Controller;

use App\Repository\CodePromoRepository;
use App\Repository\GiftCardRepository;
use App\Repository\UserRepository;
use App\Repository\WalletTransactionRepository;
use App\Repository\FidelityPointRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

class AyaReductionController extends AbstractController
{
    #[Route('/aya/order/apply-coupon', name: 'apply_coupon', methods: ['POST'])]
    public function applyCoupon(Request $request, CodePromoRepository $codePromoRepo, SessionInterface $session): JsonResponse
    {
        $couponCode = $request->request->get('coupon_code');
        $discount = 0;

        // Récupérer le coupon depuis la base de données
        $coupon = $codePromoRepo->findOneBy(['code_promo' => $couponCode]); // Utilisez 'code_promo' ici (correspond à la base de données)

        if ($coupon && $coupon->getDateExpiration() > new \DateTime()) {
            $discount = $coupon->getPourcentage() / 100; // Appliquer le pourcentage de réduction
        } else {
            return new JsonResponse(['success' => false, 'message' => 'Invalid or expired coupon']);
        }

        // Enregistrer la réduction dans la session
        $session->set('coupon_discount', $discount);

        return new JsonResponse([
            'success' => true,
            'discount' => $discount
        ]);
    }

    #[Route('/aya/order/apply-wallet', name: 'apply_wallet', methods: ['POST'])]
    public function applyWallet(Request $request, WalletTransactionRepository $walletRepo, SessionInterface $session, UserRepository $userRepo): JsonResponse
    {
        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);
        if (!$user) return new JsonResponse(['success' => false, 'message' => 'Not logged in'], 401);

        $amount = (float) $request->request->get('amount');
        $available = $walletRepo->getAvailableCredit($user);

        if ($amount > $available) {
            return new JsonResponse(['success' => false, 'message' => "Only $available TND available"]);
        }

        $session->set('wallet_used', $amount);
        return new JsonResponse(['success' => true, 'applied' => $amount]);
    }
    #[Route('/aya/order/apply-giftcard', name: 'apply_giftcard', methods: ['POST'])]
    public function applyGiftCard(Request $request, GiftCardRepository $giftCardRepo, SessionInterface $session, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer les données du formulaire
        $code = $request->request->get('code');
        $pin = $request->request->get('pin');

        // Vérifier si les données de la carte sont présentes
        if (!$code || !$pin) {
            return new JsonResponse(['success' => false, 'message' => 'Please provide both card code and PIN.']);
        }

        // Chercher la carte cadeau dans la base de données
        $card = $giftCardRepo->findOneBy(['code' => $code, 'pin' => $pin, 'isUsed' => false]);

        // Si la carte n'est pas trouvée ou est déjà utilisée
        if (!$card) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid card or already used']);
        }

        // Mark the gift card as used and set the usedAt timestamp
        $card->setIsUsed(true);
        $card->setUsedAt(new \DateTime()); // Set the current timestamp when used

        // Save the changes to the database using the injected EntityManagerInterface
        $entityManager->flush(); // Commit changes to the database

        // Enregistrer le montant de la carte et son ID dans la session
        $session->set('gift_card_amount', $card->getBalance());
        $session->set('gift_card_id', $card->getId());

        // Retourner la réponse avec le montant de la carte
        return new JsonResponse(['success' => true, 'amount' => $card->getBalance()]);
    }


    // Apply points
    #[Route('/aya/order/apply-points', name: 'apply_points', methods: ['POST'])]
    public function applyPoints(Request $request, FidelityPointRepository $pointRepo, SessionInterface $session, UserRepository $userRepo): JsonResponse
    {
        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);
        if (!$user) return new JsonResponse(['success' => false, 'message' => 'Not logged in'], 401);

        $points = (int) $request->request->get('points');
        $earned = $pointRepo->getUserPoints($user);

        if ($points > $earned) {
            return new JsonResponse(['success' => false, 'message' => "You only have $earned points"]);
        }

        $amount = $points * 0.5; // 1 point = 0.5 TND
        $session->set('points_used', $amount);
        return new JsonResponse(['success' => true, 'applied' => $amount]);
    }
}
