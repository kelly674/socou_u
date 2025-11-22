SOCOU_U - Système de Gestion Coopérative
Afficher l'image
Afficher l'image
Afficher l'image
Afficher l'image
📋 Description
SOCOU_U (Société Coopérative UMUSHINGE W'UBUZIMA) est une plateforme web de gestion coopérative dédiée au développement agropastoral et social au Burundi. Cette application permet de gérer efficacement les membres, les activités, les cotisations et les projets de la coopérative.
Fonctionnalités principales

✅ Gestion des membres et adhésions
💰 Suivi des cotisations et contributions
📊 Tableau de bord avec statistiques en temps réel
📁 Gestion documentaire
🔐 Système d'authentification sécurisé (utilisateurs, administrateurs)
📝 Historique des activités (logs)
🖼️ Upload de fichiers (images, documents)
📄 Génération de rapports


🚀 Installation
Prérequis
Avant de commencer, assurez-vous d'avoir installé :

Serveur web local : XAMPP, WAMP, MAMP ou équivalent
PHP : Version 7.4 ou supérieure
MySQL : Version 8.0 ou supérieure
Extensions PHP requises :

PDO
PDO_MySQL
GD (pour la manipulation d'images)
mbstring



Étapes d'installation
1. Télécharger le projet
bash# Option 1 : Cloner le dépôt (recommandé)
git clone https://github.com/kelly674/socou_u.git

# Option 2 : Télécharger le ZIP
# Téléchargez depuis https://github.com/kelly674/socou_u
# Puis décompressez l'archive
2. Placer le projet
Déplacez le dossier socou_u dans le répertoire de votre serveur local :

XAMPP : C:\xampp\htdocs\socou_u
WAMP : C:\wamp64\www\socou_u
MAMP : /Applications/MAMP/htdocs/socou_u

3. Créer la base de données

Démarrez votre serveur MySQL
Accédez à phpMyAdmin : http://localhost/phpmyadmin
Créez une nouvelle base de données : socou_u_db
Importez le fichier SQL :

Cliquez sur la base de données créée
Allez dans l'onglet "Importer"
Sélectionnez le fichier database/socou_u_db.sql
Cliquez sur "Exécuter"



4. Configuration
Modifiez le fichier config/database.php avec vos paramètres :
php<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');     
define('DB_NAME', 'socou_u_db');     
define('DB_USER', 'root'); 
define('DB_PASS', '');               
define('DB_CHARSET', 'utf8mb4');
?>
Ajustez également le fichier config/config.php :
php// Modifiez l'URL selon votre configuration
define('SITE_URL', 'http://localhost/socou_u');
5. Créer les dossiers nécessaires
Assurez-vous que ces dossiers existent et ont les bonnes permissions :
bashmkdir -p assets/uploads/membres
mkdir -p assets/uploads/documents
chmod -R 755 assets/uploads
6. Lancement

Démarrez Apache et MySQL depuis votre panneau de contrôle (XAMPP, WAMP, etc.)
Ouvrez votre navigateur et accédez à : http://localhost/socou_u


👤 Connexion par défaut
Une fois l'installation terminée, connectez-vous avec les identifiants par défaut :
Administrateur :

Nom d'utilisateur : kelly_mugishawimana1
Mot de passe : 67444025

⚠️ Important : Changez immédiatement le mot de passe après la première connexion !
