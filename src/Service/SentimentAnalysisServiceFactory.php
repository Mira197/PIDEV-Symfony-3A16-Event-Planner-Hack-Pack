<?php

namespace App\Service;

class SentimentAnalysisServiceFactory
{
    public static function create(): SentimentAnalysisService
    {
        $pythonScriptPath = 'C:/Users/ouech/Desktop/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack/bin/analyze_sentiment.py';
        return new SentimentAnalysisService($pythonScriptPath);
    }
}