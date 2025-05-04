<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from(new Address('dhekerlaadhibii@gmail.com', '3alaKifi Support'))
            ->to('dhekerlaadhibii@gmail.com') // tu peux changer l'email ici
            ->subject('Test Email from Symfony')
            ->text('This is a simple test email sent from Symfony immediately.');

        $mailer->send($email);

        return new Response('✅ Test email sent successfully!');
    }
}