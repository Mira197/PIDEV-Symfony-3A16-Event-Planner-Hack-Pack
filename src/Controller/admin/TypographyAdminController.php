<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TypographyAdminController extends AbstractController
{
    #[Route('/admin/typography', name: 'admin_typography')]
    public function typography(): Response
    {
        return $this->render('admin/typographyAdmin.html.twig');
    }
}
