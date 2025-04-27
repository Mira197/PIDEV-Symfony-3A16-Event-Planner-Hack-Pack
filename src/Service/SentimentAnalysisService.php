<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SentimentAnalysisService
{
    private $pythonScriptPath;

    public function __construct()
    {
        $this->pythonScriptPath = 'C:/Users/ouech/Desktop/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack/bin/analyze_sentiment.py';
    }

    public function analyzeSentiment(string $text): array
    {
        return $this->analyzeSentimentBatch([$text])[0];
    }

    public function analyzeSentimentBatch(array $texts): array
    {
        $jsonInput = json_encode($texts);
        $process = new Process(['python', $this->pythonScriptPath]);
        $process->setTimeout(600); // 10 minutes
        $process->setInput($jsonInput);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = json_decode($process->getOutput(), true);

        if ($output === null) {
            throw new \Exception('Invalid JSON output from Python script');
        }

        return $output;
    }
}