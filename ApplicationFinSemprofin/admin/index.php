<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();

// Statistiques
$stats = [
    'periodes' => $db->fetchOne("SELECT COUNT(*) as count FROM periodes")['count'],
    'matieres' => $db->fetchOne("SELECT COUNT(*) as count FROM matieres")['count'],
    'professeurs' => $db->fetchOne("SELECT COUNT(*) as count FROM utilisateurs WHERE role = 'professeur'")['count'],
    'etudiants' => $db->fetchOne("SELECT COUNT(*) as count FROM utilisateurs WHERE role = 'etudiant'")['count'],
];

// Périodes actives
$periodes_actives = $db->fetchAll("
    SELECT p.*, 
           COUNT(DISTINCT m.id) as nb_matieres,
           COUNT(DISTINCT ap.professeur_id) as nb_profs
    FROM periodes p
    LEFT JOIN affectations_profs ap ON ap.periode_id = p.id
    LEFT JOIN matieres m ON m.id = ap.matiere_id
    WHERE p.statut IN ('ouverte', 'fermee')
    GROUP BY p.id
    ORDER BY p.date_creation DESC
    LIMIT 5
");

// Progression globale
$progression = $db->fetchAll("
    SELECT m.id as matiere_id, m.nom, m.code, p.nom as periode,
           ps.pourcentage, ps.notes_saisies, ps.total_notes_attendues
    FROM progression_saisie ps
    JOIN matieres m ON m.id = ps.matiere_id
    JOIN periodes p ON p.id = ps.periode_id
    WHERE p.statut = 'ouverte'
    ORDER BY ps.pourcentage ASC
    LIMIT 10
");

$pageTitle = 'Tableau de bord Admin';
include '../includes/header.php';
?>

<div class="dashboard">
    <div class="breadcrumb">
        <a href="index.php">Accueil</a> <span>></span> <span>Tableau de bord</span>
    </div>
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i>
        Tableau de bord Administrateur
    </h1>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['periodes']; ?></h3>
                <p>Périodes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['matieres']; ?></h3>
                <p>Matières</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['professeurs']; ?></h3>
                <p>Professeurs</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #ef4444;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['etudiants']; ?></h3>
                <p>Étudiants</p>
            </div>
        </div>
    </div>

    <!-- Périodes actives -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-calendar-check"></i> Périodes actives</h2>
            <a href="periodes.php" class="btn btn-primary">Gérer les périodes</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Matières</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodes_actives)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune période active</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periodes_actives as $periode): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($periode['nom']); ?></td>
                                <td><?php echo htmlspecialchars($periode['type']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $periode['statut'] === 'ouverte' ? 'success' : 
                                            ($periode['statut'] === 'fermee' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($periode['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($periode['date_debut_saisie'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($periode['date_fin_saisie'])); ?></td>
                                <td><?php echo $periode['nb_matieres']; ?></td>
                                <td>
                                    <a href="periodes.php?id=<?php echo $periode['id']; ?>" class="btn btn-sm btn-primary" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Progression de saisie -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Progression de saisie</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Période</th>
                        <th>Progression</th>
                        <th>Notes saisies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($progression)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Aucune donnée de progression</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($progression as $prog): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prog['nom']); ?></td>
                                <td><?php echo htmlspecialchars($prog['periode']); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $prog['pourcentage']; ?>%">
                                            <?php echo number_format($prog['pourcentage'], 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $prog['notes_saisies']; ?> / <?php echo $prog['total_notes_attendues']; ?></td>
                                <td>
                                    <a href="configurations.php?matiere=<?php echo $prog['matiere_id'] ?? ''; ?>" class="btn btn-sm btn-primary" title="Voir la configuration">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
