<?php

namespace App\Controller;

use App\Entity\Report;
use App\Entity\Publication;
use App\Form\ReportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;



class ReportController extends AbstractController
{
    #[Route('/report/{id}', name: 'app_report_publication')]
public function report(Request $request, Publication $publication, EntityManagerInterface $em, Security $security): Response
{
    $report = new Report();
    $form = $this->createForm(ReportType::class, $report);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $report->setPublication($publication);
        $report->setUser($security->getUser());
        $report->setReportDate(new \DateTimeImmutable());
        $report->setStatus("Pending");

        $em->persist($report);
        $em->flush();

        $this->addFlash('success', 'Report submitted successfully.');
        return $this->redirectToRoute('app_publication_client');
    }

    return $this->render('newReport.html.twig', [
        'form' => $form->createView(),
        'publication' => $publication
    ]);
}

}
