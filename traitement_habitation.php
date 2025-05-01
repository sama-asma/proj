<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom_client = isset($_POST['nom_client']) ? ucwords(strtolower($_POST['nom_client'])) : null;
    $prenom_client = isset($_POST['prenom_client']) ? ucwords(strtolower($_POST['prenom_client'])) : null;
    $telephone = $_POST['telephone'] ?? null;
    $email = $_POST['email'] ?? null;
    $date_naissance = $_POST['date_naissance'] ?? null;
    $reduction = $_POST['reduction'] ?? 0;
    $surcharge = $_POST['surcharge'] ?? 0;
    $id_garantie = $_POST['id_garantie'] ?? null;
    $date_souscription = $_POST['date_souscription'] ?? null;
    $date_expiration = $_POST['date_expiration'] ?? null;
    $prime = isset($_POST['prime_calculee']) ? floatval($_POST['prime_calculee']) : null;
    $franchise = isset($_POST['franchise']) ? floatval($_POST['franchise']) : null;
    
    // Données spécifiques à l'habitation
    $statut_logement = $_POST['statut_logement'] ?? null;
    $type_logement = $_POST['type_logement'] ?? null;
    $wilaya = $_POST['wilaya'] ?? null;
    $commune = $_POST['commune'] ?? null;
    $adresse_detail = $_POST['adresse_detail'] ?? null;
    $superficie = (float)$_POST['superficie'];
    $annee_construction = (int)$_POST['annee_construction'];
    $capital_mobilier = (float)$_POST['capital_mobilier'];
    $nb_occupants = (int)$_POST['nb_occupants'];
    $antecedents = (int)$_POST['antecedents'];
    $localisation = $_POST['localisation'] ?? null;
    $materiaux = $_POST['materiaux'] ?? null;
    $etat_toiture = $_POST['etat_toiture'] ?? null;
    $occupation = $_POST['occupation'] ?? null;
    $securite = isset($_POST['securite']) ? implode(',', $_POST['securite']) : '';

    // Validation des données
    $fields = [
        'nom_client' => "Le nom du client est requis.",
        'prenom_client' => "Le prénom du client est requis.",
        'telephone' => "Un numéro de téléphone valide est requis.",
        'email' => "Une adresse email valide est requise.",
        'date_naissance' => "La date de naissance est requise.",
        'statut_logement' => "Le statut du logement est requis.",
        'type_logement' => "Le type de logement est requis.",
        'wilaya' => "La wilaya est requise.",
        'commune' => "La commune est requise.",
        'adresse_detail' => "L'adresse détaillée est requise.",
        'superficie' => "La superficie est requise.",
        'annee_construction' => "L'année de construction est requise.",
        'localisation' => "La localisation est requise.",
        'materiaux' => "Les matériaux de construction sont requis.",
        'etat_toiture' => "L'état de la toiture est requis.",
        'occupation' => "L'occupation est requise.",
        'nb_occupants' => "Le nombre d'occupants est requis.",
        'capital_mobilier' => "Le capital mobilier est requis.",
        'antecedents' => "Le nombre de sinistres est requis.",
        'id_garantie' => "La garantie est requise.",
        'date_souscription' => "La date de souscription est requise.",
        'date_expiration' => "La date d'expiration est requise.",
        'prime_calculee' => "La prime est requise.",
        'franchise' => "La franchise est requise."
    ];

    $errors = [];
    foreach ($fields as $field => $error_message) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            $errors[] = $error_message;
        } elseif (in_array($field, ['telephone']) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['email']) && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['superficie', 'annee_construction', 'nb_occupants', 'capital_mobilier', 'antecedents', 'id_garantie', 'prime_calculee', 'franchise']) && !is_numeric($_POST[$field])) {
            $errors[] = $error_message;
        } elseif ($field === 'date_naissance' && !DateTime::createFromFormat('Y-m-d', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['date_souscription', 'date_expiration']) && !strtotime($_POST[$field])) {
            $errors[] = $error_message;
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_habitation.php');
        exit();
    }

    require 'db.php';
    $stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ?");
    $stmt->bind_param("i", $id_garantie);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $primeBase = floatval($row['prime_base']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Garantie non trouvée']);
        exit;
    }
    $stmt->close();

    try {
        // Vérifier si le client existe déjà
        $stmt = $conn->prepare("SELECT id_client FROM client
        WHERE email = ? AND telephone = ?
        AND nom_client = ? AND prenom_client = ? AND date_naissance = ?");
        $stmt->bind_param("sssss", $email, $telephone, $nom_client, $prenom_client, $date_naissance);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Client existant
            $client = $result->fetch_assoc();
            $client_id = $client['id_client'];
            $stmt->close();
        } else {
            // Nouveau client
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la création du client: " . $conn->error);
            }

            $client_id = $stmt->insert_id;
            $stmt->close();
        }

        // Création du contrat
        $numero_contrat = uniqid("CTR-HAB-");
        $stmt = $conn->prepare("INSERT INTO contrats (
            numero_contrat, id_client, date_souscription, date_expiration, 
            type_assurance, montant_prime, reduction, surcharge
        ) VALUES (?, ?, ?, ?, 'habitation', ?, ?, ?)");
        $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, 
                         $date_expiration, $prime, $reduction, $surcharge);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du contrat habitation");
        } 
        $contrat_id = $stmt->insert_id;
        $stmt->close();

        // Insertion des détails spécifiques à l'habitation
        $stmt = $conn->prepare("INSERT INTO assurance_habitation  (
            id_contrat, id_garantie, statut, type_logement, superficie, localisation, 
            annee_construction, etat_toiture, materiaux_construction, occupation, capital_mobilier, 
            wilaya_nom, commune_nom, adresse_detail, nb_occupants, mesures_securite, antecedents
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "iissdsisssdsssisi", 
            $contrat_id, $id_garantie, $statut_logement, $type_logement, $superficie, 
            $localisation, $annee_construction, $etat_toiture, $materiaux, $occupation,
            $capital_mobilier, $wilaya, $commune, $adresse_detail, $nb_occupants, $securite, $antecedents
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du  détaill contrat habitation");
        } 
        $stmt->close();

        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Redirection</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        </head>
        <body>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire("Contrat d'habitation généré avec succès !").then(() => {
                    window.open("contrat_habitation.php?contrat=$contrat_id", "_blank");
                    window.location.href = "dashboard.php";
                });
            </script>
            <p>Génération du contrat en cours...</p>
        </body>
        </html>
        HTML;
        exit();
    } catch (Exception $e) {
        error_log("ERREUR COMPLÈTE: " . $e->getMessage());
        $_SESSION['error'] = "Erreur technique détaillée: " . $e->getMessage();
        // Affichez aussi l'erreur SQL si existe
        if (isset($stmt) && $stmt->error) {
            $_SESSION['error'] .= "<br>Erreur SQL: " . $stmt->error;
        }
        header('Location: formulaire_habitation.php');
        exit();
    }
} else {  
    // Si le formulaire n'a pas été soumis, rediriger vers le formulaire
    header('Location: formulaire_sante.php');
    exit();
}
?>