import os
import pickle
import numpy as np

def predict_sales(mois_suivant=13):
    """
    Prédit les ventes pour un mois donné en utilisant un modèle entraîné.
    """

    # Obtenir le chemin absolu du modèle par rapport à ce script
    current_dir = os.path.dirname(os.path.abspath(__file__))
    model_path = os.path.join(current_dir, 'model.pkl')

    with open(model_path, "rb") as f:
        model = pickle.load(f)

    prediction = model.predict(np.array([[mois_suivant]]))
    return round(prediction[0], 2)

if __name__ == '__main__':
    sales_prediction = predict_sales()
    print(f"{sales_prediction}")
