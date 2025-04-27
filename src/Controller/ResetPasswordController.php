<?php

namespace App\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Maile;
use Symfony\Component\Mailer\Transport; 
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    private $resetPasswordHelper;


    public function __construct(
         ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
        $this->resetPasswordHelper = $resetPasswordHelper;

    }

    /**
     * Display & process form to request a password reset.
     */
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

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse
    {

        // $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
        // $mailer = new Mailer($transport);
        // $email = (new Email())
        //     ->from('hhajer09@gmail.com')
        //     ->to('hhajer09@gmail.com')
        //     ->subject('Action Inv Selled')
        //     ->text('An action inv has been selled.');

        // $mailer->send($email); 

        // $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
        // $mailer = new Mailer($transport);
        // $email = (new TemplatedEmail())

        
        // ->from('hhajer09@gmail.com')    
        //     ->to($user->getEmail())
        //     ->subject('Your password reset request')
        //     ->htmlTemplate('reset_password/email.html.twig')
        //     ->context([
        //         'resetToken' => $resetToken,
        //     ])
        // ;


        // $mailer = new Mailer($transport);
        // $email = (new Email())
        //         ->from('hhajer09@gmail.com')
        //         ->to('hhajer09@gmail.com')
        //         ->subject('Action Inv Selled')
        //         ->text('An action inv has been selled.');

            // $mailer->send($email);



        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);
        

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute( 'app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            




        } catch (ResetPasswordExceptionInterface $e) {
           

            
        //     $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
        // $mailer = new Mailer($transport);
        // $email = (new Email())
        //     ->from('hhajer09@gmail.com')
        //     ->to($user->getEmail())
        //     ->subject('Action Inv Selled')
        //     ->text('An action inv has been selled.');

        // $mailer->send($email); 
        //     // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->redirectToRoute('app_check_email');
        }
        $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
            $mailer = new Mailer($transport);
            $email = (new TemplatedEmail())
            ->from('hhajer09@gmail.com')
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->html('
                <h1>Hi!</h1>

                <p>To reset your password, please visit the following link:</p>

                <a href="' . $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL) . '">' . $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL) . '</a>

                <p>This link will expire in ' . $resetToken->getExpiresAt()->format('Y-m-d H:i:s') . '.</p>

                <p>Cheers!</p>
            ');

        
        $mailer->send($email);

        // $transport = Transport::fromDsn('smtp://hhajer09@gmail.com:ixysoqoqqfylbgoa@smtp.gmail.com:587');
        // // $mailer = new Mailer($transport);
        // // $email = (new TemplatedEmail())

        
        // // ->from('hhajer09@gmail.com')    
        // //     ->to($user->getEmail())
        // //     ->subject('Your password reset request')
        //     ->htmlTemplate('reset_password/email.html.twig')
        //     ->context([
        //         'resetToken' => $resetToken,
        //     ])
        // ;


        // $mailer = new Mailer($transport);
        // $email = (new Email())
        //         ->from('hhajer09@gmail.com')
        //         ->to('hhajer09@gmail.com')
        //         ->subject('Action Inv Selled')
        //         ->text('An action inv has been selled.');

        //     $mailer->send($email); 


        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }










    #[Route('/change-password', name: 'user_change_password')]
public function changePassword(
    Request $request,
    SessionInterface $session,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher
): Response {
    // 🔐 Récupérer l'utilisateur connecté depuis la session
    $userId = $session->get('user_id');
    $user = $em->getRepository(User::class)->find($userId);

    if (!$user) {
        throw $this->createNotFoundException("Utilisateur non trouvé");
    }

    $form = $this->createForm(ChangePasswordFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $oldPasswordInput = $form->get('oldPassword')->getData();
        $newPassword = $form->get('newPassword')->getData();
        $confirmPassword = $form->get('confirmPassword')->getData();

        // 🔐 Vérification de l'ancien mot de passe
        if (!$hasher->isPasswordValid($user, $oldPasswordInput)) {
            $form->get('oldPassword')->addError(new FormError("L'ancien mot de passe est incorrect."));
        } elseif ($newPassword !== $confirmPassword) {
            $form->get('confirmPassword')->addError(new FormError("Les mots de passe ne correspondent pas."));
        } else {
            // ✅ Hash du nouveau mot de passe et mise à jour
            $hashedNewPassword = $hasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedNewPassword);
            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié avec succès.');
            return $this->redirectToRoute('login');
        }
    }

    return $this->render('auth/change_password.html.twig', [
        'form' => $form->createView(),
    ]);
}

















}