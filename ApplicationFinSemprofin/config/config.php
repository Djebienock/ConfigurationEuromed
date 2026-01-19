<?php
/**
 * Configuration générale de l'application
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once __DIR__ . '/database.php';

// Configuration de l'application
define('APP_NAME', 'Gestion des Notes');
define('APP_URL', 'http://localhost/profin');
define('TIMEZONE', 'Europe/Paris');

// Configuration de sécurité
define('SESSION_LIFETIME', 3600); // 1 heure
define('REMEMBER_ME_LIFETIME', 2592000); // 30 jours

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('PDF_PATH', ROOT_PATH . '/pdf');

// Créer les dossiers si nécessaire
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(PDF_PATH)) {
    mkdir(PDF_PATH, 0755, true);
}

// Définir le fuseau horaire
date_default_timezone_set(TIMEZONE);

// Fonction d'autoload pour les classes
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/classes/' . $class . '.php',
        ROOT_PATH . '/includes/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
