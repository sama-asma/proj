<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Débogage : Afficher les données soumises via $_POST
error_log("Données POST soumises : " . json_encode($_POST));

// Calculer l'âge de l'emprunteur
$date_naissance = $_POST['date_naissance'] ?? null;
if ($date_naissance) {
    $date_naissance_dt = new DateTime($date_naissance);
    $aujourdhui = new DateTime();
    $age = $aujourdhui->diff($date_naissance_dt)->y;
} else {
    $age = 0;
}

// Récupération des données
$data = [
    'age' => $age,
    'etat_sante' => $_POST['etat_sante'] ?? null,
    'situation_professionnelle' => $_POST['situation_professionnelle'] ?? null,
    'type_pret' => $_POST['type_pret'] ?? null,
    'montant_emprunt' => floatval($_POST['montant_emprunt'] ?? 0),
    'duree_emprunt' => intval($_POST['duree_emprunt'] ?? 0),
    'taux_interet' => floatval($_POST['taux_interet'] ?? 0),
    'revenu_mensuel' => floatval($_POST['revenu_mensuel'] ?? 0),
    'fumeur' => $_POST['fumeur'] ?? null,
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];

// Validation côté serveur
$errors = [];
if ($data['age'] < 18 || $data['age'] > 80) {
    $errors[] = "Le client doit avoir entre 18 et 80 ans";
}
if (!in_array($data['etat_sante'], ['excellent', 'bon', 'moyen', 'mauvais'])) {
    $errors[] = "État de santé invalide";
}
if (!in_array($data['situation_professionnelle'], ['cdi', 'cdd', 'independant', 'fonctionnaire', 'sans_emploi'])) {
    $errors[] = "Situation professionnelle invalide";
}
if (!in_array($data['type_pret'], ['immobilier', 'consommation', 'auto'])) {
    $errors[] = "Type de prêt invalide";
}
if ($data['montant_emprunt'] < 1000 || $data['montant_emprunt'] > 100000000) {
    $errors[] = "Le montant du prêt doit être entre 1 000 et 100 000 000 DZD";
}
if ($data['duree_emprunt'] < 1 || $data['duree_emprunt'] > 30) {
    $errors[] = "La durée du prêt doit être entre 1 et 30 ans";
}
if ($data['taux_interet'] < 0 || $data['taux_interet'] > 20) {
    $errors[] = "Le taux d'intérêt doit être entre 0 et 20 %";
}
if ($data['revenu_mensuel'] < 50000 || $data['revenu_mensuel'] > 5000000) {
    $errors[] = "Le revenu doit être entre 50 000 et 5 000 000 DZD";
}
if (!in_array($data['fumeur'], ['oui', 'non'])) {
    $errors[] = "Statut fumeur invalide";
}
if (!$date_naissance) {
    $errors[] = "Date de naissance requise";
}
if ($data['surcharge'] < 0 || $data['surcharge'] > 100) {
    $errors[] = "La surcharge doit être entre 0 et 100 %";
}
if ($data['reduction'] < 0 || $data['reduction'] > 100) {
    $errors[] = "La réduction doit être entre 0 et 100 %";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Récupérer prime_base et franchise depuis la table garanties
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

// Débogage : Afficher les valeurs pour vérification
error_log("primeBase: $primeBase, franchise: $franchise");

// Facteurs de calcul
$coef_age = ($data['age'] < 30) ? 0.9 : (($data['age'] > 60) ? 1.5 : 1.2);
$coef_etat_sante = [
    'excellent' => 0.85,
    'bon' => 0.9,
    'moyen' => 1.2,
    'mauvais' => 1.5,
][$data['etat_sante']] ?? 1.0;
$coef_situation_pro = [
    'cdi' => 0.9,
    'cdd' => 1.2,
    'independant' => 1.3,
    'fonctionnaire' => 0.85,
    'sans_emploi' => 1.5,
][$data['situation_professionnelle']] ?? 1.0;
$coef_type_pret = [
    'immobilier' => 1.0,
    'consommation' => 1.2,
    'auto' => 1.1,
][$data['type_pret']] ?? 1.0;
$coef_montant_pret = ($data['montant_emprunt'] < 5000000) ? 1.0 : (($data['montant_emprunt'] > 20000000) ? 1.3 : 1.15);
$coef_duree_pret = ($data['duree_emprunt'] <= 5) ? 0.9 : (($data['duree_emprunt'] > 20) ? 1.3 : 1.1);
$coef_taux_interet = ($data['taux_interet'] < 3) ? 0.95 : (($data['taux_interet'] > 5) ? 1.2 : 1.0);
$coef_revenu_mensuel = ($data['revenu_mensuel'] < 200000) ? 1.2 : (($data['revenu_mensuel'] > 500000) ? 0.9 : 1.0);
$coef_fumeur = [
    'oui' => 1.4,
    'non' => 1.0,
][$data['fumeur']] ?? 1.0;

// Ajustement de la franchise selon le type de prêt (si non défini dans la table garanties)
$franchise_par_type = [
    'immobilier' => 90,
    'consommation' => 60,
    'auto' => 30,
];
$franchise = $franchise ?: ($franchise_par_type[$data['type_pret']] ?? 90);

// Calcul de la prime nette (prime totale sur toute la durée)
$primeNet = ($primeBase * $data['montant_emprunt'] / 100) * $coef_age * $coef_etat_sante * $coef_situation_pro * $coef_type_pret
            * $coef_montant_pret * $coef_duree_pret * $coef_taux_interet * $coef_revenu_mensuel * $coef_fumeur;

// Calcul de la prime annuelle (prime nette divisée par la durée, puis ajustée par réduction/surcharge)
$primeAnnuelle = ($primeNet / $data['duree_emprunt']) * (1 - $data['reduction'] / 100) * (1 + $data['surcharge'] / 100);

// Débogage : Afficher les résultats intermédiaires
error_log("primeNet: $primeNet, primeAnnuelle: $primeAnnuelle");

// Réponse JSON
echo json_encode([
    'success' => true,
    'primeNet' => round($primeNet, 2), // Prime totale
    'prime' => round($primeAnnuelle, 2), // Prime annuelle
    'franchise' => round($franchise, 2),
    'details' => "Calcul basé sur âge, santé, situation pro, type prêt, montant, durée, taux, revenu, fumeur (en DZD)"
]);
?>