<?php
namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request; // ✅ LA BONNE IMPORTATION
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Bridge\Twilio\TwilioOptions;
use Twilio\Rest\Client;
class TestController extends AbstractController
{
    #[Route('/test-sms', name: 'test_sms')]
    public function testSMS(TexterInterface $texter): JsonResponse
    {
        $sms = new SmsMessage('+21695513380', '📲 Ceci est un test Twilio depuis Symfony');

        $sms->options(new TwilioOptions([
            'MessagingServiceSid' => $_ENV['TWILIO_SERVICE_SID']
        ]));

        $texter->send($sms);

        return new JsonResponse([
            'success' => true,
            'message' => '✅ Test SMS envoyé (si configuration correcte)',
        ]);
    }

    #[Route('/api/best-sms', name: 'best_sms', methods: ['POST'])]
    public function bestSms(LoggerInterface $logger): JsonResponse
    {
        // Vérification des variables REQUISES
        $requiredEnvVars = [
            'TWILIO_ACCOUNT_SID' => 'Account SID',
            'TWILIO_AUTH_TOKEN' => 'Auth Token',
            'TWILIO_SERVICE_SID' => 'Messaging Service SID',
            'TWILIO_FROM' => 'Numéro Twilio'
        ];
    
        foreach ($requiredEnvVars as $var => $name) {
            if (empty($_ENV[$var])) {
                $logger->error('Configuration Twilio incomplète', ['variable' => $var]);
                return new JsonResponse([
                    'success' => false,
                    'error' => "Configuration incomplète: $name manquante",
                    'solution' => "Vérifiez la variable $var dans votre .env"
                ], 500);
            }
        }
    
        try {
            $twilio = new Client(
                $_ENV['TWILIO_ACCOUNT_SID'],
                $_ENV['TWILIO_AUTH_TOKEN']
            );
    
            // Test avec numéro sandbox d'abord
            $testNumber = '+15005550006';
            $message = $twilio->messages->create(
                $testNumber,
                [
                    'messagingServiceSid' => $_ENV['TWILIO_SERVICE_SID'],
                    'body' => 'Test Twilio - ' . date('Y-m-d H:i:s')
                ]
            );
    
            return new JsonResponse([
                'success' => true,
                'message' => 'SMS envoyé avec succès',
                'sid' => $message->sid,
                'to' => $testNumber
            ]);
    
        } catch (\Throwable $e) {
            $logger->critical('Erreur Twilio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'solution' => [
                    '1. Vérifiez les crédits sur console.twilio.com',
                    '2. Activez les permissions internationales',
                    '3. Vérifiez le SID du service messaging'
                ]
            ], 500);
        }
    }
}
