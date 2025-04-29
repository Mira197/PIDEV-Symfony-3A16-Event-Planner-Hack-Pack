<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotController extends AbstractController
{
    private HttpClientInterface $client;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'];

    }

    #[Route('/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userInput = trim($data['message'] ?? '');

        // 1. Réponses FAQ rapides
        $faq = [
            'comment réserver un événement' => 'Vous pouvez réserver en ligne via notre site, rubrique Réservation.',
            'moyens de paiement' => 'Nous acceptons Carte Bancaire, PayPal et virement bancaire.',
            'contacter notre équipe' => 'Contactez-nous par email à support@3alakifi.com ou WhatsApp.'
        ];

        foreach ($faq as $question => $answer) {
            if (stripos($userInput, $question) !== false) {
                return new JsonResponse(['response' => $answer]);
            }
        }

        // 2. Sinon utiliser Gemini AI
        $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro-exp-03-25:generateContent?key=' . $this->geminiApiKey, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userInput]
                        ]
                    ]
                ]
            ],
        ]);
        

        $result = $response->toArray();

        $generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Je suis désolé, je n’ai pas compris votre question.';

        return new JsonResponse([
            'response' => $generatedText
        ]);
    }
}
