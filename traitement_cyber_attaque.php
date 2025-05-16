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
    $nom_client = ucwords(strtolower($_POST['nom_client'] ?? ''));
    $prenom_client = ucwords(strtolower($_POST['prenom_client'] ?? ''));
    $telephone = $_POST['telephone'] ?? '';
    $email = $_POST['email'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? null; // Ajouté
    $type_client = $_POST['type_client'] ?? '';
    $taille_entreprise = $_POST['taille_entreprise'] ?? null;
    $secteur_activite = $_POST['secteur_activite'] ?? '';
    $chiffre_affaires = floatval($_POST['chiffre_affaires'] ?? 0);
    $niveau_securite = $_POST['niveau_securite'] ?? '';
    $historique_attaques = $_POST['historique_attaques'] ?? '';
    $donnees_sensibles = $_POST['donnees_sensibles'] ?? '';
    $reduction = floatval($_POST['reduction'] ?? 0);
    $surcharge = floatval($_POST['surcharge'] ?? 0);
    $id_garantie = $_POST['id_garantie'] ?? '';
    $date_souscription = $_POST['date_souscription'] ?? '';
    $date_expiration = $_POST['date_expiration'] ?? '';
    $prime = floatval($_POST['prime_calculee'] ?? 0);

    // Validation des données avec une boucle
    $fields = [
        'nom_client' => "Le nom du client est requis.",
        'telephone' => "Un numéro de téléphone valide est requis.",
        'email' => "Une adresse email valide est requise.",
        'date_naissance' => "La date de naissance est requise.", // Ajouté
        'type_client' => "Le type de client est requis.",
        'id_garantie' => "L'identifiant de garantie doit être un nombre.",
        'date_souscription' => "Une date de souscription valide est requise.",
        'date_expiration' => "Une date d'expiration valide est requise.",
        'prime' => "La prime doit être un nombre.",
    ];

    $errors = [];
    foreach ($fields as $field => $error_message) {
        if (trim($$field) === '') {
            $errors[] = $error_message;
        } elseif (in_array($field, ['telephone']) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $telephone)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['email']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['id_garantie', 'prime',]) && !is_numeric($$field)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['date_naissance', 'date_souscription', 'date_expiration']) && $$field && !DateTime::createFromFormat('Y-m-d', $$field)) { // Ajouté date_naissance
            $errors[] = $error_message;
        }
    }

    // Validation spécifique pour type_client = entreprise
    if ($type_client === 'entreprise' && (!in_array($taille_entreprise, ['petite', 'moyenne', 'grande']) || !$secteur_activite)) {
        $errors[] = "La taille de l'entreprise et le secteur d'activité sont requis pour une entreprise.";
    }

    if (!in_array($niveau_securite, ['basique', 'intermediaire', 'avance'])) {
        $errors[] = "Niveau de sécurité invalide.";
    }
    if (!in_array($historique_attaques, ['aucun', 'mineur', 'majeur'])) {
        $errors[] = "Historique des cyberattaques invalide.";
    }
    if (!in_array($donnees_sensibles, ['aucune', 'personnelles', 'financieres', 'confidentielles'])) {
        $errors[] = "Type de données sensibles invalide.";
    }

    // Validation supplémentaire pour prime et franchise
    if ($prime <= 0) {
        $errors[] = "La prime doit etre nombre positif supérieur à zéro.";
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_cyber.php');
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
        $_SESSION['error'] = "Garantie non trouvée";
        header('Location: formulaire_cyber.php');
        exit();
    }
    $stmt->close();

    // Vérifier si la prime est cohérente
    $primeMin = $primeBase * 0.5;
    $primeMax = $primeBase * 3.5;
    if ($prime < $primeMin || $prime > $primeMax) {
        $_SESSION['error'] = "Incohérence détectée dans le calcul de la prime";
        header('Location: formulaire_cyber.php');
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
        $stmt = $conn->prepare("INSERT INTO contrats (numero_contrat, id_client, date_souscription, date_expiration, type_assurance, montant_prime, reduction, surcharge) VALUES (?, ?, ?, ?, 'cyberattaque', ?, ?, ?)");
        $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $prime, $reduction, $surcharge);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du contrat: " . $conn->error);
        }
        $contrat_id = $stmt->insert_id;
        $stmt->close();

        // Insérer les détails de l'assurance cyberattaque
        $stmt = $conn->prepare("INSERT INTO assurance_cyberattaque (id_contrat, id_garantie, type_client, taille_entreprise, secteur_activite, chiffre_affaires, niveau_securite, historique_attaques, donnees_sensibles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssdsss", $contrat_id, $id_garantie, $type_client, $taille_entreprise, $secteur_activite, $chiffre_affaires, $niveau_securite, $historique_attaques, $donnees_sensibles);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'insertion des détails de l'assurance cyberattaque: " . $conn->error);
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
                    window.open("contrat_cyber.php?contrat=$contrat_id", "_blank");
                    window.location.href = "dashboard.php";
                });
            </script>
            <p>Génération du contrat en cours...</p>
        </body>
        </html>
        HTML;
        exit();
    } catch (Exception $e) {
        error_log("Erreur dans traitement_cyber_attaque.php : " . $e->getMessage());
        $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
        header('Location: formulaire_cyber.php');
        exit();
    }
} else {
    header('Location: formulaire_cyber.php');
    exit();
}
?>