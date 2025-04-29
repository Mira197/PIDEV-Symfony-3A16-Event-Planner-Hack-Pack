<?php

namespace App\Controller;

use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AssistantEventController extends AbstractController
{
    #[Route('/assistant/chat', name: 'assistant_chat', methods: ['POST'])]
    public function chat(Request $request, HttpClientInterface $client, LocationRepository $locationRepo): JsonResponse
    {
        $userMessage = $request->request->get('message');

        // 🏛 Retrieve all venues
        $locations = $locationRepo->findAll();

        // 🏛 Build the list as text
        $locationsList = '';
        foreach ($locations as $loc) {
            $locationsList .= "- " . $loc->getName() . ", City: " . $loc->getCity()->value . ", Capacity: " . $loc->getCapacity() . "\n";
        }

        // 📋 Build the prompt
        $prompt = "Here is a list of available venues:\n" . $locationsList .
    "\nI am planning an event." .
    "\nPlease suggest venues based on:" .
    "\n- Venues exactly in the requested city, if any." .
    "\n- Also suggest venues in nearby cities if they have a suitable capacity (+/- 20%)." .
    "\nAlways try to give both types of suggestions: same city + nearby cities." .
    "\nOnly suggest venues from the given list." .
    "\nThe user's specific question is: " . $userMessage;


        try {
            $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['OPENROUTER_API_KEY'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'openai/gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an event planning assistant. Only answer based on the given venues list. Always respond in English.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = $response->toArray(false);

            if (isset($data['choices'][0]['message']['content'])) {
                return new JsonResponse(['reply' => $data['choices'][0]['message']['content']]);
            } else {
                return new JsonResponse(['reply' => 'Error: Empty API response.']);
            }

        } catch (\Exception $e) {
            return new JsonResponse(['reply' => 'API Error: ' . $e->getMessage()]);
        }
    }
}
