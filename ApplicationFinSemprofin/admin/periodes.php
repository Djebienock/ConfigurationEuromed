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
                        INSERT INTO periodes (nom, code, annee_universitaire, type, date_debut_saisie, date_fin_saisie, statut)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $_POST['nom'],
                        $_POST['code'],
                        $_POST['annee_universitaire'],
                        $_POST['type'],
                        $_POST['date_debut_saisie'],
                        $_POST['date_fin_saisie'],
                        'a_venir'
                    ]);
                    $message = 'Période créée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur lors de la création : ' . $e->getMessage();
                }
                break;

            case 'update':
                try {
                    $db->query("
                        UPDATE periodes 
                        SET nom = ?, code = ?, annee_universitaire = ?, type = ?,
                            date_debut_saisie = ?, date_fin_saisie = ?, statut = ?
                        WHERE id = ?
                    ", [
                        $_POST['nom'],
                        $_POST['code'],
                        $_POST['annee_universitaire'],
                        $_POST['type'],
                        $_POST['date_debut_saisie'],
                        $_POST['date_fin_saisie'],
                        $_POST['statut'],
                        $_POST['id']
                    ]);
                    $message = 'Période mise à jour avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur lors de la mise à jour : ' . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $db->query("DELETE FROM periodes WHERE id = ?", [$_POST['id']]);
                    $message = 'Période supprimée avec succès';
                } catch (Exception $e) {
                    $error = 'Erreur lors de la suppression : ' . $e->getMessage();
                }
                break;
        }
    }
}

// Récupérer toutes les périodes
$periodes = $db->fetchAll("
    SELECT p.*, 
           COUNT(DISTINCT m.id) as nb_matieres
    FROM periodes p
    LEFT JOIN affectations_profs ap ON ap.periode_id = p.id
    LEFT JOIN matieres m ON m.id = ap.matiere_id
    GROUP BY p.id
    ORDER BY p.date_creation DESC
");

// Période à éditer
$periode_edit = null;
if (isset($_GET['id'])) {
    $periode_edit = $db->fetchOne("SELECT * FROM periodes WHERE id = ?", [$_GET['id']]);
}

$pageTitle = 'Gestion des Périodes';
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-calendar"></i> Gestion des Périodes</h1>
        <button class="btn btn-primary" onclick="document.getElementById('modal-create').style.display='block'">
            <i class="fas fa-plus"></i> Nouvelle période
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
                        <th>Nom</th>
                        <th>Code</th>
                        <th>Année universitaire</th>
                        <th>Type</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Matières</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodes)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Aucune période</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periodes as $periode): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($periode['nom']); ?></td>
                                <td><?php echo htmlspecialchars($periode['code']); ?></td>
                                <td><?php echo htmlspecialchars($periode['annee_universitaire']); ?></td>
                                <td><?php echo htmlspecialchars($periode['type']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($periode['date_debut_saisie'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($periode['date_fin_saisie'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $periode['statut'] === 'ouverte' ? 'success' : 
                                            ($periode['statut'] === 'fermee' ? 'warning' : 
                                            ($periode['statut'] === 'publiee' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $periode['statut'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $periode['nb_matieres']; ?></td>
                                <td>
                                    <a href="?id=<?php echo $periode['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette période ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $periode['id']; ?>">
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
<div id="modal-create" class="modal" style="display: <?php echo $periode_edit ? 'block' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $periode_edit ? 'Modifier' : 'Créer'; ?> une période</h2>
            <span class="close" onclick="document.getElementById('modal-create').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?php echo $periode_edit ? 'update' : 'create'; ?>">
            <?php if ($periode_edit): ?>
                <input type="hidden" name="id" value="<?php echo $periode_edit['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required 
                       value="<?php echo $periode_edit ? htmlspecialchars($periode_edit['nom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="code">Code *</label>
                <input type="text" id="code" name="code" required 
                       value="<?php echo $periode_edit ? htmlspecialchars($periode_edit['code']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="annee_universitaire">Année universitaire *</label>
                <input type="text" id="annee_universitaire" name="annee_universitaire" 
                       pattern="\d{4}-\d{4}" placeholder="2024-2025" required
                       value="<?php echo $periode_edit ? htmlspecialchars($periode_edit['annee_universitaire']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="type">Type *</label>
                <select id="type" name="type" required>
                    <option value="semestre" <?php echo $periode_edit && $periode_edit['type'] === 'semestre' ? 'selected' : ''; ?>>Semestre</option>
                    <option value="trimestre" <?php echo $periode_edit && $periode_edit['type'] === 'trimestre' ? 'selected' : ''; ?>>Trimestre</option>
                    <option value="session" <?php echo $periode_edit && $periode_edit['type'] === 'session' ? 'selected' : ''; ?>>Session</option>
                    <option value="rattrapage" <?php echo $periode_edit && $periode_edit['type'] === 'rattrapage' ? 'selected' : ''; ?>>Rattrapage</option>
                </select>
            </div>

            <div class="form-group">
                <label for="date_debut_saisie">Date début saisie *</label>
                <input type="datetime-local" id="date_debut_saisie" name="date_debut_saisie" required
                       value="<?php echo $periode_edit ? date('Y-m-d\TH:i', strtotime($periode_edit['date_debut_saisie'])) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="date_fin_saisie">Date fin saisie *</label>
                <input type="datetime-local" id="date_fin_saisie" name="date_fin_saisie" required
                       value="<?php echo $periode_edit ? date('Y-m-d\TH:i', strtotime($periode_edit['date_fin_saisie'])) : ''; ?>">
            </div>

            <?php if ($periode_edit): ?>
                <div class="form-group">
                    <label for="statut">Statut *</label>
                    <select id="statut" name="statut" required>
                        <option value="a_venir" <?php echo $periode_edit['statut'] === 'a_venir' ? 'selected' : ''; ?>>À venir</option>
                        <option value="ouverte" <?php echo $periode_edit['statut'] === 'ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                        <option value="fermee" <?php echo $periode_edit['statut'] === 'fermee' ? 'selected' : ''; ?>>Fermée</option>
                        <option value="publiee" <?php echo $periode_edit['statut'] === 'publiee' ? 'selected' : ''; ?>>Publiée</option>
                    </select>
                </div>
            <?php endif; ?>

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

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 600px;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    color: var(--text-light);
}

.close:hover {
    color: var(--text-color);
}

.modal-content form {
    padding: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.btn-secondary {
    background-color: var(--text-light);
    color: white;
}

.badge-secondary {
    background-color: #e5e7eb;
    color: #374151;
}
</style>

<?php include '../includes/footer.php'; ?>
