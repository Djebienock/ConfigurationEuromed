# Application de Gestion des Notes

Système dynamique de configuration des colonnes de notes par l'admin et saisie par les professeurs.

## 🎯 Fonctionnalités

### Administrateur
- ✅ Gestion des périodes de notation (création, ouverture, fermeture, publication)
- ✅ Gestion des matières et filières
- ✅ Configuration dynamique des colonnes de notes par matière et période
- ✅ Définition de formules de calcul personnalisées
- ✅ Suivi de la progression de saisie
- ✅ Tableau de bord avec statistiques

### Professeur
- ✅ Consultation des matières assignées
- ✅ Saisie des notes dans un tableau type tableur
- ✅ Gestion des statuts (ABS, DIS, DEF)
- ✅ Sauvegarde automatique
- ✅ Visualisation des moyennes calculées

### Étudiant
- ✅ Consultation des notes (une fois publiées)
- ✅ Affichage des moyennes par matière
- ✅ Téléchargement des relevés de notes

## 🚀 Installation

### Prérequis
- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx) ou PHP built-in server

### Étapes d'installation

1. **Cloner ou télécharger le projet**
   ```bash
   cd profin
   ```

2. **Configurer la base de données**
   - Créer une base de données MySQL :
     ```sql
     CREATE DATABASE gestion_notes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Importer le schéma :
     ```bash
     mysql -u root -p gestion_notes < database/schema.sql
     ```

3. **Configurer la connexion à la base de données**
   - Éditer `config/database.php` :
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'gestion_notes');
     define('DB_USER', 'root');
     define('DB_PASS', 'votre_mot_de_passe');
     ```

4. **Configurer l'URL de l'application**
   - Éditer `config/config.php` :
     ```php
     define('APP_URL', 'http://localhost/profin');
     ```

5. **Démarrer le serveur**
   ```bash
   php -S localhost:8000
   ```

6. **Accéder à l'application**
   - Ouvrir un navigateur : `http://localhost:8000`
   - Se connecter avec :
     - **Email** : `admin@univ.fr`
     - **Mot de passe** : `admin123`

## 📁 Structure du projet

```
profin/
├── admin/              # Interfaces administrateur
│   ├── index.php       # Tableau de bord
│   ├── periodes.php    # Gestion des périodes
│   ├── matieres.php    # Gestion des matières
│   └── configurations.php # Configuration des colonnes et formules
├── professeur/         # Interfaces professeur
│   ├── index.php       # Liste des matières
│   └── saisie.php      # Saisie des notes
├── etudiant/           # Interfaces étudiant
│   ├── index.php       # Consultation des notes
│   └── releves.php      # Téléchargement des relevés
├── classes/            # Classes PHP
│   ├── Auth.php        # Authentification
│   ├── FormulaParser.php # Moteur de calcul sécurisé
│   └── PDFGenerator.php  # Génération de documents
├── config/             # Configuration
│   ├── config.php      # Configuration générale
│   └── database.php    # Configuration BDD
├── includes/           # Fichiers inclus
│   ├── header.php      # En-tête
│   └── footer.php      # Pied de page
├── assets/             # Ressources statiques
│   ├── css/           # Styles CSS
│   └── js/            # Scripts JavaScript
├── database/           # Schémas de base de données
│   └── schema.sql      # Schéma MySQL
└── templates/          # Templates PDF
    └── releve_template.php
```

## 🔐 Sécurité

- ✅ Authentification sécurisée avec hashage des mots de passe (bcrypt)
- ✅ Protection CSRF (à implémenter)
- ✅ Validation des entrées utilisateur
- ✅ Parser mathématique sécurisé (pas d'eval())
- ✅ Historisation complète des modifications
- ✅ Contrôle d'accès par rôle

## 📝 Utilisation

### Créer une période
1. Se connecter en tant qu'admin
2. Aller dans "Périodes"
3. Cliquer sur "Nouvelle période"
4. Remplir les informations et enregistrer

### Configurer une matière
1. Aller dans "Matières"
2. Créer ou sélectionner une matière
3. Aller dans "Configurations"
4. Sélectionner la matière et la période
5. Ajouter les colonnes de notes
6. Définir la formule de calcul

### Saisir des notes (Professeur)
1. Se connecter en tant que professeur
2. Aller dans "Mes matières"
3. Cliquer sur "Saisir les notes"
4. Remplir le tableau et enregistrer

### Consulter les notes (Étudiant)
1. Se connecter en tant qu'étudiant
2. Aller dans "Mes notes"
3. Sélectionner une période publiée
4. Consulter ou télécharger le relevé

## 🔧 Améliorations futures

- [ ] Génération PDF réelle avec TCPDF/DomPDF
- [ ] Import/Export Excel
- [ ] Notifications par email
- [ ] Graphiques et statistiques avancées
- [ ] API REST
- [ ] Tests unitaires
- [ ] Interface mobile responsive améliorée

## 📄 Licence

Ce projet est un exemple éducatif.

## 👥 Support

Pour toute question ou problème, contactez l'administrateur système.
