<?php
/**
 * Script pour créer/mettre à jour le compte admin
 * À exécuter une fois pour créer le compte admin avec le bon hash
 */

require_once 'config/config.php';

$db = Database::getInstance();

// Générer le hash du mot de passe admin123
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

echo "<h1>Création du compte admin</h1>";

try {
    // Vérifier si l'admin existe déjà
    $existing = $db->fetchOne("SELECT * FROM utilisateurs WHERE email = 'admin@univ.fr'");
    
    if ($existing) {
        // Mettre à jour le mot de passe
        $db->query("
            UPDATE utilisateurs 
            SET mot_de_passe = ?, nom = 'Admin', prenom = 'Système', role = 'admin', actif = 1
            WHERE email = 'admin@univ.fr'
        ", [$password_hash]);
        echo "<p style='color: green;'>✅ Compte admin mis à jour avec succès</p>";
    } else {
        // Créer le compte admin
        $db->query("
            INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, actif)
            VALUES (?, ?, 'Admin', 'Système', 'admin', 1)
        ", ['admin@univ.fr', $password_hash]);
        echo "<p style='color: green;'>✅ Compte admin créé avec succès</p>";
    }
    
    echo "<p><strong>Identifiants :</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@univ.fr</li>";
    echo "<li>Mot de passe: admin123</li>";
    echo "</ul>";
    
    echo "<p><a href='login.php'>Aller à la page de connexion</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>La base de données est créée</li>";
    echo "<li>Le schéma a été importé (database/schema.sql)</li>";
    echo "<li>La connexion est configurée (config/database.php)</li>";
    echo "</ul>";
}
?>
