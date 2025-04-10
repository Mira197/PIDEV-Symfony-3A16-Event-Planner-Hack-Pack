<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SignupAdminController extends AbstractController
{
    #[Route('/admin/signup', name: 'admin_signup')]
    public function index(): Response
    {
        return $this->render('admin/signupAdmin.html.twig');
    }
}
