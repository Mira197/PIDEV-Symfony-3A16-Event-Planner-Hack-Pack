import os
import pickle
import numpy as np
from sklearn.linear_model import LinearRegression

# === 1. Créer des données simulées
X = np.array([[1], [2], [3], [4], [5], [6], [7], [8], [9], [10], [11], [12]])
y = np.array([150, 160, 170, 180, 210, 230, 220, 240, 250, 270, 280, 300])

# === 2. Entraîner le modèle
model = LinearRegression()
model.fit(X, y)

# === 3. Sauvegarder le modèle dans le dossier du script
current_dir = os.path.dirname(os.path.abspath(__file__))
model_path = os.path.join(current_dir, "model.pkl")

with open(model_path, "wb") as f:
    pickle.dump(model, f)

print(f"✅ Modèle sauvegardé dans : {model_path}")
