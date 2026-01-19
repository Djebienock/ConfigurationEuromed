<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->isProfesseur()) {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Récupérer les matières du professeur
$matieres = $db->fetchAll("
    SELECT DISTINCT m.*, p.id as periode_id, p.nom as periode_nom, p.statut as periode_statut,
           p.date_fin_saisie, ap.groupe
    FROM affectations_profs ap
    JOIN matieres m ON m.id = ap.matiere_id
    JOIN periodes p ON p.id = ap.periode_id
    WHERE ap.professeur_id = ?
    ORDER BY p.date_fin_saisie DESC, m.nom
", [$user_id]);

$pageTitle = 'Mes Matières';
include '../includes/header.php';
?>

<div class="page-container">
    <h1><i class="fas fa-chalkboard-teacher"></i> Mes Matières</h1>

    <?php if (empty($matieres)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Aucune matière assignée pour le moment.
        </div>
    <?php else: ?>
        <div class="matieres-grid">
            <?php foreach ($matieres as $matiere): ?>
                <div class="matiere-card">
                    <div class="matiere-header">
                        <h3><?php echo htmlspecialchars($matiere['nom']); ?></h3>
                        <span class="badge badge-<?php 
                            echo $matiere['periode_statut'] === 'ouverte' ? 'success' : 
                                ($matiere['periode_statut'] === 'fermee' ? 'warning' : 'secondary'); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $matiere['periode_statut'])); ?>
                        </span>
                    </div>
                    <div class="matiere-body">
                        <p><strong>Code:</strong> <?php echo htmlspecialchars($matiere['code']); ?></p>
                        <p><strong>Période:</strong> <?php echo htmlspecialchars($matiere['periode_nom']); ?></p>
                        <?php if ($matiere['groupe']): ?>
                            <p><strong>Groupe:</strong> <?php echo htmlspecialchars($matiere['groupe']); ?></p>
                        <?php endif; ?>
                        <p><strong>Date limite:</strong> 
                            <?php echo date('d/m/Y H:i', strtotime($matiere['date_fin_saisie'])); ?>
                        </p>
                    </div>
                    <div class="matiere-actions">
                        <?php if ($matiere['periode_statut'] === 'ouverte'): ?>
                            <a href="saisie.php?matiere=<?php echo $matiere['id']; ?>&periode=<?php echo $matiere['periode_id']; ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-edit"></i> Saisir les notes
                            </a>
                        <?php else: ?>
                            <a href="saisie.php?matiere=<?php echo $matiere['id']; ?>&periode=<?php echo $matiere['periode_id']; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-eye"></i> Consulter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.matieres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.matiere-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.matiere-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.matiere-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.matiere-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.matiere-body {
    padding: 1.5rem;
}

.matiere-body p {
    margin: 0.5rem 0;
    color: var(--text-color);
}

.matiere-actions {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color);
    background: var(--light-color);
}

.matiere-actions .btn {
    width: 100%;
    justify-content: center;
}
</style>

<?php include '../includes/footer.php'; ?>
