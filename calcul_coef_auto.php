<?php
/**
 * Fichier contenant les fonctions de calcul des coefficients d'assurance auto
 */

// Facteurs de calcul pour les modèles de véhicules
function getFacteursVehicule() {
    return [
        'Renault' => [
            'Clio' => 0.85,
            'Megane' => 0.95,
            'Kadjar' => 1.1
        ],
        'Peugeot' => [
            '208' => 0.9,
            '308' => 1.0,
            '3008' => 1.15
        ],
        'Citroën' => [
            'C3' => 0.88,
            'C4' => 0.98,
            'C5 Aircross' => 1.12
        ],
        'Volkswagen' => [
            'Golf' => 0.92,
            'Passat' => 1.05,
            'Tiguan' => 1.18
        ],
        'BMW' => [
            'Série 1' => 1.1,
            'Série 3' => 1.25,
            'X3' => 1.4
        ]
    ];
}

// Calcul de l'âge du conducteur
function calculerAgeConducteur($date_naissance) {
    $date_naissance_obj = new DateTime($date_naissance);
    $aujourdhui = new DateTime();
    return $aujourdhui->diff($date_naissance_obj)->y; // y pour récupérer l'année
}

// Calcul de tous les coefficients
function calculerCoefficients($data) {
    $coefficients = [];
    $facteurs_vehicule = getFacteursVehicule();
    $current_year = date('Y');
    
    // Si l'âge du conducteur n'est pas déjà calculé, le calculer
    if (!isset($data['age_conducteur']) && isset($data['date_naissance'])) {
        $data['age_conducteur'] = calculerAgeConducteur($data['date_naissance']);
    }
    
    // Coefficient modèle
    $coefficients['coef_modele'] = $facteurs_vehicule[$data['marque_vehicule']][$data['type_vehicule']] ?? 1.0;
    
    // Coefficient puissance
    $puissance = $data['puissance'];
    $coefficients['coef_puissance'] = ($puissance < 7) ? 0.8 : (($puissance > 10) ? 1.3 : 1.0);
    
    // Coefficient âge véhicule
    $annee_vehicule =  $data['annee_vehicule'] ?? date('Y');
    $coefficients['coef_age_vehicule'] = ($current_year - $annee_vehicule < 3) ? 0.9 : 
                                        (($current_year - $annee_vehicule > 10) ? 1.2 : 1.0);
    
    // Coefficient stationnement
    $coefficients['coef_stationnement'] = ($data['stationnement'] == 'garage') ? 0.8 : 
                                       (($data['stationnement'] == 'parking privé') ? 1.1 : 1.3);
    
    // Coefficient âge conducteur
    $coefficients['coef_age_conducteur'] = ($data['age_conducteur'] < 26) ? 1.4 : 
                                          (($data['age_conducteur'] > 65) ? 1.3 : 1.0);
    
    // Coefficient expérience
    $experience = $data['experience'];
    $coefficients['coef_experience'] = ($experience < 2) ? 1.5 : 
                                      (($experience > 10) ? 0.8 : 1.0);
    
    // Coefficient usage
    $usage =  $data['usage'];
    $coefficients['coef_usage'] = ($usage == 'professionnel') ? 1.3 : 1.0;
    
    // Coefficient environnement
    $environnement = $data['environnement'];
    $coefficients['coef_environnement'] = ($environnement == 'urbain') ? 1.3 : 
                                         (($environnement == 'rural') ? 0.9 : 1.0);
    
    return $coefficients;
}

// Calcul de la prime
function calculerPrime($primeBase, $coefficients, $bonus_malus, $reduction = 0, $surcharge = 0) {
    $primeNet = $primeBase;
    
    // Multiplier par tous les coefficients
    foreach ($coefficients as $coef) {
        $primeNet *= $coef;
    }
    
    // Appliquer le bonus/malus
    $primeNet *= $bonus_malus;
    
    // Appliquer réduction et surcharge
    $prime = $primeNet * (1 - $reduction/100) * (1 + $surcharge/100);
    
    return [
        'primeNet' => round($primeNet, 2),
        'prime' => round($prime, 2)
    ];
}
?>