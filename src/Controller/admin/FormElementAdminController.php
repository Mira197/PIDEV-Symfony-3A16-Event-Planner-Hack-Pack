<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FormElementAdminController extends AbstractController
{
    #[Route('/admin/formelements', name: 'app_form_elements')]
    public function index(): Response
    {
        return $this->render('admin/formelementsAdmin.html.twig');
    }
}
