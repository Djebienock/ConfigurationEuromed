<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

$auth = new Auth();

// Déterminer le chemin de base
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if ($base_path === '/') {
    $base_path = '';
}

if (!$auth->isLoggedIn()) {
    header('Location: ' . $base_path . '/login.php');
    exit;
}

// Rediriger selon le rôle
$role = $_SESSION['user_role'];
if ($role === 'admin') {
    header('Location: ' . $base_path . '/admin/index.php');
} elseif ($role === 'professeur') {
    header('Location: ' . $base_path . '/professeur/index.php');
} elseif ($role === 'etudiant') {
    header('Location: ' . $base_path . '/etudiant/index.php');
}

exit;
