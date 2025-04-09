<?php
namespace App\Controller;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use App\Repository\CartProductRepository;
use App\Repository\CartRepository;
use Symfony\Component\Security\Core\Security;

class AyaCartServiceTwig extends AbstractExtension implements GlobalsInterface
{
    private $security;
    private $cartRepository;
    private $cartProductRepository;

    public function __construct(Security $security, CartRepository $cartRepository, CartProductRepository $cartProductRepository)
    {
        $this->security = $security;
        $this->cartRepository = $cartRepository;
        $this->cartProductRepository = $cartProductRepository;
    }

    public function getGlobals(): array
    {
        $cartPreview = [];

        $user = $this->security->getUser();

        if ($user) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if ($cart) {
                $cartPreview = $this->cartProductRepository->findBy(['cart' => $cart]);
            }
        }

        return [
            'cartPreview' => $cartPreview,
        ];
    }
}
