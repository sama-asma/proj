<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- navigation -->
     <?php include 'commun/nav.php'; ?>
    <!-- Contenu principal -->
    <div class="main-content">
        <!-- En-tête -->
        <?php include 'commun/header.php'; ?>

        <!-- Section des statistiques
        <div class="stats-section">
            <div class="stat-card">
                <h2>150</h2>
                <p>Contrats signés</p>
            </div>
            <div class="stat-card">
                <h2>€25,000</h2>
                <p>Chiffre d'affaires</p>
            </div>
            <div class="stat-card">
                <h2>95%</h2>
                <p>Taux de satisfaction</p>
            </div>
        </div> -->
        <?php 
          require('db.php');
          try{
                $stmt = $conn->prepare("
                        SELECT c.id_contrat, c.numero_contrat, c.type_assurance, c.date_souscription,
                            cl.nom_client, cl.prenom_client
                        FROM contrats c
                        JOIN client cl ON c.id_client = cl.id_client
                        ORDER BY c.date_souscription DESC 
                        LIMIT 10
                    ");
                    if (!$stmt->execute()) {
                        throw new Exception("Erreur lors de la récupération des contrats");
                    }
                $result = $stmt->get_result();
          }
          catch(Exception $e){
            echo "<div>Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
            $result = [];
          }
          ?>
            
        <!-- Section des contrats récents -->
        <div class="recent-contracts">
            <h2>Contrats Récents</h2>
            <table>
                <thead>
                    <tr>
                        <th>Numero contrat</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Date de souscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $contrat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($contrat['numero_contrat']); ?></td>
                            <td><?php echo htmlspecialchars($contrat['nom_client'] . ' '. $contrat['prenom_client']); ?></td>
                            <td><?php echo htmlspecialchars($contrat['type_assurance']); ?></td>
                            <td><?php echo htmlspecialchars($contrat['date_souscription']); ?></td>
                            <td>
                                <!-- Bouton avec icône + ouverture en nouvel onglet -->
                                <a href="contrat_auto.php?contrat=<?= $contrat['id_contrat'] ?>" 
                                target="_blank"
                                class="btn-view"
                                title="Visualiser le contrat">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>