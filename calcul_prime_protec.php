<?php
session_start();
require 'db.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Récupération des données
$data = [
    'situation_pro' => $_POST['situation_pro'] ?? null,
    'secteur_activite' => $_POST['secteur_activite'] ?? null,
    'type_litige' => $_POST['type_litige'] ?? null,
    'frequence_litige' => $_POST['frequence_litige'] ?? null,
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];

// Validation côté serveur
$errors = [];
$valid_situation_pro = ['salarie', 'independant', 'retraite', 'sans_emploi'];
$valid_secteur_activite = ['agriculture', 'industries_extractives', 'industrie_manufacturiere', 'commerce', 'information_communication', 'sante_humaine', 'activites_extra_territoriales', 'education'];
$valid_type_litige = ['personnel', 'professionnel', 'mixte'];
$valid_frequence_litige = ['rare', 'occasionnel', 'frequent'];

if (!in_array($data['situation_pro'], $valid_situation_pro)) {
    $errors[] = "Situation professionnelle invalide";
}
if (!in_array($data['secteur_activite'], $valid_secteur_activite)) {
    $errors[] = "Secteur d'activité invalide";
}
if (!in_array($data['type_litige'], $valid_type_litige)) {
    $errors[] = "Type de litige invalide";
}
if (!in_array($data['frequence_litige'], $valid_frequence_litige)) {
    $errors[] = "Fréquence de litige invalide";
}
if (!$data['situation_pro'] || !$data['secteur_activite'] || !$data['type_litige'] || !$data['frequence_litige']) {
    $errors[] = "Tous les champs de risques sont requis";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Récupération de la prime de base et de la franchise
$stmt = $conn->prepare("SELECT prime_base, franchise FROM garanties WHERE id_garantie = ? AND nom_garantie = 'protection'");
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
$coef_situation_pro = [
    'salarie' => 1.0,
    'independant' => 1.2,
    'retraite' => 0.9,
    'sans_emploi' => 1.1,
][$data['situation_pro']] ?? 1.0;

$coef_secteur_activite = [
    'agriculture' => 0.9,
    'industries_extractives' => 1.4,
    'industrie_manufacturiere' => 1.2,
    'commerce' => 0.95,
    'information_communication' => 1.1,
    'sante_humaine' => 1.5,
    'activites_extra_territoriales' => 1.3,
    'education' => 0.9,
][$data['secteur_activite']] ?? 1.0;

$coef_type_litige = [
    'personnel' => 1.0,
    'professionnel' => 1.3,
    'mixte' => 1.5,
][$data['type_litige']] ?? 1.0;

$coef_frequence_litige = [
    'rare' => 0.9,
    'occasionnel' => 1.0,
    'frequent' => 1.2,
][$data['frequence_litige']] ?? 1.0;

// Calcul de la prime nette
$primeNet = $primeBase * $coef_situation_pro * $coef_secteur_activite * $coef_type_litige * $coef_frequence_litige;

// Application des réductions/surcharges
$prime = $primeNet * (1 - $data['reduction'] / 100) * (1 + $data['surcharge'] / 100);

echo json_encode([
    'success' => true,
    'primeNet' => round($primeNet, 2),
    'prime' => round($prime, 2),
    'franchise' => round($franchise, 2),
    'details' => "Calcul basé sur : situation_pro=" . $data['situation_pro'] . ", secteur_activite=" . $data['secteur_activite'] . ", type_litige=" . $data['type_litige'] . ", frequence_litige=" . $data['frequence_litige']
]);
?>