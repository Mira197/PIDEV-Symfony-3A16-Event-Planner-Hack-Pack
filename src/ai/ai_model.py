# ai_model.py
import numpy as np
import pickle

def predict_sales():
    """
    Simule une prédiction des ventes pour le mois suivant.
    Remplace cette fonction par ton propre modèle de prédiction.
    """
    # Exemple de prédiction simple : prédiction basée sur des données historiques.
    predicted_sales = np.random.uniform(100, 1000)  # Valeur aléatoire pour la simulation.
    return predicted_sales

if __name__ == '__main__':
    sales_prediction = predict_sales()
    print(sales_prediction)  # Retourne la valeur pour l'utiliser dans Symfony
