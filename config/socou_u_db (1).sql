-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 20 nov. 2025 à 13:16
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

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `numero_commande`, `client_id`, `date_commande`, `statut`, `montant_total`, `frais_livraison`, `mode_paiement`, `statut_paiement`, `date_livraison`, `adresse_livraison`, `notes`, `date_creation`) VALUES
(1, 'CMD20253623', 5, '2025-10-01', 'preparation', 100000.00, 50000.00, 'espece', 'en_attente', '2025-10-01', 'Kibenga lac', 'sacs de mais', '2025-10-01 08:45:05');

-- --------------------------------------------------------

--
-- Structure de la table `compte_cooperative`
--

CREATE TABLE `compte_cooperative` (
  `id_compte` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `solde_disponible` decimal(15,2) DEFAULT 0.00,
  `total_investi` decimal(15,2) DEFAULT 0.00,
  `statut_compte` enum('actif','bloque','ferme') DEFAULT 'actif',
  `date_ouverture` date DEFAULT NULL,
  `date_derniere_operation` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Structure de la table `credits_accordes`
--

CREATE TABLE `credits_accordes` (
  `id_credit` int(11) NOT NULL,
  `numero_credit` varchar(30) NOT NULL,
  `id_demande` int(11) DEFAULT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `id_groupe` int(11) DEFAULT NULL,
  `montant_accorde` decimal(15,2) NOT NULL,
  `taux_interet` decimal(5,2) NOT NULL,
  `duree_mois` int(11) NOT NULL,
  `montant_total_a_rembourser` decimal(15,2) NOT NULL,
  `montant_mensuel` decimal(15,2) NOT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `statut` enum('actif','rembourse','en_retard','restructure','annule') DEFAULT 'actif',
  `accorde_par` int(11) DEFAULT NULL,
  `date_octroi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_credit`
--

CREATE TABLE `demandes_credit` (
  `id_demande` int(11) NOT NULL,
  `numero_demande` varchar(30) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `id_groupe` int(11) DEFAULT NULL,
  `id_type_credit` int(11) DEFAULT NULL,
  `montant_demande` decimal(15,2) NOT NULL,
  `duree_mois` int(11) NOT NULL,
  `motif` text DEFAULT NULL,
  `garanties_proposees` text DEFAULT NULL,
  `documents_justificatifs` text DEFAULT NULL,
  `statut` enum('soumise','en_etude','approuvee','rejetee','annulee') DEFAULT 'soumise',
  `evaluee_par` int(11) DEFAULT NULL,
  `date_evaluation` timestamp NULL DEFAULT NULL,
  `commentaire_evaluation` text DEFAULT NULL,
  `montant_approuve` decimal(15,2) DEFAULT NULL,
  `duree_approuvee` int(11) DEFAULT NULL,
  `taux_applique` decimal(5,2) DEFAULT NULL,
  `date_demande` date DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Structure de la table `failed_login_attempts`
--

CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `failed_login_attempts`
--

INSERT INTO `failed_login_attempts` (`id`, `username`, `ip_address`, `attempt_time`) VALUES
(1, 'janvier_nzambimana01', '::1', '2025-09-12 04:30:34'),
(2, 'janvier_nzambimana01', '::1', '2025-09-12 04:31:09'),
(3, 'janvier_nzambimana01', '::1', '2025-09-12 04:32:30'),
(4, 'dorine_kezakimana0', '::1', '2025-09-12 09:14:54'),
(5, 'dorine_kezakimana0', '::1', '2025-09-12 09:15:21'),
(6, 'dorine_kezakimana0', '::1', '2025-09-12 09:20:17'),
(7, 'dorine_kezakimana0', '::1', '2025-10-01 08:46:47'),
(8, 'dorine_kezakimana0', '::1', '2025-10-01 08:47:00'),
(9, 'dorine_kezakimana0', '::1', '2025-11-19 15:49:05'),
(10, 'dorine_kezakimana0', '::1', '2025-11-19 15:49:16');

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
-- Structure de la table `groupes`
--

CREATE TABLE `groupes` (
  `id_groupe` int(11) NOT NULL,
  `nom_groupe` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `montant_max_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nombre_max_membres` int(11) DEFAULT 20,
  `nombre_membres_actuel` int(11) DEFAULT 0,
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Structure de la table `investissement`
--

CREATE TABLE `investissement` (
  `id_investissement` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `montant` decimal(15,2) NOT NULL,
  `type_investissement` varchar(100) DEFAULT NULL,
  `date_investissement` date DEFAULT NULL,
  `statut` enum('en_attente','valide','rejete') DEFAULT 'en_attente',
  `preuve_paiement` varchar(255) DEFAULT NULL,
  `valide_par` int(11) DEFAULT NULL,
  `date_validation` timestamp NULL DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
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
(53, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-09 17:10:07'),
(54, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-12 04:30:14'),
(55, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-09-12 04:31:27'),
(56, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-12 04:32:13'),
(57, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-09-12 04:37:08'),
(58, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-12 04:37:34'),
(59, 3, 'connexion', 'utilisateurs', 3, 'Connexion réussie - Rôle: gestionnaire', '::1', '2025-09-12 04:37:50'),
(60, 3, 'deconnexion', 'utilisateurs', 3, 'Déconnexion', '::1', '2025-09-12 05:22:02'),
(61, 3, 'connexion', 'utilisateurs', 3, 'Connexion réussie - Rôle: gestionnaire', '::1', '2025-09-12 05:22:35'),
(62, 3, 'deconnexion', 'utilisateurs', 3, 'Déconnexion', '::1', '2025-09-12 09:05:19'),
(63, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-09-12 09:05:52'),
(64, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-12 09:06:12'),
(65, 3, 'connexion', 'utilisateurs', 3, 'Connexion réussie - Rôle: gestionnaire', '::1', '2025-09-12 09:06:30'),
(66, 3, 'deconnexion', 'utilisateurs', 3, 'Déconnexion', '::1', '2025-09-12 09:14:21'),
(67, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-09-12 09:22:50'),
(68, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-09-12 09:24:55'),
(69, 2, 'connexion', 'utilisateurs', 2, 'Connexion réussie - Rôle: membre', '::1', '2025-09-12 09:25:10'),
(70, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-10-01 08:29:19'),
(71, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-10-01 08:46:17'),
(72, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-11-14 11:21:24'),
(73, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-11-19 15:47:45'),
(74, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-11-19 15:48:51'),
(75, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-11-19 15:49:41'),
(76, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-11-19 15:52:03'),
(77, 2, 'connexion', 'utilisateurs', 2, 'Connexion réussie - Rôle: membre', '::1', '2025-11-19 15:52:37'),
(78, 2, 'deconnexion', 'utilisateurs', 2, 'Déconnexion', '::1', '2025-11-19 15:53:33'),
(79, 1, 'connexion', 'utilisateurs', 1, 'Connexion réussie - Rôle: admin', '::1', '2025-11-19 15:53:55'),
(80, 1, 'deconnexion', 'utilisateurs', 1, 'Déconnexion', '::1', '2025-11-19 15:54:18'),
(81, 3, 'connexion', 'utilisateurs', 3, 'Connexion réussie - Rôle: gestionnaire', '::1', '2025-11-19 15:54:30'),
(82, 3, 'deconnexion', 'utilisateurs', 3, 'Déconnexion', '::1', '2025-11-19 17:06:37');

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
(3, 'SOCOU2025003', 'KEZAKIMANA', 'Dorine', 'dorinekeza@gmail.com', '67775733', 'kiriri', 'Bujumbura', 'Mukaza', 'Rohero', '2003-02-14', 'F', '2025-09-03', 'actif', 'producteur', 'Livraison des fruits', NULL, '2025-09-03 07:30:43', '2025-09-03 07:30:43'),
(4, 'MBR3733', 'NZAMBIMANA', 'Janvier', 'janviernzambimana91@gmail.com', '64775733', 'KANYOSHA MUHUTA BUJUMBURA', 'Bujumbura', 'MUHUTA', 'KANYOSHA', '2002-07-06', 'M', '2022-01-01', 'actif', 'producteur', 'Elevage de poules', NULL, '2025-09-12 04:29:48', '2025-09-12 04:29:48'),
(5, 'MBR9752', 'MUGISHA ', 'Keilla', 'keillamugisha@gmail.com', '67454038', 'kibenga large', 'Bujumbura', 'MUHA', 'Kinindo', '2025-10-15', 'F', '2025-10-01', 'actif', 'commercial', 'commerce des produits', NULL, '2025-10-01 08:40:01', '2025-10-01 08:40:01');

-- --------------------------------------------------------

--
-- Structure de la table `membres_groupes`
--

CREATE TABLE `membres_groupes` (
  `id_appartenance` int(11) NOT NULL,
  `id_membre` int(11) DEFAULT NULL,
  `id_groupe` int(11) DEFAULT NULL,
  `date_adhesion` date DEFAULT NULL,
  `statut` enum('actif','inactif','transfere') DEFAULT 'actif',
  `role` enum('membre','responsable','tresorier') DEFAULT 'membre',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Maïs blanc', 'Maïs blanc de qualité supérieure, récolte locale', 1, 1500.00, 'Kg', 500.00, 10, 3, NULL, NULL, 'Janvier-Mars', 'Maïs blanc de qualité supérieure, récolte locale vient dans la region chaude au Burundi', 'disponible', '2025-09-01 11:15:55');

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
-- Structure de la table `remboursements_credit`
--

CREATE TABLE `remboursements_credit` (
  `id_remboursement` int(11) NOT NULL,
  `numero_remboursement` varchar(30) NOT NULL,
  `id_credit` int(11) DEFAULT NULL,
  `montant_capital` decimal(15,2) NOT NULL,
  `montant_interet` decimal(15,2) NOT NULL,
  `montant_total` decimal(15,2) NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `date_paiement` date DEFAULT NULL,
  `methode_paiement` enum('mobile_money','espece','virement','cheque') DEFAULT 'mobile_money',
  `reference_paiement` varchar(100) DEFAULT NULL,
  `statut` enum('en_attente','paye','retard','partiel') DEFAULT 'en_attente',
  `montant_paye` decimal(15,2) DEFAULT 0.00,
  `penalite_retard` decimal(15,2) DEFAULT 0.00,
  `recu_par` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Structure de la table `types_credit`
--

CREATE TABLE `types_credit` (
  `id_type_credit` int(11) NOT NULL,
  `nom_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `taux_interet` decimal(5,2) NOT NULL,
  `duree_max_mois` int(11) DEFAULT 12,
  `montant_min` decimal(15,2) DEFAULT 0.00,
  `montant_max` decimal(15,2) DEFAULT 0.00,
  `garantie_requise` enum('oui','non') DEFAULT 'non',
  `statut` enum('actif','inactif') DEFAULT 'actif',
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
  `nb_connexions` int(11) NOT NULL DEFAULT 0,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `token_reinitialisation` varchar(255) DEFAULT NULL,
  `expiration_token` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `id_membre`, `username`, `password`, `salt`, `role`, `derniere_connexion`, `nb_connexions`, `statut`, `token_reinitialisation`, `expiration_token`) VALUES
(1, 1, 'kelly_mugishawimana1', '$2y$10$Nz8N.keIjFHth5sjEY69hOnfVYA4KsU9dQiIb.JFc65FpmpG8nxqu', '', 'admin', '2025-11-19 15:53:55', 9, 'actif', NULL, NULL),
(2, 3, 'dorine_kezakimana0', '$2y$10$5juf./Vlu9eqz1FAWIEgDut5FvHBcb699YLfEHqB8aGdk99ycPYoi', '2f19204e61d4d55513887baae01dff46', 'membre', '2025-11-19 15:52:36', 2, 'actif', NULL, NULL),
(3, 4, 'janvier_nzambimana01', '$2y$10$t88.WIfuDCYjesZyHPasBuEX.DFoMtHQS8sx2cc1o98EENfDikTaK', '2c5425b279d6e2a69deb03f8e72a8114', 'gestionnaire', '2025-11-19 15:54:30', 4, 'actif', NULL, NULL);

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
-- Index pour la table `compte_cooperative`
--
ALTER TABLE `compte_cooperative`
  ADD PRIMARY KEY (`id_compte`),
  ADD UNIQUE KEY `unique_compte_membre` (`id_membre`);

--
-- Index pour la table `configurations`
--
ALTER TABLE `configurations`
  ADD PRIMARY KEY (`id_config`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `credits_accordes`
--
ALTER TABLE `credits_accordes`
  ADD PRIMARY KEY (`id_credit`),
  ADD UNIQUE KEY `numero_credit` (`numero_credit`),
  ADD KEY `id_demande` (`id_demande`),
  ADD KEY `id_membre` (`id_membre`),
  ADD KEY `id_groupe` (`id_groupe`),
  ADD KEY `accorde_par` (`accorde_par`);

--
-- Index pour la table `demandes_credit`
--
ALTER TABLE `demandes_credit`
  ADD PRIMARY KEY (`id_demande`),
  ADD UNIQUE KEY `numero_demande` (`numero_demande`),
  ADD KEY `id_membre` (`id_membre`),
  ADD KEY `id_groupe` (`id_groupe`),
  ADD KEY `id_type_credit` (`id_type_credit`),
  ADD KEY `evaluee_par` (`evaluee_par`);

--
-- Index pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id_formation`);

--
-- Index pour la table `groupes`
--
ALTER TABLE `groupes`
  ADD PRIMARY KEY (`id_groupe`);

--
-- Index pour la table `inscriptions_formations`
--
ALTER TABLE `inscriptions_formations`
  ADD PRIMARY KEY (`id_inscription`),
  ADD KEY `id_formation` (`id_formation`),
  ADD KEY `id_membre` (`id_membre`);

--
-- Index pour la table `investissement`
--
ALTER TABLE `investissement`
  ADD PRIMARY KEY (`id_investissement`),
  ADD KEY `id_membre` (`id_membre`),
  ADD KEY `valide_par` (`valide_par`);

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
-- Index pour la table `membres_groupes`
--
ALTER TABLE `membres_groupes`
  ADD PRIMARY KEY (`id_appartenance`),
  ADD UNIQUE KEY `unique_membre_groupe_actif` (`id_membre`,`id_groupe`,`statut`),
  ADD KEY `id_groupe` (`id_groupe`);

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
-- Index pour la table `remboursements_credit`
--
ALTER TABLE `remboursements_credit`
  ADD PRIMARY KEY (`id_remboursement`),
  ADD UNIQUE KEY `numero_remboursement` (`numero_remboursement`),
  ADD KEY `id_credit` (`id_credit`),
  ADD KEY `recu_par` (`recu_par`);

--
-- Index pour la table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Index pour la table `temoignages`
--
ALTER TABLE `temoignages`
  ADD PRIMARY KEY (`id_temoignage`);

--
-- Index pour la table `types_credit`
--
ALTER TABLE `types_credit`
  ADD PRIMARY KEY (`id_type_credit`);

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
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `compte_cooperative`
--
ALTER TABLE `compte_cooperative`
  MODIFY `id_compte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `configurations`
--
ALTER TABLE `configurations`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `credits_accordes`
--
ALTER TABLE `credits_accordes`
  MODIFY `id_credit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demandes_credit`
--
ALTER TABLE `demandes_credit`
  MODIFY `id_demande` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `id_formation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `groupes`
--
ALTER TABLE `groupes`
  MODIFY `id_groupe` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inscriptions_formations`
--
ALTER TABLE `inscriptions_formations`
  MODIFY `id_inscription` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `investissement`
--
ALTER TABLE `investissement`
  MODIFY `id_investissement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `logs_activites`
--
ALTER TABLE `logs_activites`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT pour la table `medias`
--
ALTER TABLE `medias`
  MODIFY `id_media` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `membres`
--
ALTER TABLE `membres`
  MODIFY `id_membre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `membres_groupes`
--
ALTER TABLE `membres_groupes`
  MODIFY `id_appartenance` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT pour la table `remboursements_credit`
--
ALTER TABLE `remboursements_credit`
  MODIFY `id_remboursement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `temoignages`
--
ALTER TABLE `temoignages`
  MODIFY `id_temoignage` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `types_credit`
--
ALTER TABLE `types_credit`
  MODIFY `id_type_credit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Contraintes pour la table `compte_cooperative`
--
ALTER TABLE `compte_cooperative`
  ADD CONSTRAINT `compte_cooperative_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`);

--
-- Contraintes pour la table `credits_accordes`
--
ALTER TABLE `credits_accordes`
  ADD CONSTRAINT `credits_accordes_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes_credit` (`id_demande`),
  ADD CONSTRAINT `credits_accordes_ibfk_2` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`),
  ADD CONSTRAINT `credits_accordes_ibfk_3` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id_groupe`),
  ADD CONSTRAINT `credits_accordes_ibfk_4` FOREIGN KEY (`accorde_par`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `demandes_credit`
--
ALTER TABLE `demandes_credit`
  ADD CONSTRAINT `demandes_credit_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`),
  ADD CONSTRAINT `demandes_credit_ibfk_2` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id_groupe`),
  ADD CONSTRAINT `demandes_credit_ibfk_3` FOREIGN KEY (`id_type_credit`) REFERENCES `types_credit` (`id_type_credit`),
  ADD CONSTRAINT `demandes_credit_ibfk_4` FOREIGN KEY (`evaluee_par`) REFERENCES `utilisateurs` (`id_utilisateur`);

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
-- Contraintes pour la table `investissement`
--
ALTER TABLE `investissement`
  ADD CONSTRAINT `investissement_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`),
  ADD CONSTRAINT `investissement_ibfk_2` FOREIGN KEY (`valide_par`) REFERENCES `utilisateurs` (`id_utilisateur`);

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
-- Contraintes pour la table `membres_groupes`
--
ALTER TABLE `membres_groupes`
  ADD CONSTRAINT `membres_groupes_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`),
  ADD CONSTRAINT `membres_groupes_ibfk_2` FOREIGN KEY (`id_groupe`) REFERENCES `groupes_membres` (`id_groupe`);

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
-- Contraintes pour la table `remboursements_credit`
--
ALTER TABLE `remboursements_credit`
  ADD CONSTRAINT `remboursements_credit_ibfk_1` FOREIGN KEY (`id_credit`) REFERENCES `credits_accordes` (`id_credit`),
  ADD CONSTRAINT `remboursements_credit_ibfk_2` FOREIGN KEY (`recu_par`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_membre`) REFERENCES `membres` (`id_membre`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
