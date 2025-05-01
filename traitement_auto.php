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
    $type_vehicule = $_POST['type_vehicule'] ?? null;
    $puissance_vehicule = $_POST['puissance_vehicule'] ?? null;
    $date_naissance = $_POST['date_naissance'] ?? null;
    $condition_stationnement = $_POST['condition_stationnement'] ?? null;
    $annee_vehicule = $_POST['annee_vehicule'] ?? null;
    $experience_conducteur = $_POST['experience_conducteur'] ?? null;
    $bonus_malus = $_POST['bonus_malus'] ?? null;
    $usage = $_POST['usage'] ?? null;
    $environnement = $_POST['environnement'] ?? null;
    $reduction = $_POST['reduction'] ?? 0;
    $surcharge = $_POST['surcharge'] ?? 0;
    $id_garantie = $_POST['id_garantie'] ?? null;
    $date_souscription = $_POST['date_souscription'] ?? null;
    $date_expiration = $_POST['date_expiration'] ?? null;
    $prime = floatval($_POST['prime_calculee'] ?? null);
    $franchise = floatval($_POST['franchise'] ?? null);
    $marque_vehicule = $_POST['marque_vehicule'] ?? null;
    $numero_serie = $_POST['numero_serie'] ?? null;
    $immatriculation = $_POST['immatriculation'] ?? null;
    
    // Validation des données avec une boucle
    $fields = [
        'nom_client' => "Le nom du client est requis.",
        'prenom_client' => "Le prénom du client est requis.",
        'telephone' => "Un numéro de téléphone valide est requis.",
        'email' => "Une adresse email valide est requise.",
        'date_naissance' => "La date de naissance est requise.",
        'marque_vehicule' => "La marque du véhicule est requise.",
        'numero_serie' => "Le numéro de série est requis.",
        'immatriculation' => "L'immatriculation est requise.",
        'type_vehicule' => "Le type de véhicule est requis.",
        'puissance_vehicule' => "La puissance du véhicule doit être un nombre.",
        'annee_vehicule' => "L'année du véhicule est requise",
        'condition_stationnement' => "La condition de stationnement est requise.",
        'experience_conducteur' => "L'expérience du conducteur doit être un nombre.",
        'bonus_malus' => "Le bonus-malus doit être un nombre.",
        'usage' => "L'usage est requis.",
        'environnement' => "L'environnement est requis.",
        'id_garantie' => "L'identifiant de garantie doit être un nombre.",
        'date_souscription' => "Une date de souscription valide est requise.",
        'date_expiration' => "Une date d'expiration valide est requise.",
        'prime_calculee' => "La prime doit etre un nombre.",
        'franchise' => "La franchise doit etre un nombre."
    ];

    $errors = [];
    foreach ($fields as $field => $error_message) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') { // Vérifier si la variable correspondant au nom du champ est vide ou non définie
            $errors[] = $error_message;
        } elseif (in_array($field, ['telephone']) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['email']) && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_message;
        } elseif (in_array($field, ['puissance_vehicule', 'experience_conducteur', 'bonus_malus', 'id_garantie', 'prime_calculee', 'franchise']) && !is_numeric($_POST[$field])) {
            $errors[] = $error_message;
        } elseif ($field === 'date_naissance' && !DateTime::createFromFormat('Y-m-d', $_POST[$field])) {
            $errors[] = $error_message;
        }
         elseif (in_array($field, ['date_souscription', 'date_expiration']) && !strtotime($_POST[$field])) {
            $errors[] = $error_message;
        }
    }

    
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: formulaire_vehicule.php');
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
    // Vérifier si la prime est cohérente
    $primeMin = $primeBase * 0.5;
    $primeMax = $primeBase * 3.5;
    if ($prime < $primeMin || $prime > $primeMax) {
        $_SESSION['error'] = "Incohérence détectée dans le calcul de la prime";
        header('Location: formulaire_vehicule.php');
        exit();
    }
    try{
        // Vérifier si le client existe déjà avec plusieurs critères
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

        $client_id = $stmt->insert_id; // Récupérer l'ID du client inséré
        $stmt->close();
        }
        // Préparer la requête d'insertion
        $numero_contrat = uniqid("CTR-"); // Générer un numéro de contrat unique
        $stmt = $conn->prepare("INSERT INTO contrats (
            numero_contrat, id_client, date_souscription, date_expiration, 
            type_assurance, montant_prime, reduction, surcharge
        ) VALUES (?, ?, ?, ?, 'automobile', ?, ?, ?)");
        $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, 
                         $date_expiration, $prime, $reduction, $surcharge);
        if (!$stmt->execute()) {
           throw new Exception("Erreur lors de la création du contrat");
        } 
        $contrat_id = $stmt->insert_id; // Récupérer l'ID du contrat inséré
        $stmt->close();
        // Préparer la requête d'insertion pour les détails du contrat
        $stmt = $conn->prepare("INSERT INTO assurance_automobile (
            id_contrat, id_garantie, experience_conducteur, 
            environnement, type_usage, condition_stationnement, puissance_vehicule, 
            marque_vehicule, type_vehicule, annee_vehicule, bonus_malus, numero_serie, immatriculation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("iiisssssssiss", 
            $contrat_id, $id_garantie, $experience_conducteur,
            $environnement, $usage, $condition_stationnement, $puissance_vehicule,
            $marque_vehicule, $type_vehicule, $annee_vehicule, $bonus_malus, $numero_serie, $immatriculation);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création du contrat");
        } 

        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Redirection</title>
            <!-- Inclure SweetAlert2 CSS -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        </head>
        <body>

            <!-- Inclure SweetAlert2 JS -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
               // 1. Afficher l'alerte
               Swal.fire("Contrat généré avec succès !").then(() => {
                    // 2. Ouvrir le PDF après la fermeture de l'alerte
                    window.open("contrat_auto.php?contrat=$contrat_id", "_blank");
                    
                    // 3. Redirection vers le dashboard
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
        header('Location: formulaire_vehicule.php');
        exit();
    }
} else {  
    // Si le formulaire n'a pas été soumis, rediriger vers le formulaire
    header('Location: formulaire_vehicule.php');
    exit();
}
?>
