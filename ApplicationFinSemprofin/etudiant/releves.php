<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/PDFGenerator.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->isEtudiant()) {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Récupérer les périodes publiées
$periodes = $db->fetchAll("
    SELECT * FROM periodes 
    WHERE statut = 'publiee'
    ORDER BY date_creation DESC
");

$periode_id = $_GET['periode'] ?? null;
$action = $_GET['action'] ?? 'view';

if ($action === 'download' && $periode_id) {
    $generator = new PDFGenerator();
    try {
        $html = $generator->generateReleve($user_id, $periode_id);
        
        // En production, convertir en PDF avec TCPDF/DomPDF
        // Pour l'instant, on affiche le HTML
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    } catch (Exception $e) {
        die('Erreur : ' . $e->getMessage());
    }
}

$pageTitle = 'Mes Relevés';
include '../includes/header.php';
?>

<div class="page-container">
    <h1><i class="fas fa-file-pdf"></i> Mes Relevés de Notes</h1>

    <?php if (empty($periodes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Aucune période publiée pour le moment.
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Télécharger un relevé</h2>
            <p>Sélectionnez une période pour télécharger votre relevé de notes au format PDF.</p>
            
            <div class="releves-list">
                <?php foreach ($periodes as $periode): ?>
                    <div class="releve-item">
                        <div class="releve-info">
                            <h3><?php echo htmlspecialchars($periode['nom']); ?></h3>
                            <p>Année universitaire : <?php echo htmlspecialchars($periode['annee_universitaire']); ?></p>
                            <p>Date de publication : <?php echo date('d/m/Y', strtotime($periode['date_publication'])); ?></p>
                        </div>
                        <div class="releve-actions">
                            <a href="?action=download&periode=<?php echo $periode['id']; ?>" 
                               class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i> Télécharger le relevé
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.releves-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}

.releve-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: var(--light-color);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

.releve-info h3 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.releve-info p {
    margin: 0.25rem 0;
    color: var(--text-light);
    font-size: 0.875rem;
}
</style>

<?php include '../includes/footer.php'; ?>
