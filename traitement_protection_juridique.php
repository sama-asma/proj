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
    $nom_client = ucwords(strtolower($_POST['nom_client'])) ?? null;
    $prenom_client = ucwords(strtolower($_POST['prenom_client'])) ?? null;
    $telephone = $_POST['telephone'] ?? null;
    $email = $_POST['email'] ?? null;
    $date_naissance = $_POST['date_naissance'] ?? null;
    $situation_pro = $_POST['situation_pro'] ?? null;
    $secteur_activite = $_POST['secteur_activite'] ?? null;
    $type_litige = $_POST['type_litige'] ?? null;
    $frequence_litige = $_POST['frequence_litige'] ?? null;
    $reduction = floatval($_POST['reduction'] ?? 0);
    $surcharge = floatval($_POST['surcharge'] ?? 0);
    $id_garantie = $_POST['id_garantie'] ?? null;
    $date_souscription = $_POST['date_souscription'] ?? null;
    $date_expiration = $_POST['date_expiration'] ?? null;
    $prime = floatval($_POST['prime_calculee'] ?? null);
    $franchise = floatval($_POST['franchise'] ?? null);

    // Validation des données avec une boucle
    $fields = [
        'nom_client' => "Le nom du client est requis.",
        'prenom_client' => "Le prénom du client est requis.",
        'telephone' => "Un numéro de téléphone valide est requis.",
        'email' => "Une adresse email valide est requise.",
        'date_naissance' => "La date de naissance du client est requise.",
        'situation_pro' => "La situation professionnelle est requise.",
        'secteur_activite' => "Le secteur d'activité est requis.",
        'type_litige' => "Le type de litige est requis.",
        'frequence_litige' => "La fréquence de litige est requise.",
        'id_garantie' => "L'identifiant de garantie doit être un nombre.",
        'date_souscription' => "Une date de souscription valide est requise.",
        'date_expiration' => "Une date d'expiration valide est requise.",
        'prime_calculee' => "La prime doit être un nombre.",
        'franchise' => "La franchise doit être un nombre."
    ];

    $errors = [];
    $valid_situation_pro = ['salarie', 'independant', 'retraite', 'sans_emploi'];
    $valid_secteur_activite = ['agriculture', 'industries_extractives', 'industrie_manufacturiere', 'commerce', 'information_communication', 'sante_humaine', 'activites_extra_territoriales', 'education'];
    $valid_type_litige = ['personnel', 'professionnel', 'mixte'];
    $valid_frequence_litige = ['rare', 'occasionnel', 'frequent'];

    foreach ($fields as $field => $error_message) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = $error_message;
        } elseif ($field === 'telephone' && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif ($field === 'email' && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['id_garantie', 'prime_calculee', 'franchise']) && !is_numeric($_POST[$field])) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['date_naissance', 'date_souscription', 'date_expiration']) && !DateTime::createFromFormat('Y-m-d', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif ($field === 'situation_pro' && !in_array($_POST[$field], $valid_situation_pro)) {
            $errors[] = "Situation professionnelle invalide.";
        } elseif ($field === 'secteur_activite' && !in_array($_POST[$field], $valid_secteur_activite)) {
            $errors[] = "Secteur d'activité invalide.";
        } elseif ($field === 'type_litige' && !in_array($_POST[$field], $valid_type_litige)) {
            $errors[] = "Type de litige invalide.";
        } elseif ($field === 'frequence_litige' && !in_array($_POST[$field], $valid_frequence_litige)) {
            $errors[] = "Fréquence de litige invalide.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_protec.php');
        exit();
    }

    require 'db.php';
    $stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ? AND nom_garantie = 'protection'");
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

    // Vérifier si la prime est cohérente
    $primeMin = $primeBase * 0.5;
    $primeMax = $primeBase * 3.5;
    if ($prime < $primeMin || $prime > $primeMax) {
        $_SESSION['error'] = "Incohérence détectée dans le calcul de la prime";
        header('Location: formulaire_protec.php');
        exit();
    }

    try {
        // Vérifier si le client existe déjà
        $stmt = $conn->prepare("SELECT id_client FROM client WHERE email = ? AND telephone = ? AND nom_client = ? AND prenom_client = ? AND date_naissance = ?");
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
            $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la création du client: " . $conn->error);
            }

            $client_id = $stmt->insert_id;
            $stmt->close();
        }

        // Insérer le contrat
        $numero_contrat = uniqid("CTR-");
        $stmt = $conn->prepare("INSERT INTO contrats (numero_contrat, id_client, date_souscription, date_expiration, type_assurance, montant_prime, reduction, surcharge) VALUES (?, ?, ?, ?, 'protection_juridique', ?, ?, ?)");
        $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $prime, $reduction, $surcharge);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du contrat: " . $conn->error);
        }
        $contrat_id = $stmt->insert_id;
        $stmt->close();

        // Insérer les détails de l'assurance protection juridique
        $stmt = $conn->prepare("INSERT INTO assurance_protection_juridique (id_contrat, id_garantie, situation_pro, secteur_activite, type_litige, frequence_litige) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $contrat_id, $id_garantie, $situation_pro, $secteur_activite, $type_litige, $frequence_litige);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'insertion des détails de l'assurance protection juridique: " . $conn->error);
        }
        $stmt->close();

        // Afficher une alerte de succès et rediriger
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
                Swal.fire("Contrat généré avec succès !").then(() => {
                    window.open("contrat_protection_juridique.php?contrat=$contrat_id", "_blank");
                    window.location.href = "dashboard.php";
                });
            </script>
            <p>Génération du contrat en cours...</p>
        </body>
        </html>
        HTML;
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
        header('Location: formulaire_protec.php');
        exit();
    }
} else {
    header('Location: formulaire_protec.php');
    exit();
}
?>