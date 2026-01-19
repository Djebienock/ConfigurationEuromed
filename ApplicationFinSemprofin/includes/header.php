<?php
// Déterminer si on est dans un sous-dossier (admin, professeur, etudiant)
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
$is_in_subdir = strpos($current_dir, '/admin') !== false || 
                strpos($current_dir, '/professeur') !== false || 
                strpos($current_dir, '/etudiant') !== false;

// Chemin simple pour les assets : ../ si on est dans un sous-dossier, sinon rien
$asset_path = $is_in_subdir ? '../' : '';

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo defined('APP_NAME') ? APP_NAME : 'Gestion des Notes'; ?></title>
    <link rel="stylesheet" href="<?php echo $asset_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if ($is_logged_in): ?>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?php echo defined('APP_NAME') ? APP_NAME : 'Gestion des Notes'; ?></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <?php
                    // Déterminer si on est dans le dossier admin
                    $is_in_admin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
                    $admin_prefix = $is_in_admin ? '' : 'admin/';
                    ?>
                    <a href="<?php echo $admin_prefix; ?>index.php" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/index.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                    <a href="<?php echo $admin_prefix; ?>periodes.php" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/periodes.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Périodes</span>
                    </a>
                    <a href="<?php echo $admin_prefix; ?>filieres.php" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/filieres.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Filières</span>
                    </a>
                    <a href="<?php echo $admin_prefix; ?>matieres.php" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/matieres.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Matières</span>
                    </a>
                    <a href="<?php echo $admin_prefix; ?>configurations.php" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], '/admin/configurations.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Configurations</span>
                    </a>
                <?php elseif ($_SESSION['user_role'] === 'professeur'): ?>
                    <?php
                    $is_in_prof = strpos($_SERVER['SCRIPT_NAME'], '/professeur/') !== false;
                    $prof_prefix = $is_in_prof ? '' : 'professeur/';
                    ?>
                    <a href="<?php echo $prof_prefix; ?>index.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Mes matières</span>
                    </a>
                    <a href="<?php echo $prof_prefix; ?>saisie.php" class="nav-item">
                        <i class="fas fa-edit"></i>
                        <span>Saisie des notes</span>
                    </a>
                <?php elseif ($_SESSION['user_role'] === 'etudiant'): ?>
                    <?php
                    $is_in_etud = strpos($_SERVER['SCRIPT_NAME'], '/etudiant/') !== false;
                    $etud_prefix = $is_in_etud ? '' : 'etudiant/';
                    ?>
                    <a href="<?php echo $etud_prefix; ?>index.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Mes notes</span>
                    </a>
                    <a href="<?php echo $etud_prefix; ?>releves.php" class="nav-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Relevés</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <i class="fas fa-user-circle"></i>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    </div>
                </div>
                <a href="<?php echo $asset_path; ?>logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Top Header Bar -->
            <header class="top-header">
                <div class="header-content">
                    <div class="header-title">
                        <h2>Panneau de contrôle Administrateur</h2>
                    </div>
                    <div class="header-actions">
                        <div class="user-menu">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>
            <main class="main-content">
    <?php else: ?>
        <!-- Page sans sidebar (login, etc.) -->
        <main class="main-content">
    <?php endif; ?>
