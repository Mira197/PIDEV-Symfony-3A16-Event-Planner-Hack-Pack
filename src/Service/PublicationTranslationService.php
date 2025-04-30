<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class PublicationTranslationService
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function translate(string $text, string $targetLanguage): string
    {
        $this->logger->debug("Traduction du texte : {$text} vers la langue {$targetLanguage}");
        $translatedText = $this->mockTranslate($text, $targetLanguage);
        $this->logger->info("Texte traduit en {$targetLanguage}: {$translatedText}");
        return $translatedText;
    }

    private function mockTranslate(string $text, string $targetLanguage): string
    {
        switch ($targetLanguage) {
            case 'es':
                if ($text === "Magical Wedding Setup at Dar EL Marsa") {
                    return "Configuración mágica de boda en Dar EL Marsa";
                }
                if ($text === "We organized a romantic wedding by the sea for Lina & Mehdi. The floral arch, sunset lighting, and custom table decor made the evening unforgettable. Thank you 3alakifi for bringing our vision to life!") {
                    return "Organizamos una boda romántica junto al mar para Lina y Mehdi. El arco floral, la iluminación al atardecer y la decoración personalizada de las mesas hicieron que la noche fuera inolvidable. ¡Gracias 3alakifi por hacer realidad nuestra visión!";
                }
                break;
            case 'fr':
                if ($text === "Magical Wedding Setup at Dar EL Marsa") {
                    return "Installation magique de mariage à Dar EL Marsa";
                }
                if ($text === "We organized a romantic wedding by the sea for Lina & Mehdi. The floral arch, sunset lighting, and custom table decor made the evening unforgettable. Thank you 3alakifi for bringing our vision to life!") {
                    return "Nous avons organisé un mariage romantique au bord de la mer pour Lina et Mehdi. L'arche florale, l'éclairage au coucher du soleil et la décoration personnalisée des tables ont rendu la soirée inoubliable. Merci 3alakifi d'avoir donné vie à notre vision !";
                }
                break;
        }
        $this->logger->warning("Aucune traduction disponible pour le texte {$text} en langue {$targetLanguage}");
        return $text;
    }
}