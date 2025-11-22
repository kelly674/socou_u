-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 10 sep. 2025 à 20:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `socou_u_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `actualites`
--

CREATE TABLE `actualites` (
  `id_actualite` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `resume` text DEFAULT NULL,
  `contenu` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type_actualite` enum('nouvelle','evenement','annonce') DEFAULT 'nouvelle',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `lieu` varchar(100) DEFAULT NULL,
  `auteur_id` int(11) DEFAULT NULL,
  `statut` enum('brouillon','publie','archive') DEFAULT 'brouillon',
  `vues` int(11) DEFAULT 0,
  `mots_cles` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_publication` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `beneficiaires_projets`
--

CREATE TABLE `beneficiaires_projets` (
  `id_beneficiaire` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `id_projet` int(11) DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `statut_participation` enum('inscrit','actif','termine','abandonne') DEFAULT 'inscrit',
  `evaluation` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories_produits`
--

CREATE TABLE `categories_produits` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordre_affichage` int(11) DEFAULT 0,
  `statut` enum('actif','inactif') DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories_produits`
--

INSERT INTO `categories_produits` (`id_categorie`, `nom_categorie`, `description`, `icone`, `ordre_affichage`, `statut`) VALUES
(1, 'Céréales', 'Maïs, riz, blé et autres céréales cultivées par nos membres', 'fas fa-seedling', 1, 'actif'),
(2, 'Légumineuses', 'Haricots, pois, lentilles et autres légumineuses riches en protéines', 'fas fa-leaf', 2, 'actif'),
(3, 'Tubercules', 'Pommes de terre, patates douces, manioc et autres tubercules', 'fas fa-mountain', 3, 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id_commande` int(11) NOT NULL,
  `numero_commande` varchar(20) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `date_commande` date NOT NULL,
  `statut` enum('en_attente','confirmee','preparation','expediee','livree','annulee') DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `frais_livraison` decimal(10,2) DEFAULT 0.00,
  `mode_paiement` enum('espece','mobile','virement','cheque') DEFAULT 'espece',
  `statut_paiement` enum('en_attente','partiel','paye') DEFAULT 'en_attente',
  `date_livraison` date DEFAULT NULL,
  `adresse_livraison` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `configurations`
--

CREATE TABLE `configurations` (
  `id_config` int(11) NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `configurations`
--

INSERT INTO `configurations` (`id_config`, `cle`, `valeur`, `description`, `date_modification`) VALUES
(1, 'nom_cooperative', 'Société Coopérative UMUSHINGE W\'UBUZIMA (SOCOU_U)', 'Nom officiel de la coopérative', '2025-08-29 07:14:25'),
(2, 'slogan', 'Solidarité, Autonomie et Développement Durable', 'Slogan de la coopérative', '2025-08-29 07:14:25'),
(3, 'adresse', 'Province de Bujumbura, Zone Rohero, Commune Mukaza', 'Adresse physique de la coopérative', '2025-08-29 07:14:25'),
(4, 'telephone', '+257 22 66 99 01', 'Numéro de téléphone principal', '2025-08-29 07:14:25'),
(5, 'email', 'contact@socou-u.bi', 'Adresse email de contact', '2025-08-29 07:14:25'),
(6, 'annee_fondation', '2019', 'Année de fondation de la coopérative', '2025-08-29 07:14:25');

-- --------------------------------------------------------

--
-- Structure de la table `details_commandes`
--

CREATE TABLE `details_commandes` (
  `id_detail` int(11) NOT NULL,
  `id_commande` int(11) DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `sous_total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `formations`
--

CREATE TABLE `formations` (
  `id_formation` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `objectifs` text DEFAULT NULL,
  `formateur` varchar(100) DEFAULT NULL,
  `date_formation` date DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `duree_heures` int(11) DEFAULT NULL,
  `lieu` varchar(100) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `frais_participation` decimal(10,2) DEFAULT 0.00,
  `statut` enum('programmee','en_cours','terminee','annulee') DEFAULT 'programmee',
  `supports` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions_formations`
--

CREATE TABLE `inscriptions_formations` (
  `id_inscription` int(11) NOT NULL,
  `id_formation` int(11) DEFAULT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `statut` enum('inscrit','present','absent','certifie') DEFAULT 'inscrit',
  `note_evaluation` decimal(3,1) DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs_activites`
--

CREATE TABLE `logs_activites` (
  `id_log` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_concernee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs_activites`
--

INSERT INTO `logs_activites` (`id_log`, `utilisateur_id`, `action`, `table_concernee`, `id_enregistrement`, `details`, `adresse_ip`, `date_action`) VALUES
(1, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 10:24:23'),
(2, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 10:39:37'),
(3, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 10:40:08'),
(4, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 15:35:30'),
(5, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 15:35:51'),
(6, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 16:03:00'),
(7, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 16:11:59'),
(8, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 16:13:30'),
(9, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 16:36:45'),
(10, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 16:37:25'),
(11, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 17:01:10'),
(12, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 17:21:35'),
(13, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 18:18:32'),
(14, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-30 18:25:01'),
(15, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-30 18:26:21'),
(16, NULL, 'newsletter_subscription', 'newsletter_abonnes', 1, 'Nouvel abonnement newsletter pour janviernzambimana91@gmail.com', '::1', '2025-08-31 06:10:16'),
(17, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-31 10:22:03'),
(18, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-08-31 11:22:55'),
(19, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-08-31 16:39:03'),
(20, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 07:44:51'),
(21, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 07:45:21'),
(22, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 07:46:34'),
(23, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 07:51:52'),
(24, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 08:36:50'),
(25, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 08:42:03'),
(26, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 08:53:49'),
(27, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 08:59:42'),
(28, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 11:06:08'),
(29, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 11:08:05'),
(30, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-01 11:09:15'),
(31, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-01 21:37:08'),
(32, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-02 08:34:44'),
(33, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-02 18:04:29'),
(34, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-02 18:09:33'),
(35, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-02 18:11:57'),
(36, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-03 07:17:07'),
(37, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-03 07:21:36'),
(38, 2, 'connexion', 'utilisateurs', 2, 'Connexion réussie', '::1', '2025-09-03 07:32:06'),
(39, 2, 'deconnexion', 'utilisateurs', 2, 'Déconnexion', '::1', '2025-09-03 07:40:18'),
(40, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-03 07:40:30'),
(41, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-03 09:53:02'),
(42, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-03 09:57:12'),
(43, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-03 10:29:49'),
(44, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-03 10:30:04'),
(45, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-05 09:33:16'),
(46, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-05 09:46:16'),
(47, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-05 09:47:46'),
(48, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-05 09:48:36'),
(49, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-05 10:10:50'),
(50, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-05 10:11:06'),
(51, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-05 10:11:29'),
(52, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie', '::1', '2025-09-09 05:20:24'),
(53, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-09 17:10:07');

-- --------------------------------------------------------

--
-- Structure de la table `medias`
--

CREATE TABLE `medias` (
  `id_media` int(11) NOT NULL,
  `titre` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fichier` varchar(255) NOT NULL,
  `type_media` enum('image','video','document') NOT NULL,
  `categorie` enum('production','formation','evenement','projet') DEFAULT 'production',
  `date_publication` date DEFAULT NULL,
  `auteur_id` int(11) DEFAULT NULL,
  `statut` enum('public','prive') DEFAULT 'public',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medias`
--

INSERT INTO `medias` (`id_media`, `titre`, `description`, `fichier`, `type_media`, `categorie`, `date_publication`, `auteur_id`, `statut`, `date_creation`) VALUES
(1, 'Formation en Agriculture Moderne', 'Session de formation sur les techniques agricoles durables', '68b47fdee3044.jpg', 'image', 'production', '2025-08-31', 1, 'public', '2025-08-31 17:01:18'),
(2, 'Récolte de Maïs - Saison A 2024', 'Excellente récolte de nos producteurs membres', '68b4826ed3434.jpg', 'image', 'production', '2025-08-31', 1, 'public', '2025-08-31 17:12:14');

-- --------------------------------------------------------

--
-- Structure de la table `membres`
--

CREATE TABLE `membres` (
  `id_membre` int(11) NOT NULL,
  `code_membre` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `commune` varchar(50) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `genre` enum('M','F') DEFAULT NULL,
  `date_adhesion` date DEFAULT NULL,
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `type_membre` enum('producteur','transformateur','commercial','administratif') NOT NULL,
  `specialisation` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `membres`
--

INSERT INTO `membres` (`id_membre`, `code_membre`, `nom`, `prenom`, `email`, `telephone`, `adresse`, `province`, `commune`, `zone`, `date_naissance`, `genre`, `date_adhesion`, `statut`, `type_membre`, `specialisation`, `photo`, `date_creation`, `date_modification`) VALUES
(1, 'SOCOU2025001', 'MUGISHAWIMANA', 'Kelly', 'kmugishawimana@gmail.com', '77999756', 'BUJUMBURA KIBENGA', 'Bujumbura', 'MUHA', 'KIBENGA', '2004-10-21', 'F', '2025-08-30', 'actif', 'commercial', 'Elevage Bovin', NULL, '2025-08-30 10:23:28', '2025-08-30 10:23:28'),
(2, 'MBR5867', 'NZAMBIMANA', 'Janvier', 'janviernzambimana91@gmail.com', '69951268', 'BUJUMBURA BURUNDI KANYOSHA', 'BUJUMBURA', 'MUHA', 'KANYOSHA', '2002-06-07', 'M', '2025-08-30', 'actif', 'producteur', 'Vendeur des produits agricoles et elevages', NULL, '2025-08-30 15:43:34', '2025-08-30 15:43:34'),
(3, 'SOCOU2025003', 'KEZAKIMANA', 'Dorine', 'dorinekeza@gmail.com', '67775733', 'kiriri', 'Bujumbura', 'Mukaza', 'Rohero', '2003-02-14', 'F', '2025-09-03', 'actif', 'producteur', 'Livraison des fruits', NULL, '2025-09-03 07:30:43', '2025-09-03 07:30:43');

-- --------------------------------------------------------

--
-- Structure de la table `messages_contact`
--

CREATE TABLE `messages_contact` (
  `id_message` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('non_lu','lu','repondu') DEFAULT 'non_lu',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages_contact`
--

INSERT INTO `messages_contact` (`id_message`, `nom`, `email`, `telephone`, `sujet`, `message`, `statut`, `date_creation`) VALUES
(1, 'NZAMBIMANA Janvier', 'kmugishawimana@gmail.com', '69951268', 'Adhésion', 'Bonsoir...!!! je pense que vous etes bien alors j\'ai le souhait de vous demander que vous pouvez m\'ajouter dans votre Cooperative comme un membre', 'non_lu', '2025-08-30 18:06:02'),
(2, 'NZAMBIMANA Janvier', 'kmugishawimana@gmail.com', '69951268', 'Adhésion', 'Bonsoir...!!! je pense que vous etes bien alors j\'ai le souhait de vous demander que vous pouvez m\'ajouter dans votre Cooperative comme un membre', 'non_lu', '2025-08-30 18:08:29');

-- --------------------------------------------------------

--
-- Structure de la table `newsletter_abonnes`
--

CREATE TABLE `newsletter_abonnes` (
  `id_abonne` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `statut` enum('actif','desabonne') DEFAULT 'actif',
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `newsletter_abonnes`
--

INSERT INTO `newsletter_abonnes` (`id_abonne`, `email`, `nom`, `statut`, `date_inscription`) VALUES
(1, 'janviernzambimana91@gmail.com', 'NZAMBIMANA Janvier', 'actif', '2025-08-31 06:10:16');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `nom_produit` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `unite_mesure` varchar(20) DEFAULT NULL,
  `stock_disponible` decimal(10,2) DEFAULT 0.00,
  `stock_alerte` int(11) DEFAULT 10,
  `producteur_id` int(11) DEFAULT NULL,
  `image_principale` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `saisonnalite` varchar(100) DEFAULT NULL,
  `caracteristiques` text DEFAULT NULL,
  `statut` enum('disponible','epuise','saisonnier','inactif') DEFAULT 'disponible',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `nom_produit`, `description`, `id_categorie`, `prix_unitaire`, `unite_mesure`, `stock_disponible`, `stock_alerte`, `producteur_id`, `image_principale`, `images`, `saisonnalite`, `caracteristiques`, `statut`, `date_creation`) VALUES
(1, 'Maïs blanc', 'Maïs blanc de qualité supérieure, récolte locale', 1, 1500.00, 'Kg', 500.00, 10, 2, NULL, NULL, 'Janvier-Mars', 'Maïs blanc de qualité supérieure, récolte locale vient dans la region chaude au Burundi', 'disponible', '2025-09-01 11:15:55');

-- --------------------------------------------------------

--
-- Structure de la table `projets_sociaux`
--

CREATE TABLE `projets_sociaux` (
  `id_projet` int(11) NOT NULL,
  `nom_projet` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `objectif` text DEFAULT NULL,
  `resultats_attendus` text DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `budget_previsto` decimal(15,2) DEFAULT NULL,
  `budget_depense` decimal(15,2) DEFAULT 0.00,
  `statut` enum('planifie','en_cours','termine','suspendu') DEFAULT 'planifie',
  `responsable_id` int(11) DEFAULT NULL,
  `beneficiaires_cibles` int(11) DEFAULT NULL,
  `zone_intervention` varchar(100) DEFAULT NULL,
  `partenaires` text DEFAULT NULL,
  `indicateurs_succes` text DEFAULT NULL,
  `image_illustration` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `projets_sociaux`
--

INSERT INTO `projets_sociaux` (`id_projet`, `nom_projet`, `description`, `objectif`, `resultats_attendus`, `date_debut`, `date_fin`, `budget_previsto`, `budget_depense`, `statut`, `responsable_id`, `beneficiaires_cibles`, `zone_intervention`, `partenaires`, `indicateurs_succes`, `image_illustration`, `date_creation`) VALUES
(1, 'Programme d\\\'Autonomisation des Femmes Rurales', 'Programme visant à renforcer les capacités des femmes rurales dans l\\\'entrepreneuriat agricole et la transformation des produits.', 'Former 100 femmes aux techniques de transformation et leur fournir un accompagnement pour créer leurs micro-entreprises.', '100 femmes formées, 50 micro-entreprises créées, amélioration des revenus de 60%', '2025-08-30', '2025-12-31', 25000000.00, 15000000.00, 'en_cours', 1, 100, 'Communes Mukaza, Muha, Ntahangwa', 'ONU Femmes, Ministère du Genre', '', NULL, '2025-08-30 15:56:06');

-- --------------------------------------------------------

--
-- Structure de la table `temoignages`
--

CREATE TABLE `temoignages` (
  `id_temoignage` int(11) NOT NULL,
  `auteur` varchar(100) NOT NULL,
  `fonction` varchar(100) DEFAULT NULL,
  `contenu` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `statut` enum('en_attente','approuve','rejete') DEFAULT 'en_attente',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` text NOT NULL,
  `role` enum('admin','gestionnaire','membre') DEFAULT 'membre',
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `token_reinitialisation` varchar(255) DEFAULT NULL,
  `expiration_token` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `id_membre`, `username`, `password`, `salt`, `role`, `derniere_connexion`, `statut`, `token_reinitialisation`, `expiration_token`) VALUES
(1, 1, 'kelly_mugishawimana1', '$2y$10$Nz8N.keIjFHth5sjEY69hOnfVYA4KsU9dQiIb.JFc65FpmpG8nxqu', '', 'admin', '2025-09-09 05:20:23', 'actif', NULL, NULL),
(2, 3, 'dorine_kezakimana0', '$2y$10$0LMn7ZuAhWFxRhLpOLEuFOoHen6OH8WFDi5h1jZ/mFPc/U0MDgnC2', '', 'membre', '2025-09-03 07:32:06', 'actif', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `actualites`
--
ALTER TABLE `actualites`
  ADD PRIMARY KEY (`id_actualite`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `beneficiaires_projets`
--
ALTER TABLE `beneficiaires_projets`
  ADD PRIMARY KEY (`id_beneficiaire`),
  ADD KEY `id_membre` (`id_membre`),
  ADD KEY `id_projet` (`id_projet`);

--
-- Index pour la table `categories_produits`
--
ALTER TABLE `categories_produits`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_commande`),
  ADD UNIQUE KEY `numero_commande` (`numero_commande`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `configurations`
--
ALTER TABLE `configurations`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id_formation`);

--
-- Index pour la table `inscriptions_formations`
--
ALTER TABLE `inscriptions_formations`
  ADD PRIMARY KEY (`id_inscription`),
  ADD KEY `id_formation` (`id_formation`),
  ADD KEY `id_membre` (`id_membre`);

--
-- Index pour la table `logs_activites`
--
ALTER TABLE `logs_activites`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `medias`
--
ALTER TABLE `medias`
  ADD PRIMARY KEY (`id_media`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `membres`
--
ALTER TABLE `membres`
  ADD PRIMARY KEY (`id_membre`),
  ADD UNIQUE KEY `code_membre` (`code_membre`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD PRIMARY KEY (`id_message`);

--
-- Index pour la table `newsletter_abonnes`
--
ALTER TABLE `newsletter_abonnes`
  ADD PRIMARY KEY (`id_abonne`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `id_categorie` (`id_categorie`),
  ADD KEY `producteur_id` (`producteur_id`);

--
-- Index pour la table `projets_sociaux`
--
ALTER TABLE `projets_sociaux`
  ADD PRIMARY KEY (`id_projet`),
  ADD KEY `responsable_id` (`responsable_id`);

--
-- Index pour la table `temoignages`
--
ALTER TABLE `temoignages`
  ADD PRIMARY KEY (`id_temoignage`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_membre` (`id_membre`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `actualites`
--
ALTER TABLE `actualites`
  MODIFY `id_actualite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `beneficiaires_projets`
--
ALTER TABLE `beneficiaires_projets`
  MODIFY `id_beneficiaire` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories_produits`
--
ALTER TABLE `categories_produits`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `configurations`
--
ALTER TABLE `configurations`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `id_formation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inscriptions_formations`
--
ALTER TABLE `inscriptions_formations`
  MODIFY `id_inscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `logs_activites`
--
ALTER TABLE `logs_activites`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `medias`
--
ALTER TABLE `medias`
  MODIFY `id_media` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `membres`
--
ALTER TABLE `membres`
  MODIFY `id_membre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `newsletter_abonnes`
--
ALTER TABLE `newsletter_abonnes`
  MODIFY `id_abonne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `projets_sociaux`
--
ALTER TABLE `projets_sociaux`
  MODIFY `id_projet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `temoignages`
--
ALTER TABLE `temoignages`
  MODIFY `id_temoignage` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `actualites`
--
ALTER TABLE `actualites`
  ADD CONSTRAINT `actualites_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `beneficiaires_projets`
--
ALTER TABLE `beneficiaires_projets`
  ADD CONSTRAINT `beneficiaires_projets_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`),
  ADD CONSTRAINT `beneficiaires_projets_ibfk_2` FOREIGN KEY (`id_projet`) REFERENCES `projets_sociaux` (`id_projet`);

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `membres` (`id_membre`);

--
-- Contraintes pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD CONSTRAINT `details_commandes_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_commandes_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`);

--
-- Contraintes pour la table `inscriptions_formations`
--
ALTER TABLE `inscriptions_formations`
  ADD CONSTRAINT `inscriptions_formations_ibfk_1` FOREIGN KEY (`id_formation`) REFERENCES `formations` (`id_formation`),
  ADD CONSTRAINT `inscriptions_formations_ibfk_2` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`);

--
-- Contraintes pour la table `logs_activites`
--
ALTER TABLE `logs_activites`
  ADD CONSTRAINT `logs_activites_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `medias`
--
ALTER TABLE `medias`
  ADD CONSTRAINT `medias_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_produits` (`id_categorie`),
  ADD CONSTRAINT `produits_ibfk_2` FOREIGN KEY (`producteur_id`) REFERENCES `membres` (`id_membre`);

--
-- Contraintes pour la table `projets_sociaux`
--
ALTER TABLE `projets_sociaux`
  ADD CONSTRAINT `projets_sociaux_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
