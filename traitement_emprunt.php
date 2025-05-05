<?php
session_start();
ob_start(); // Démarrer le tampon de sortie

// Activer le débogage (journalisation uniquement)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Erreur: Utilisateur non connecté");
    header('Location: login.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Erreur: Méthode non POST, URL: " . $_SERVER['REQUEST_URI']);
    header('Location: formulaire_emprunt.php');
    exit();
}

// Récupérer et journaliser les données du formulaire
error_log("Données POST brutes: " . json_encode($_POST));
$nom_client = ucwords(strtolower(trim($_POST['nom_client'] ?? '')));
$prenom_client = ucwords(strtolower(trim($_POST['prenom_client'] ?? '')));
$telephone = trim($_POST['telephone'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;
$date_naissance = trim($_POST['date_naissance'] ?? '');
$montant_emprunt = floatval($_POST['montant_emprunt'] ?? 0);
$duree_emprunt = intval($_POST['duree_emprunt'] ?? 0);
$type_pret = trim($_POST['type_pret'] ?? '') ?: null;
$taux_interet = floatval($_POST['taux_interet'] ?? 0);
$etat_sante = trim($_POST['etat_sante'] ?? '') ?: null;
$fumeur = trim($_POST['fumeur'] ?? '') ?: null;
$situation_professionnelle = trim($_POST['situation_professionnelle'] ?? '') ?: null;
$revenu_mensuel = floatval($_POST['revenu_mensuel'] ?? 0);
$reduction = floatval($_POST['reduction'] ?? 0);
$surcharge = floatval($_POST['surcharge'] ?? 0);
$id_garantie = intval($_POST['id_garantie'] ?? 0);
$date_souscription = trim($_POST['date_souscription'] ?? '') ?: null;
$date_expiration = trim($_POST['date_expiration'] ?? '') ?: null;
$prime_calculee = floatval($_POST['prime_calculee'] ?? 0);
$franchise = floatval($_POST['franchise'] ?? 0);

// Validation des données
$errors = [];
if (empty($nom_client)) $errors[] = "Le nom du client est requis.";
if (empty($prenom_client)) $errors[] = "Le prénom du client est requis.";
if (empty($date_naissance)) {
    $errors[] = "La date de naissance est requise.";
} else {
    $date_naissance_dt = DateTime::createFromFormat('Y-m-d', $date_naissance);
    if (!$date_naissance_dt || $date_naissance_dt->format('Y-m-d') !== $date_naissance) {
        $errors[] = "La date de naissance est invalide (doit être au format AAAA-MM-JJ).";
    } else {
        $aujourdhui = new DateTime();
        $age = $aujourdhui->diff($date_naissance_dt)->y;
        if ($age < 18 || $age > 80) $errors[] = "Le client doit avoir entre 18 et 80 ans.";
    }
}
if ($telephone && !preg_match('/^(\+213|0)(5|6|7)\d{8}$/', $telephone)) $errors[] = "Le numéro de téléphone est invalide.";
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
if (empty($montant_emprunt) || $montant_emprunt < 1000 || $montant_emprunt > 100000000) $errors[] = "Le montant du prêt doit être entre 1 000 et 100 000 000 DZD.";
if (empty($duree_emprunt) || $duree_emprunt < 1 || $duree_emprunt > 30) $errors[] = "La durée du prêt doit être entre 1 et 30 ans.";
if (empty($type_pret) || !in_array($type_pret, ['immobilier', 'consommation', 'auto'])) $errors[] = "Type de prêt invalide.";
if (empty($taux_interet) || $taux_interet < 0 || $taux_interet > 20) $errors[] = "Le taux d'intérêt doit être entre 0 et 20 %.";
if (empty($etat_sante) || !in_array($etat_sante, ['excellent', 'bon', 'moyen', 'mauvais'])) $errors[] = "État de santé invalide.";
if (empty($fumeur) || !in_array($fumeur, ['oui', 'non'])) $errors[] = "Statut fumeur invalide.";
if (empty($situation_professionnelle) || !in_array($situation_professionnelle, ['cdi', 'cdd', 'independant', 'fonctionnaire', 'sans_emploi'])) $errors[] = "Situation professionnelle invalide.";
if (empty($revenu_mensuel) || $revenu_mensuel < 50000 || $revenu_mensuel > 5000000) $errors[] = "Le revenu mensuel doit être entre 50 000 et 5 000 000 DZD.";
if ($surcharge < 0 || $surcharge > 100) $errors[] = "La surcharge doit être entre 0 et 100 %.";
if ($reduction < 0 || $reduction > 100) $errors[] = "La réduction doit être entre 0 et 100 %.";
if (empty($id_garantie)) $errors[] = "L'identifiant de garantie est requis.";
if (empty($date_souscription) || !strtotime($date_souscription)) $errors[] = "Une date de souscription valide est requise.";
if (empty($date_expiration) || !strtotime($date_expiration)) $errors[] = "Une date d'expiration valide est requise.";
if (empty($prime_calculee)) $errors[] = "La prime calculée est requise.";
if (empty($franchise)) $errors[] = "La franchise est requise.";

if (!empty($errors)) {
    error_log("Erreurs de validation: " . implode(", ", $errors));
    $_SESSION['form_errors'] = $errors;
    header('Location: formulaire_emprunt.php');
    exit();
}

require 'db.php';

// Vérifier la connexion à la base de données
if (!$conn) {
    $error_message = mysqli_connect_error() ?? 'Connexion non initialisée';
    error_log("Erreur: Connexion à la base de données échouée: " . $error_message);
    $_SESSION['error'] = "Erreur de connexion à la base de données: " . $error_message;
    header('Location: formulaire_emprunt.php');
    exit();
}
error_log("Connexion à la base de données réussie");

// Forcer le format de date_naissance
$date_naissance_dt = DateTime::createFromFormat('Y-m-d', $date_naissance);
$date_naissance = $date_naissance_dt->format('Y-m-d');
error_log("Données formatées pour insertion: nom_client=$nom_client, prenom_client=$prenom_client, telephone=$telephone, email=$email, date_naissance=$date_naissance");

try {
    // Insérer le client
    $stmt = $conn->prepare("INSERT INTO client (nom_client, prenom_client, telephone, email, date_naissance) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête insertion client: " . $conn->error);
    }
    error_log("Requête préparée avec succès");
    $stmt->bind_param("sssss", $nom_client, $prenom_client, $telephone, $email, $date_naissance);
    error_log("Paramètres liés avec succès");
    if (!$stmt->execute()) {
        throw new Exception("Erreur insertion client: " . $stmt->error);
    }
    $client_id = $stmt->insert_id;
    error_log("Client inséré avec succès, id_client: $client_id");
    $stmt->close();

    // Insérer le contrat avec type_assurance corrigé
    $numero_contrat = 'CTR-' . uniqid();
    $stmt = $conn->prepare("INSERT INTO contrats (numero_contrat, id_client, date_souscription, date_expiration, type_assurance, montant_prime, reduction, surcharge) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête insertion contrat: " . $conn->error);
    }
    $type_assurance = 'emprunteur'; // Correction : 'emprunt' -> 'emprunteur'
    $stmt->bind_param("sisssddd", $numero_contrat, $client_id, $date_souscription, $date_expiration, $type_assurance, $prime_calculee, $reduction, $surcharge);
    if (!$stmt->execute()) {
        throw new Exception("Erreur insertion contrat: " . $stmt->error);
    }
    $contrat_id = $stmt->insert_id;
    error_log("Contrat inséré avec succès, id_contrat: $contrat_id, type_assurance: $type_assurance");
    $stmt->close();

    // Insérer dans assurance_emprunteur
    $stmt = $conn->prepare("INSERT INTO assurance_emprunteur (id_contrat, id_garantie, etat_sante, situation_professionnelle, montant_emprunt, duree_emprunt, taux_interet, revenu_mensuel, type_pret, fumeur, date_naissance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête insertion assurance_emprunteur: " . $conn->error);
    }
    $stmt->bind_param("iissiddssss", $contrat_id, $id_garantie, $etat_sante, $situation_professionnelle, $montant_emprunt, $duree_emprunt, $taux_interet, $revenu_mensuel, $type_pret, $fumeur, $date_naissance);
    if (!$stmt->execute()) {
        throw new Exception("Erreur insertion assurance_emprunteur: " . $stmt->error);
    }
    error_log("Assurance emprunteur insérée avec succès");
    $stmt->close();

    // Afficher une alerte de succès et rediriger
    ob_clean();
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
            console.log('SweetAlert script exécuté, contrat_id: $contrat_id');
            Swal.fire({
                icon: 'success',
                title: 'Succès',
                text: 'Contrat généré avec succès !',
                confirmButtonText: 'OK'
            }).then(() => {
                console.log('Alerte confirmée, ouverture du PDF et redirection...');
                window.open("contrat_emprunt.php?contrat=$contrat_id", "_blank");
                window.location.href = "dashboard.php";
            }).catch((error) => {
                console.error('Erreur SweetAlert:', error);
                window.location.href = "dashboard.php";
            });
        </script>
        <p>Génération du contrat en cours...</p>
    </body>
    </html>
    HTML;
    ob_end_flush();
    exit();
} catch (Exception $e) {
    error_log("Erreur technique: " . $e->getMessage());
    $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
    header('Location: formulaire_emprunt.php');
    ob_end_flush();
    exit();
}
?>