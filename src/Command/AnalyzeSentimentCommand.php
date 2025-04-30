<?php

namespace App\Command;

use App\Entity\Publication;
use App\Service\SentimentAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:analyze-sentiment')]
class AnalyzeSentimentCommand extends Command
{
    private $entityManager;
    private $sentimentAnalysisService;

    public function __construct(EntityManagerInterface $entityManager, SentimentAnalysisService $sentimentAnalysisService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->sentimentAnalysisService = $sentimentAnalysisService;
    }

    protected function configure(): void
    {
        $this->setDescription('Analyzes sentiment for all publications in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Analyzing sentiment for all publications...');

        // Récupérer toutes les publications
        $publications = $this->entityManager->getRepository(Publication::class)->findAll();
        $texts = array_map(fn($pub) => $pub->getDescription() ?: '', $publications); // Utiliser description ou chaîne vide si null

        // Analyser en batch pour plus d'efficacité
        $results = $this->sentimentAnalysisService->analyzeSentimentBatch($texts);

        // Mettre à jour chaque publication
        foreach ($publications as $index => $publication) {
            $result = $results[$index];
            if ($result['sentiment'] !== 'ERROR') {
                $publication->setSentiment($result['sentiment']);
                $this->entityManager->persist($publication);
            } else {
                $output->writeln(sprintf('Error analyzing publication ID %d: %s', $publication->getPublicationId(), $result['error']));
            }
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();
        $output->writeln('Sentiment analysis completed.');

        return Command::SUCCESS;
    }
}