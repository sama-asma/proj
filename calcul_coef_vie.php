<?php
/**
 * Fichier de calcul des coefficients et primes pour l'assurance vie - Version corrigée
 */

function calculerAge($date_naissance) {
    $date_naissance_obj = new DateTime($date_naissance);
    $aujourdhui = new DateTime();
    return $aujourdhui->diff($date_naissance_obj)->y;
}

function calculerCoeff($data) {
    // Coefficients de base plus réalistes
    $age = calculerAge($data['date_naissance']);
    
    // Coefficient âge (augmentation progressive après 40 ans)
    $coef_age = ($age <= 40) ? 1.0 : min(1.0 + (($age - 40) * 0.03), 2.0);
    
    // Coefficient état de santé
    $coef_etat_sante = match($data['etat_sante']) {
        'bon' => 1.0,
        'moyen' => 1.3,
        'mauvais' => 1.8,
        default => 1.0
    };

    // Antécédents médicaux (max 1.5)
    $coef_antecedents = 1.0;
    foreach ($data['antecedents'] ?? [] as $antecedent) {
        $coef_antecedents += match($antecedent) {
            'diabete' => 0.15,
            'hypertension' => 0.10,
            'cancer' => 0.25,
            'maladie_cardiaque' => 0.20,
            'asthme' => 0.05,
            'avc' => 0.30,
            default => 0
        };
    }
    $coef_antecedents = min($coef_antecedents, 1.5);

    // Fumeur
    $coef_fumeur = match($data['fumeur']) {
        'non' => 1.0,
        'occasionnel' => 1.2,
        'regulier' => 1.5,
        default => 1.0
    };

    // Sexe
    $coef_sexe = match($data['sexe']) {
        'Homme' => 1.1,
        'Femme' => 1.0
    };

    // Profession à risque
    $professions_risque = ['pompier', 'militaire', 'chauffeur', 'policier'];
    $coef_profession = in_array($data['profession'], $professions_risque) ? 1.4 : 1.0;

    // Capital (diminution du coefficient pour les gros capitaux)
    $capital = $data['capital'] ?? 0;
    $coef_capital = ($capital <= 1000000) ? 1.0 : min(1.0 + ($capital / 10000000), 1.3);

    return [
        'coef_age' => $coef_age,
        'coef_etat_sante' => $coef_etat_sante,
        'coef_antecedents' => $coef_antecedents,
        'coef_fumeur' => $coef_fumeur,
        'coef_sexe' => $coef_sexe,
        'coef_profession' => $coef_profession,
        'coef_capital' => $coef_capital
    ];
}
function calculerPrimeBase($capital, $primeBase, $franchise = 0) {
     // Calcul du capital garanti après franchise
    $capital_garanti = $capital * (1 - ($franchise / 100));
    $primeBaseCalcule = $primeBase * ($capital_garanti / 100000);
    // Calcul de la prime de base
    return [
        'primeBaseCalcule' => round($primeBaseCalcule, 2),
        'capital_garanti' => round($capital_garanti, 2)
    ];

}

function calculerPrimeVie($coefficients, $primeBaseCalcule, $reduction = 0, $surcharge = 0) {
       
    // Application des coefficients multiplicatifs
    $primeNet = $primeBaseCalcule;
    foreach ($coefficients as $coef) {
        $primeNet *= $coef;
    }
    
    // Application des ajustements (réduction/surcharge)
    $primeFinale = $primeNet * (1 - ($reduction/100)) * (1 + ($surcharge/100));
    
    return [
        'primeNet' => round($primeNet, 2),
        'prime' => round($primeFinale, 2)
    ];
}
?>