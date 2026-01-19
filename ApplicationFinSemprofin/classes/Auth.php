<?php
/**
 * Classe de gestion de l'authentification
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login($email, $password, $remember = false) {
        $user = $this->db->fetchOne(
            "SELECT * FROM utilisateurs WHERE email = ? AND actif = 1",
            [$email]
        );

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Mettre à jour la dernière connexion
            $this->db->query(
                "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?",
                [$user['id']]
            );

            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];

            // Cookie "Se souvenir de moi"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
                
                // Stocker le token en base (nécessiterait une table tokens)
                // Pour simplifier, on stocke juste l'email dans le cookie (non sécurisé en production)
                // En production, utiliser une table tokens avec hash
            }

            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        // Vérifier le cookie "se souvenir de moi"
        if (isset($_COOKIE['remember_token'])) {
            // En production, vérifier le token en base
            // Pour l'instant, on retourne false
            return false;
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est professeur
     */
    public function isProfesseur() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'professeur';
    }

    /**
     * Vérifier si l'utilisateur est étudiant
     */
    public function isEtudiant() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'etudiant';
    }

    /**
     * Déconnexion
     */
    public function logout() {
        $_SESSION = [];
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetchOne(
            "SELECT * FROM utilisateurs WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    /**
     * Rediriger si non connecté
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $base_path = dirname($_SERVER['SCRIPT_NAME']);
            if ($base_path === '/') {
                $base_path = '';
            }
            header('Location: ' . $base_path . '/login.php');
            exit;
        }
    }

    /**
     * Rediriger si non admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            $base_path = dirname($_SERVER['SCRIPT_NAME']);
            if ($base_path === '/') {
                $base_path = '';
            }
            header('Location: ' . $base_path . '/index.php');
            exit;
        }
    }
}
