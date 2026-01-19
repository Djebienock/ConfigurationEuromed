-- Script SQL pour créer/mettre à jour le compte admin
-- Mot de passe: admin123

-- Supprimer l'ancien admin s'il existe
DELETE FROM utilisateurs WHERE email = 'admin@univ.fr';

-- Créer le compte admin avec un nouveau hash
-- Note: Ce hash doit être généré avec password_hash('admin123', PASSWORD_DEFAULT)
-- Pour générer un nouveau hash, utilisez create_admin.php ou cette commande PHP:
-- php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"

-- Hash généré pour 'admin123'
INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role, actif) VALUES
('admin@univ.fr', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Admin', 'Système', 'admin', 1);

-- Alternative: Si vous voulez générer votre propre hash, exécutez:
-- UPDATE utilisateurs SET mot_de_passe = 'VOTRE_HASH_ICI' WHERE email = 'admin@univ.fr';
