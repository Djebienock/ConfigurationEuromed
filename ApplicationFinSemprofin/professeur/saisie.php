<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/FormulaParser.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->isProfesseur()) {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$parser = new FormulaParser();
$user_id = $_SESSION['user_id'];

$matiere_id = $_GET['matiere'] ?? null;
$periode_id = $_GET['periode'] ?? null;

if (!$matiere_id || !$periode_id) {
    header('Location: index.php');
    exit;
}

// Vérifier que le professeur est bien affecté à cette matière
$affectation = $db->fetchOne("
    SELECT * FROM affectations_profs 
    WHERE professeur_id = ? AND matiere_id = ? AND periode_id = ?
", [$user_id, $matiere_id, $periode_id]);

if (!$affectation) {
    die('Accès non autorisé à cette matière');
}

// Récupérer les informations
$matiere = $db->fetchOne("SELECT * FROM matieres WHERE id = ?", [$matiere_id]);
$periode = $db->fetchOne("SELECT * FROM periodes WHERE id = ?", [$periode_id]);
$colonnes = $db->fetchAll("
    SELECT * FROM configuration_colonnes 
    WHERE matiere_id = ? AND periode_id = ?
    ORDER BY ordre
", [$matiere_id, $periode_id]);

// Récupérer les étudiants inscrits
$etudiants = $db->fetchAll("
    SELECT u.*, im.groupe 
    FROM inscriptions_matieres im
    JOIN utilisateurs u ON u.id = im.etudiant_id
    WHERE im.matiere_id = ? AND im.periode_id = ?
    ORDER BY u.nom, u.prenom
", [$matiere_id, $periode_id]);

// Récupérer les notes existantes
$notes_data = [];
if (!empty($colonnes)) {
    $colonne_ids = array_column($colonnes, 'id');
    $placeholders = str_repeat('?,', count($colonne_ids) - 1) . '?';
    $notes = $db->fetchAll("
        SELECT * FROM notes 
        WHERE colonne_id IN ($placeholders)
    ", $colonne_ids);

    foreach ($notes as $note) {
        $notes_data[$note['etudiant_id']][$note['colonne_id']] = $note;
    }
}

// Traitement de la sauvegarde
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_notes') {
    if ($periode['statut'] !== 'ouverte') {
        $message = 'La période n\'est pas ouverte à la saisie';
    } else {
        $saved = 0;
        foreach ($_POST['notes'] as $etudiant_id => $notes_etudiant) {
            foreach ($notes_etudiant as $colonne_id => $data) {
                $valeur = !empty($data['valeur']) ? $data['valeur'] : null;
                $statut = $data['statut'] ?? 'saisie';

                // Vérifier que la colonne existe
                $colonne = array_filter($colonnes, fn($c) => $c['id'] == $colonne_id);
                if (empty($colonne)) continue;
                $colonne = reset($colonne);

                // Validation
                if ($valeur !== null && ($valeur < 0 || $valeur > $colonne['note_max'])) {
                    continue; // Note invalide
                }

                // Sauvegarder ou mettre à jour
                $existing = $db->fetchOne("
                    SELECT * FROM notes 
                    WHERE etudiant_id = ? AND colonne_id = ?
                ", [$etudiant_id, $colonne_id]);

                if ($existing) {
                    // Historique avant modification
                    $db->query("
                        INSERT INTO historique_notes 
                        (note_id, ancienne_valeur, nouvelle_valeur, modifie_par, adresse_ip)
                        VALUES (?, ?, ?, ?, ?)
                    ", [
                        $existing['id'],
                        $existing['valeur'],
                        $valeur,
                        $user_id,
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);

                    $db->query("
                        UPDATE notes 
                        SET valeur = ?, statut = ?, date_modification = NOW()
                        WHERE id = ?
                    ", [$valeur, $statut, $existing['id']]);
                } else {
                    $db->query("
                        INSERT INTO notes (etudiant_id, colonne_id, valeur, statut, saisi_par)
                        VALUES (?, ?, ?, ?, ?)
                    ", [$etudiant_id, $colonne_id, $valeur, $statut, $user_id]);
                }
                $saved++;
            }
        }

        // Mettre à jour la progression
        $total_notes = count($etudiants) * count($colonnes);
        $notes_saisies = $db->fetchOne("
            SELECT COUNT(*) as count FROM notes n
            JOIN configuration_colonnes cc ON cc.id = n.colonne_id
            WHERE cc.matiere_id = ? AND cc.periode_id = ?
        ", [$matiere_id, $periode_id])['count'];

        $pourcentage = $total_notes > 0 ? ($notes_saisies / $total_notes) * 100 : 0;

        $db->query("
            INSERT INTO progression_saisie 
            (matiere_id, periode_id, professeur_id, total_etudiants, total_notes_attendues, notes_saisies, pourcentage)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                notes_saisies = VALUES(notes_saisies),
                pourcentage = VALUES(pourcentage),
                date_mise_a_jour = NOW()
        ", [
            $matiere_id,
            $periode_id,
            $user_id,
            count($etudiants),
            $total_notes,
            $notes_saisies,
            $pourcentage
        ]);

        $message = "$saved note(s) enregistrée(s) avec succès";
    }
}

// Recalculer les moyennes si nécessaire
$formule = $db->fetchOne("
    SELECT * FROM formules 
    WHERE matiere_id = ? AND periode_id = ?
", [$matiere_id, $periode_id]);

$pageTitle = 'Saisie des Notes - ' . htmlspecialchars($matiere['nom']);
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1>
            <i class="fas fa-edit"></i>
            Saisie des Notes - <?php echo htmlspecialchars($matiere['nom']); ?>
        </h1>
        <div>
            <span class="badge badge-<?php echo $periode['statut'] === 'ouverte' ? 'success' : 'warning'; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $periode['statut'])); ?>
            </span>
        </div>
    </div>

    <div class="info-box">
        <p><strong>Période:</strong> <?php echo htmlspecialchars($periode['nom']); ?></p>
        <p><strong>Date limite:</strong> <?php echo date('d/m/Y H:i', strtotime($periode['date_fin_saisie'])); ?></p>
        <?php if ($formule): ?>
            <p><strong>Formule:</strong> <code><?php echo htmlspecialchars($formule['formule']); ?></code></p>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($colonnes)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Aucune colonne configurée pour cette matière. Contactez l'administrateur.
        </div>
    <?php elseif (empty($etudiants)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Aucun étudiant inscrit à cette matière.
        </div>
    <?php else: ?>
        <form method="POST" id="form-notes">
            <input type="hidden" name="action" value="save_notes">
            
            <div class="card">
                <div class="table-container">
                    <table class="notes-table" id="notes-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <?php foreach ($colonnes as $col): ?>
                                    <th>
                                        <?php echo htmlspecialchars($col['nom_colonne']); ?>
                                        <br>
                                        <small>Max: <?php echo $col['note_max']; ?> | Coef: <?php echo $col['coefficient']; ?></small>
                                    </th>
                                <?php endforeach; ?>
                                <?php if ($formule): ?>
                                    <th>Moyenne</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etudiant): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></strong>
                                    </td>
                                    <?php foreach ($colonnes as $col): ?>
                                        <td>
                                            <?php 
                                            $note = $notes_data[$etudiant['id']][$col['id']] ?? null;
                                            $disabled = $periode['statut'] !== 'ouverte' ? 'disabled' : '';
                                            ?>
                                            <input type="number" 
                                                   name="notes[<?php echo $etudiant['id']; ?>][<?php echo $col['id']; ?>][valeur]"
                                                   class="note-input"
                                                   step="0.01"
                                                   min="0"
                                                   max="<?php echo $col['note_max']; ?>"
                                                   value="<?php echo $note && $note['statut'] === 'saisie' ? $note['valeur'] : ''; ?>"
                                                   placeholder="0-<?php echo $col['note_max']; ?>"
                                                   <?php echo $disabled; ?>>
                                            <select name="notes[<?php echo $etudiant['id']; ?>][<?php echo $col['id']; ?>][statut]"
                                                    class="statut-select" <?php echo $disabled; ?>>
                                                <option value="saisie" <?php echo !$note || $note['statut'] === 'saisie' ? 'selected' : ''; ?>>Note</option>
                                                <option value="absent" <?php echo $note && $note['statut'] === 'absent' ? 'selected' : ''; ?>>ABS</option>
                                                <option value="dispense" <?php echo $note && $note['statut'] === 'dispense' ? 'selected' : ''; ?>>DIS</option>
                                                <option value="defaillant" <?php echo $note && $note['statut'] === 'defaillant' ? 'selected' : ''; ?>>DEF</option>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if ($formule): ?>
                                        <td class="moyenne-cell">
                                            <?php
                                            // Calculer la moyenne
                                            $valeurs = [];
                                            foreach ($colonnes as $col) {
                                                $note = $notes_data[$etudiant['id']][$col['id']] ?? null;
                                                if ($note && $note['statut'] === 'saisie' && $note['valeur'] !== null) {
                                                    $valeurs[$col['code_colonne']] = $note['valeur'];
                                                }
                                            }
                                            if (!empty($valeurs)) {
                                                try {
                                                    $moyenne = $parser->evaluer($formule['formule'], $valeurs);
                                                    echo number_format($moyenne, 2);
                                                } catch (Exception $e) {
                                                    echo '-';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($periode['statut'] === 'ouverte'): ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Enregistrer les notes
                    </button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<style>
.info-box {
    background: white;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow);
}

.info-box p {
    margin: 0.5rem 0;
}

.notes-table {
    font-size: 0.875rem;
}

.notes-table th {
    position: sticky;
    top: 0;
    background: var(--light-color);
    z-index: 10;
}

.notes-table td {
    padding: 0.5rem;
}

.note-input {
    width: 80px;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
}

.statut-select {
    width: 80px;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
    margin-top: 0.25rem;
}

.moyenne-cell {
    font-weight: 600;
    background: var(--light-color);
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.125rem;
}
</style>

<script>
// Sauvegarde automatique toutes les 30 secondes
let autoSaveTimer;
const form = document.getElementById('form-notes');

if (form) {
    form.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            // Sauvegarde automatique via AJAX (à implémenter)
            console.log('Auto-save...');
        }, 30000);
    });
}
</script>

<?php include '../includes/footer.php'; ?>
