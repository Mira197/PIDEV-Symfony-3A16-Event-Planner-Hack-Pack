<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private RequestStack $requestStack;
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

  
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        $verificationCode = $this->requestStack->getSession()->get('verificationCode');
        $resetUrl = $this->requestStack->getSession()->get('resetUrl');
    
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }
    
        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
            'verificationCode' => $verificationCode,
            'resetUrl' => $resetUrl, // 🛠 on passe resetUrl pour Twig ici
        ]);
    }
    

    #[Route('/reset-password/reset/{userId}', name: 'app_reset_password')]
    public function reset(int $userId, Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
    
        if ($request->isMethod('POST')) {
            $verificationCodeInput = $request->request->get('verificationCode');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');
    
            $expectedCode = $this->requestStack->getSession()->get('verificationCode'); // 🛠 session
    
            if (!$verificationCodeInput || !$newPassword || !$confirmPassword) {
                $this->addFlash('error', 'All fields are required.');
                return $this->redirectToRoute('app_reset_password', ['userId' => $userId]);
            }
    
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('app_reset_password', ['userId' => $userId]);
            }
    
            if ($verificationCodeInput !== $expectedCode) {
                $this->addFlash('error', 'Invalid verification code.');
                return $this->redirectToRoute('app_reset_password', ['userId' => $userId]);
            }
    
            // 🔥 Si tout est valide : changer le mot de passe
            $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($encodedPassword);
    
            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            // Nettoyer la session de reset
            $this->removeResetPasswordSession();
    
            $this->addFlash('success', 'Your password has been successfully reset.');
    
            return $this->redirectToRoute('login'); 
        }
    
        return $this->render('reset_password/reset.html.twig', [
            'userId' => $userId,
            'verificationCodeFromSession' => $this->requestStack->getSession()->get('verificationCode'),
        ]);
    }
    

    #[Route('/reset/{token}', name: 'app_reset_password_token')]
    public function resetWithToken(string $token): RedirectResponse
    {
        $this->storeTokenInSession($token);
        return $this->redirectToRoute('app_reset_password');
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $emailFormData]);
    
        if (!$user) {
            return $this->redirectToRoute('app_forgot_password_request');
        }
    
        $resetUrl = 'http://127.0.0.1:8000/reset-password/reset/' . $user->getId();
        $verificationCode = (string) random_int(100000, 999999);
    
        $email = (new TemplatedEmail())
            ->from(new Address('dhekerlaadhibii@gmail.com', '3alaKifi Support'))
            ->to($user->getEmail())
            ->subject('Reset Your Password')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'verificationCode' => $verificationCode,
            ]);
    
        $mailer->send($email);
    
        // Stocker pour vérifier plus tard
        $this->requestStack->getSession()->set('resetUrl', $resetUrl);
        $this->requestStack->getSession()->set('verificationCode', $verificationCode);
    
        // 🎯 Rediriger directement vers l'interface de reset mot de passe
        return $this->redirectToRoute('app_reset_password', [
            'userId' => $user->getId()
        ]);
    }
    
    
    private function removeResetPasswordSession(): void
    {
        $this->requestStack->getSession()->remove('ResetPasswordPublicToken');
    }
} 