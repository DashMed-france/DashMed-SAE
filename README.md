# 🩺 DashMed – Application Web MVC en PHP / PHP MVC Web Application

[![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: All Rights Reserved](https://img.shields.io/badge/License-All%20Rights%20Reserved-red.svg)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-phpDocumentor-blue)](./docs)

---

## 🇫🇷 Présentation du projet

**DashMed** est une application web réalisée en **PHP** suivant une architecture **MVC (Modèle – Vue – Contrôleur)**.  
Conçue dans un cadre universitaire, elle illustre la mise en œuvre d’un site structuré, documenté et sécurisé, appliquant les bonnes pratiques de développement web moderne.

Le but du projet est de proposer une **plateforme de gestion médicale** simple et modulaire, permettant aux utilisateurs (patients, médecins, administrateurs) d’interagir avec leurs données via une interface claire et responsive.

---

### 🎯 Objectifs pédagogiques

- Structurer une application PHP autour du **pattern MVC**
- Implémenter des opérations **CRUD sécurisées avec PDO**
- Gérer l’**authentification complète** (connexion, inscription, réinitialisation de mot de passe)
- Documenter le code source via **phpDocumentor**
- Concevoir une **interface web ergonomique et responsive**

---

### 🧩 Fonctionnalités principales

- 🔐 **Authentification**
    - Connexion, inscription, déconnexion
    - Réinitialisation de mot de passe par e-mail (via PHPMailer)
- 👤 **Gestion du profil**
    - Consultation et modification des informations utilisateur
- 🩹 **Tableau de bord**
    - Interface dynamique adaptée au rôle (patient / médecin / admin)
- 🧠 **Documentation intégrée**
    - Génération automatique des fichiers de documentation (`/docs`)
- ⚙️ **Architecture évolutive**
    - Organisation claire en dossiers `models`, `views`, `controllers`

---

### 🧱 Architecture technique

| Composant                | Description                                                                          |
| ------------------------ |--------------------------------------------------------------------------------------|
| **Langage principal**    | PHP 8.x                                                                              |
| **Base de données**      | MySQL (Base de données pour stocker les données des médecins ainsi que des patients) |
| **Modèle architectural** | MVC                                                                                  |
| **Dépendances**          | Composer, PHPMailer, phpDocumentor                                                   |
| **Documentation**        | Générée automatiquement dans `docs/`                                                 |
| **Serveur web**          | PHP intégré / Apache / Nginx                                                         |

**Arborescence simplifiée :**

```
DashMed-Projet/
├── app/
│   ├── controllers/
│   ├── models/
│   └── views/
├── public/
│   ├── index.php
│   └── assets/css/
├── assets/includes/
│   ├── database.php
│   └── Mailer.php
└── docs/
```

---

### 🚀 Démarrage rapide

#### Prérequis

- PHP ≥ 8.0
- Composer
- MySQL 
- Serveur SMTP (pour les tests de mail)

#### Installation

```bash
composer install
php -S localhost:8888 -t public
```

Puis ouvre : [http://localhost:8888](http://localhost:8888)

#### Configuration `.env`

Crée un fichier `.env` à la racine (ne pas le versionner) :

```dotenv
DB_HOST=your_host
DB_USER=your_user
DB_PASS=your_password
DB_NAME=your_database

SMTP_HOST=your_smtp_host
SMTP_PORT=465
SMTP_USER=your_email
SMTP_PASS=your_smtp_password
```

---

### 🧭 Perspectives d’évolution

- Ajout d’un système de **rôles avancé** (permissions utilisateur)
- Intégration d’**API REST** pour les données médicales
- Passage à un **framework PHP** (Laravel, Symfony)
- Ajout de **tests unitaires** et pipeline CI/CD
- Refonte du design en **Tailwind CSS** ou **Bootstrap 5**

---

### ⚖️ Mentions légales

> Ce projet est réalisé à des fins **pédagogiques** dans le cadre d’un enseignement universitaire.  
> Il ne collecte ni ne traite de données réelles de santé.

**Éditeur du site** : DashMed (projet universitaire)  
**Responsable du contenu** : Équipe de développement DashMed  
**Contact** : [dashmed@alwaysdata.net](mailto:dashmed@alwaysdata.net)  
**Hébergement** : alwaysdata.net / Serveur local<br>
**Adresse** : 13080, France<br>
**Nationalité** : Française

**Crédits :**

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) – Licence MIT
- [phpDocumentor](https://www.phpdoc.org/) – Licence MIT
- Police “Poppins” via Google Fonts
- Images [Flaticons](https://www.flaticon.com/)

---

## 🇬🇧 Project Overview

**DashMed** is a **PHP MVC web application** built as part of an academic project.  
It aims to demonstrate how to design a structured, maintainable, and documented application following professional web development standards.

The project provides a **medical management dashboard** allowing users (patients, doctors, admins) to interact with data through a clear and responsive interface.

---

### 🎯 Educational Objectives

- Implement a clean **MVC structure** in PHP
- Develop secure **CRUD operations** using PDO
- Build a **complete authentication system** (login, signup, password reset)
- Generate **automatic code documentation** using phpDocumentor
- Design a **responsive, accessible web interface**

---

### 🧩 Key Features

- 🔐 **Authentication**
    - Login, signup, logout
    - Password reset via email (PHPMailer)
- 👤 **User Profile**
    - Edit and view personal data
- 🩹 **Dashboard**
    - Role-based dynamic interface (patient / doctor / admin)
- 🧠 **Documentation**
    - Auto-generated developer documentation (`/docs`)
- ⚙️ **Scalable architecture**
    - Modular file structure for long-term maintainability

---

### 🧱 Tech Stack

| Component         | Description                        |
| ----------------- | ---------------------------------- |
| **Language**      | PHP 8.x                            |
| **Database**      | MySQL                              |
| **Architecture**  | MVC                                |
| **Dependencies**  | Composer, PHPMailer, phpDocumentor |
| **Documentation** | Auto-generated via phpDocumentor   |
| **Server**        | PHP built-in / Apache / Nginx      |

---

### ⚖️ Legal Notice

> This project is for **educational purposes only** and does **not process any real medical data**.

**Publisher**: DashMed (University Project)  
**Team**: DashMed Development Team  
**Contact**: [dashmed@alwaysdata.net](mailto:dashmed@alwaysdata.net)  
**Hosting**: alwaysdata.net / Local Server  
**Address**: 13080, France  
**Nationality**: French

**Credits:**

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) – MIT License
- [phpDocumentor](https://www.phpdoc.org/) – MIT License
- “Poppins” font via Google Fonts
- Images [Flaticons](https://www.flaticon.com/)

---

© 2025 DashMed Project – All rights reserved.
