<?php
// Test pour vérifier le chargement du CSS
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$path_parts = explode('/', trim($script_path, '/'));
$depth = count($path_parts) - 1;
$relative_path = '';
if ($depth > 0) {
    $relative_path = str_repeat('../', $depth);
}

// Vérifier le chemin réel - utiliser __DIR__ qui est le dossier du fichier actuel
$css_path = __DIR__ . DIRECTORY_SEPARATOR . $relative_path . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css';
$css_path_normalized = realpath($css_path);
if (!$css_path_normalized) {
    // Essayer avec des chemins alternatifs
    $alt_paths = [
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css',
    ];
    foreach ($alt_paths as $alt) {
        if (file_exists($alt)) {
            $css_path_normalized = realpath($alt);
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test CSS</title>
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <h1>Test du chargement CSS</h1>
    <p><strong>SCRIPT_NAME:</strong> <code><?php echo $_SERVER['SCRIPT_NAME']; ?></code></p>
    <p><strong>__DIR__:</strong> <code><?php echo __DIR__; ?></code></p>
    <p><strong>Profondeur calculée:</strong> <?php echo $depth; ?></p>
    <p><strong>Chemin relatif calculé:</strong> <code><?php echo $relative_path; ?>assets/css/style.css</code></p>
    <p><strong>Chemin absolu normalisé:</strong> <code><?php echo $css_path_normalized; ?></code></p>
    <p><strong>Chemin normalisé (realpath):</strong> <code><?php echo $css_path_normalized ?: 'Non trouvé'; ?></code></p>
    <p><strong>Fichier existe:</strong> <?php echo $css_path_normalized && file_exists($css_path_normalized) ? '<span style="color:green">OUI ✓</span>' : '<span style="color:red">NON ✗</span>'; ?></p>
    
    <?php if (!$css_path_normalized): ?>
        <h2>Essais de chemins alternatifs:</h2>
        <ul>
            <?php
            $alternatives = [
                '../assets/css/style.css',
                '../../assets/css/style.css',
                'assets/css/style.css',
            ];
            foreach ($alternatives as $alt) {
                $alt_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $alt);
                $exists = file_exists($alt_path);
                $real = $exists ? realpath($alt_path) : 'N/A';
                echo "<li><code>$alt</code> → " . ($exists ? '<span style="color:green">EXISTE</span> (' . $real . ')' : '<span style="color:red">N\'existe pas</span>') . "</li>";
            }
            // Essayer depuis la racine du projet
            $root_css = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css';
            $root_exists = file_exists($root_css);
            echo "<li><code>depuis racine: " . dirname(__DIR__) . "</code> → " . ($root_exists ? '<span style="color:green">EXISTE</span> (' . realpath($root_css) . ')' : '<span style="color:red">N\'existe pas</span>') . "</li>";
            ?>
        </ul>
    <?php endif; ?>
    
    <div class="stat-card" style="max-width: 300px; margin: 2rem;">
        <div class="stat-icon" style="background: #3b82f6;">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-content">
            <h3>5</h3>
            <p>Périodes</p>
        </div>
    </div>
    
    <p>Si vous voyez une carte bleue avec une icône et "5 Périodes", le CSS fonctionne !</p>
</body>
</html>
