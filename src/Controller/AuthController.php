<?php

namespace App\Controller;



use App\Entity\User;
use App\Form\AuthFormType;
use App\Form\InscriptionFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;


class AuthController extends AbstractController
{


    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }



    
     /**
     * @Route("/auth", name="app_auth")
     */
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }


    
    
     #[Route('/login', name: 'login')]
    public function login(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AuthFormType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            
            $user = $form->getData(); // Récupérer l'objet User du formulaire
            $email = $user->getEmail(); // Accéder à l'email de l'utilisateur
            $password = $user->getPassword();
    
            $userRepository = $entityManager->getRepository(User::class);
            $authenticatedUser = $userRepository->findUserByEmailAndPassword($email, $password);
            
            if ($authenticatedUser) {
                if ($authenticatedUser->isBlocked()) {
                    // Vérifier si la date de fin de blocage est dépassée
                    if ($authenticatedUser->getBlockEndDate() < new \DateTime()) {
                        // Débloquer l'utilisateur et continuer l'authentification
                        $authenticatedUser->setBlocked(false);
                        $authenticatedUser->setBlockEndDate(null);
                        $entityManager->flush();
                    } else {
                        // Si la date de fin de blocage n'est pas dépassée, afficher un message d'erreur et rester sur la page de connexion
                        $form->get('email')->addError(new FormError('Le compte est bloqué.'));
                        return $this->render('auth/login.html.twig', [
                            'form' => $form->createView(),
                        ]);
                    }
                }
    
                // Authentification réussie, continuer avec le processus d'authentification
                $userId = $authenticatedUser->getIdUser();
                $session->set('user_id', $userId);
    
                $role = $authenticatedUser->getRole();
    
                switch ($role) {
                    case 'FOURNISSEUR':
                        return $this->redirectToRoute('Artistpage');
                        break;
                    case 'CLIENT':
                        return $this->redirectToRoute('userpage');
                        break;
                    case 'ADMIN':
                        return $this->redirectToRoute('Adminpage');
                        break;
                    default:
                        // Si le rôle n'est pas reconnu, rediriger vers la page d'accueil
                        return $this->redirectToRoute('homepage');
                }
            } else {
                // Authentification échouée, afficher un message d'erreur à l'utilisateur
                $form->get('email')->addError(new FormError('Les informations sont incorrectes.'));
            }
        }
    
        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/register', name: 'user_register')]
public function register1(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = new User();
    $form = $this->createForm(InscriptionFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()]);
        if ($existingUsername) {
            $form->get('username')->addError(new FormError('Ce nom d\'utilisateur est déjà utilisé.'));
        }

        $existingEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingEmail) {
            $form->get('email')->addError(new FormError('Cet email est déjà utilisé.'));
        }

        if ($user->getPassword() !== $form->get('passwordConfirmation')->getData()) {
            $form->get('passwordConfirmation')->addError(new FormError('Les mots de passe ne correspondent pas.'));
        }

        if ($form->getErrors(true)->count() === 0) {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));

            $entityManager->persist($user);
            $entityManager->flush();
            dump($form->getErrors(true, false));
            return $this->redirectToRoute('login'); // ✅ route correcte
        }
      
        dump($form->isSubmitted()); // doit être true
        dump($form->isValid()); 
        foreach ($form as $child) {
            foreach ($child->getErrors() as $error) {
                dump('Champ : ' . $child->getName(), 'Erreur : ' . $error->getMessage());
            }
        }    // doit être true
        dump($form->getErrors(true, false)); // pour voir les erreurs
        dump($user);                // voir les valeurs remplies
        die();
    }
    dump($form->getErrors(true, false));
    return $this->render('auth/auth.html.twig', [
        'form' => $form->createView(),
    ]);
}




    /**
     * @Route("/registration/success", name="registration_success")
     */
    
    public function registrationSuccess(): Response
    {
        return $this->forward('App\Controller\SecurityController::login');
    }


    /**
     * @Route("/userpage", name="userpage")
     */
    public function directto(): Response
    {
        return $this->forward('App\Controller\UserController::index');
    }
/**
     * @Route("/homepage", name="homepage")
     */
    public function directto1(): Response
    {
        return $this->forward('App\Controller\AuthController::index');
    }


     /**
     * @Route("/userpage1", name="userpage1")
     */
    public function directtouser(): Response
    {
        return $this->render('base.html.twig'); 
    }

     /**
     * @Route("/Adminpage", name="Adminpage")
     */
    public function directtoAdmin(): Response
    {
        return $this->render('backOfficeAdmin.html.twig'); 
    }
    /**
     * @Route("/Artistpage", name="Artistpage")
     */
    public function directtoArtist(): Response
    {
        return $this->render('backOffice.html.twig');    }




    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(SessionInterface $session): response
    {        $session->clear();
        return $this->forward('App\Controller\AuthController::login');
    }

    
    /**
    * @Route("/roles", name="statistiques_roles")
    */
   public function roles(UserRepository $userRepository): Response
   {
       // Récupérer les données de statistiques sur les utilisateurs par rôle
       $stats = $userRepository->countUsersByRole();

       // Préparer les données pour l'affichage dans la vue
       $roleLabels = [];
       $userCounts = [];

       foreach ($stats as $stat) {
           $roleLabels[] = $stat['role'];
           $userCounts[] = $stat['userCount'];
       }

       // Rendre la vue avec les données de statistiques
       return $this->render('user/roles.html.twig', [
           'roleLabels' => $roleLabels,
           'userCounts' => $userCounts,
       ]);



}}
