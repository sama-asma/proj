<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulaire_cyber.php');
    exit();
}

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Récupérer et nettoyer les données du formulaire
$nom_client = ucwords(strtolower(trim($_POST['nom_client'] ?? '')));
$prenom_client = ucwords(strtolower(trim($_POST['prenom_client'] ?? '')));
$telephone = trim($_POST['telephone'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$date_naissance = trim($_POST['date_naissance'] ?? '');
$type_client = trim($_POST['type_client'] ?? '');
$taille_entreprise = trim($_POST['taille_entreprise'] ?? null);
$secteur_activite = trim($_POST['secteur_activite'] ?? '');
$chiffre_affaires = floatval($_POST['chiffre_affaires'] ?? 0);
$niveau_securite = trim($_POST['niveau_securite'] ?? '');
$historique_attaques = trim($_POST['historique_attaques'] ?? '');
$donnees_sensibles = trim($_POST['donnees_sensibles'] ?? '');
$reduction = floatval($_POST['reduction'] ?? 0);
$surcharge = floatval($_POST['surcharge'] ?? 0);
$id_garantie = intval($_POST['id_garantie'] ?? 0);
$date_souscription = trim($_POST['date_souscription'] ?? '');
$date_expiration = trim($_POST['date_expiration'] ?? '');
$prime = floatval($_POST['prime_calculee'] ?? 0);

// Débogage : Afficher les données reçues
error_log("Données POST reçues : " . print_r($_POST, true));

// Validation des données
$fields = [
    'nom_client' => "Le nom du client est requis.",
    'prenom_client' => "Le prénom du client est requis.",
    'telephone' => "Un numéro de téléphone valide est requis.",
    'email' => "Une adresse email valide est requise.",
    'date_naissance' => "La date de naissance est requise.",
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
    } elseif ($field === 'telephone' && !empty($telephone) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $telephone)) {
        $errors[] = $error_message;
    } elseif ($field === 'email' && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $error_message;
    } elseif (in_array($field, ['id_garantie', 'prime']) && !is_numeric($$field)) {
        $errors[] = $error_message;
    } elseif (in_array($field, ['date_naissance', 'date_souscription', 'date_expiration']) && !DateTime::createFromFormat('Y-m-d', $$field)) {
        $errors[] = $error_message;
    }
}

// Validation des longueurs
if (strlen($nom_client) > 50) {
    $errors[] = "Le nom du client ne doit pas dépasser 50 caractères.";
}
if (strlen($prenom_client) > 50) {
    $errors[] = "Le prénom du client ne doit pas dépasser 50 caractères.";
}
if (!empty($telephone) && strlen($telephone) > 20) {
    $errors[] = "Le numéro de téléphone ne doit pas dépasser 20 caractères.";
}
if (!empty($email) && strlen($email) > 100) {
    $errors[] = "L'email ne doit pas dépasser 100 caractères.";
}

// Validation spécifique pour type_client = entreprise
if ($type_client === 'entreprise') {
    if (!in_array($taille_entreprise, ['petite', 'moyenne', 'grande'])) {
        $errors[] = "La taille de l'entreprise est requise pour une entreprise.";
    }
    if (empty($secteur_activite)) {
        $errors[] = "Le secteur d'activité est requis pour une entreprise.";
    }
}

// Validation des autres champs
if (!in_array($niveau_securite, ['basique', 'intermediaire', 'avance'])) {
    $errors[] = "Niveau de sécurité invalide.";
}
if (!in_array($historique_attaques, ['aucun', 'mineur', 'majeur'])) {
    $errors[] = "Historique des cyberattaques invalide.";
}
if (!in_array($donnees_sensibles, ['aucune', 'personnelles', 'financieres', 'confidentielles'])) {
    $errors[] = "Type de données sensibles invalide.";
}
if ($prime <= 0) {
    $errors[] = "La prime doit être un nombre positif supérieur à zéro.";
}

if (!empty($errors)) {
    error_log("Validation échouée : " . print_r($errors, true));
    $_SESSION['form_errors'] = $errors;
    header('Location: formulaire_cyber.php');
    exit();
}

require 'db.php';

// Vérifier la connexion à la base de données
if (!$conn) {
    error_log("Échec de la connexion à la base de données: " . mysqli_connect_error());
    $_SESSION['error'] = "Erreur de connexion à la base de données.";
    header('Location: formulaire_cyber.php');
    exit();
}

// Assurer l'encodage UTF-8
mysqli_set_charset($conn, "utf8");

// Vérifier la garantie
$stmt = $conn->prepare("SELECT franchise FROM garanties WHERE id_garantie = ?");
if (!$stmt) {
    error_log("Erreur de préparation de la requête garantie: " . $conn->error);
    $_SESSION['error'] = "Erreur de base de données.";
    header('Location: formulaire_cyber.php');
    exit();
}
$stmt->bind_param("i", $id_garantie);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $franchise = floatval($row['franchise']);
} else {
    $_SESSION['error'] = "Garantie non trouvée";
    header('Location: formulaire_cyber.php');
    exit();
}
$stmt->close();

// Calculer la prime de base dynamiquement (comme dans calcul_prime_cyber_attaque.php)
$primeBase = 50000; // Valeur par défaut pour particulier
if ($type_client === 'entreprise') {
    $primeBase = match ($taille_entreprise) {
        'petite' => 50000,
        'moyenne' => 150000,
        'grande' => 300000,
        default => 50000, // Fallback
    };
}
error_log("PrimeBase calculée: $primeBase pour type_client: $type_client, taille_entreprise: " . ($taille_entreprise ?? 'N/A'));

// Vérifier la cohérence de la prime
$primeMin = $primeBase * 0.5;
$primeMax = $primeBase * 3.5;
if ($prime < $primeMin || $prime > $primeMax) {
    $_SESSION['error'] = "Incohérence détectée dans le calcul de la prime (doit être entre $primeMin et $primeMax)";
    header('Location: formulaire_cyber.php');
    exit();
}

try {
    // Vérifier si le client existe déjà
    error_log("Recherche client avec email: $email, téléphone: $telephone");
    $stmt = $conn->prepare("SELECT id_client, nom_client, prenom_client, date_naissance FROM client WHERE LOWER(email) = ? AND telephone = ?");
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête client: " . $conn->error);
    }
    $stmt->bind_param("ss", $email, $telephone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Client existant
        $client = $result->fetch_assoc();
        $client_id = $client['id_client'];
        error_log("Client existant trouvé, id_client: $client_id");
        // Vérifier si les informations doivent être mises à jour
        if ($client['nom_client'] !== $nom_client || $client['prenom_client'] !== $prenom_client || $client['date_naissance'] !== $date_naissance) {
            error_log("Mise à jour des informations du client: $client_id");
            $stmt->close();
            $stmt = $conn->prepare("UPDATE client SET nom_client = ?, prenom_client = ?, date_naissance = ? WHERE id_client = ?");
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la mise à jour client: " . $conn->error);
            }
            $stmt->bind_param("sssi", $nom_client, $prenom_client, $date_naissance, $client_id);
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour du client: " . $conn->error);
            }
            $stmt->close();
        } else {
            $stmt->close();
        }
    } else {
        // Nouveau client
        error_log("Nouveau client à insérer: nom=$nom_client, prenom=$prenom_client, telephone=$telephone, email=$email, date_naissance=$date_naissance");
        $stmt->close();
        // Vérifier les contraintes de longueur
        if (strlen($nom_client) > 50) {
            throw new Exception("Le nom du client dépasse 50 caractères.");
        }
        if (strlen($prenom_client) > 50) {
            throw new Exception("Le prénom du client dépasse 50 caractères.");
        }
        if (!empty($telephone) && strlen($telephone) > 20) {
            throw new Exception("Le numéro de téléphone dépasse 20 caractères.");
        }
        if (!empty($email) && strlen($email) > 100) {
            throw new Exception("L'email dépasse 100 caractères.");
        }
        $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Erreur de préparation de l'insertion client: " . $conn->error);
        }
        // Utiliser des valeurs par défaut si vide pour telephone et email
        $telephone = empty($telephone) ? null : $telephone;
        $email = empty($email) ? null : $email;
        $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);

        if (!$stmt->execute()) {
            $error = $conn->error;
            error_log("Échec de l'insertion client, erreur SQL: $error");
            throw new Exception("Erreur lors de la création du client: $error");
        }
        error_log("Client inséré avec succès, id_client: " . $conn->insert_id);
        $client_id = $conn->insert_id;
        $stmt->close();
    }

    // Insérer le contrat
    $numero_contrat = uniqid("CTR-");
    $stmt = $conn->prepare("INSERT INTO contrats (numero_contrat, id_client, date_souscription, date_expiration, type_assurance, montant_prime, reduction, surcharge) VALUES (?, ?, ?, ?, 'cyberattaque', ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur de préparation de l'insertion contrat: " . $conn->error);
    }
    $stmt->bind_param("sisssdd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $prime, $reduction, $surcharge);
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création du contrat: " . $conn->error);
    }
    $contrat_id = $stmt->insert_id;
    $stmt->close();

    // Insérer les détails de l'assurance cyberattaque
    $stmt = $conn->prepare("INSERT INTO assurance_cyberattaque (id_contrat, id_garantie, type_client, taille_entreprise, secteur_activite, chiffre_affaires, niveau_securite, historique_attaques, donnees_sensibles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur de préparation de l'insertion assurance cyberattaque: " . $conn->error);
    }
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
            Swal.fire({
                title: "Contrat généré avec succès !",
                icon: "success",
                confirmButtonText: "OK"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open("contrat_cyberattaque.php?contrat=$contrat_id&primeBase=$primeBase", "_blank");
                    window.location.href = "dashboard.php";
                }
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
?>