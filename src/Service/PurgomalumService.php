<?php

namespace App\Service;

use Psr\Http\Client\ClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Nyholm\Psr7\Request;

class PurgomalumService
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function containsProfanity(string $text): bool
    {
        // Utiliser la bonne URL pour Purgomalum
        $url = "https://www.purgomalum.com/service/json?text=" . urlencode($text);

        try {
            // Créer une requête PSR-7 avec Nyholm\Psr7\Request
            $request = new Request('GET', $url);

            // Utiliser sendRequest pour envoyer la requête
            $response = $this->client->sendRequest($request);

            // Vérifier le code de statut de la réponse
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Échec de l\'appel à l\'API Purgomalum : statut ' . $response->getStatusCode());
            }

            // Récupérer le contenu de la réponse
            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Échec de l\'analyse de la réponse JSON de l\'API Purgomalum');
            }

            // Si le texte filtré diffère du texte d'entrée, cela indique un contenu inapproprié
            return $data['result'] !== $text;
        } catch (TransportExceptionInterface $e) {
            // En cas d'erreur réseau, lever une exception pour bloquer la soumission
            throw new \RuntimeException('Erreur de connexion à l\'API Purgomalum : ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            // Lever une exception pour toute autre erreur
            throw new \RuntimeException('Erreur du service Purgomalum : ' . $e->getMessage(), 0, $e);
        }
    }
}