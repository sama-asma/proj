<?php
require_once('contrat_pdf.php');

class ContratEmpruntAssurance extends ContratPDF {
    // Titre spécifique pour les contrats d'assurance emprunteur
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE EMPRUNTEUR';
    }

    // Afficher les détails spécifiques de l'emprunt
    public function addEmpruntDetails($montant_emprunt, $duree_emprunt, $type_pret, $taux_interet, $etat_sante, $fumeur, $situation_professionnelle, $revenu_mensuel) {
        $this->SectionTitle('DÉTAILS DE L\'EMPRUNT');
        $montant_emprunt = is_numeric($montant_emprunt) ? $montant_emprunt : 0;
        $duree_emprunt = is_numeric($duree_emprunt) ? $duree_emprunt : 0;
        $taux_interet = is_numeric($taux_interet) ? $taux_interet : 0;
        $revenu_mensuel = is_numeric($revenu_mensuel) ? $revenu_mensuel : 0;
        $this->InfoLineDouble('Montant de l\'emprunt :', number_format($montant_emprunt, 2, ',', ' ') . ' DZD', 'Durée :', $duree_emprunt . ' ans');
        $this->InfoLineDouble('Type de prêt :', ucfirst($type_pret ?? 'N/A'), 'Taux d\'intérêt :', number_format($taux_interet, 2, ',', ' ') . ' %');
        $this->InfoLineDouble('État de santé :', ucfirst($etat_sante ?? 'N/A'), 'Fumeur :', ucfirst($fumeur ?? 'N/A'));
        $this->InfoLineDouble('Profession:', ucfirst($situation_professionnelle ?? 'N/A'), 'Revenu mensuel :', number_format($revenu_mensuel, 2, ',', ' ') . ' DZD');
        $this->Ln(5);
    }

    // Afficher les garanties spécifiques
    public function addGarantiesEmprunt($nom_garantie, $description, $type_pret) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . ($nom_garantie ?? 'N/A'), 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        // Calculer la franchise localement selon le type de prêt
        $franchise_par_type = [
            'immobilier' => 90,
            'consommation' => 60,
            'auto' => 30,
        ];
        $franchise = $franchise_par_type[$type_pret] ?? 90; // Par défaut 90 si type_pret inconnu
        error_log("Franchise calculée localement pour type_pret=$type_pret: $franchise");
        $this->Cell(0, 6, 'Franchise : ' . number_format($franchise) . ' jours', 0, 1);
        // Ajout de la phrase après la franchise
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 6, 'La franchise correspond au nombre de jours qui restent à la charge du souscripteur en cas de sinistre.');
        $this->Ln(5);
        // Afficher les garanties sous forme de liste
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Garanties incluses :', 0, 1);
        $this->SetFont('DejaVu', '', 10);

        // Découper et afficher les garanties
        $description = $description ?? '';
        $garanties = explode(',', $description);
        foreach ($garanties as $garantie) {
            $garantie = trim($garantie);
            if (!empty($garantie)) {
                $this->Cell(10); // Indentation
                $this->Cell(5, 5, '-', 0, 0); // Puces
                $this->MultiCell(0, 5, $garantie);
            }
        }
        $this->Ln(10);
    }
}

// Vérification de l'ID du contrat
if (!isset($_GET['contrat']) || !is_numeric($_GET['contrat'])) {
    die("Numéro de contrat invalide.");
}
$id_contrat = (int)$_GET['contrat'];

require 'db.php';

// Connexion à la base de données et récupération des données
try {
    // Assurer que la connexion utilise UTF-8
    if (!mysqli_set_charset($conn, "utf8")) {
        throw new Exception("Erreur lors de la définition du jeu de caractères : " . mysqli_error($conn));
    }
    // Récupération des informations du contrat
    $stmt = $conn->prepare("
        SELECT c.*, e.*, cl.*, g.nom_garantie, g.description, g.prime_base
        FROM contrats c
        JOIN assurance_emprunteur e ON c.id_contrat = e.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        JOIN garanties g ON e.id_garantie = g.id_garantie
        WHERE c.id_contrat = ?
    ");
    if (!$stmt) {
        throw new Exception("Erreur préparation requête : " . $conn->error);
    }
    $stmt->bind_param("i", $id_contrat);
    if (!$stmt->execute()) {
        throw new Exception("Erreur exécution requête : " . $stmt->error);
    }
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Contrat introuvable.");
    }

    $contrat = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Erreur récupération données contrat: " . $e->getMessage());
    die("Erreur lors de la récupération des données : " . htmlspecialchars($e->getMessage()));
}

// Calculer l'âge du client pour les coefficients
$date_naissance = new DateTime($contrat['date_naissance']);
$aujourdhui = new DateTime();
$age = $aujourdhui->diff($date_naissance)->y;

// Définir les coefficients
$coef_age = ($age < 30) ? 0.9 : (($age > 60) ? 1.5 : 1.0);
$coef_etat_sante = [
    'excellent' => 0.8,
    'bon' => 0.9,
    'moyen' => 1.1,
    'mauvais' => 1.4,
][$contrat['etat_sante']] ?? 1.0;
$coef_fumeur = [
    'oui' => 1.2,
    'non' => 0.95,
][$contrat['fumeur']] ?? 1.0;
$coef_situation_professionnelle = [
    'cdi' => 0.9,
    'fonctionnaire' => 0.85,
    'cdd' => 1.1,
    'independant' => 1.15,
    'sans_emploi' => 1.3,
][$contrat['situation_professionnelle']] ?? 1.0;
$coef_type_pret = [
    'immobilier' => 1.0,
    'consommation' => 1.1,
    'auto' => 1.05,
][$contrat['type_pret']] ?? 1.0;
$coef_montant_pret = ($contrat['montant_emprunt'] < 5000000) ? 1.0 : (($contrat['montant_emprunt'] > 20000000) ? 1.3 : 1.15);
$coef_duree_pret = ($contrat['duree_emprunt'] <= 5) ? 0.9 : (($contrat['duree_emprunt'] > 20) ? 1.3 : 1.1);
$coef_revenu_mensuel = ($contrat['revenu_mensuel'] < 200000) ? 1.2 : (($contrat['revenu_mensuel'] > 500000) ? 0.9 : 1.0);

// Préparer les coefficients pour l'affichage
$coefficients = [
    'Âge du client' => $coef_age,
    'État de santé' => $coef_etat_sante,
    'Fumeur' => $coef_fumeur,
    'Situation professionnelle' => $coef_situation_professionnelle,
    'Type de prêt' => $coef_type_pret,
    'Montant du prêt' => $coef_montant_pret,
    'Durée du prêt' => $coef_duree_pret,
    'Revenu mensuel' => $coef_revenu_mensuel
];

// Vérifications des données avant génération PDF
$required_fields = ['numero_contrat', 'date_souscription', 'date_expiration', 'montant_prime', 'nom_client', 'prenom_client', 'telephone', 'email', 'date_naissance', 'montant_emprunt', 'duree_emprunt', 'type_pret', 'taux_interet', 'etat_sante', 'fumeur', 'situation_professionnelle', 'revenu_mensuel', 'prime_base', 'reduction', 'surcharge', 'nom_garantie', 'description'];
foreach ($required_fields as $field) {
    if (!isset($contrat[$field])) {
        error_log("Champ manquant dans contrat: $field");
        die("Erreur : Données incomplètes pour générer le contrat (champ manquant : $field).");
    }
}

// Création du PDF
try {
    $pdf = new ContratEmpruntAssurance();
    $pdf->AliasNbPages(); // Pour {nb} dans le pied de page
    $pdf->AddPage();

    // Informations générales
    $pdf->SectionTitle('INFORMATIONS GÉNÉRALES');
    $pdf->InfoLine('Numéro du contrat :', $contrat['numero_contrat']);
    $pdf->InfoLine('Date de souscription :', date("d/m/Y", strtotime($contrat['date_souscription'])));
    $pdf->InfoLine('Date d\'expiration :', date("d/m/Y", strtotime($contrat['date_expiration'])));
    $pdf->InfoLine('Prime annuelle :', number_format($contrat['montant_prime'], 2, ',', ' ') . ' DZD');
    $pdf->Ln(5);

    // Informations du souscripteur
    $pdf->SectionTitle('INFORMATIONS DU SOUSCRIPTEUR');
    $pdf->InfoLine('Nom et prénom :', $contrat['nom_client'] . ' ' . $contrat['prenom_client']);
    $pdf->InfoLine('Date de naissance :', date("d/m/Y", strtotime($contrat['date_naissance'])));
    $pdf->InfoLine('Téléphone :', $contrat['telephone'] ?? 'N/A');
    $pdf->InfoLine('Email :', $contrat['email'] ?? 'N/A');
    $pdf->InfoLine('Âge :', $age . ' ans');
    $pdf->Ln(5);

    // Détails de l'emprunt
    $pdf->addEmpruntDetails(
        $contrat['montant_emprunt'],
        $contrat['duree_emprunt'],
        $contrat['type_pret'],
        $contrat['taux_interet'],
        $contrat['etat_sante'],
        $contrat['fumeur'],
        $contrat['situation_professionnelle'],
        $contrat['revenu_mensuel']
    );

    // Détails de la prime
    $pdf->addPrimeDetails(
        $contrat['prime_base'],
        $contrat['reduction'],
        $contrat['surcharge'],
        $contrat['montant_prime'],
        $coefficients
    );

    // Garanties
    $pdf->addGarantiesEmprunt($contrat['nom_garantie'], $contrat['description'], $contrat['type_pret']);

    // Signatures
    $pdf->AddSignatureBlock();
    $pdf->SetTitle('Contrat d\'assurance emprunteur');
    $pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
} catch (Exception $e) {
    error_log("Erreur génération PDF: " . $e->getMessage());
    die("Erreur lors de la génération du PDF : " . htmlspecialchars($e->getMessage()));
}
?>