<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CardsAdminController extends AbstractController
{
    #[Route('/cards', name: 'app_cards')]
    public function index(): Response
    {
        return $this->render('admin/cardsAdmin.html.twig');
    }
}
