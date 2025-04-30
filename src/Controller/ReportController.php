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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ReportRepository;

class ReportController extends AbstractController
{
    #[Route('/report/{id}', name: 'app_report_publication')]
    public function report(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        SessionInterface $session,
        ReportRepository $reportRepository
    ): Response {
        // Vérifier si l'utilisateur est connecté via la session
        $userId = $session->get('user_id');
        
        if (!$userId) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('login');
        }
        
        // Récupérer l'utilisateur à partir de l'ID dans la session
        $user = $userRepository->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'User not found. Please log in again.');
            return $this->redirectToRoute('login');
        }

        // Vérifier si l'utilisateur a déjà signalé cette publication
        $existingReport = $reportRepository->findOneBy([
            'publication' => $publication,
            'user' => $user,
        ]);

        if ($existingReport) {
            $this->addFlash('error', 'You have already reported this publication.');
            return $this->redirectToRoute('app_publication_client');
        }

        // Création d'un nouvel objet Report et d'un formulaire
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $report->setPublication($publication);
            $report->setUser($user);
            $report->setReportDate(new \DateTimeImmutable());
            $report->setStatus('Pending');
    
            $em->persist($report);
            $publication->addReport($report);
           // $this->addFlash('debug', 'Number of reports: ' . $publication->getReportCount());
    
            // Vérifier si le seuil de 3 signalements (donc 3 utilisateurs distincts) est atteint
            if ($publication->getReportCount() >= 3) {
                $em->remove($publication);
                //$this->addFlash('info', 'The publication has been deleted because it was reported by 3 different users.');
            }
    
            $em->flush();
    
            $this->addFlash('success', 'Report submitted successfully.');
            return $this->redirectToRoute('app_publication_client', ['report_success' => 1]);
        }
    
        return $this->render('newReport.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }
}