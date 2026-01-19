
CREATE TABLE utilisateurs (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    email               VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe        VARCHAR(255) NOT NULL,
    nom                 VARCHAR(100) NOT NULL,
    prenom              VARCHAR(100) NOT NULL,
    role                ENUM('admin', 'professeur', 'etudiant') NOT NULL,
    actif               BOOLEAN DEFAULT TRUE,
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion  DATETIME
);


CREATE TABLE periodes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    nom                 VARCHAR(100) NOT NULL,
    code                VARCHAR(20) UNIQUE NOT NULL,
    annee_universitaire VARCHAR(9) NOT NULL,
    type                ENUM('semestre', 'trimestre', 'session', 'rattrapage') NOT NULL,
    date_debut_saisie   DATETIME NOT NULL,
    date_fin_saisie     DATETIME NOT NULL,
    statut              ENUM('a_venir', 'ouverte', 'fermee', 'publiee') DEFAULT 'a_venir',
    date_publication    DATETIME,
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE filieres (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    code                VARCHAR(20) UNIQUE NOT NULL,
    nom                 VARCHAR(150) NOT NULL,
    niveau              VARCHAR(20),
    responsable_id      INT,
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id)
);


CREATE TABLE matieres (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    code                VARCHAR(20) UNIQUE NOT NULL,
    nom                 VARCHAR(150) NOT NULL,
    filiere_id          INT NOT NULL,
    coefficient         DECIMAL(3,1) DEFAULT 1,
    credits             INT,
    seuil_validation    DECIMAL(4,2) DEFAULT 10,
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id)
);


CREATE TABLE affectations_profs (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    professeur_id       INT NOT NULL,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    groupe              VARCHAR(50),
    FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    UNIQUE KEY unique_affectation (professeur_id, matiere_id, periode_id, groupe)
);


CREATE TABLE configuration_colonnes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    nom_colonne         VARCHAR(50) NOT NULL,
    code_colonne        VARCHAR(20) NOT NULL,
    type                ENUM('note', 'bonus', 'malus', 'info') DEFAULT 'note',
    note_max            DECIMAL(5,2) DEFAULT 20,
    coefficient         DECIMAL(3,1) DEFAULT 1,
    obligatoire         BOOLEAN DEFAULT TRUE,
    ordre               INT NOT NULL,
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    UNIQUE KEY unique_colonne (matiere_id, periode_id, code_colonne)
);


CREATE TABLE formules (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    formule             TEXT NOT NULL,
    description         VARCHAR(255),
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    UNIQUE KEY unique_formule (matiere_id, periode_id)
);


CREATE TABLE inscriptions_matieres (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id         INT NOT NULL,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    groupe              VARCHAR(50),
    dispense            BOOLEAN DEFAULT FALSE,
    date_inscription    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    UNIQUE KEY unique_inscription (etudiant_id, matiere_id, periode_id)
);


CREATE TABLE notes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id         INT NOT NULL,
    colonne_id          INT NOT NULL,
    valeur              DECIMAL(5,2),
    statut              ENUM('saisie', 'absent', 'dispense', 'defaillant') DEFAULT 'saisie',
    saisi_par           INT NOT NULL,
    date_saisie         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (colonne_id) REFERENCES configuration_colonnes(id),
    FOREIGN KEY (saisi_par) REFERENCES utilisateurs(id),
    UNIQUE KEY unique_note (etudiant_id, colonne_id)
);


CREATE TABLE moyennes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id         INT NOT NULL,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    moyenne             DECIMAL(5,2),
    rang                INT,
    decision            ENUM('valide', 'non_valide', 'rattrapage', 'en_attente') DEFAULT 'en_attente',
    credits_obtenus     INT,
    date_calcul         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    UNIQUE KEY unique_moyenne (etudiant_id, matiere_id, periode_id)
);


CREATE TABLE progression_saisie (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    matiere_id          INT NOT NULL,
    periode_id          INT NOT NULL,
    professeur_id       INT NOT NULL,
    total_etudiants     INT NOT NULL,
    total_notes_attendues INT NOT NULL,
    notes_saisies       INT DEFAULT 0,
    pourcentage         DECIMAL(5,2) DEFAULT 0,
    valide_par_prof     BOOLEAN DEFAULT FALSE,
    date_validation     DATETIME,
    date_mise_a_jour    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (periode_id) REFERENCES periodes(id),
    FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id),
    UNIQUE KEY unique_progression (matiere_id, periode_id)
);


CREATE TABLE historique_notes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    note_id             INT NOT NULL,
    ancienne_valeur     DECIMAL(5,2),
    nouvelle_valeur     DECIMAL(5,2),
    ancien_statut       VARCHAR(20),
    nouveau_statut      VARCHAR(20),
    modifie_par         INT NOT NULL,
    motif               TEXT,
    adresse_ip          VARCHAR(45),
    date_modification   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (note_id) REFERENCES notes(id),
    FOREIGN KEY (modifie_par) REFERENCES utilisateurs(id)
);


CREATE TABLE templates_formules (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    nom                 VARCHAR(100) NOT NULL,
    description         TEXT,
    colonnes_requises   JSON NOT NULL,
    formule             TEXT NOT NULL,
    categorie           VARCHAR(50),
    date_creation       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role) VALUES
('admin@univ.fr', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Admin', 'Système', 'admin');


INSERT INTO templates_formules (nom, description, colonnes_requises, formule, categorie) VALUES
('Moyenne simple', 'Moyenne arithmétique de toutes les notes', 
 '["Note1", "Note2"]', 'MOYENNE(Note1, Note2)', 'Standard'),
('DS + Examen', 'DS coefficient 1, Examen coefficient 2', 
 '["DS", "Examen"]', '(DS + Examen * 2) / 3', 'Standard'),
('Meilleure des deux', 'Garde la meilleure note entre deux évaluations', 
 '["Note1", "Note2"]', 'MAX(Note1, Note2)', 'Spécial'),
('TP + Projet + Examen', 'Moyenne TP 30%, Projet 30%, Examen 40%', 
 '["TP", "Projet", "Examen"]', 'TP * 0.3 + Projet * 0.3 + Examen * 0.4', 'Standard');


CREATE INDEX idx_notes_etudiant ON notes(etudiant_id);
CREATE INDEX idx_notes_colonne ON notes(colonne_id);
CREATE INDEX idx_config_matiere_periode ON configuration_colonnes(matiere_id, periode_id);
CREATE INDEX idx_moyennes_periode ON moyennes(periode_id);
CREATE INDEX idx_utilisateurs_email ON utilisateurs(email);
CREATE INDEX idx_utilisateurs_role ON utilisateurs(role);
