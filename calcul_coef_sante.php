<?php
/**
 * Fichier contenant les fonctions de calcul des coefficients d'assurance santé
 */

// Facteurs de calcul pour l'assurance santé
function getFacteursSante() {
    return [
        'etat_sante' => [
            'bon' => 1.0,
            'moyen' => 1.3,
            'mauvais' => 1.7
        ],
        'antecedents' => [
            'diabete' => 0.25,
            'hypertension' => 0.15,
            'cancer' => 0.4,
            'maladie_cardiaque' => 0.3,
            'asthme' => 0.2
        ],
        'fumeur' => [
            'non' => 1.0,
            'occasionnel' => 1.1,
            'regulier' => 1.4
        ]
    ];
}

// Calcul de l'âge de l'assuré
function calculerAgeAssure($date_naissance) {
    $annee_naissance = date('Y', strtotime($date_naissance));
    $annee_actuelle = date('Y');
    return $annee_actuelle - $annee_naissance;
}

// Calcul de l'IMC
function calculerIMC($poids, $taille) {
    if ($taille <= 0) return 0;
    return $poids / (($taille / 100) ** 2);
}

// Calcul de tous les coefficients santé
function calculerCoefficientsSante($data) {
    $coefficients = [];
    $facteurs = getFacteursSante();
    
    // Âge de l'assuré (coefficient progressif)
    $age = calculerAgeAssure($data['date_naissance']);
    $coefficients['coef_age'] = min(1.0 + ($age / 100), 2.0); // Max 2.0 - Le risque médical augmente progressivement avec l'âge

    // IMC (Indice de Masse Corporelle)
    $imc = calculerIMC($data['poids'], $data['taille']);
    if ($imc < 18.5) {
        $coefficients['coef_imc'] = 1.2;
    } elseif ($imc > 30) {
        $coefficients['coef_imc'] = 1.4;
    } else {
        $coefficients['coef_imc'] = 1.0;
    }

    // État de santé général
    $coefficients['coef_etat_sante'] = $facteurs['etat_sante'][$data['etat_sante']] ?? 1.0;

    // Antécédents médicaux (somme des risques)
    $antecedents = $data['antecedents'] ?? [];
    $coef_antecedents = 1.0;
    foreach ($antecedents as $antecedent) {
        $coef_antecedent = $facteurs['antecedents'][$antecedent] ?? 0;
        $coef_antecedents += $coef_antecedent;
    }
    $coefficients['coef_antecedents'] = min($coef_antecedents, 2.0);

    // Fumeur
    $coefficients['coef_fumeur'] = $facteurs['fumeur'][$data['fumeur']] ?? 1.0;

    // Sexe
    $coefficients['coef_sexe'] = ($data['sexe'] ==='homme') ? 1.1 : 1.0;

   // Profession à risque
   $professions_risque = ['pompier', 'militaire', 'chauffeur', 'policier'];
   $coefficients['coef_profession'] = in_array(($data['profession']), $professions_risque) ? 1.5 : 1.0;
    return $coefficients;
}

// Calcul de la prime santé
function calculerPrimeSante($primeBase, $coefficients, $reduction = 0, $surcharge = 0) {
    $primeNet = $primeBase;
    
    foreach ($coefficients as $coef) {
        $primeNet *= $coef;
    }
    $prime = $primeNet * (1 - $reduction/100) * (1 + $surcharge/100);
    return [
        'primeNet' => round($primeNet, 2),
        'prime' => round($prime, 2)
    ];
}
?>