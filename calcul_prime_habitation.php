<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

// Récupération des données
$data = [
    'superficie' => floatval($_POST['superficie'] ?? 0),
    'type_logement' => $_POST['type_logement'] ?? null,
    'annee_construction' => intval($_POST['annee_construction'] ?? date('Y')),
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
    'wilaya_code' => $_POST['wilaya'] ?? null,
    'commune_code' => $_POST['commune'] ?? null,
    'adresse_detail' => $_POST['adresse_detail'] ?? ''
];

// Validation côté serveur
$errors = [];
if ($data['superficie'] < 10 || $data['superficie'] > 1000) $errors[] = "Superficie invalide (10-1000 m²)";
if ($data['capital_mobilier'] < 0 || $data['capital_mobilier'] > 5000000) $errors[] = "Capital mobilier invalide";
if ($data['antecedents'] < 0 || $data['antecedents'] > 20) $errors[] = "Nombre de sinistres antérieurs invalide";
if (empty($data['wilaya_code'])) $errors[] = "Wilaya requise";

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

// Facteurs de calcul spécifiques à l'habitation
$facteurs = [
    'type_logement' => [
        'maison' => 1.1,
        'appartement' => 0.9
    ],
    'materiaux' => [
        'resistant' => 0.8,
        'standard' => 1.0,
        'fragile' => 1.3
    ],
    'etat_toiture' => [
        'excellent' => 0.85,
        'bon' => 1.0,
        'moyen' => 1.15,
        'mauvais' => 1.4
    ],
    'statut_occupation' => [
        'proprietaire' => 1.1,
        'locataire' => 0.9
    ],
    'localisation' => [
        'urbain' => 1.3,
        'rural' => 0.9,
        'risque' => 1.6
    ],
    'securite' => [
        'alarme' => 0.9,
        'detecteur_fumee' => 0.95,
        'surveillance' => 0.85
    ],
    'nb_occupants' => [
        '1-2' => 1.0,
        '3-4' => 1.1,
        '5+' => 1.25
    ]

];

// Calcul des coefficients
$coef_type = $facteurs['type_logement'][$data['type_logement']] ?? 1.0;
$coef_materiaux = $facteurs['materiaux'][$data['materiaux']] ?? 1.0;
$coef_toiture = $facteurs['etat_toiture'][$data['etat_toiture']] ?? 1.0;
$coef_statut = $facteurs['statut_occupation'][$data['statut_occupation']] ?? 1.0;
$coef_localisation = $facteurs['localisation'][$data['localisation']] ?? 1.0;
$coef_occupants = $facteurs['nb_occupants'][$data['nb_occupants']] ?? 1.0;

// Calcul sécurité (multiplicatif)
$coef_securite = 1.0;
foreach ($data['securite'] as $securite) {
    $coef_securite *= $facteurs['securite'][$securite] ?? 1.0;
}

// Calcul superficie (non linéaire)
$coef_superficie = min(1.0 + ($data['superficie'] / 200), 2.5);

// Calcul capital mobilier
$coef_capital = min(1.0 + ($data['capital_mobilier'] / 5000000), 3.0);

// Calcul sinistres antérieurs
$coef_sinistres = 1.0 + ($data['antecedents'] * 0.1);

// Calcul prime
$primeNet = $primeBase 
    * $coef_type
    * $coef_materiaux
    * $coef_toiture
    * $coef_statut
    * $coef_localisation
    * $coef_securite
    * $coef_superficie
    * $coef_capital
    * $coef_sinistres
    * $coef_occupants;

// Application réductions/surcharges
$prime = $primeNet * (1 - $data['reduction']/100) * (1 + $data['surcharge']/100);

echo json_encode([
    'success' => true,
    'primeNet' => round($primeNet, 2),
    'prime' => round($prime, 2),
    'franchise' => round($franchise, 2),
]);
?>