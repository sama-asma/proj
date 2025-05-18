<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $nom_client = ucwords(strtolower($_POST['nom_client'] ?? ''));
    $prenom_client = ucwords(strtolower($_POST['prenom_client'] ?? ''));
    $telephone = $_POST['telephone'] ?? '';
    $email = $_POST['email'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $etat_sante = $_POST['etat_sante'] ?? '';
    $antecedents = $_POST['antecedents'] ?? [];
    $fumeur = $_POST['fumeur'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $sexe = $_POST['sexe'] ?? '';
    $capital = floatval($_POST['capital'] ?? 0);
    $id_garantie = intval($_POST['id_garantie'] ?? 0);
    $date_souscription = $_POST['date_souscription'] ?? '';
    $date_expiration = $_POST['date_expiration'] ?? '';
    $beneficiaires = json_decode($_POST['beneficiaires'] ?? '[]', true);//table associative
    $prime = floatval($_POST['prime_calculee'] ?? 0);
    $capital_garantie = floatval($_POST['capital_garantie'] ?? 0);
    $reduction = floatval($_POST['reduction'] ?? 0);
    $surcharge = floatval($_POST['surcharge'] ?? 0);

    // Validation des données
    $errors = [];
    $requiredFields = [
        'nom_client' => "Le nom est requis",
        'prenom_client' => "Le prénom est requis",
        'telephone' => "Le téléphone est requis",
        'email' => "L'email est requis",
        'date_naissance' => "La date de naissance est requise",
        'etat_sante' => "L'état de santé est requis",
        'profession' => "La profession est requise",
        'sexe' => "Le sexe est requis",
        'capital' => "Le capital est requis",
        'fumeur' => "Le statut de fumeur est requis",
        'antecedents' => "Les antécédents médicaux sont requis",
        'id_garantie' => "La garantie est requise",
        'date_souscription' => "La date de souscription est requise",
        'date_expiration' => "La date d'expiration est requise",
        'prime_calculee' => "La prime calculée est requise",
        'capital_garantie' => "Le capital garanti est requis"
    ];

    foreach ($requiredFields as $field => $error_message) {
        if (empty($_POST[$field]) && $field !== 'antecedents') {
            $errors[] = $error_message;
        } elseif ($field === 'telephone' && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
            $errors[] = "Format de téléphone invalide";
        } elseif ($field === 'email' && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide";
        } elseif ($field === 'capital' && $_POST[$field] < 100000) {
            $errors[] = "Le capital minimum est de 100 000 DZD";
        } 
    }

    // Validation spécifique des bénéficiaires
    if (!empty($beneficiaires) && is_array($beneficiaires)) {
        $total_parts = 0;
        foreach ($beneficiaires as $benef) {
            if (empty($benef['nom']) || empty($benef['lien']) || !isset($benef['part'])) {
                $errors[] = "Informations bénéficiaire incomplètes";
                break;
            }
            $total_parts += floatval($benef['part']);
        }
        if ($total_parts != 100) {
            $errors[] = "La somme des parts doit être égale à 100%";
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_vie.php');
        exit();
    }

    require 'db.php';
    if (!$conn) {
        $_SESSION['error'] = "Erreur de connexion à la base de données.";
        header('Location: formulaire_vie.php');
        exit();
    }
    
    try {
        // Vérifier si le client existe déjà
        $stmt = $conn->prepare("SELECT id_client FROM client WHERE email = ? AND telephone = ? 
        AND nom_client = ? AND prenom_client = ? AND date_naissance = ?");
        $stmt->bind_param("sssss", $email, $telephone, $nom_client, $prenom_client, $date_naissance);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $client = $result->fetch_assoc();
            $client_id = $client['id_client'];
        } else {
            // Créer un nouveau client
            $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);
            $stmt->execute();
            $client_id = $stmt->insert_id;
        }
        $stmt->close();

        // Créer le contrat
        do {
            $numero_contrat = uniqid("CTR-VIE-");
            $result = $conn->query("SELECT id_contrat FROM contrats WHERE numero_contrat = '$numero_contrat'");
        } while ($result->num_rows > 0);
        $stmt = $conn->prepare("INSERT INTO contrats (
                numero_contrat, id_client, date_souscription, date_expiration, 
                type_assurance, montant_prime,reduction, surcharge)
             VALUES (?, ?, ?, ?,'vie', ?, ?, ?)");
        $stmt->bind_param("sissddd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $prime, $reduction, $surcharge);
        $stmt->execute();
        $contrat_id = $stmt->insert_id;
        $stmt->close();

        // Convertir les antécédents en chaîne JSON
        $antecedents_str = json_encode($antecedents);
        $beneficiaires_json = json_encode($beneficiaires);
        
        // Enregistrer les détails de l'assurance vie
        $stmt = $conn->prepare("INSERT INTO assurance_vie (
            id_contrat, id_garantie, sexe, emploi, etat_sante, fumeur, 
            capital_souhaite, capital_garanti, antecedents_medicaux, beneficiaires
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iissssddss", 
            $contrat_id, 
            $id_garantie, 
            $sexe, 
            $profession, 
            $etat_sante, 
            $fumeur,
            $capital,
            $capital_garantie,
            $antecedents_str,
            $beneficiaires_json
        );
        $stmt->execute();
        $stmt->close();

        // Réponse de succès
        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Succès</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        </head>
        <body>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: "Contrat généré avec succès !",
                    text: "Le contrat d'assurance vie a été créé",
                    icon: "success"
                }).then(() => {
                    window.open("contrat_vie.php?contrat=$contrat_id", "_blank");
                    window.location.href = "dashboard.php";
                });
            </script>
        </html>
        HTML;

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la création du contrat : " . $e->getMessage();
        echo $_SESSION['error'];
        //header('Location: formulaire_vie.php');
        exit();
    }
} else {
    header('Location: dashboard.php');
    exit();
}
?>