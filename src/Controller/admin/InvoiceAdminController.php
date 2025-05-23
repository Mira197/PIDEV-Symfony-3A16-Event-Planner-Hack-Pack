<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceAdminController extends AbstractController
{
    #[Route('/admin/invoice', name: 'admin_invoice')]
    public function index(): Response
    {
        return $this->render('admin/invoiceAdmin.html.twig');
    }
}
