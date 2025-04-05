<?php

namespace App\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthAuthenticator extends AbstractLoginFormAuthenticator
{    private AuthorizationCheckerInterface $authorizationChecker;

    private SessionInterface $session;
    private TokenStorageInterface $tokenStorage;
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, RequestStack $requestStack,AuthorizationCheckerInterface $authorizationChecker,TokenStorageInterface $tokenStorage // Injection de dépendance du TokenStorageInterface
    ) {
        $this->tokenStorage = $tokenStorage; // Assigner le tokenStorage)
    
        $this->session = $requestStack->getSession();
        $this->authorizationChecker = $authorizationChecker;


    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    { $user = $token->getUser();

if (!$user instanceof \App\Entity\User) {
    throw new \LogicException('User not instance of App\Entity\User.');
}

        // Vérifier si l'utilisateur est bloqué
        if ($user->isBlocked()) {

            if ($user->getBlockEndDate() < new \DateTime()) {
                // Débloquer l'utilisateur et continuer l'authentification
                $user->setBlocked(false);
                $user->setBlockEndDate(null);

            } else{
            // Si l'utilisateur est bloqué, déconnectez-le et redirigez-le vers une page d'erreur ou de connexion
            $this->session->invalidate();
            $this->logoutUser($request);
            $DUREE=$user->getBlockEndDate();

            // Redirection vers une page d'erreur ou de connexion avec un message approprié
            return new RedirectResponse($this->urlGenerator->generate('app_login', ['blocked' => true,'duree'=>$DUREE]));
        }
    }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $user = $token->getUser();
// Pour corriger l'erreur dans IDE + pour PHP
if (!$user instanceof \App\Entity\User) {
    throw new \LogicException('User is not an instance of App\Entity\User');
}
        // Récupérer l'ID de l'utilisateur
        $userId = $user->getIdUser();
        $userUsername= $user->getUsername();

        // Stocker l'ID de l'utilisateur dans la session
        $this->session->set('user_id', $userId);
        $this->session->set('username', $userUsername);

// Vérifiez le rôle de l'utilisateur
if ( $user->getRole()=='FOURNISSEUR') {
    // Redirection pour l'utilisateur ayant le rôle admin
    return new RedirectResponse('/Artistpage');
}elseif ($user->getRole()=='CLIENT') {
    return new RedirectResponse('/userpage');
} elseif ($user->getRole()=='ADMIN') {
    return new RedirectResponse('/Adminpage');
} else {
    // Gérer d'autres rôles ou cas par défaut
}

// } else if ($this->authorizationChecker->isGranted('ARTIST')) {
//     return new RedirectResponse('/Artistpage"');

//     // Redirection pour l'utilisateur ayant le rôle user
// } else {    if ($this->authorizationChecker->isGranted('ClIENT'))         
//     {return new RedirectResponse('/userpage');}
    

//     // Redirection par défaut pour les autres utilisateurs
// }
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }


    private function logoutUser(Request $request)
{
    $request->getSession()->invalidate();
    $this->session->clear();
    $this->session->invalidate();
    $this->tokenStorage->setToken(null);
}

}