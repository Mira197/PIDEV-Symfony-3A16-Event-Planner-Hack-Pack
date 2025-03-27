<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ButtonsAdminController extends AbstractController
{
    #[Route('admin/buttons', name: 'app_buttons_admin')]
    public function index(): Response
    {
        return $this->render('admin/buttonsAdmin.html.twig', [
            'controller_name' => 'ButtonsAdminController',
        ]);
    }
}
