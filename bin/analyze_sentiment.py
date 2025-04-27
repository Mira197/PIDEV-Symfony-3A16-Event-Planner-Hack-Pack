import sys
import json
from transformers import pipeline

# Charger le modèle multilingue
sentiment_analyzer = pipeline("sentiment-analysis", model="nlptown/bert-base-multilingual-uncased-sentiment")

def analyze_sentiment(text):
    try:
        result = sentiment_analyzer(text)
        score = int(result[0]["label"].split()[0])  # Extrait le score (1 à 5)
        # Convertir le score en catégorie
        if score >= 4:
            sentiment = "POSITIVE"
        elif score <= 2:
            sentiment = "NEGATIVE"
        else:
            sentiment = "NEUTRAL"
        return {
            "sentiment": sentiment
        }
    except Exception as e:
        return {
            "sentiment": "ERROR",
            "error": str(e)
        }

def analyze_sentiment_batch(texts):
    try:
        results = sentiment_analyzer(texts)
        return [
            {
                "sentiment": "POSITIVE" if int(result["label"].split()[0]) >= 4 else "NEGATIVE" if int(result["label"].split()[0]) <= 2 else "NEUTRAL"
            }
            for result in results
        ]
    except Exception as e:
        return [
            {"sentiment": "ERROR", "error": str(e)}
            for _ in texts
        ]

if __name__ == "__main__":
    # Lire les textes depuis l'entrée standard
    input_data = sys.stdin.read()
    texts = json.loads(input_data)

    # Analyser les sentiments
    results = analyze_sentiment_batch(texts)

    # Retourner les résultats en JSON
    print(json.dumps(results))