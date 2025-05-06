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
    $poids = floatval($_POST['poids'] ?? 0);
    $taille = floatval($_POST['taille'] ?? 0);
    $etat_sante = $_POST['etat_sante'] ?? '';
    $antecedents = $_POST['antecedents'] ?? [];
    $fumeur = $_POST['fumeur'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $sexe = $_POST['sexe'] ?? '';
    $id_garantie = intval($_POST['id_garantie'] ?? 0);
    $reduction = floatval($_POST['reduction'] ?? 0);
    $surcharge = floatval($_POST['surcharge'] ?? 0);
    $date_souscription = $_POST['date_souscription'] ?? '';
    $date_expiration = $_POST['date_expiration'] ?? '';
    $prime = floatval($_POST['prime_calculee'] ?? 0);

    // Validation des données
    $errors = [];
    $requiredFields = [
        'nom_client' => "Le nom est requis",
        'prenom_client' => "Le prénom est requis",
        'telephone' => "Le téléphone est requis",
        'email' => "L'email est requis",
        'date_naissance' => "La date de naissance est requise",
        'poids' => "Le poids est requis",
        'taille' => "La taille est requise",
        'etat_sante' => "L'état de santé est requis",
        'antecedents' => "Les antécédents médicaux sont requis",
        'profession' => "La profession est requise",
        'sexe' => "Le sexe est requis",
        'id_garantie' => "La garantie est requise",
        'date_souscription' => "La date de souscription est requise",
        'date_expiration' => "La date d'expiration est requise"
    ];

    foreach ($requiredFields as $field => $error_message) {
        if (!isset($_POST[$field])) { // Vérifier si la variable correspondant au nom du champ est vide ou non définie
            $errors[] = $error_message;
        } elseif (in_array($field, ['telephone']) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['email']) && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['id_garantie', 'prime_calculee']) && !is_numeric($_POST[$field])) {
            $errors[] = $error_message;
        } elseif ($field === 'date_naissance' && !DateTime::createFromFormat('Y-m-d', $_POST[$field])) {
            $errors[] = $error_message;
        }
         elseif (in_array($field, ['date_souscription', 'date_expiration']) && !strtotime($_POST[$field])) {
            $errors[] = $error_message;
        }
    }

    // Validation spécifique
    if ($poids <= 0 || $taille <= 0) {
        $errors[] = "Poids ou taille invalide";
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_sante.php');
        exit();
    }

    require 'db.php';
    
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
            $numero_contrat = uniqid("CTR-SANTE-");
            $result = $conn->query("SELECT id_contrat FROM contrats WHERE numero_contrat = '$numero_contrat'");
        } while ($result->num_rows > 0);
        $stmt = $conn->prepare("INSERT INTO contrats (
                numero_contrat, id_client, date_souscription, date_expiration, 
                type_assurance, montant_prime, reduction, surcharge
            ) VALUES (?, ?, ?, ?, 'santé', ?, ?, ?)");
            $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $prime, $reduction, $surcharge);
            $stmt->execute();
            $contrat_id = $stmt->insert_id;
            $stmt->close();
            $antecedents_str = !empty($antecedents) ? implode(',', $antecedents) : 'aucun';
            // 5. Détails santé 
            $stmt = $conn->prepare("INSERT INTO assurance_sante (
                id_contrat, id_garantie, fumeur, poids, taille, etat_sante, antecedents_medicaux, profession, sexe
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisddssss", $contrat_id, $id_garantie, $fumeur, $poids, $taille, $etat_sante, $antecedents_str, $profession, $sexe);
            $stmt->execute();
            $stmt->close();

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
                    // 1. Afficher l'alerte
                Swal.fire({
                     title: "Contrat généré avec succès !",
                     text: "Le contrat d'assurance santé a été créé",
                     icon: "success"
                  }).then(() => {
                    // 2. Ouvrir le PDF après la fermeture de l'alerte
                    window.open("contrat_sante.php?contrat=$contrat_id", "_blank");
                    
                    // 3. Redirection vers le dashboard
                    window.location.href = "dashboard.php";
                });
                </script>
            </body>
            </html>
            HTML;

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: formulaire_sante.php');
            exit();
        }
    } else {
        header('Location: formulaire_sante.php');
        exit();
    }
    ?>