<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LogSnagService
{
    private $httpClient;
    private $apiKey;
    private $project;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'ad287df367d2438fda8dd88b4ab89044'; // ← Remplace par ta vraie clé
        $this->project = '3alaKifi'; // ← Le nom de projet que tu as donné sur LogSnag
    }

    public function sendStockUpdate(string $event, int $stockId, int $newQuantity): void
    {
        $this->httpClient->request('POST', 'https://api.logsnag.com/v1/log', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'project' => '3alakifi',
                'channel' => 'stock_changes', // Tu peux choisir un autre nom de channel si tu veux
                'event' => $event,     // Ex: "Stock Updated"
                'description' => "Stock number: $stockId to quantity $newQuantity",
                'icon' => '📦',
                'notify' => true, // Envoie une notif sur LogSnag
            ]
        ]);
    }
}
