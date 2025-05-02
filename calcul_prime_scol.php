<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Calculer l'âge de l'élève
$date_naissance_eleve = $_POST['date_naissance_eleve'] ?? null;
if ($date_naissance_eleve) {
    $date_naissance = new DateTime($date_naissance_eleve);
    $aujourdhui = new DateTime();
    $age_eleve = $aujourdhui->diff($date_naissance)->y; // Récupérer l'âge en années
} else {
    $age_eleve = 0; // Valeur par défaut en cas d'absence
}

// Récupération des données
$data = [
    'age_eleve' => $age_eleve,
    'type_etablissement' => $_POST['type_etablissement'] ?? null,
    'etat_sante' => $_POST['etat_sante'] ?? null,
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];

// Validation côté serveur
$errors = [];
if ($data['age_eleve'] < 3 || $data['age_eleve'] > 25) {
    $errors[] = "Âge de l'élève doit être entre 3 et 25 ans";
}
if (!in_array($data['type_etablissement'], ['primaire', 'collège', 'lycée', 'université'])) {
    $errors[] = "Type d'établissement invalide";
}
if (!in_array($data['etat_sante'], ['bon', 'fragile', 'maladie_chronique'])) {
    $errors[] = "État de santé invalide";
}
if (!$date_naissance_eleve) {
    $errors[] = "Date de naissance de l'élève requise";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Calcul de la prime 
$stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ?");
$stmt->bind_param("i", $data['id_garantie']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $primeBase = floatval($row['prime_base']);
    $franchise = floatval($row['franchise']);
} else {
    echo json_encode(['success' => false, 'message' => 'Garantie non trouvée']);
    exit;
}
$stmt->close();

// Facteurs de calcul
$coef_age_eleve = ($data['age_eleve'] < 12) ? 0.9 : (($data['age_eleve'] > 18) ? 1.2 : 1.0);
$coef_type_etablissement = [
    'primaire' => 0.85,
    'collège' => 0.95,
    'lycée' => 1.0,
    'université' => 1.15,
][$data['type_etablissement']] ?? 1.0;
$coef_etat_sante = [
    'bon' => 0.9,
    'fragile' => 1.2,
    'maladie_chronique' => 1.5,
][$data['etat_sante']] ?? 1.0;

// Calcul de la prime nette
$primeNet = $primeBase * $coef_age_eleve * $coef_type_etablissement * $coef_etat_sante;

// Application des réductions/surcharges
$prime = $primeNet * (1 - $data['reduction'] / 100) * (1 + $data['surcharge'] / 100);

echo json_encode([
    'success' => true,
    'primeNet' => round($primeNet, 2),
    'prime' => round($prime, 2),
    'franchise' => round($franchise, 2),
    'details' => "Calcul basé sur les critères fournis"
]);
?>