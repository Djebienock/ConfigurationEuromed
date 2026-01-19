<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

$auth = new Auth();

// Si déjà connecté, rediriger
if ($auth->isLoggedIn()) {
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    if ($base_path === '/') {
        $base_path = '';
    }
    header('Location: ' . $base_path . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($auth->login($email, $password, $remember)) {
        // Déterminer le chemin de base
        $base_path = dirname($_SERVER['SCRIPT_NAME']);
        if ($base_path === '/') {
            $base_path = '';
        }
        
        // Rediriger selon le rôle
        $role = $_SESSION['user_role'];
        if ($role === 'admin') {
            header('Location: ' . $base_path . '/admin/index.php');
        } elseif ($role === 'professeur') {
            header('Location: ' . $base_path . '/professeur/index.php');
        } elseif ($role === 'etudiant') {
            header('Location: ' . $base_path . '/etudiant/index.php');
        } else {
            header('Location: ' . $base_path . '/index.php');
        }
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect';
    }
}

$pageTitle = 'Connexion';
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-graduation-cap"></i>
            <h1>Connexion</h1>
            <p>Application de Gestion des Notes</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email
                </label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Mot de passe
                </label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    <span>Se souvenir de moi</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

        <div class="login-footer">
            <p>Compte par défaut : admin@univ.fr / admin123</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
