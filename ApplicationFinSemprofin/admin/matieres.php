<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $db->query("
                        INSERT INTO matieres (code, nom, filiere_id, coefficient, credits, seuil_validation)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [
                        $_POST['code'],
                        $_POST['nom'],
                        $_POST['filiere_id'],
                        $_POST['coefficient'],
                        $_POST['credits'] ?? null,
                        $_POST['seuil_validation']
                    ]);
                    $message = 'Matière créée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'update':
                try {
                    $db->query("
                        UPDATE matieres 
                        SET code = ?, nom = ?, filiere_id = ?, coefficient = ?, 
                            credits = ?, seuil_validation = ?
                        WHERE id = ?
                    ", [
                        $_POST['code'],
                        $_POST['nom'],
                        $_POST['filiere_id'],
                        $_POST['coefficient'],
                        $_POST['credits'] ?? null,
                        $_POST['seuil_validation'],
                        $_POST['id']
                    ]);
                    $message = 'Matière mise à jour avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $db->query("DELETE FROM matieres WHERE id = ?", [$_POST['id']]);
                    $message = 'Matière supprimée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer les matières avec leurs filières
$matieres = $db->fetchAll("
    SELECT m.*, f.nom as filiere_nom 
    FROM matieres m
    LEFT JOIN filieres f ON f.id = m.filiere_id
    ORDER BY m.nom
");

// Récupérer les filières
$filieres = $db->fetchAll("SELECT * FROM filieres ORDER BY nom");

// Matière à éditer
$matiere_edit = null;
if (isset($_GET['id'])) {
    $matiere_edit = $db->fetchOne("SELECT * FROM matieres WHERE id = ?", [$_GET['id']]);
}

$pageTitle = 'Gestion des Matières';
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> Gestion des Matières</h1>
        <button class="btn btn-primary" onclick="document.getElementById('modal-create').style.display='block'">
            <i class="fas fa-plus"></i> Nouvelle matière
        </button>
    </div>

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

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Filière</th>
                        <th>Coefficient</th>
                        <th>Crédits</th>
                        <th>Seuil validation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matieres)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune matière</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matieres as $matiere): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($matiere['code']); ?></td>
                                <td><?php echo htmlspecialchars($matiere['nom']); ?></td>
                                <td><?php echo htmlspecialchars($matiere['filiere_nom'] ?? '-'); ?></td>
                                <td><?php echo $matiere['coefficient']; ?></td>
                                <td><?php echo $matiere['credits'] ?? '-'; ?></td>
                                <td><?php echo $matiere['seuil_validation']; ?></td>
                                <td>
                                    <a href="configurations.php?matiere=<?php echo $matiere['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Configurer">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <a href="?id=<?php echo $matiere['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Supprimer cette matière ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $matiere['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
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
</div>

<!-- Modal Création/Édition -->
<div id="modal-create" class="modal" style="display: <?php echo $matiere_edit ? 'block' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $matiere_edit ? 'Modifier' : 'Créer'; ?> une matière</h2>
            <span class="close" onclick="document.getElementById('modal-create').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $matiere_edit ? 'update' : 'create'; ?>">
            <?php if ($matiere_edit): ?>
                <input type="hidden" name="id" value="<?php echo $matiere_edit['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="code">Code *</label>
                <input type="text" id="code" name="code" required 
                       value="<?php echo $matiere_edit ? htmlspecialchars($matiere_edit['code']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required 
                       value="<?php echo $matiere_edit ? htmlspecialchars($matiere_edit['nom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="filiere_id">Filière *</label>
                <select id="filiere_id" name="filiere_id" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?php echo $f['id']; ?>" 
                                <?php echo $matiere_edit && $matiere_edit['filiere_id'] == $f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="coefficient">Coefficient *</label>
                <input type="number" id="coefficient" name="coefficient" step="0.1" min="0" value="1" required
                       value="<?php echo $matiere_edit ? $matiere_edit['coefficient'] : '1'; ?>">
            </div>

            <div class="form-group">
                <label for="credits">Crédits</label>
                <input type="number" id="credits" name="credits" min="0"
                       value="<?php echo $matiere_edit ? $matiere_edit['credits'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="seuil_validation">Seuil de validation *</label>
                <input type="number" id="seuil_validation" name="seuil_validation" step="0.01" min="0" max="20" value="10" required
                       value="<?php echo $matiere_edit ? $matiere_edit['seuil_validation'] : '10'; ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-create').style.display='none'">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
