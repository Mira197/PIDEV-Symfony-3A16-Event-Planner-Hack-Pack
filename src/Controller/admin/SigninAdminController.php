<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SigninAdminController extends AbstractController
{
    #[Route('/admin/signin', name: 'admin_signin')]
    public function index(): Response
    {
        return $this->render('admin/signinAdmin.html.twig');
    }
}
