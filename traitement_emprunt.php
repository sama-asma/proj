<?php
session_start();

// Activer le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté");
    header('Location: login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Erreur: Méthode non POST");
    header('Location: formulaire_emprunt.php');
    exit();
}

// Récupérer les données du formulaire
$nom_client = ucwords(strtolower($_POST['nom_client'] ?? '')) ?: null;
$prenom_client = ucwords(strtolower($_POST['prenom_client'] ?? '')) ?: null;
$telephone = $_POST['telephone'] ?? null;
$email = $_POST['email'] ?? null;
$date_naissance = $_POST['date_naissance'] ?? null;
$montant_emprunt = floatval($_POST['montant_emprunt'] ?? 0);
$duree_emprunt = intval($_POST['duree_emprunt'] ?? 0);
$type_pret = $_POST['type_pret'] ?? null;
$taux_interet = floatval($_POST['taux_interet'] ?? 0);
$etat_sante = $_POST['etat_sante'] ?? null;
$fumeur = $_POST['fumeur'] ?? null;
$situation_professionnelle = $_POST['situation_professionnelle'] ?? null;
$revenu_mensuel = floatval($_POST['revenu_mensuel'] ?? 0);
$reduction = floatval($_POST['reduction'] ?? 0);
$surcharge = floatval($_POST['surcharge'] ?? 0);
$id_garantie = intval($_POST['id_garantie'] ?? 0);
$date_souscription = $_POST['date_souscription'] ?? null;
$date_expiration = $_POST['date_expiration'] ?? null;
$prime = floatval($_POST['prime_calculee'] ?? 0);
$franchise = floatval($_POST['franchise'] ?? 0);

// Débogage des données reçues
error_log("Données POST: " . print_r($_POST, true));

// Validation des données
$fields = [
    'nom_client' => "Le nom du client est requis.",
    'prenom_client' => "Le prénom du client est requis.",
    'date_naissance' => "La date de naissance est requise.",
    'montant_emprunt' => "Le montant du prêt doit être un nombre valide.",
    'duree_emprunt' => "La durée du prêt doit être un nombre valide.",
    'type_pret' => "Le type de prêt est requis.",
    'taux_interet' => "Le taux d'intérêt doit être un nombre valide.",
    'etat_sante' => "L'état de santé est requis.",
    'fumeur' => "Le statut fumeur est requis.",
    'situation_professionnelle' => "La situation professionnelle est requise.",
    'revenu_mensuel' => "Le revenu mensuel doit être un nombre valide.",
    'id_garantie' => "L'identifiant de garantie est requis.",
    'date_souscription' => "Une date de souscription valide est requise.",
    'date_expiration' => "Une date d'expiration valide est requise.",
    'prime_calculee' => "La prime doit être un nombre valide.",
];

$errors = [];
foreach ($fields as $field => $error_message) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = $error_message;
    } elseif ($field === 'telephone' && !empty($_POST[$field]) && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $_POST[$field])) {
        $errors[] = $error_message;
    } elseif ($field === 'email' && !empty($_POST[$field]) && !filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
        $errors[] = $error_message;
    } elseif (in_array($field, ['montant_emprunt', 'duree_emprunt', 'taux_interet', 'revenu_mensuel', 'id_garantie', 'prime_calculee']) && !is_numeric($_POST[$field])) {
        $errors[] = $error_message;
    } elseif ($field === 'date_naissance' && !DateTime::createFromFormat('Y-m-d', $_POST[$field])) {
        $errors[] = $error_message;
    } elseif (in_array($field, ['date_souscription', 'date_expiration']) && !strtotime($_POST[$field])) {
        $errors[] = $error_message;
    }
}

// Validations spécifiques
if ($date_naissance) {
    $date_naissance_dt = new DateTime($date_naissance);
    $aujourdhui = new DateTime();
    $age = $aujourdhui->diff($date_naissance_dt)->y;
    if ($age < 18 || $age > 80) {
        $errors[] = "Le client doit avoir entre 18 et 80 ans.";
    }
}
if (!in_array($type_pret, ['immobilier', 'consommation', 'auto'])) {
    $errors[] = "Type de prêt invalide.";
}
if ($montant_emprunt < 1000 || $montant_emprunt > 100000000) {
    $errors[] = "Le montant du prêt doit être entre 1 000 et 100 000 000 DZD.";
}
if ($duree_emprunt < 1 || $duree_emprunt > 30) {
    $errors[] = "La durée du prêt doit être entre 1 et 30 ans.";
}
if ($taux_interet < 0 || $taux_interet > 20) {
    $errors[] = "Le taux d'intérêt doit être entre 0 et 20 %.";
}
if ($revenu_mensuel < 50000 || $revenu_mensuel > 5000000) {
    $errors[] = "Le revenu mensuel doit être entre 50 000 et 5 000 000 DZD.";
}
if (!in_array($etat_sante, ['excellent', 'bon', 'moyen', 'mauvais'])) {
    $errors[] = "État de santé invalide.";
}
if (!in_array($fumeur, ['oui', 'non'])) {
    $errors[] = "Statut fumeur invalide.";
}
if (!in_array($situation_professionnelle, ['cdi', 'cdd', 'independant', 'fonctionnaire'])) {
    $errors[] = "Situation professionnelle invalide.";
}
if ($surcharge < 0 || $surcharge > 100) {
    $errors[] = "La surcharge doit être entre 0 et 100 %.";
}
if ($reduction < 0 || $reduction > 100) {
    $errors[] = "La réduction doit être entre 0 et 100 %.";
}

if (!empty($errors)) {
    error_log("Erreurs de validation: " . implode(", ", $errors));
    $_SESSION['form_errors'] = $errors;
    header('Location: formulaire_emprunt.php');
    exit();
}
error_log("Validation réussie, passage à la connexion DB");

require 'db.php';

// Forcer la récupération de $conn depuis la portée globale
global $conn;
if (!$conn) {
    error_log("Erreur: Connexion à la base de données non initialisée ou échouée");
    $_SESSION['error'] = "Erreur de connexion à la base de données.";
    header('Location: formulaire_emprunt.php');
    exit();
}
error_log("Connexion à la base de données réussie");

// Vérifier la garantie
$stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ?");
if (!$stmt) {
    error_log("Erreur préparation requête garantie: " . $conn->error);
    $_SESSION['error'] = "Erreur lors de la vérification de la garantie.";
    header('Location: formulaire_emprunt.php');
    exit();
}
$stmt->bind_param("i", $id_garantie);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Garantie non trouvée pour id_garantie: $id_garantie");
    $_SESSION['error'] = "Garantie non trouvée.";
    header('Location: formulaire_emprunt.php');
    exit();
}
$row = $result->fetch_assoc();
$primeBase = floatval($row['prime_base']);
$franchise = floatval($row['franchise'] ?? 0);
$stmt->close();

// Vérifier la cohérence de la prime
$primeMin = $primeBase * 0.5;
$primeMax = $primeBase * 3.5;
if ($prime < $primeMin || $prime > $primeMax) {
    error_log("Incohérence prime: $prime (min: $primeMin, max: $primeMax)");
    $_SESSION['error'] = "Incohérence détectée dans le calcul de la prime.";
    header('Location: formulaire_emprunt.php');
    exit();
}

try {
    // Vérifier si le client existe
    $query = "SELECT id_client FROM client WHERE nom_client = ? AND prenom_client = ? AND date_naissance = ?";
    $params = [$nom_client, $prenom_client, $date_naissance];
    $types = "sss";
    if ($telephone !== null) {
        $query .= " AND telephone = ?";
        $params[] = $telephone;
        $types .= "s";
    } else {
        $query .= " AND telephone IS NULL";
    }
    if ($email !== null) {
        $query .= " AND email = ?";
        $params[] = $email;
        $types .= "s";
    } else {
        $query .= " AND email IS NULL";
    }
    error_log("Requête SELECT client: $query avec types: $types et params: " . print_r($params, true));
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Erreur préparation requête client: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("Résultat de la requête SELECT: " . ($result->num_rows > 0 ? "Client trouvé" : "Aucun client trouvé"));

    if ($result->num_rows > 0) {
        $client = $result->fetch_assoc();
        $client_id = $client['id_client'];
        error_log("Client existant trouvé, id_client: $client_id");
    } else {
        // Créer un nouveau client
        $stmt->close();
        error_log("Aucun client trouvé, préparation de l'insertion");
        $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Erreur préparation requête insertion client: " . $conn->error);
        }
        $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);
        if (!$stmt->execute()) {
            throw new Exception("Erreur insertion client: " . $conn->error);
        }
        $client_id = $stmt->insert_id;
        error_log("Client inséré avec succès, id_client: $client_id");
    }
    $stmt->close();

    // Insérer le contrat
    $numero_contrat = uniqid("CTR-");
    $type_assurance = 'individuel';
    error_log("Préparation de l'insertion du contrat avec numero_contrat: $numero_contrat");
    $stmt = $conn->prepare("INSERT INTO contrats (numero_contrat, id_client, date_souscription, date_expiration, type_assurance, montant_prime, reduction, surcharge) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête contrat: " . $conn->error);
    }
    $stmt->bind_param("sisssddd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $type_assurance, $prime, $reduction, $surcharge);
    if (!$stmt->execute()) {
        throw new Exception("Erreur insertion contrat: " . $conn->error);
    }
    $contrat_id = $stmt->insert_id;
    error_log("Contrat inséré avec succès, id_contrat: $contrat_id");
    $stmt->close();

    // Insérer les détails de l'assurance emprunteur
    error_log("Préparation de l'insertion dans assurance_emprunteur pour id_contrat: $contrat_id");
    $stmt = $conn->prepare("INSERT INTO assurance_emprunteur (id_contrat, id_garantie, montant_emprunt, duree_emprunt, type_pret, taux_interet, etat_sante, fumeur, situation_professionnelle, revenu_mensuel, date_naissance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête assurance_emprunteur: " . $conn->error);
    }
    $stmt->bind_param("iidisssdssd", $contrat_id, $id_garantie, $montant_emprunt, $duree_emprunt, $type_pret, $taux_interet, $etat_sante, $fumeur, $situation_professionnelle, $revenu_mensuel, $date_naissance);
    if (!$stmt->execute()) {
        throw new Exception("Erreur insertion assurance_emprunteur: " . $conn->error);
    }
    error_log("Insertion dans assurance_emprunteur réussie");
    $stmt->close();

    // Assurer qu'aucune sortie n'a été envoyée avant
    if (ob_get_length()) {
        ob_clean();
    }

    error_log("Tentative d'affichage de SweetAlert pour contrat_id: $contrat_id");
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Redirection</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    </head>
    <body>
        <p>Test de sortie avant SweetAlert</p>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: 'Contrat généré avec succès !',
                confirmButtonText: 'OK'
            }).then(() => {
                window.open("contrat_emprunt.php?contrat=<?php echo $contrat_id; ?>", "_blank");
                window.location.href = "dashboard.php";
            });
        </script>
    </body>
    </html>
    <?php
    exit();
} catch (Exception $e) {
    error_log("Erreur technique: " . $e->getMessage());
    $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
    header('Location: formulaire_emprunt.php');
    exit();
}
?>