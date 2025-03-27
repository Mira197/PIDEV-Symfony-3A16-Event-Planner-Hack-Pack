<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlankPageAdminController extends AbstractController
{
    #[Route('/admin/blank-page', name: 'admin_blank_page')]
    public function index(): Response
    {
        return $this->render('admin/blankpageAdmin.html.twig');
    }
}
