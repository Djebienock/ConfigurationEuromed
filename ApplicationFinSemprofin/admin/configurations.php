<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/FormulaParser.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$parser = new FormulaParser();

// Récupérer les matières et périodes
$matieres = $db->fetchAll("SELECT * FROM matieres ORDER BY nom");
$periodes = $db->fetchAll("SELECT * FROM periodes ORDER BY date_creation DESC");

$matiere_id = $_GET['matiere'] ?? ($_POST['matiere_id'] ?? null);
$periode_id = $_GET['periode'] ?? ($_POST['periode_id'] ?? null);

$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_column':
                try {
                    // Récupérer le dernier ordre
                    $last_order = $db->fetchOne("
                        SELECT MAX(ordre) as max_ordre 
                        FROM configuration_colonnes 
                        WHERE matiere_id = ? AND periode_id = ?
                    ", [$matiere_id, $periode_id]);
                    $ordre = ($last_order['max_ordre'] ?? 0) + 1;

                    $db->query("
                        INSERT INTO configuration_colonnes 
                        (matiere_id, periode_id, nom_colonne, code_colonne, type, note_max, coefficient, obligatoire, ordre)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $matiere_id,
                        $periode_id,
                        $_POST['nom_colonne'],
                        $_POST['code_colonne'],
                        $_POST['type'],
                        $_POST['note_max'],
                        $_POST['coefficient'],
                        isset($_POST['obligatoire']) ? 1 : 0,
                        $ordre
                    ]);
                    $message = 'Colonne ajoutée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'update_column':
                try {
                    $db->query("
                        UPDATE configuration_colonnes 
                        SET nom_colonne = ?, code_colonne = ?, type = ?, 
                            note_max = ?, coefficient = ?, obligatoire = ?
                        WHERE id = ?
                    ", [
                        $_POST['nom_colonne'],
                        $_POST['code_colonne'],
                        $_POST['type'],
                        $_POST['note_max'],
                        $_POST['coefficient'],
                        isset($_POST['obligatoire']) ? 1 : 0,
                        $_POST['column_id']
                    ]);
                    $message = 'Colonne mise à jour avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'delete_column':
                try {
                    // Vérifier qu'aucune note n'est saisie
                    $notes_count = $db->fetchOne("
                        SELECT COUNT(*) as count FROM notes WHERE colonne_id = ?
                    ", [$_POST['column_id']]);
                    
                    if ($notes_count['count'] > 0) {
                        throw new Exception('Impossible de supprimer : des notes ont déjà été saisies');
                    }
                    
                    $db->query("DELETE FROM configuration_colonnes WHERE id = ?", [$_POST['column_id']]);
                    $message = 'Colonne supprimée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'save_formula':
                try {
                    // Tester la formule
                    $test_result = $parser->tester($_POST['formule'], []);
                    if (!$test_result['success']) {
                        throw new Exception('Formule invalide : ' . $test_result['message']);
                    }

                    $db->query("
                        INSERT INTO formules (matiere_id, periode_id, formule, description)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            formule = VALUES(formule),
                            description = VALUES(description),
                            date_modification = NOW()
                    ", [
                        $matiere_id,
                        $periode_id,
                        $_POST['formule'],
                        $_POST['description'] ?? ''
                    ]);
                    $message = 'Formule enregistrée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer les colonnes configurées
$colonnes = [];
$formule = null;
if ($matiere_id && $periode_id) {
    $colonnes = $db->fetchAll("
        SELECT * FROM configuration_colonnes 
        WHERE matiere_id = ? AND periode_id = ?
        ORDER BY ordre
    ", [$matiere_id, $periode_id]);

    $formule = $db->fetchOne("
        SELECT * FROM formules 
        WHERE matiere_id = ? AND periode_id = ?
    ", [$matiere_id, $periode_id]);
}

$pageTitle = 'Configuration des Colonnes';
include '../includes/header.php';
?>

<div class="page-container">
    <h1><i class="fas fa-cog"></i> Configuration des Colonnes de Notes</h1>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Sélection Matière/Période -->
    <div class="card">
        <h2>Sélectionner une matière et une période</h2>
        <form method="GET" class="form-inline">
            <div class="form-group">
                <label for="matiere">Matière</label>
                <select id="matiere" name="matiere" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($matieres as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $matiere_id == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['code'] . ' - ' . $m['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="periode">Période</label>
                <select id="periode" name="periode" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($periodes as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $periode_id == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Charger
            </button>
        </form>
    </div>

    <?php if ($matiere_id && $periode_id): ?>
        <!-- Liste des colonnes -->
        <div class="card">
            <div class="card-header">
                <h2>Colonnes configurées</h2>
                <button class="btn btn-primary" onclick="document.getElementById('modal-column').style.display='block'">
                    <i class="fas fa-plus"></i> Ajouter une colonne
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Nom</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Note max</th>
                            <th>Coefficient</th>
                            <th>Obligatoire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($colonnes)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucune colonne configurée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($colonnes as $col): ?>
                                <tr>
                                    <td><?php echo $col['ordre']; ?></td>
                                    <td><?php echo htmlspecialchars($col['nom_colonne']); ?></td>
                                    <td><code><?php echo htmlspecialchars($col['code_colonne']); ?></code></td>
                                    <td><?php echo htmlspecialchars($col['type']); ?></td>
                                    <td><?php echo $col['note_max']; ?></td>
                                    <td><?php echo $col['coefficient']; ?></td>
                                    <td><?php echo $col['obligatoire'] ? 'Oui' : 'Non'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editColumn(<?php echo htmlspecialchars(json_encode($col)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette colonne ?');">
                                            <input type="hidden" name="action" value="delete_column">
                                            <input type="hidden" name="column_id" value="<?php echo $col['id']; ?>">
                                            <input type="hidden" name="matiere_id" value="<?php echo $matiere_id; ?>">
                                            <input type="hidden" name="periode_id" value="<?php echo $periode_id; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configuration de la formule -->
        <div class="card">
            <h2>Formule de calcul de la moyenne</h2>
            <p class="text-muted">Utilisez les codes des colonnes dans la formule (ex: DS1, TP2, Examen)</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_formula">
                <input type="hidden" name="matiere_id" value="<?php echo $matiere_id; ?>">
                <input type="hidden" name="periode_id" value="<?php echo $periode_id; ?>">

                <div class="form-group">
                    <label for="formule">Formule *</label>
                    <input type="text" id="formule" name="formule" required 
                           placeholder="Ex: (DS1 + DS2 + Examen*2) / 4"
                           value="<?php echo $formule ? htmlspecialchars($formule['formule']) : ''; ?>">
                    <small>Variables disponibles: <?php 
                        $codes = array_column($colonnes, 'code_colonne');
                        echo implode(', ', $codes);
                    ?></small>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="2"><?php echo $formule ? htmlspecialchars($formule['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer la formule
                </button>
            </form>

            <div class="formula-examples mt-3">
                <h3>Exemples de formules :</h3>
                <ul>
                    <li><code>(DS1 + DS2 + Examen*2) / 4</code> - Moyenne avec DS coef 1 et Examen coef 2</li>
                    <li><code>TP * 0.3 + Projet * 0.3 + Examen * 0.4</code> - Moyenne pondérée</li>
                    <li><code>MAX(Note1, Note2)</code> - Meilleure des deux notes</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Colonne -->
<div id="modal-column" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-column-title">Ajouter une colonne</h2>
            <span class="close" onclick="document.getElementById('modal-column').style.display='none'">&times;</span>
        </div>
        <form method="POST" id="form-column">
            <input type="hidden" name="action" value="add_column" id="column-action">
            <input type="hidden" name="matiere_id" value="<?php echo $matiere_id; ?>">
            <input type="hidden" name="periode_id" value="<?php echo $periode_id; ?>">
            <input type="hidden" name="column_id" id="column_id">

            <div class="form-group">
                <label for="nom_colonne">Nom de la colonne *</label>
                <input type="text" id="nom_colonne" name="nom_colonne" required placeholder="Ex: DS1, TP2, Examen">
            </div>

            <div class="form-group">
                <label for="code_colonne">Code (pour la formule) *</label>
                <input type="text" id="code_colonne" name="code_colonne" required placeholder="Ex: DS1, TP2, Examen">
                <small>Utilisé dans les formules de calcul</small>
            </div>

            <div class="form-group">
                <label for="type">Type *</label>
                <select id="type" name="type" required>
                    <option value="note">Note</option>
                    <option value="bonus">Bonus</option>
                    <option value="malus">Malus</option>
                    <option value="info">Information</option>
                </select>
            </div>

            <div class="form-group">
                <label for="note_max">Note maximale *</label>
                <input type="number" id="note_max" name="note_max" step="0.01" min="0" value="20" required>
            </div>

            <div class="form-group">
                <label for="coefficient">Coefficient *</label>
                <input type="number" id="coefficient" name="coefficient" step="0.1" min="0" value="1" required>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="obligatoire" id="obligatoire" checked>
                    <span>Obligatoire</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-column').style.display='none'">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-inline {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}

.form-inline .form-group {
    flex: 1;
}

.text-muted {
    color: var(--text-light);
    font-size: 0.875rem;
}

.formula-examples {
    background: var(--light-color);
    padding: 1rem;
    border-radius: 0.375rem;
    margin-top: 1rem;
}

.formula-examples ul {
    margin-top: 0.5rem;
    padding-left: 1.5rem;
}

.formula-examples code {
    background: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
}
</style>

<script>
function editColumn(col) {
    document.getElementById('modal-column-title').textContent = 'Modifier la colonne';
    document.getElementById('column-action').value = 'update_column';
    document.getElementById('column_id').value = col.id;
    document.getElementById('nom_colonne').value = col.nom_colonne;
    document.getElementById('code_colonne').value = col.code_colonne;
    document.getElementById('type').value = col.type;
    document.getElementById('note_max').value = col.note_max;
    document.getElementById('coefficient').value = col.coefficient;
    document.getElementById('obligatoire').checked = col.obligatoire == 1;
    document.getElementById('modal-column').style.display = 'block';
}

// Réinitialiser le formulaire à la fermeture
document.querySelector('#modal-column .close').addEventListener('click', function() {
    document.getElementById('form-column').reset();
    document.getElementById('column-action').value = 'add_column';
    document.getElementById('column_id').value = '';
    document.getElementById('modal-column-title').textContent = 'Ajouter une colonne';
});
</script>

<?php include '../includes/footer.php'; ?>
