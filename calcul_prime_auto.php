<?php
session_start();
require 'db.php';
require 'calcul_coef_auto.php'; // Inclure le fichier de fonctions de calcul

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Récupération des données
$data = [
    'marque_vehicule' => $_POST['marque_vehicule'] ?? null,
    'numero_serie' => $_POST['numero_serie'] ?? null,
    'immatriculation' => $_POST['immatriculation'] ?? null,
    'puissance' => floatval($_POST['puissance_vehicule'] ?? 0),
    'annee_vehicule' => intval($_POST['annee_vehicule'] ?? date('Y')),
    'date_naissance' => $_POST['date_naissance'] ?? null,
    'experience' => intval($_POST['experience_conducteur'] ?? 0),
    'bonus_malus' => floatval($_POST['bonus_malus'] ?? 0),
    'usage' => $_POST['usage'] ?? null,
    'environnement' => $_POST['environnement'] ?? 'mixte',
    'stationnement' => $_POST['condition_stationnement'] ?? null,
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'type_vehicule' => $_POST['type_vehicule'] ?? null,
];

// Calculer l'âge du conducteur
$data['age_conducteur'] = calculerAgeConducteur($data['date_naissance']);

// Validation côté serveur
$errors = [];
if ($data['age_conducteur'] < 18) $errors[] = "Age conducteur invalide";
if ($data['bonus_malus'] < 0.5 || $data['bonus_malus'] > 3.5) $errors[] = "Bonus-malus invalide";
$current_year = date('Y');
if ($data['annee_vehicule']< 1900 || $data['annee_vehicule'] > $current_year) {
    $errors[] = "L'année du véhicule doit être entre 1900 et $current_year";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Récupération de la prime de base et de la franchise
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

// Calcul des coefficients et de la prime
$coefficients = calculerCoefficients($data);
$resultatPrime = calculerPrime($primeBase, $coefficients, $data['bonus_malus'], $data['reduction'], $data['surcharge']);

echo json_encode([
    'success' => true,
    'primeNet' => $resultatPrime['primeNet'],
    'prime' => $resultatPrime['prime'],
    'franchise' => round($franchise, 2),
    'details' => "Calcul basé sur les critères fournis"
]);
?>