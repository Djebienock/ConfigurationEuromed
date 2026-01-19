<?php
// Test des chemins
$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$current_dir = dirname($current_script);

echo "<h2>Informations de débogage</h2>";
echo "<p><strong>SCRIPT_NAME:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Current dir:</strong> " . $current_dir . "</p>";
echo "<p><strong>Dans admin?:</strong> " . (strpos($current_dir, '/admin') !== false ? 'Oui' : 'Non') . "</p>";

// Fonction pour obtenir le chemin relatif vers un fichier
function getRelativePath($target_file) {
    $current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $current_dir = dirname($current_script);
    
    // Si on est dans admin/ et qu'on veut aller vers admin/
    if (strpos($current_dir, '/admin') !== false && strpos($target_file, 'admin/') !== false) {
        return basename($target_file); // Retourne juste "periodes.php"
    }
    
    // Si on est dans professeur/ et qu'on veut aller vers professeur/
    if (strpos($current_dir, '/professeur') !== false && strpos($target_file, 'professeur/') !== false) {
        return basename($target_file);
    }
    
    // Si on est dans etudiant/ et qu'on veut aller vers etudiant/
    if (strpos($current_dir, '/etudiant') !== false && strpos($target_file, 'etudiant/') !== false) {
        return basename($target_file);
    }
    
    // Si on est dans un sous-dossier mais qu'on veut aller ailleurs
    if (strpos($current_dir, '/admin') !== false || strpos($current_dir, '/professeur') !== false || strpos($current_dir, '/etudiant') !== false) {
        return '../' . $target_file;
    }
    
    // On est à la racine
    return $target_file;
}

echo "<h3>Test des chemins:</h3>";
echo "<ul>";
echo "<li>admin/index.php → " . getRelativePath('admin/index.php') . "</li>";
echo "<li>admin/periodes.php → " . getRelativePath('admin/periodes.php') . "</li>";
echo "<li>admin/filieres.php → " . getRelativePath('admin/filieres.php') . "</li>";
echo "<li>admin/matieres.php → " . getRelativePath('admin/matieres.php') . "</li>";
echo "<li>admin/configurations.php → " . getRelativePath('admin/configurations.php') . "</li>";
echo "</ul>";

echo "<h3>Liens de test:</h3>";
echo "<ul>";
echo "<li><a href='" . getRelativePath('admin/index.php') . "'>Tableau de bord</a></li>";
echo "<li><a href='" . getRelativePath('admin/periodes.php') . "'>Périodes</a></li>";
echo "<li><a href='" . getRelativePath('admin/filieres.php') . "'>Filières</a></li>";
echo "<li><a href='" . getRelativePath('admin/matieres.php') . "'>Matières</a></li>";
echo "<li><a href='" . getRelativePath('admin/configurations.php') . "'>Configurations</a></li>";
echo "</ul>";
?>
