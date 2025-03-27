<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MdiconsAdminController extends AbstractController
{
    #[Route('/icons/mdi', name: 'mdi_icons')]
    public function mdiIcons(): Response
    {
        return $this->render('admin/mdiconsAdmin.html.twig');
    }
}
