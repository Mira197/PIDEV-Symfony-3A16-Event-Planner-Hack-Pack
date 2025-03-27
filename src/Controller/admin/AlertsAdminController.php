<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlertsAdminController extends AbstractController
{
    #[Route('/admin/alerts', name: 'admin_alerts')]
    public function index(): Response
    {
        return $this->render('admin/alertsAdmin.html.twig');
    }
}
