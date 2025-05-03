<?php
session_start();
require 'db.php';
require 'calcul_coef_sante.php'; // Inclure le fichier de fonctions de calcul

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}
// Récupération des données
$data = [
    'date_naissance' => $_POST['date_naissance'] ?? null,
    'poids' => floatval($_POST['poids'] ?? 0),
    'taille' => floatval($_POST['taille'] ?? 0),
    'etat_sante' => $_POST['etat_sante'] ?? null,
    'antecedents' => $_POST['antecedents'] ?? [],
    'fumeur' => $_POST['fumeur'] ?? null,
    'profession' => $_POST['profession'] ?? null,
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];
// Validation côté serveur
$errors = [];
if (empty($data['date_naissance'])) $errors[] = "Date de naissance invalide";
if ($data['poids'] <= 0) $errors[] = "Poids invalide";
if ($data['taille'] <= 0) $errors[] = "Taille invalide";

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

// Calculer l'âge de l'assuré
$data['age_assure'] = calculerAgeAssure($data['date_naissance']);
// Calculer les coefficients santé
$coefficients = calculerCoefficientsSante($data);
// Calculer la prime santé
$resultatPrime = calculerPrimeSante($primeBase, $coefficients, $data['reduction'], $data['surcharge']);

// Envoi de la réponse JSON
echo json_encode([
    'success' => true,
    'primeNet' => $resultatPrime['primeNet'],
    'prime' => $resultatPrime['prime'],
    'franchise' => $franchise,
]);