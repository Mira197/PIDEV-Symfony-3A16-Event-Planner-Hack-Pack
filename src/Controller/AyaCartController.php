<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Entity\Product;
use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AyaCartController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/aya/cart', name: 'aya_cart')]
    public function index(
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        EntityManagerInterface $em // 👈 injecte ici
    ): Response {
        //$user = $this->getUser();
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cartProducts = [];
        $total = 0;

        if ($user) {
            $cart = $cartRepository->findOneBy(['user' => $user]);

            if ($cart) {
                $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

                foreach ($cartProducts as $item) {
                    $total += $item->getTotalPrice();
                }
            }
        }

        return $this->render('aya_cart/aya_cart.html.twig', [
            'user' => $user,
            'cartProducts' => $cartProducts,
            'total' => $total,
        ]);
    }

    // Méthode pour inclure les données du panier dans toutes les vues (mini-cart)
    public function getCartSummary(
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): array {
        $user = $this->getUser();
        $cartProducts = [];
        $total = 0;
        if ($user) {
            $cart = $cartRepository->findOneBy(['user' => $user]);
            if ($cart) {
                $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);
                foreach ($cartProducts as $item) {
                    $total += $item->getTotalPrice();
                }
            }
        }

        return [
            'cartProducts' => $cartProducts,
            'total' => $total,
        ];
    }
    #[Route('/aya/cart/add/{id}', name: 'aya_cart_add', methods: ['POST'])]
    public function addToCart(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
            $em->flush(); // Pour obtenir un cart_id valide
        }

        $cartProduct = $cartProductRepository->findOneBy(['cart' => $cart, 'product' => $product]);

        if ($cartProduct) {
            $cartProduct->setQuantity($cartProduct->getQuantity() + 1);
        } else {
            $cartProduct = new CartProduct();
            $cartProduct->setCart($cart);
            $cartProduct->setProduct($product);
            $cartProduct->setQuantity(1); // ← initialise ici aussi !
            $em->persist($cartProduct);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Product added to cart',
            'product' => $product->getName(),
            'quantity' => $cartProduct->getQuantity()
        ]);
    }



    #[Route('/aya/cart/json', name: 'aya_cart_json', methods: ['GET'])]
    public function getCartJson(
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);
        $items = [];
        $total = 0;

        if ($cart) {
            $products = $cartProductRepository->findBy(['cart' => $cart]);
            foreach ($products as $item) {
                $items[] = [
                    'product_id' => $item->getProduct()->getId(),
                    'product_name' => $item->getProduct()->getName(),
                    'price' => $item->getProduct()->getPrice(),
                    'quantity' => $item->getQuantity(),
                    'subtotal' => $item->getTotalPrice()
                ];
                $total += $item->getTotalPrice();
            }
        }

        return new JsonResponse([
            'items' => $items,
            'total' => $total
        ]);
    }

    #[Route('/aya/cart/update/{id}', name: 'aya_cart_update', methods: ['PUT'])]
    public function updateQuantity(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);
        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? null;

        if (!$cart || !$quantity || $quantity < 1) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        $product = $em->getRepository(Product::class)->find($id);
        $cartProduct = $cartProductRepository->findOneBy(['cart' => $cart, 'product' => $product]);

        if (!$cartProduct) {
            return new JsonResponse(['error' => 'Product not found in cart'], 404);
        }

        $cartProduct->setQuantity((int)$quantity);
        $previousQuantity = $cartProduct->getQuantity();


        $errors = $validator->validate($cartProduct);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => $errors[0]->getMessage()], 400);
        }

        // 🎯 Mettre à jour le stock
        $diff = (int)$quantity - $previousQuantity;
        $stock = $product->getStock();
        if ($stock) {
            $newAvailable = $stock->getAvailableQuantity() - $diff;
            if ($newAvailable < 0) {
                return new JsonResponse(['error' => 'Not enough stock'], 400);
            }
            $stock->setAvailableQuantity($newAvailable);
        }

        $em->flush();

        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);
        $cartTotal = array_reduce($cartProducts, fn($sum, $item) => $sum + $item->getTotalPrice(), 0);

        return new JsonResponse([
            'message' => 'Quantity updated successfully',
            'new_quantity' => $cartProduct->getQuantity(),
            'available_stock' => $product->getStock()->getAvailableQuantity(),
            'total_price' => $cartProduct->getTotalPrice(),
            'cart_total' => $cartTotal
        ]);
    }

    #[Route('/aya/cart/remove/{id}', name: 'aya_cart_remove', methods: ['DELETE'])]
    public function removeFromCart(
        int $id,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return new JsonResponse(['error' => 'Cart not found'], 404);
        }

        $cartProduct = $cartProductRepository->findOneBy(['cart' => $cart, 'product' => $id]);
        if (!$cartProduct) {
            return new JsonResponse(['error' => 'Product not in cart'], 404);
        }

        $em->remove($cartProduct);
        $em->flush();

        return new JsonResponse(['message' => 'Product removed from cart']);
    }


    #[Route('/aya/cart/clear', name: 'aya_cart_clear', methods: ['DELETE'])]
    public function clearCart(
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return new JsonResponse(['message' => 'Cart already empty']);
        }

        $products = $cartProductRepository->findBy(['cart' => $cart]);
        foreach ($products as $item) {
            $em->remove($item);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Cart cleared successfully']);
    }
    #[Route('/aya/cart/increase/{id}', name: 'aya_cart_increase', methods: ['POST'])]
    public function increaseQuantity(
        int $id,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);
        $cartProduct = $cartProductRepository->findOneBy(['cart' => $cart, 'product' => $id]);

        if (!$cartProduct) {
            return new JsonResponse(['error' => 'Product not found in cart'], 404);
        }

        $product = $cartProduct->getProduct();
        $stock = $product->getStock();

        if (!$stock || $stock->getAvailableQuantity() < 1) {
            return new JsonResponse(['error' => 'Insufficient stock available'], 400);
        }

        // ➕ Augmenter la quantité
        $cartProduct->setQuantity($cartProduct->getQuantity() + 1);

        // 🔻 Diminuer le stock
        $stock->setAvailableQuantity($stock->getAvailableQuantity() - 1);

        $em->flush();

        return new JsonResponse([
            'message' => 'Quantity increased',
            'quantity' => $cartProduct->getQuantity(),
            'total_price' => $cartProduct->getTotalPrice()
        ]);
    }



    #[Route('/aya/cart/decrease/{id}', name: 'aya_cart_decrease', methods: ['POST'])]
    public function decreaseQuantity(
        int $id,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);
        $cartProduct = $cartProductRepository->findOneBy(['cart' => $cart, 'product' => $id]);

        if (!$cartProduct) {
            return new JsonResponse(['error' => 'Product not found in cart'], 404);
        }

        $currentQty = $cartProduct->getQuantity();
        if ($currentQty <= 1) {
            return new JsonResponse(['error' => 'Cannot decrease quantity below 1'], 400);
        }

        // ➖ Diminuer la quantité
        $cartProduct->setQuantity($currentQty - 1);

        // 🔁 Remettre 1 produit en stock
        $product = $cartProduct->getProduct();
        $stock = $product->getStock();
        if ($stock) {
            $stock->setAvailableQuantity($stock->getAvailableQuantity() + 1);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Quantity decreased',
            'quantity' => $cartProduct->getQuantity(),
            'total_price' => $cartProduct->getTotalPrice()
        ]);
    }
}
