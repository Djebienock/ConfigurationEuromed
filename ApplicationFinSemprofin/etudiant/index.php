<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/FormulaParser.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->isEtudiant()) {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$parser = new FormulaParser();
$user_id = $_SESSION['user_id'];

// Récupérer les périodes publiées
$periodes = $db->fetchAll("
    SELECT * FROM periodes 
    WHERE statut = 'publiee'
    ORDER BY date_creation DESC
");

$periode_id = $_GET['periode'] ?? ($periodes[0]['id'] ?? null);

// Récupérer les matières de l'étudiant avec leurs notes
$matieres = [];
if ($periode_id) {
    $matieres = $db->fetchAll("
        SELECT m.*, im.groupe
        FROM inscriptions_matieres im
        JOIN matieres m ON m.id = im.matiere_id
        WHERE im.etudiant_id = ? AND im.periode_id = ?
        ORDER BY m.nom
    ", [$user_id, $periode_id]);

    // Pour chaque matière, récupérer les notes
    foreach ($matieres as &$matiere) {
        // Colonnes configurées
        $colonnes = $db->fetchAll("
            SELECT * FROM configuration_colonnes 
            WHERE matiere_id = ? AND periode_id = ?
            ORDER BY ordre
        ", [$matiere['id'], $periode_id]);

        // Notes de l'étudiant
        $notes = [];
        if (!empty($colonnes)) {
            $colonne_ids = array_column($colonnes, 'id');
            $placeholders = str_repeat('?,', count($colonne_ids) - 1) . '?';
            $notes_data = $db->fetchAll("
                SELECT n.*, cc.code_colonne, cc.nom_colonne, cc.note_max
                FROM notes n
                JOIN configuration_colonnes cc ON cc.id = n.colonne_id
                WHERE n.etudiant_id = ? AND n.colonne_id IN ($placeholders)
            ", array_merge([$user_id], $colonne_ids));

            foreach ($notes_data as $note) {
                $notes[$note['code_colonne']] = $note;
            }
        }

        $matiere['colonnes'] = $colonnes;
        $matiere['notes'] = $notes;

        // Calculer la moyenne
        $formule = $db->fetchOne("
            SELECT * FROM formules 
            WHERE matiere_id = ? AND periode_id = ?
        ", [$matiere['id'], $periode_id]);

        if ($formule) {
            $valeurs = [];
            foreach ($notes as $code => $note) {
                if ($note['statut'] === 'saisie' && $note['valeur'] !== null) {
                    $valeurs[$code] = $note['valeur'];
                }
            }
            if (!empty($valeurs)) {
                try {
                    $matiere['moyenne'] = $parser->evaluer($formule['formule'], $valeurs);
                } catch (Exception $e) {
                    $matiere['moyenne'] = null;
                }
            }
        }
    }
    unset($matiere);
}

$pageTitle = 'Mes Notes';
include '../includes/header.php';
?>

<div class="page-container">
    <h1><i class="fas fa-user-graduate"></i> Mes Notes</h1>

    <?php if (empty($periodes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Aucune période publiée pour le moment.
        </div>
    <?php else: ?>
        <!-- Sélection de période -->
        <div class="card">
            <form method="GET" class="form-inline">
                <div class="form-group">
                    <label for="periode">Période</label>
                    <select id="periode" name="periode" onchange="this.form.submit()">
                        <?php foreach ($periodes as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $periode_id == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if (empty($matieres)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Aucune matière pour cette période.
            </div>
        <?php else: ?>
            <!-- Tableau des notes -->
            <div class="card">
                <h2>Notes de la période : <?php echo htmlspecialchars($periodes[array_search($periode_id, array_column($periodes, 'id'))]['nom'] ?? ''); ?></h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Code</th>
                                <th>Notes</th>
                                <th>Moyenne</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matieres as $matiere): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($matiere['nom']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($matiere['code']); ?></td>
                                    <td>
                                        <div class="notes-detail">
                                            <?php if (empty($matiere['colonnes'])): ?>
                                                <span class="text-muted">Aucune note</span>
                                            <?php else: ?>
                                                <?php foreach ($matiere['colonnes'] as $col): ?>
                                                    <?php 
                                                    $note = $matiere['notes'][$col['code_colonne']] ?? null;
                                                    ?>
                                                    <div class="note-item">
                                                        <span class="note-label"><?php echo htmlspecialchars($col['nom_colonne']); ?>:</span>
                                                        <span class="note-value">
                                                            <?php if ($note): ?>
                                                                <?php if ($note['statut'] === 'saisie' && $note['valeur'] !== null): ?>
                                                                    <?php echo number_format($note['valeur'], 2); ?>/<?php echo $col['note_max']; ?>
                                                                <?php else: ?>
                                                                    <span class="statut-badge statut-<?php echo $note['statut']; ?>">
                                                                        <?php 
                                                                        echo $note['statut'] === 'absent' ? 'ABS' : 
                                                                            ($note['statut'] === 'dispense' ? 'DIS' : 'DEF'); 
                                                                        ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (isset($matiere['moyenne'])): ?>
                                            <strong class="moyenne-value <?php echo $matiere['moyenne'] >= $matiere['seuil_validation'] ? 'success' : 'danger'; ?>">
                                                <?php echo number_format($matiere['moyenne'], 2); ?>/20
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($matiere['moyenne'])): ?>
                                            <?php if ($matiere['moyenne'] >= $matiere['seuil_validation']): ?>
                                                <span class="badge badge-success">Validé</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Non validé</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.notes-detail {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.note-item {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.note-label {
    font-weight: 500;
    min-width: 80px;
}

.note-value {
    color: var(--text-color);
}

.moyenne-value {
    font-size: 1.125rem;
}

.moyenne-value.success {
    color: var(--success-color);
}

.moyenne-value.danger {
    color: var(--danger-color);
}

.statut-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.statut-absent {
    background-color: #fee2e2;
    color: #991b1b;
}

.statut-dispense {
    background-color: #dbeafe;
    color: #1e40af;
}

.statut-defaillant {
    background-color: #fef3c7;
    color: #92400e;
}
</style>

<?php include '../includes/footer.php'; ?>
