<?php
session_start();
require 'db.php';
require 'calcul_coef_habit.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Récupération des données
$data = [
    'superficie' => floatval($_POST['superficie'] ?? 0),
    'type_logement' => $_POST['type_logement'] ?? null,
    'annee_construction' = intval($_POST['annee_construction']) ?? date('Y'),
    'materiaux' => $_POST['materiaux'] ?? null,
    'etat_toiture' => $_POST['etat_toiture'] ?? null,
    'statut_occupation' => $_POST['statut_occupation'] ?? null,
    'nb_occupants' => intval($_POST['nb_occupants'] ?? 1),
    'capital_mobilier' => floatval($_POST['capital_mobilier'] ?? 0),
    'localisation' => $_POST['localisation'] ?? null,
    'antecedents' => intval($_POST['antecedents'] ?? 0),
    'securite' => $_POST['securite'] ?? [],
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
 
];

$data['age_logement'] = calculerAgeLogement($data['annee_construction']);
// Validation côté serveur
$errors = [];
if ($data['superficie'] < 10 || $data['superficie'] > 1000) $errors[] = "Superficie invalide (10-1000 m²)";
if ($data['capital_mobilier'] < 0 || $data['capital_mobilier'] > 5000000) $errors[] = "Capital mobilier invalide";
if ($data['antecedents'] < 0 || $data['antecedents'] > 20) $errors[] = "Nombre de sinistres antérieurs invalide";
// if (empty($data['wilaya'])) $errors[] = "Wilaya requise";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Récupération de la prime de base
$stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ? AND type_assurance = 'habitation'");
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

$coefficients = calculerCoefficientsHabitation($data);
$resultPrime = calculerPrimeHabitation(
    $primeBase,
    $coefficients,
    $data['reduction'],
    $data['surcharge']
);

echo json_encode([
    'success' => true,
    'primeNet' => $resultPrime['primeNet'], // Récupéré depuis le résultat
    'prime' => $resultPrime['prime'],       // Récupéré depuis le résultat
    'franchise' => round($franchise, 2),
]);
?>