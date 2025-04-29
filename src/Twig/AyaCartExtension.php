<?php

namespace App\Twig;

use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Extension\GlobalsInterface;


class AyaCartExtension extends AbstractExtension implements GlobalsInterface
{
    private RequestStack $requestStack;
    private CartRepository $cartRepository;
    private CartProductRepository $cartProductRepository;
    private UserRepository $userRepository;

    public function __construct(
        RequestStack $requestStack,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        UserRepository $userRepository
    ) {
        $this->requestStack = $requestStack;
        $this->cartRepository = $cartRepository;
        $this->cartProductRepository = $cartProductRepository;
        $this->userRepository = $userRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('aya_cart_summary', [$this, 'getCartSummary']),
        ];
    }

    public function getCartSummary(): array
    {
        $session = $this->requestStack->getSession();
        $userId = $session->get('user_id');

        // Gérer explicitement si aucun utilisateur connecté
        if (!$userId) {
            return [
                'cartProducts' => [],
                'total' => 0,
            ];
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return [
                'cartProducts' => [],
                'total' => 0,
            ];
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);
        $cartProducts = [];
        $total = 0;

        if ($cart) {
            $cartProducts = $this->cartProductRepository->findBy(['cart' => $cart]);
            foreach ($cartProducts as $item) {
                $total += $item->getTotalPrice();
            }
        }

        return [
            'cartProducts' => $cartProducts,
            'total' => $total,
        ];
    }
    public function getGlobals(): array
    {
        return [
            'aya_user_connected' => $this->requestStack->getSession()->has('user_id'),
        ];
    }
}
