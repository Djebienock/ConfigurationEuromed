<?php
/**
 * Script d'installation et de configuration initiale
 * À exécuter une seule fois après la création de la base de données
 */

// Déterminer le chemin de base
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if ($base_path === '/') {
    $base_path = '';
}

// Essayer de charger la configuration
$config_loaded = false;
$db_error = '';

try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        $config_loaded = true;
        
        // Vérifier si déjà installé
        try {
            $db = Database::getInstance();
            $test = $db->fetchOne("SELECT COUNT(*) as count FROM utilisateurs");
            if ($test['count'] > 0) {
                $already_installed = true;
            }
        } catch (Exception $e) {
            $db_error = $e->getMessage();
            // Base de données vide ou erreur de connexion, on peut continuer
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$message = '';
$error = '';
$already_installed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    if (!$config_loaded) {
        $error = 'Le fichier de configuration n\'a pas pu être chargé. Vérifiez que config/config.php existe.';
    } else {
    try {
        // Créer une filière de test
        $db->query("
            INSERT INTO filieres (code, nom, niveau) 
            VALUES ('INFO', 'Informatique', 'Licence')
        ");
        $filiere = $db->fetchOne("SELECT id FROM filieres WHERE code = 'INFO'");
        $filiere_id = $filiere['id'];

        // Créer des matières de test
        $matieres = [
            ['MATH101', 'Mathématiques', 2, 3],
            ['PROG101', 'Programmation', 3, 4],
            ['WEB101', 'Programmation Web', 2, 3],
        ];

        foreach ($matieres as $m) {
            $db->query("
                INSERT INTO matieres (code, nom, filiere_id, coefficient, credits, seuil_validation)
                VALUES (?, ?, ?, ?, ?, 10)
            ", [$m[0], $m[1], $filiere_id, $m[2], $m[3]]);
        }

        // Créer une période de test
        $date_debut = date('Y-m-d H:i:s', strtotime('+1 day'));
        $date_fin = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $db->query("
            INSERT INTO periodes (nom, code, annee_universitaire, type, date_debut_saisie, date_fin_saisie, statut)
            VALUES (?, ?, ?, ?, ?, ?, 'a_venir')
        ", [
            'Semestre 1 - ' . date('Y'),
            'S1-' . date('Y'),
            date('Y') . '-' . (date('Y') + 1),
            'semestre',
            $date_debut,
            $date_fin
        ]);

        $message = 'Installation réussie ! Vous pouvez maintenant vous connecter avec admin@univ.fr / admin123';
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'installation : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Gestion des Notes</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-cog"></i>
                <h1>Installation</h1>
                <p>Configuration initiale de l'application</p>
            </div>

            <?php if ($already_installed): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    L'application est déjà installée. Supprimez ce fichier pour des raisons de sécurité.
                    <br><br>
                    <a href="<?php echo $base_path; ?>/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Aller à la page de connexion
                    </a>
                </div>
            <?php elseif ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <br><br>
                    <a href="<?php echo $base_path; ?>/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Aller à la page de connexion
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <?php if ($db_error): ?>
                        <br><br><strong>Détails :</strong> <?php echo htmlspecialchars($db_error); ?>
                    <?php endif; ?>
                </div>
            <?php elseif (!$config_loaded): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Erreur de configuration</strong><br>
                    Le fichier config/config.php n'a pas pu être chargé.
                    <br><br>
                    <strong>Vérifiez :</strong>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Que le fichier config/config.php existe</li>
                        <li>Que la base de données est créée</li>
                        <li>Que les paramètres de connexion dans config/database.php sont corrects</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Ce script va créer des données de test pour faciliter la démonstration.
                    <br><br>
                    <strong>Compte admin par défaut :</strong><br>
                    Email: admin@univ.fr<br>
                    Mot de passe: admin123
                </div>

                <form method="POST">
                    <input type="hidden" name="install" value="1">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-download"></i>
                        Installer les données de test
                    </button>
                </form>

                <div class="login-footer mt-3">
                    <p><strong>Note :</strong> Assurez-vous d'avoir :</p>
                    <ul style="text-align: left; margin-top: 1rem;">
                        <li>Créé la base de données</li>
                        <li>Importé le schéma (database/schema.sql)</li>
                        <li>Configuré la connexion (config/database.php)</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
