<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

$auth = new Auth();
$auth->logout();

// Déterminer le chemin de base
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if ($base_path === '/') {
    $base_path = '';
}

header('Location: ' . $base_path . '/login.php');
exit;
