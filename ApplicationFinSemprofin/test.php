<?php
/**
 * Fichier de test pour diagnostiquer les problèmes d'accès
 */

echo "<h1>Test d'accès</h1>";
echo "<p>Si vous voyez ce message, PHP fonctionne correctement.</p>";

echo "<h2>Informations serveur :</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "</pre>";

echo "<h2>Fichiers présents :</h2>";
echo "<ul>";
$files = ['install.php', 'login.php', 'index.php', 'config/config.php', 'database/schema.sql'];
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "<li>$file : " . ($exists ? "✅ Existe" : "❌ N'existe pas") . "</li>";
}
echo "</ul>";

echo "<h2>Permissions :</h2>";
echo "<pre>";
echo "install.php : " . substr(sprintf('%o', fileperms(__DIR__ . '/install.php')), -4) . "\n";
echo "Dossier racine : " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
echo "</pre>";

echo "<h2>Test de connexion à la base de données :</h2>";
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance();
        echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Fichier config/database.php non trouvé</p>";
}

echo "<hr>";
echo "<p><a href='install.php'>Essayer d'accéder à install.php</a></p>";
echo "<p><a href='login.php'>Essayer d'accéder à login.php</a></p>";
?>
