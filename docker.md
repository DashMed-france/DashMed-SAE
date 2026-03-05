# Guide d'utilisation de DashMed avec Docker

Ce projet est configuré pour fonctionner entièrement sous Docker, orchestrant le serveur web PHP, la base de données MariaDB, et le générateur asynchrone Python.

## Prérequis

1. Avoir **Docker Desktop** (ou Docker Engine) installé et démarré sur votre machine.
2. Avoir téléchargé/cloné le code source du projet.

---

## 🚀 Démarrer le projet

Ouvrez un terminal à la racine du projet (là où se trouve le fichier `docker-compose.yml`) et exécutez :

```bash
docker compose up -d
```

- Le flag `-d` permet de lancer les conteneurs en arrière-plan (mode détaché) pour rendre la main au terminal.
- Lors du tout premier lancement :
  - Docker va télécharger les images nécessaires.
  - La base de données va s'initialiser automatiquement à l'aide des scripts présents dans le dossier `database` (`dashmed_dev.sql`, `dashmed_inserts.sql`, `dashmed_consultations.sql`).

**Une fois démarré, le site est accessible sur : [http://localhost:8000](http://localhost:8000)**

---

## 🛑 Arrêter le projet

Pour stopper tous les conteneurs proprement, exécutez :

```bash
docker compose down
```

*Note : Vos données (dont celles de la base de données) sont conservées d'un lancement à l'autre grâce au volume Docker que nous avons configuré.*

---

## 🧹 Réinitialiser complètement la Base de Données

Si vous souhaitez effacer complètement la base de données et forcer les scripts SQL à se rejouer (pour repartir à zéro) :

```bash
# Coupe les conteneurs et supprime le volume contenant les données
docker compose down -v

# Relance les conteneurs (qui vont réexécuter les scripts d'initialisation)
docker compose up -d
```

---

## 🔍 Voir les journaux (Logs)

Si vous souhaitez vérifier ce qu'il se passe en arrière-plan (erreurs PHP, progression du générateur Python, requêtes SQL) :

```bash
# Voir tous les logs en direct
docker compose logs -f

# Voir les logs d'un service spécifique (ex: web, db, ou generator)
docker compose logs -f generator
```
*(Utilisez `Ctrl+C` pour quitter l'affichage des logs)*

---

## ⚙️ Détails de l'architecture

- **`web`** (Port 8000) : Image `php:8.2-apache`. Le code PHP/HTML/CSS qui s'y trouve est synchronisé en temps réel avec votre dossier local. Vous pouvez modifier votre code et rafraîchir la page, les changements seront immédiats.
- **`db`** (Port 3306) : Image `mariadb:10.11`. Stocke toutes les données.
- **`generator`** : Un conteneur Python `3.11` qui exécute en boucle infinie (toutes les 10 secondes) le fichier `database/main.py`. Celui-ci a pour rôle de générer des données patient en continu pour que les graphiques du tableau de bord soit alimentés et animés en temps réel.
