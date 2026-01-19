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
                        INSERT INTO filieres (code, nom, niveau, responsable_id)
                        VALUES (?, ?, ?, ?)
                    ", [
                        $_POST['code'],
                        $_POST['nom'],
                        $_POST['niveau'] ?? null,
                        $_POST['responsable_id'] ?? null
                    ]);
                    $message = 'Filière créée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'update':
                try {
                    $db->query("
                        UPDATE filieres 
                        SET code = ?, nom = ?, niveau = ?, responsable_id = ?
                        WHERE id = ?
                    ", [
                        $_POST['code'],
                        $_POST['nom'],
                        $_POST['niveau'] ?? null,
                        $_POST['responsable_id'] ?? null,
                        $_POST['id']
                    ]);
                    $message = 'Filière mise à jour avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $db->query("DELETE FROM filieres WHERE id = ?", [$_POST['id']]);
                    $message = 'Filière supprimée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer les filières
$filieres = $db->fetchAll("
    SELECT f.*, u.nom as responsable_nom, u.prenom as responsable_prenom
    FROM filieres f
    LEFT JOIN utilisateurs u ON u.id = f.responsable_id
    ORDER BY f.nom
");

// Récupérer les responsables possibles
$responsables = $db->fetchAll("
    SELECT * FROM utilisateurs 
    WHERE role IN ('admin', 'professeur')
    ORDER BY nom
");

// Filière à éditer
$filiere_edit = null;
if (isset($_GET['id'])) {
    $filiere_edit = $db->fetchOne("SELECT * FROM filieres WHERE id = ?", [$_GET['id']]);
}

$pageTitle = 'Gestion des Filières';
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Gestion des Filières</h1>
        <button class="btn btn-primary" onclick="document.getElementById('modal-create').style.display='block'">
            <i class="fas fa-plus"></i> Nouvelle filière
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
                        <th>Niveau</th>
                        <th>Responsable</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filieres)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Aucune filière</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filieres as $filiere): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($filiere['code']); ?></td>
                                <td><?php echo htmlspecialchars($filiere['nom']); ?></td>
                                <td><?php echo htmlspecialchars($filiere['niveau'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($filiere['responsable_nom']): ?>
                                        <?php echo htmlspecialchars($filiere['responsable_prenom'] . ' ' . $filiere['responsable_nom']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?id=<?php echo $filiere['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Supprimer cette filière ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $filiere['id']; ?>">
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
</div>

<!-- Modal Création/Édition -->
<div id="modal-create" class="modal" style="display: <?php echo $filiere_edit ? 'block' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $filiere_edit ? 'Modifier' : 'Créer'; ?> une filière</h2>
            <span class="close" onclick="document.getElementById('modal-create').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $filiere_edit ? 'update' : 'create'; ?>">
            <?php if ($filiere_edit): ?>
                <input type="hidden" name="id" value="<?php echo $filiere_edit['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="code">Code *</label>
                <input type="text" id="code" name="code" required 
                       value="<?php echo $filiere_edit ? htmlspecialchars($filiere_edit['code']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required 
                       value="<?php echo $filiere_edit ? htmlspecialchars($filiere_edit['nom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="niveau">Niveau</label>
                <input type="text" id="niveau" name="niveau" 
                       placeholder="Ex: Licence, Master"
                       value="<?php echo $filiere_edit ? htmlspecialchars($filiere_edit['niveau'] ?? '') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="responsable_id">Responsable</label>
                <select id="responsable_id" name="responsable_id">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($responsables as $resp): ?>
                        <option value="<?php echo $resp['id']; ?>" 
                                <?php echo $filiere_edit && $filiere_edit['responsable_id'] == $resp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($resp['prenom'] . ' ' . $resp['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
