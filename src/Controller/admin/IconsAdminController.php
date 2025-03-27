<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IconsAdminController extends AbstractController
{
    #[Route('/icons', name: 'app_icons')]
    public function index(): Response
    {
        return $this->render('iconsAdmin.html.twig');
    }
}

