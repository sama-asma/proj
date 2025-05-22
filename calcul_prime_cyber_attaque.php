<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée', 'prime' => 0, 'primeNet' => 0]);
    exit;
}

// Récupération des données du formulaire
$data = [
    'taille_entreprise' => $_POST['taille_entreprise'] ?? null,
    'donnees_sensibles' => $_POST['donnees_sensibles'] ?? null,
    'niveau_securite' => $_POST['niveau_securite'] ?? null,
    'historique_attaques' => $_POST['historique_attaques'] ?? null,
    'id_garantie' => intval($_POST['id_garantie'] ?? 0),
    'reduction' => floatval($_POST['reduction'] ?? 0),
    'surcharge' => floatval($_POST['surcharge'] ?? 0),
];

// Validation côté serveur
$errors = [];

if (!in_array($data['donnees_sensibles'], ['aucune', 'personnelles', 'financieres', 'confidentielles'])) {
    $errors[] = "Type de données sensibles invalide";
}
if (!in_array($data['niveau_securite'], ['basique', 'intermediaire', 'avance'])) {
    $errors[] = "Niveau de sécurité invalide";
}
if (!in_array($data['historique_attaques'], ['aucun', 'mineur', 'majeur'])) {
    $errors[] = "Historique des cyberattaques invalide";
}
if ($data['id_garantie'] <= 0) {
    $errors[] = "Garantie non sélectionnée";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors), 'prime' => 0, 'primeNet' => 0]);
    exit;
}

// Définir la prime de base selon la taille de l'entreprise 

    $primeBase = match ($data['taille_entreprise']) {
        'petite' => 50000,
        'moyenne' => 150000,
        'grande' => 300000,
        default => 50000, // Fallback
    };

// Coefficients des facteurs de risque
    $coef_taille_entreprise = match ($data['taille_entreprise']) {
        'petite' => 0.8,
        'moyenne' => 1.0,
        'grande' => 1.3,
        default => 1.0,
    };
$coef_donnees_sensibles = match ($data['donnees_sensibles']) {
    'aucune' => 0.9,
    'personnelles' => 1.1,
    'financieres' => 1.3,
    'confidentielles' => 1.5,
    default => 1.0,
};

$coef_niveau_securite = match ($data['niveau_securite']) {
    'basique' => 1.3,
    'intermediaire' => 1.0,
    'avance' => 0.8,
    default => 1.0,
};

$coef_historique_attaques = match ($data['historique_attaques']) {
    'aucun' => 0.9,
    'mineur' => 1.2,
    'majeur' => 1.5,
    default => 1.0,
};

// Calcul de la prime nette
$primeNet = $primeBase * $coef_taille_entreprise * $coef_donnees_sensibles * $coef_niveau_securite * $coef_historique_attaques;

// Application des réductions/surcharges
$prime = $primeNet * (1 - $data['reduction'] / 100) * (1 + $data['surcharge'] / 100);
if ($prime <= 0) {
    echo json_encode(['success' => false, 'message' => 'La prime calculée doit être positive', 'prime' => 0, 'franchise' => 0, 'primeNet' => 0]);
    exit;
}

// Retourner le résultat
echo json_encode([
    'success' => true,
    'primeNet' => round($primeNet, 2),
    'prime' => round($prime, 2),
    'details' => "Calcul basé sur la taille de l'entreprise, les données sensibles, le niveau de sécurité et l'historique des cyberattaques"
]);
?>