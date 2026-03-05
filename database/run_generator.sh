#!/bin/sh

echo "Démarrage du générateur de données en boucle infinie..."

while true; do
  echo ">>> Lancement d'un cycle de génération (python main.py)..."
  python main.py
  echo ">>> Cycle de génération terminé. Attente de 10 secondes avant le prochain cycle..."
  sleep 10
done
