            </main>
            <footer class="footer">
                <div class="footer-content">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Gestion des Notes'; ?>. Tous droits réservés.</p>
                </div>
            </footer>
        </div>
    </div>
    <?php else: ?>
    </main>
    <?php endif; ?>
    <?php
    // Chemin simple pour les scripts JS (même logique que header)
    $current_dir_js = dirname($_SERVER['SCRIPT_NAME']);
    $is_in_subdir_js = strpos($current_dir_js, '/admin') !== false || 
                       strpos($current_dir_js, '/professeur') !== false || 
                       strpos($current_dir_js, '/etudiant') !== false;
    $js_path = $is_in_subdir_js ? '../' : '';
    ?>
    <script src="<?php echo $js_path; ?>assets/js/main.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
