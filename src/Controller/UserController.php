<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminRegisterFormType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Regex;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Maile;
use Symfony\Component\Mailer\Transport; 
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/admin')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
{
    $searchQuery = $request->query->get('query');

    if ($searchQuery) {
        // Si une requête de recherche est soumise
        $users = $userRepository->findBySearchQuery($searchQuery);
    } else {
        // Si aucune requête de recherche n'est soumise, afficher tous les utilisateurs
        $users = $userRepository->findAll();
    }

    // Vérifier si la durée de blocage est dépassée pour chaque utilisateur et le débloquer si nécessaire
    foreach ($users as $user) {
        if ($user->isBlocked() && $user->getBlockEndDate() < new \DateTime()) {
            $user->setBlocked(false);
            $user->setBlockEndDate(null);
            $entityManager->flush();
        }
    }
    $form = $this->createForm(AdminRegisterFormType::class, new User());

    return $this->render('admin/listAdmins.html.twig', [
        'users' => $users,
        'adminForm' => $form->createView(), // ⬅️ très important !
    ]);
    
}

    #[Route('/export-pdf', name: 'app_user_export_pdf', methods: ['GET'])]
    public function exportPdf(UserRepository $userRepository): Response
    {
        // Récupérer tous les utilisateurs
        $users = $userRepository->findAll();

        // Rendre la vue avec la liste des utilisateurs en HTML
        $html = $this->renderView('admin/print.html.twig', [
            'users' => $users,
        ]);

        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        // Instanciation de Dompdf
        $dompdf = new Dompdf($options);

        // Chargement du HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Rendu du PDF
        $dompdf->render();

        // Renvoyer le PDF en tant que réponse
        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
        ]);
    }


    #[Route('/export-excel', name: 'app_user_export_excel', methods: ['GET'])]
    public function exportExcel(UserRepository $userRepository): Response
    {
        // Récupérer tous les utilisateurs
        $users = $userRepository->findAll();

        // Initialiser une instance de Spreadsheet (fichier Excel)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Entêtes de colonne
        $sheet->setCellValue('A1', 'Last Name');
        $sheet->setCellValue('B1', 'First Name');
        $sheet->setCellValue('C1', 'Username');
        $sheet->setCellValue('D1', 'Role');
        $sheet->setCellValue('E1', 'Phone Number');
        $sheet->setCellValue('F1', 'Blocked');

        // Ajouter les données des utilisateurs au fichier Excel
        $row = 2; // Commencer à la deuxième ligne
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user->getLastName());
            $sheet->setCellValue('B' . $row, $user->getFirstName());
            $sheet->setCellValue('C' . $row, $user->getUsername());
            $sheet->setCellValue('D' . $row, $user->getRole());
            $sheet->setCellValue('E' . $row, $user->getNumtel());
            $sheet->setCellValue('F' . $row, $user->isBlocked() ? 'Oui' : 'Non');
            $row++;
        }

        // Créer un objet Writer pour écrire le fichier Excel
        $writer = new Xlsx($spreadsheet);

        // Nom du fichier Excel à télécharger
        $excelFileName = 'users.xlsx';

        // Configurer la réponse HTTP pour le téléchargement du fichier Excel
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $excelFileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{idUser}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $role = $user->getRole();

        // Déterminer le template à utiliser en fonction du rôle de l'utilisateur
        switch ($role) {
            case 'CLIENT':
                $editTemplate = 'user/show.html.twig';
                break;
            case 'Fournissuer':
                $editTemplate = 'user/profileFournissuer.html.twig';
                break;
            case 'ADMIN':
                $editTemplate = 'user/profileAdmin.html.twig';
                break;
            default:
                // Rediriger vers une page d'erreur ou la page d'accueil si le rôle n'est pas reconnu
                return $this->redirectToRoute('homepage');
        }

      
        return $this->render($editTemplate, [
            'user' => $user,
        ]);
    }

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/{idUser}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le rôle de l'utilisateur actuel
        $role = $user->getRole();

        // Déterminer le template à utiliser en fonction du rôle de l'utilisateur
        switch ($role) {
            case 'CLIENT':
                $editTemplate = 'user/edit.html.twig';
                break;
            case 'FOURNISSEUR':
                $editTemplate = 'user/editArtist.html.twig';
                break;
            case 'ADMIN':
                $editTemplate = 'user/editAdmin.html.twig';
                break;
            default:
                // Rediriger vers une page d'erreur ou la page d'accueil si le rôle n'est pas reconnu
                return $this->redirectToRoute('homepage');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Rediriger l'utilisateur vers la page appropriée après l'édition
            switch ($role) {
                case 'CLIENT':
                    return $this->redirectToRoute('userpage');
                case 'ARTIST':
                    return $this->redirectToRoute('Artistpage');
                case 'ADMIN':
                    return $this->redirectToRoute('Adminpage');
                default:
                    // Rediriger vers une page d'erreur ou la page d'accueil si le rôle n'est pas reconnu
                    return $this->redirectToRoute('homepage');
            }
        }

        return $this->renderForm($editTemplate, [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{idUser}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getIdUser(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
/**
     * @Route("/saisir-duree/{id}", name="saisir_duree")
     */
    public function saisirDuree(MailerInterface $mailer,Request $request, $id,EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $entityManager->getRepository(User::class)->find($id);

        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Créer un formulaire pour saisir la durée de blocage
        $form = $this->createFormBuilder()
    ->add('duree', TextType::class, [
        'label' => 'Durée en minutes',
        'attr' => ['placeholder' => 'Entrez la durée en minutes'],
        'constraints' => [
            new NotBlank(['message' => 'La durée est requise']),
            new PositiveOrZero(['message' => 'La durée doit être un nombre positif ou zéro']),
            new Regex([
                'pattern' => '/^\d+$/',
                'message' => 'La durée doit être un nombre entier positif',
            ]),
        ],
    ])
    ->add('save', SubmitType::class, ['label' => 'Valider'])
    ->getForm();


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le formulaire est soumis et valide, traiter les données
            $data = $form->getData();
            $duree = $data['duree'];

            // Mettre à jour l'utilisateur avec la durée de blocage
            $utilisateur->setBlocked(true);
            $dateFinBlocage = new \DateTime();
            $dateFinBlocage->modify("+{$duree} minutes");
            $utilisateur->setBlockEndDate($dateFinBlocage);

            $entityManager->flush();
            $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
            $mailer = new Mailer($transport);
            $email = (new TemplatedEmail())
            ->from('hhajer09@gmail.com')
            ->to($utilisateur->getEmail())
            ->subject('votre compte est Bloqué')
            ->html('
        <h1 style="color: red;">Compte Bloqué</h1>
        <p>Votre compte a été bloqué pour une durée de ' . $duree . ' minutes.</p>
    ');

        
        $mailer->send($email);

            // Rediriger vers une autre page ou afficher un message de confirmation
            return $this->redirectToRoute('app_user_index');
        }

        // Afficher le formulaire de saisie de la durée
        return $this->render('user/bloquer.html.twig', [
            'form' => $form->createView(),
        ]);
    }
   /**
     * @Route("/debloquer-utilisateur/{id}", name="debloquer_utilisateur")
     */
    public function debloquerUtilisateur($id,EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur en fonction de l'ID
        $utilisateur = $entityManager->getRepository(User::class)->find($id);
     

        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Débloquer l'utilisateur
        $utilisateur->setBlocked(false);
        $utilisateur->setBlockEndDate(null);

        $entityManager->flush();

        // Rediriger vers une autre page ou afficher un message de confirmation
        return $this->redirectToRoute('app_user_index');
    }



}