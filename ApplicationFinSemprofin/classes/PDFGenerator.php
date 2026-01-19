<?php
/**
 * Générateur de documents PDF
 * Nécessite TCPDF ou FPDF
 */

// Pour simplifier, on utilise une classe basique
// En production, utiliser TCPDF ou FPDF
class PDFGenerator {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Génère un relevé de notes pour un étudiant
     */
    public function generateReleve($etudiant_id, $periode_id) {
        $etudiant = $this->db->fetchOne("SELECT * FROM utilisateurs WHERE id = ?", [$etudiant_id]);
        $periode = $this->db->fetchOne("SELECT * FROM periodes WHERE id = ?", [$periode_id]);

        if (!$etudiant || !$periode) {
            throw new Exception("Données invalides");
        }

        // Récupérer les matières et notes
        $matieres = $this->db->fetchAll("
            SELECT m.*, im.groupe
            FROM inscriptions_matieres im
            JOIN matieres m ON m.id = im.matiere_id
            WHERE im.etudiant_id = ? AND im.periode_id = ?
            ORDER BY m.nom
        ", [$etudiant_id, $periode_id]);

        // Pour chaque matière, récupérer les notes
        $parser = new FormulaParser();
        foreach ($matieres as &$matiere) {
            $colonnes = $this->db->fetchAll("
                SELECT * FROM configuration_colonnes 
                WHERE matiere_id = ? AND periode_id = ?
                ORDER BY ordre
            ", [$matiere['id'], $periode_id]);

            $notes = [];
            if (!empty($colonnes)) {
                $colonne_ids = array_column($colonnes, 'id');
                $placeholders = str_repeat('?,', count($colonne_ids) - 1) . '?';
                $notes_data = $this->db->fetchAll("
                    SELECT n.*, cc.code_colonne, cc.nom_colonne, cc.note_max
                    FROM notes n
                    JOIN configuration_colonnes cc ON cc.id = n.colonne_id
                    WHERE n.etudiant_id = ? AND n.colonne_id IN ($placeholders)
                ", array_merge([$etudiant_id], $colonne_ids));

                foreach ($notes_data as $note) {
                    $notes[$note['code_colonne']] = $note;
                }
            }

            $matiere['colonnes'] = $colonnes;
            $matiere['notes'] = $notes;

            // Calculer la moyenne
            $formule = $this->db->fetchOne("
                SELECT * FROM formules 
                WHERE matiere_id = ? AND periode_id = ?
            ", [$matiere['id'], $periode_id]);

            if ($formule) {
                $valeurs = [];
                foreach ($notes as $code => $note) {
                    if ($note['statut'] === 'saisie' && $note['valeur'] !== null) {
                        $valeurs[$code] = $note['valeur'];
                    }
                }
                if (!empty($valeurs)) {
                    try {
                        $matiere['moyenne'] = $parser->evaluer($formule['formule'], $valeurs);
                    } catch (Exception $e) {
                        $matiere['moyenne'] = null;
                    }
                }
            }
        }
        unset($matiere);

        // Générer le HTML du relevé
        ob_start();
        include __DIR__ . '/../templates/releve_template.php';
        $html = ob_get_clean();

        // En production, convertir le HTML en PDF avec TCPDF ou DomPDF
        // Pour l'instant, on retourne le HTML
        return $html;
    }

    /**
     * Génère un PV de délibération pour une période
     */
    public function generatePV($periode_id, $filiere_id = null) {
        $periode = $this->db->fetchOne("SELECT * FROM periodes WHERE id = ?", [$periode_id]);
        
        if (!$periode) {
            throw new Exception("Période invalide");
        }

        // Récupérer les matières
        $query = "
            SELECT DISTINCT m.*
            FROM matieres m
            JOIN affectations_profs ap ON ap.matiere_id = m.id
            WHERE ap.periode_id = ?
        ";
        $params = [$periode_id];
        
        if ($filiere_id) {
            $query .= " AND m.filiere_id = ?";
            $params[] = $filiere_id;
        }
        
        $matieres = $this->db->fetchAll($query . " ORDER BY m.nom", $params);

        // Pour chaque matière, récupérer tous les étudiants et leurs notes
        $parser = new FormulaParser();
        foreach ($matieres as &$matiere) {
            $etudiants = $this->db->fetchAll("
                SELECT u.*, im.groupe
                FROM inscriptions_matieres im
                JOIN utilisateurs u ON u.id = im.etudiant_id
                WHERE im.matiere_id = ? AND im.periode_id = ?
                ORDER BY u.nom, u.prenom
            ", [$matiere['id'], $periode_id]);

            foreach ($etudiants as &$etudiant) {
                $colonnes = $this->db->fetchAll("
                    SELECT * FROM configuration_colonnes 
                    WHERE matiere_id = ? AND periode_id = ?
                    ORDER BY ordre
                ", [$matiere['id'], $periode_id]);

                $notes = [];
                if (!empty($colonnes)) {
                    $colonne_ids = array_column($colonnes, 'id');
                    $placeholders = str_repeat('?,', count($colonne_ids) - 1) . '?';
                    $notes_data = $this->db->fetchAll("
                        SELECT n.*, cc.code_colonne, cc.nom_colonne
                        FROM notes n
                        JOIN configuration_colonnes cc ON cc.id = n.colonne_id
                        WHERE n.etudiant_id = ? AND n.colonne_id IN ($placeholders)
                    ", array_merge([$etudiant['id']], $colonne_ids));

                    foreach ($notes_data as $note) {
                        $notes[$note['code_colonne']] = $note;
                    }
                }

                $etudiant['notes'] = $notes;

                // Calculer la moyenne
                $formule = $this->db->fetchOne("
                    SELECT * FROM formules 
                    WHERE matiere_id = ? AND periode_id = ?
                ", [$matiere['id'], $periode_id]);

                if ($formule) {
                    $valeurs = [];
                    foreach ($notes as $code => $note) {
                        if ($note['statut'] === 'saisie' && $note['valeur'] !== null) {
                            $valeurs[$code] = $note['valeur'];
                        }
                    }
                    if (!empty($valeurs)) {
                        try {
                            $etudiant['moyenne'] = $parser->evaluer($formule['formule'], $valeurs);
                        } catch (Exception $e) {
                            $etudiant['moyenne'] = null;
                        }
                    }
                }
            }
            unset($etudiant);
            $matiere['etudiants'] = $etudiants;
        }
        unset($matiere);

        // Générer le HTML du PV
        ob_start();
        include __DIR__ . '/../templates/pv_template.php';
        $html = ob_get_clean();

        return $html;
    }
}
