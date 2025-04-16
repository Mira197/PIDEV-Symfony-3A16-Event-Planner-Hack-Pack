<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Maile;
use Symfony\Component\Mailer\Transport; 
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

class MailerController extends AbstractController
{
    /**
     * @Route("/envoyer-email", name="envoyer_email")
     */
        public function sendEmail(MailerInterface $mailer): Response
    {
        $transport = Transport::fromDsn('smtp://dhekerlaadhibii@gmail.com:cpqf yatk ngdz morv@smtp.gmail.com:587');
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


        $mailer = new Mailer($transport);
        $email = (new Email())
                ->from('dhekerlaadhibii@gmail.com')
                ->to('dhekerlaadhibii@gmail.com')
                ->subject('Action Inv Selled')
                ->text('An action inv has been selled.');

            $mailer->send($email); 


        return new Response('Email envoyé');

        // ...
    }
}