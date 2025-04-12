<?php

namespace App\Controller;



use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\AdminRegisterFormType;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
    public function login(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(AuthFormType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $submittedUser = $form->getData();
    
            $email = $submittedUser->getEmail();
            $plainPassword = $submittedUser->getPassword();
    
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            dump($email);
            dump($plainPassword);
            dump($user->getPassword()); // mot de passe hashé en BDD
            dump($passwordHasher->isPasswordValid($user, $plainPassword));
           
         
            if ($user && $passwordHasher->isPasswordValid($user, $plainPassword)) {
    
                // Vérifie si le compte est bloqué
                if ($user->isBlocked()) {
                    $now = new \DateTime();
    
                    if ($user->getBlockEndDate() !== null && $user->getBlockEndDate() > $now) {
                        $form->get('email')->addError(new FormError('Votre compte est bloqué jusqu\'au ' . $user->getBlockEndDate()->format('d/m/Y H:i')));
                        return $this->render('auth/login.html.twig', [
                            'form' => $form->createView(),
                        ]);
                    }
    
                    // Déblocage si la date est expirée
                    $user->setBlocked(false);
                    $user->setBlockEndDate(null);
                    $entityManager->flush();
                }
    
                // Authentification réussie
                $session->set('user_id', $user->getIdUser());
                $session->set('username', $user->getUsername());
                $session->set('email', $user->getEmail());
                
                $session->set('first_name', $user->getFirstName());
                $session->set('last_name', $user->getLastName());
                $session->set('phone', $user->getNumTel());
                $session->set('img', $user->getImgPath());
                // Redirection selon le rôle
                switch ($user->getRole()) {
                    case 'ADMIN':
                        return $this->redirectToRoute('app_user_index');
                    case 'CLIENT':
                        return $this->redirectToRoute('userpage');
                    case 'FOURNISSEUR':
                        return $this->redirectToRoute('FourniPage');
                   
                    default:
                        return $this->redirectToRoute('homepage');
                }
            }
         
           
            
            // Email ou mot de passe incorrect
            $form->get('email')->addError(new FormError('Email ou mot de passe incorrect.'));
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
   
    }
    dump($form->getErrors(true, false));
    return $this->render('auth/auth.html.twig', [
        'form' => $form->createView(),
    ]);
}







    #[Route('/registration/success', name:'registration_success')]
    public function registrationSuccess(): Response
    {
        return $this->forward('App\Controller\SecurityController::login');
    }


 

     #[Route('/userpage', name: 'userpage')]
    public function directto(): Response
    {
        return $this->forward('App\Controller\UserController::index');
    }

    #[Route('/homepage', name: 'homepage')]
    public function directto1(): Response
    {
        return $this->forward('App\Controller\AuthController::index');
    }


     
    #[Route('/userpage1', name: 'userpage1')]
    public function directtouser(): Response
    {
        return $this->render('base.html.twig'); 
    }

    #[Route('/Adminpage', name: 'Adminpage')]
    public function directtoAdmin(): Response
    {
        return $this->render('backOfficeAdmin.html.twig'); 
    }
   

     #[Route('/FourniPage', name: 'FourniPage')]
    public function directtoFourni(): Response
    {
        return $this->render('backOffice.html.twig');    }






     #[Route('/logout', name: 'app_logout')]
    public function logout(SessionInterface $session): response
    {        $session->clear();
        return $this->forward('App\Controller\AuthController::login');
    }

    
    
    #[Route('/roles', name: 'statistiques_roles')]
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
       

}
#[Route('/admin/register', name: 'admin_register', methods: ['POST'])]
public function registerAdmin(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = new User();
    $form = $this->createForm(AdminRegisterFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        // Validation personnalisée
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

        // Appeler isValid() après vérifications personnalisées
        if ($form->isValid()) {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
            $user->setRole("ADMIN"); // 💡 On force ici le rôle, pas dans le form
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index');
        }

        // En cas d'erreurs → facultatif, pour debug
        /*
        foreach ($form as $child) {
            foreach ($child->getErrors() as $error) {
                dump('Champ : ' . $child->getName(), 'Erreur : ' . $error->getMessage());
            }
        }
        dump($form->getErrors(true, false));
        dump($user);
        die();
        */
    }

    return $this->redirectToRoute('app_user_index');
}




#[Route('/test-session', name: 'test_session')]
public function testSession(SessionInterface $session): Response
{
    dd($session->all());
}






}