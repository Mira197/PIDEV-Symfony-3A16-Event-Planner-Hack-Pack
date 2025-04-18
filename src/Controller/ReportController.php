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
use App\Repository\UserRepository;



class ReportController extends AbstractController
{
    #[Route('/report/{id}', name: 'app_report_publication')]
    public function report(Request $request, Publication $publication, EntityManagerInterface $em, Security $security,UserRepository $userRepository): Response
    {
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Tester l'utilisateur connecté
           // $user = $security->getUser();
           $user = $userRepository->find(49);
    
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to report a publication.');
                return $this->redirectToRoute('app_login'); // redirection si non connecté
            }
    
            $report->setPublication($publication);
            $report->setUser($user);
            $report->setReportDate(new \DateTimeImmutable());
            $report->setStatus("Pending");
    
            $em->persist($report);
            $em->flush();
    
            $this->addFlash('success', 'Report submitted successfully.');
            return $this->redirectToRoute('app_publication_client', ['report_success' => 1]);
        }
    
        return $this->render('newReport.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication
        ]);
    }
    
    

}
