<?php
/**
 * Moteur de calcul sécurisé des formules
 * NE JAMAIS utiliser eval() - Parser mathématique sécurisé
 */
class FormulaParser {
    private $variables = [];
    private $operateurs = ['+', '-', '*', '/', '(', ')'];
    private $fonctions = ['MAX', 'MIN', 'MOYENNE', 'SI', 'ABS'];

    /**
     * Évalue une formule de manière sécurisée
     * @param string $formule Ex: "(DS1 + DS2 + Examen*2) / 4"
     * @param array $valeurs Ex: ['DS1' => 14, 'DS2' => 12, 'Examen' => 16]
     * @return float|null
     */
    public function evaluer($formule, $valeurs) {
        // 1. Validation de la formule
        if (!$this->validerFormule($formule)) {
            throw new Exception("Formule invalide : " . $formule);
        }

        // 2. Remplacer les variables par leurs valeurs
        $expression = $this->substituerVariables($formule, $valeurs);

        // 3. Évaluer l'expression mathématique de manière sécurisée
        return $this->evaluerExpression($expression);
    }

    /**
     * Valide qu'une formule ne contient que des éléments autorisés
     */
    private function validerFormule($formule) {
        // Whitelist stricte des caractères autorisés
        $pattern = '/^[A-Za-z0-9_+\-*\/().,%\s]+$/';
        
        if (!preg_match($pattern, $formule)) {
            return false;
        }

        // Vérifier les parenthèses équilibrées
        $compteur = 0;
        foreach (str_split($formule) as $char) {
            if ($char === '(') $compteur++;
            if ($char === ')') $compteur--;
            if ($compteur < 0) return false;
        }

        return $compteur === 0;
    }

    /**
     * Remplace les noms de variables par leurs valeurs numériques
     */
    private function substituerVariables($formule, $valeurs) {
        // Trier par longueur décroissante pour éviter les remplacements partiels
        uksort($valeurs, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($valeurs as $nom => $valeur) {
            // Gérer les absences
            if ($valeur === 'ABS' || $valeur === null || $valeur === '') {
                $valeur = '0'; // Traiter comme 0 pour le calcul
            }
            
            // Remplacement avec frontières de mot
            $formule = preg_replace('/\b' . preg_quote($nom, '/') . '\b/', (string)$valeur, $formule);
        }
        
        return $formule;
    }

    /**
     * Évalue une expression mathématique de manière sécurisée
     * Utilise une approche basée sur la notation polonaise inverse
     */
    private function evaluerExpression($expression) {
        // Nettoyer l'expression
        $expression = preg_replace('/\s+/', '', $expression);
        
        // Vérifier que l'expression ne contient que des nombres et opérateurs
        if (!preg_match('/^[0-9+\-*\/().,]+$/', $expression)) {
            throw new Exception("Expression invalide après substitution");
        }

        // Utiliser une fonction d'évaluation sécurisée
        // On utilise une approche avec parsing manuel
        try {
            // Convertir en notation polonaise inverse
            $rpn = $this->versRPN($expression);
            return $this->evaluerRPN($rpn);
        } catch (Exception $e) {
            throw new Exception("Erreur d'évaluation : " . $e->getMessage());
        }
    }

    /**
     * Convertit une expression en notation polonaise inverse (RPN)
     */
    private function versRPN($expression) {
        $output = [];
        $operateurs = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        $i = 0;
        $len = strlen($expression);

        while ($i < $len) {
            $char = $expression[$i];

            if (ctype_digit($char) || $char === '.') {
                // Lire le nombre complet
                $num = '';
                while ($i < $len && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $num .= $expression[$i];
                    $i++;
                }
                $output[] = floatval($num);
                continue;
            }

            if ($char === '(') {
                $operateurs[] = $char;
            } elseif ($char === ')') {
                while (!empty($operateurs) && end($operateurs) !== '(') {
                    $output[] = array_pop($operateurs);
                }
                array_pop($operateurs); // Retirer '('
            } elseif (isset($precedence[$char])) {
                while (!empty($operateurs) && 
                       isset($precedence[end($operateurs)]) &&
                       $precedence[end($operateurs)] >= $precedence[$char]) {
                    $output[] = array_pop($operateurs);
                }
                $operateurs[] = $char;
            }

            $i++;
        }

        while (!empty($operateurs)) {
            $output[] = array_pop($operateurs);
        }

        return $output;
    }

    /**
     * Évalue une expression en notation polonaise inverse
     */
    private function evaluerRPN($rpn) {
        $stack = [];

        foreach ($rpn as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } else {
                $b = array_pop($stack);
                $a = array_pop($stack);

                switch ($token) {
                    case '+':
                        $stack[] = $a + $b;
                        break;
                    case '-':
                        $stack[] = $a - $b;
                        break;
                    case '*':
                        $stack[] = $a * $b;
                        break;
                    case '/':
                        if ($b == 0) {
                            throw new Exception("Division par zéro");
                        }
                        $stack[] = $a / $b;
                        break;
                }
            }
        }

        if (count($stack) !== 1) {
            throw new Exception("Expression invalide");
        }

        return round($stack[0], 2);
    }

    /**
     * Teste une formule avec des valeurs fictives
     */
    public function tester($formule, $variables = []) {
        try {
            $resultat = $this->evaluer($formule, $variables);
            return [
                'success' => true,
                'resultat' => $resultat,
                'message' => 'Formule valide'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'resultat' => null,
                'message' => $e->getMessage()
            ];
        }
    }
}
