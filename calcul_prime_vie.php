<?php
session_start();
require 'db.php';
require 'calcul_coef_vie.php'; // Inclure le fichier de fonctions de calcul

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Récupération des données
$data = [
    'date_naissance' => $_POST['date_naissance'] ?? null,
    'etat_sante' => $_POST['etat_sante'] ?? null,
    'antecedents' => $_POST['antecedents'] ?? [],
    'fumeur' => $_POST['fumeur'] ?? null,
    'sexe' => $_POST['sexe'] ?? null,
    'profession' => $_POST['profession'] ?? null,
    'capital' => floatval($_POST['capital'] ?? 0),
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];

// Validation côté serveur
$errors = [];
if (empty($data['date_naissance'])) $errors[] = "Date de naissance invalide";
if ($data['capital'] < 100000) $errors[] = "Capital minimum non atteint (100000 DZD)";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Récupération de la prime de base et de la franchise pour la garantie
$stmt = $conn->prepare("SELECT prime_base, franchise, nom_garantie FROM garanties WHERE id_garantie = ?");
$stmt->bind_param("i", $data['id_garantie']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $primeBase = floatval($row['prime_base']);
    $franchise = floatval($row['franchise']);
} else {
    echo json_encode(['success' => false, 'message' => 'Garantie vie non trouvée']);
    exit;
}
$stmt->close();

// Calculer l'âge de l'assuré
$data['age_assure'] = calculerAge($data['date_naissance']);

// Calculer les coefficients vie
$coefficients = calculerCoeff($data);
$resultInter = calculerPrimeBase($data['capital'], $primeBase, $franchise);

// Calculer la prime vie en tenant compte du capital ajusté
$resultatPrime = calculerPrimeVie($coefficients, $resultInter['primeBaseCalcule'], $data['reduction'], $data['surcharge']);

// Envoi de la réponse JSON
echo json_encode([
    'success' => true,
    'primeNet' => $resultatPrime['primeNet'],
    'prime' => $resultatPrime['prime'],
    'franchise' => $franchise,
    'capital_garanti' => $resultInter['capital_garanti']
]);
?>