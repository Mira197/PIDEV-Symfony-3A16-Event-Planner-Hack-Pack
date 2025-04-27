<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UrlGeneratorInterface $urlGenerator;
    private SessionInterface $session;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UserRepository $userRepository;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        UserRepository $userRepository
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->session = $requestStack->getSession();
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            }),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User not instance of App\Entity\User.');
        }

        // Vérifie s’il est bloqué
        if ($user->isBlocked()) {
            if ($user->getBlockEndDate() < new \DateTime()) {
                $user->setBlocked(false);
                $user->setBlockEndDate(null);
            } else {
                $this->logoutUser($request);
                return new RedirectResponse($this->urlGenerator->generate('app_login', [
                    'blocked' => true,
                    'duree' => $user->getBlockEndDate(),
                ]));
            }
        }

        // Enregistre dans la session
        $this->session->set('user_id', $user->getIdUser());
        $this->session->set('username', $user->getUsername());

        // Redirection personnalisée selon le rôle
        return match ($user->getRole()) {
            'FOURNISSEUR' => new RedirectResponse('/Artistpage'),
            'CLIENT' => new RedirectResponse('/userpage'),
            'ADMIN' => new RedirectResponse('/Adminpage'),
            default => $this->redirectToDefault()
        };
    }

    private function redirectToDefault(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function logoutUser(Request $request): void
    {
        $request->getSession()->invalidate();
        $this->session->clear();
        $this->tokenStorage->setToken(null);
    }
}
