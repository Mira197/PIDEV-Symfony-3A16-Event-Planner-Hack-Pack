<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $weatherApiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->weatherApiKey = $_ENV['WEATHER_API_KEY']; // utilisation depuis .env.local
    }

    #[Route('/weather', name: 'weather')]
    public function getWeather(Request $request): JsonResponse
    {
        $city = $request->query->get('city');
        $start = $request->query->get('start'); // format YYYY-MM-DD
        $end = $request->query->get('end');     // format YYYY-MM-DD

        if (!$city || !$start || !$end) {
            return new JsonResponse(['error' => 'Missing parameters'], 400);
        }

        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $interval = $startDate->diff($endDate)->days + 1;

        $url = 'http://api.weatherapi.com/v1/forecast.json';

        $response = $this->httpClient->request('GET', $url, [
            'query' => [
                'key' => $this->weatherApiKey,
                'q' => $city,
                'days' => $interval,
                'lang' => 'en'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['error' => 'API error'], 500);
        }

        $data = $response->toArray();

        try {
            $forecasts = $data['forecast']['forecastday'];
            $results = [];

            foreach ($forecasts as $day) {
                $results[] = [
                    'date' => $day['date'],
                    'temp' => $day['day']['avgtemp_c'],
                    'condition' => $day['day']['condition']['text'],
                    'icon' => 'https:' . $day['day']['condition']['icon'],
                ];
            }

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Data parsing failed'], 500);
        }
    }

}
