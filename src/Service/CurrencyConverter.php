<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyConverter
{
    private $httpClient;
    private $accessKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        #$this->accessKey = 'be9767d355afe1f2cea6c2cbe61c81db'; // 🔥 Remplacer par ta vraie access_key obtenue sur exchangerate.host
    }

    public function convert(float $amount, string $targetCurrency): float
    {
        // Si la monnaie cible est TND (pas besoin de conversion)
        if ($targetCurrency === 'TND') {
            return $amount;
        }

        $response = $this->httpClient->request('GET', 'https://api.exchangerate.host/convert', [
            'query' => [
                'access_key' => $this->accessKey,  // Clé ajoutée ici
                'from' => 'TND',
                'to' => $targetCurrency,
                'amount' => $amount
            ]
        ]);

        $data = $response->toArray();

        if (isset($data['result'])) {
            return $data['result']; // retourne directement le prix converti
        }

        // En cas d'erreur, retourne le prix original
        return $amount;
    }

    public function getConversionRate(string $from, string $to): float
{
    if ($from === $to) {
        return 1.0;
    }

    $response = $this->httpClient->request('GET', 'https://api.exchangerate.host/convert', [
        'query' => [
            'access_key' => $this->accessKey,
            'from' => $from,
            'to' => $to,
            'amount' => 1
        ],
    ]);

    $data = $response->toArray();

    if (isset($data['result'])) {  // <<< C'EST `result` !!
        return $data['result'];
    }

    return 1.0;
}
}
