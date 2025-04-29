<?php

namespace App\Twig;

use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AyaCartExtension extends AbstractExtension
{
    private Security $security;
    private CartRepository $cartRepository;
    private CartProductRepository $cartProductRepository;
    private EntityManagerInterface $em;

    public function __construct(
        Security $security,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        EntityManagerInterface $em
    ) {
        $this->security = $security;
        $this->cartRepository = $cartRepository;
        $this->cartProductRepository = $cartProductRepository;
        $this->em = $em;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('aya_cart_summary', [$this, 'getCartSummary']),
        ];
    }

    public function getCartSummary(): array
    {
        $user = $this->security->getUser() ?? $this->em->getRepository(\App\Entity\User::class)->find(49);
        $cartProducts = [];
        $total = 0;

        if ($user) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if ($cart) {
                $cartProducts = $this->cartProductRepository->findBy(['cart' => $cart]);
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
}
