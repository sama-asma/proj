<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Paramètres de pagination
$resultats_par_page = 10; // Nombre de résultats par page
$page_courante = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page_courante < 1) $page_courante = 1;

// Paramètres de tri
$ordre_tri = isset($_GET['ordre']) ? $_GET['ordre'] : 'DESC';
$ordre_tri = ($ordre_tri == 'ASC') ? 'ASC' : 'DESC'; // Validation pour éviter injection SQL
$texte_ordre = ($ordre_tri == 'ASC') ? 'Du plus ancien au plus récent' : 'Du plus récent au plus ancien';
$ordre_inverse = ($ordre_tri == 'ASC') ? 'DESC' : 'ASC';

// Paramètres de recherche
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les Contrats</title>
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

        <?php 
        require('db.php');
        try {
            // Construction de la clause WHERE pour la recherche
            $where_clause = "1=1"; // Toujours vrai, permet d'ajouter facilement des conditions avec AND
            $params = array();
            $types = "";
            
            if (!empty($recherche)) {
                switch ($filtre) {
                    case 'numero':
                        $where_clause .= " AND c.numero_contrat LIKE ?";
                        $search_param = "%{$recherche}%";
                        $params[] = &$search_param;
                        $types .= "s";
                        break;
                    case 'client':
                        $where_clause .= " AND (cl.nom_client LIKE ? OR cl.prenom_client LIKE ?)";
                        $search_param1 = "%{$recherche}%";
                        $search_param2 = "%{$recherche}%";
                        $params[] = &$search_param1;
                        $params[] = &$search_param2;
                        $types .= "ss";
                        break;
                    case 'type':
                        $where_clause .= " AND c.type_assurance LIKE ?";
                        $search_param = "%{$recherche}%";
                        $params[] = &$search_param;
                        $types .= "s";
                        break;
                    default: // 'tous'
                        $where_clause .= " AND (c.numero_contrat LIKE ? OR cl.nom_client LIKE ? OR cl.prenom_client LIKE ? OR c.type_assurance LIKE ?)";
                        $search_param1 = "%{$recherche}%";
                        $search_param2 = "%{$recherche}%";
                        $search_param3 = "%{$recherche}%";
                        $search_param4 = "%{$recherche}%";
                        $params[] = &$search_param1;
                        $params[] = &$search_param2;
                        $params[] = &$search_param3;
                        $params[] = &$search_param4;
                        $types .= "ssss";
                        break;
                }
            }
            
            // Compter le nombre total de contrats pour la pagination
            $query_count = "
                SELECT COUNT(*) as total 
                FROM contrats c
                JOIN client cl ON c.id_client = cl.id_client
                WHERE {$where_clause}
            ";
            
            $stmt_count = $conn->prepare($query_count);
            if (!empty($params)) {
                call_user_func_array(array($stmt_count, 'bind_param'), array_merge(array($types), $params));
            }
            
            if (!$stmt_count->execute()) {
                throw new Exception("Erreur lors du comptage des contrats");
            }
            
            $count_result = $stmt_count->get_result();
            $total_resultats = $count_result->fetch_assoc()['total'];
            $total_pages = ceil($total_resultats / $resultats_par_page);
            
            // Vérifier que la page courante ne dépasse pas le nombre total de pages
            if ($page_courante > $total_pages && $total_pages > 0) {
                $page_courante = $total_pages;
            }
            
            // Calculer l'offset pour la requête SQL
            $offset = ($page_courante - 1) * $resultats_par_page;
            
            // Préparer et exécuter la requête pour récupérer les contrats avec pagination, tri et recherche
            $query = "
                SELECT c.id_contrat, c.numero_contrat, c.type_assurance, c.date_souscription,
                       cl.nom_client, cl.prenom_client
                FROM contrats c
                JOIN client cl ON c.id_client = cl.id_client
                WHERE {$where_clause}
                ORDER BY c.date_souscription {$ordre_tri}
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $conn->prepare($query);
            
            // Ajouter les paramètres de limite et d'offset
            $params[] = &$resultats_par_page;
            $params[] = &$offset;
            $types .= "ii";
            
            // Lier tous les paramètres
            if (!empty($params)) {
                call_user_func_array(array($stmt, 'bind_param'), array_merge(array($types), $params));
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la récupération des contrats");
            }
            
            $result = $stmt->get_result();
        } catch (Exception $e) {
            echo "<div>Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
            $result = null;
            $total_pages = 0;
            $total_resultats = 0;
        }
        ?>
        <!-- Section de tous les contrats -->
        <div class="all-contracts">
            <h2>Tous les Contrats</h2>
            
            <?php if (!empty($recherche)): ?>
            <div class="search-summary">
                <strong>Recherche:</strong> <?php echo htmlspecialchars($recherche); ?> 
                <?php if ($filtre != 'tous'): ?>
                    dans <?php echo $filtre == 'numero' ? 'numéros de contrat' : ($filtre == 'client' ? 'noms des clients' : 'types d\'assurance'); ?>
                <?php endif; ?>
                (<?php echo $total_resultats; ?> résultat<?php echo $total_resultats > 1 ? 's' : ''; ?>)
                <a href="contrats.php"><i class="fas fa-times"></i> Effacer la recherche</a>
            </div>
            <?php endif; ?>
            
            <!-- Options de tri -->
            <div class="sort-options">
                <a href="?ordre=<?php echo $ordre_inverse; ?>&page=<?php echo $page_courante; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : ''; ?>" class="sort-link">
                    <?php echo $texte_ordre; ?>
                    <i class="fas fa-sort<?php echo ($ordre_tri == 'DESC') ? '-down' : '-up'; ?>"></i>
                </a>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Numéro contrat</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Date de souscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($contrat = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contrat['numero_contrat']); ?></td>
                                    <td><?php echo htmlspecialchars($contrat['nom_client'] . ' ' . $contrat['prenom_client']); ?></td>
                                    <td><?php echo htmlspecialchars($contrat['type_assurance']); ?></td>
                                    <td><?php echo htmlspecialchars($contrat['date_souscription']); ?></td>
                                    <td>
                                        <?php
                                        $type_assurance = $contrat['type_assurance'];
                                        switch ($type_assurance) {
                                            case 'automobile':
                                                $script = 'contrat_auto.php';
                                                break;
                                            case 'habitation':
                                                $script = 'contrat_habitation.php';
                                                break;
                                            case 'santé':
                                                $script = 'contrat_sante.php';
                                                break;
                                            case 'vie':
                                                $script = 'contrat_vie.php';
                                                break;
                                            case 'scolarité':
                                                $script = 'contrat_scolarite.php';
                                                break;
                                            case 'emprunteur':
                                                $script = 'contrat_emprunt.php';
                                                break;
                                            case 'cyberattaque':
                                                $script = 'contrat_cyberattaque.php';
                                                break;
                                            case 'protection_juridique':
                                                $script = 'contrat_protection_juridique.php';
                                                break;
                                            default:
                                                $script = 'erreur.php';
                                                break;
                                        }
                                        ?>
                                        <a href="<?php echo $script; ?>?contrat=<?= $contrat['id_contrat'] ?>" 
                                           target="_blank"
                                           class="btn-view"
                                           title="Visualiser le contrat">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Aucun contrat trouvé<?php echo !empty($recherche) ? ' pour cette recherche.' : '.'; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page_courante > 1): ?>
                    <a href="?page=1&ordre=<?php echo $ordre_tri; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : ''; ?>" title="Première page"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?php echo ($page_courante - 1); ?>&ordre=<?php echo $ordre_tri; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : ''; ?>" title="Page précédente"><i class="fas fa-angle-left"></i></a>
                <?php endif; ?>
                
                <?php
                // Afficher un nombre limité de liens de page
                $plage = 2; // Nombre de pages à afficher de chaque côté de la page courante
                $debut_plage = max(1, $page_courante - $plage);
                $fin_plage = min($total_pages, $page_courante + $plage);
                
                // Afficher "..." si nécessaire au début
                if ($debut_plage > 1) {
                    echo '<span>...</span>';
                }
                
                // Afficher les liens de pagination
                for ($i = $debut_plage; $i <= $fin_plage; $i++) {
                    if ($i == $page_courante) {
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="?page=' . $i . '&ordre=' . $ordre_tri . (!empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : '') . '">' . $i . '</a>';
                    }
                }
                
                // Afficher "..." si nécessaire à la fin
                if ($fin_plage < $total_pages) {
                    echo '<span>...</span>';
                }
                ?>
                
                <?php if ($page_courante < $total_pages): ?>
                    <a href="?page=<?php echo ($page_courante + 1); ?>&ordre=<?php echo $ordre_tri; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : ''; ?>" title="Page suivante"><i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $total_pages; ?>&ordre=<?php echo $ordre_tri; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre) : ''; ?>" title="Dernière page"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
                
                <div style="margin-top: 10px;">
                    Page <?php echo $page_courante; ?> sur <?php echo $total_pages; ?> 
                    (<?php echo $total_resultats; ?> contrat<?php echo $total_resultats > 1 ? 's' : ''; ?> au total)
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>