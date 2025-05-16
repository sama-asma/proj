<?php

// Facteurs de calcul pour les types de logement
function getFacteursHabitation() {
    return [
        'materiaux' => [
            'resistant' => 0.8,  // Béton armé, pierre
            'standard' => 1.0,   // Parpaing, brique
            'fragile' => 1.3     // Bois, terre
        ],
        'etat_toiture' => [
            'neuf' => 0.85, // < 5 ans
            'bon' => 1.0,        // 5-10 ans
            'moyen' => 1.15,     // 10-20 ans
            'mauvais' => 1.4     // > 20 ans
        ],
        'statut_occupation' => [
            'proprietaire' => 1.1,
            'locataire' => 0.9
        ],
        'localisation' => [
            'urbain' => 1.2,
            'rural' => 0.9,
            'risque' => 1.5      // Zone inondable, sismique
        ],
        'securite' => [
            'alarme' => 0.9,
            'detecteur fumee' => 0.95,
            'surveillance' => 0.85
        ],
        'nb_occupants' => [
            '1-2' => 1.0,
            '3-4' => 1.1,
            '5+' => 1.25
        ],
        'age_logement' => [
            '1-5' => 0.9,
            '6-10' => 1.0,
            '11-20' => 1.1,
            '21+' => 1.2
        ]
    ];
}

// Calcul de l'âge du logement
function calculerAgeLogement($annee_construction) {
    $current_year = date('Y');
    return $current_year - $annee_construction;
}

// Calcul de tous les coefficients pour l'habitation
function calculerCoefficientsHabitation($data) {
    $coefficients = [];
    $facteurs = getFacteursHabitation();
    $current_year = date('Y');
    
    if (!isset($data['age_logement']) && isset($data['annee_construction'])) {
        $data['age_logement'] = calculerAgeLogement($data['annee_construction']);
    }
    
    // Coefficient type de logement
    $coefficients['coef_type'] = ($data['type_logement'] ==='maison') ? 1.1 : 0.9;
    
    // Coefficient matériaux
    $coefficients['coef_materiaux'] = $facteurs['materiaux'][$data['materiaux']] ?? 1.0;
    
    // Coefficient état toiture
    $coefficients['coef_toiture'] = $facteurs['etat_toiture'][$data['etat_toiture']] ?? 1.0;
    
    // Coefficient statut occupation
    $coefficients['coef_statut'] = $facteurs['statut_occupation'][$data['statut_occupation']] ?? 1.0;
    
    // Coefficient localisation
    $coefficients['coef_localisation'] = $facteurs['localisation'][$data['localisation']] ?? 1.0;
    // Coefficient occupation
    $coefficients['coef_occupation'] = ($data['occupation'] === 'secondaire') ? 1.3 : 1.0;

    
    // Coefficient sécurité (multiplicatif)
    $coefficients['coef_securite'] = 1.0;
    if (!empty($data['securite'])) {
        foreach ($data['securite'] as $securite) {
            $coefficients['coef_securite'] *= $facteurs['securite'][$securite] ?? 1.0;
        }
    }
    
    // Coefficient nombre d'occupants
    $nb_occupants = $data['nb_occupants'] ?? 1;
    if ($nb_occupants <= 2) {
        $coefficients['coef_occupants'] = $facteurs['nb_occupants']['1-2'];
    } elseif ($nb_occupants <= 4) {
        $coefficients['coef_occupants'] = $facteurs['nb_occupants']['3-4'];
    } else {
        $coefficients['coef_occupants'] = $facteurs['nb_occupants']['5+'];
    }
    
    // Coefficient âge du logement
    $age_logement = $data['age_logement'] ?? 0;
    if ($age_logement <= 5) {
        $coefficients['coef_age_logement'] = $facteurs['age_logement']['1-5'];
    } elseif ($age_logement <= 10) {
        $coefficients['coef_age_logement'] = $facteurs['age_logement']['6-10'];
    } elseif ($age_logement <= 20) {
        $coefficients['coef_age_logement'] = $facteurs['age_logement']['11-20'];
    } else {
        $coefficients['coef_age_logement'] = $facteurs['age_logement']['21+'];
    }
    
    // Coefficient superficie (non linéaire)
    $superficie = $data['superficie'] ?? 0;
    $coefficients['coef_superficie'] = min(1.0 + ($superficie / 250), 2.3);    
   
    // Coefficient capital mobilier
    $capital = $data['capital_mobilier'] ?? 0;
    $coefficients['coef_capital'] = round(min(1.0 + ($capital / 1000000), 2.8), 2);

    // Coefficient sinistres antérieurs
    $antecedents = $data['antecedents'] ?? 0;
    $coefficients['coef_sinistres'] = 1.0 + ($antecedents * 0.1);
    return $coefficients;
}

// Calcul de la prime habitation
function calculerPrimeHabitation($primeBase, $coefficients, $reduction = 0, $surcharge = 0) {
    $primeNet = $primeBase;
    // Multiplier par tous les coefficients
    foreach ($coefficients as $coef) {
        $primeNet *= $coef;
    }
    
    // Appliquer réduction et surcharge
    $prime = $primeNet * (1 - $reduction/100) * (1 + $surcharge/100);
    
    return [
        'primeNet' => round($primeNet, 2),
        'prime' => round($prime, 2)
    ];
}
?>