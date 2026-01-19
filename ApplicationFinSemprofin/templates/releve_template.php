<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de Notes - <?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .student-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #2563eb;
            color: white;
        }
        .moyenne {
            font-weight: bold;
            font-size: 1.1em;
        }
        .valide {
            color: #10b981;
        }
        .non-valide {
            color: #ef4444;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELEVÉ DE NOTES</h1>
        <p><strong><?php echo htmlspecialchars($periode['nom']); ?></strong></p>
        <p>Année universitaire : <?php echo htmlspecialchars($periode['annee_universitaire']); ?></p>
    </div>

    <div class="student-info">
        <p><strong>Étudiant :</strong> <?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></p>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($etudiant['email']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Code</th>
                <th>Notes</th>
                <th>Moyenne</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matieres as $matiere): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($matiere['nom']); ?></strong></td>
                    <td><?php echo htmlspecialchars($matiere['code']); ?></td>
                    <td>
                        <?php if (!empty($matiere['colonnes'])): ?>
                            <?php foreach ($matiere['colonnes'] as $col): ?>
                                <?php 
                                $note = $matiere['notes'][$col['code_colonne']] ?? null;
                                ?>
                                <div>
                                    <?php echo htmlspecialchars($col['nom_colonne']); ?>: 
                                    <?php if ($note && $note['statut'] === 'saisie' && $note['valeur'] !== null): ?>
                                        <?php echo number_format($note['valeur'], 2); ?>/<?php echo $col['note_max']; ?>
                                    <?php elseif ($note): ?>
                                        <?php echo strtoupper($note['statut']); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Aucune note
                        <?php endif; ?>
                    </td>
                    <td class="moyenne">
                        <?php if (isset($matiere['moyenne'])): ?>
                            <span class="<?php echo $matiere['moyenne'] >= $matiere['seuil_validation'] ? 'valide' : 'non-valide'; ?>">
                                <?php echo number_format($matiere['moyenne'], 2); ?>/20
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($matiere['moyenne'])): ?>
                            <?php if ($matiere['moyenne'] >= $matiere['seuil_validation']): ?>
                                <span class="valide">Validé</span>
                            <?php else: ?>
                                <span class="non-valide">Non validé</span>
                            <?php endif; ?>
                        <?php else: ?>
                            En attente
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré le <?php echo date('d/m/Y à H:i'); ?></p>
        <p>Ce document est officiel et certifié</p>
    </div>
</body>
</html>
