<?php
require_once('contrat_pdf.php');

class ContratCyberAssurance extends ContratPDF {
    // Titre spécifique pour les contrats cyber
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE CYBER';
    }

    // Afficher les garanties spécifiques cyber
    public function addGarantiesCyber($nom_garantie, $description) {
        $this->SectionTitle('FORMULE ET GARANTIES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $nom_garantie, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Garanties incluses :', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        $garanties = explode(',', $description);
        foreach ($garanties as $garantie) {
            $garantie = trim($garantie);
            if (!empty($garantie)) {
                $this->Cell(10);
                $this->Cell(5, 5, '-', 0, 0);
                $this->MultiCell(0, 5, $garantie);
            }
        }
        $this->Ln(10);
    }

    // Afficher les détails spécifiques du contrat cyber
    public function addCyberDetails($taille_entreprise, $secteur_activite, $chiffre_affaires, $niveau_securite, $historique_attaques, $donnees_sensibles) {
        $this->SectionTitle('DÉTAILS DE L\'ASSURANCE CYBERATTAQUE');
        $this->InfoLine('Taille de l\'entreprise :', ucfirst($taille_entreprise));
        $this->InfoLine('Secteur d\'activité :', $secteur_activite);
        $this->InfoLine('Chiffre d\'affaires :', number_format($chiffre_affaires, 2, ',', ' ') . ' DZD');
        $this->InfoLine('Niveau de sécurité :', ucfirst($niveau_securite));
        $this->InfoLine('Historique des cyberattaques :', ucfirst($historique_attaques));
        $this->InfoLine('Données sensibles :', ucfirst($donnees_sensibles));
        $this->Ln(5);
    }
}

if (!isset($_GET['contrat']) || !is_numeric($_GET['contrat'])) {
    die("Numéro de contrat invalide.");
}
$id_contrat = $_GET['contrat'];

require 'db.php';

try {
    mysqli_set_charset($conn, "utf8");
    $stmt = $conn->prepare("
        SELECT c.*, ac.*, cl.*, g.nom_garantie, g.description
        FROM contrats c
        JOIN assurance_cyberattaque ac ON c.id_contrat = ac.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        JOIN garanties g ON ac.id_garantie = g.id_garantie
        WHERE c.id_contrat = ?
    ");
    $stmt->bind_param("i", $id_contrat);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die("Contrat introuvable.");
    }
    $contrat = $result->fetch_assoc();
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Définir la prime de base localement
    $primeBase = match ($contrat['taille_entreprise']) {
        'petite' => 50000,
        'moyenne' => 150000,
        'grande' => 300000,
        default => 50000,
    };

$coef_taille_entreprise = match ($contrat['taille_entreprise']) {
    'petite' => 0.8,
    'moyenne' => 1.0,
    'grande' => 1.3,
    default => 1.0,
} ;

$coef_donnees_sensibles = match ($contrat['donnees_sensibles']) {
    'aucune' => 0.9,
    'personnelles' => 1.1,
    'financieres' => 1.3,
    'confidentielles' => 1.5,
    default => 1.0,
};

$coef_niveau_securite = match ($contrat['niveau_securite']) {
    'basique' => 1.3,
    'intermediaire' => 1.0,
    'avance' => 0.8,
    default => 1.0,
};

$coef_historique_attaques = match ($contrat['historique_cyberattaques']) {
    'aucun' => 0.9,
    'mineur' => 1.2,
    'majeur' => 1.5,
    default => 1.0,
};

$coefficients = [
    'Taille de l\'entreprise' => $coef_taille_entreprise,
    'Données sensibles' => $coef_donnees_sensibles,
    'Niveau de sécurité' => $coef_niveau_securite,
    'Historique des cyberattaques' => $coef_historique_attaques
];

$pdf = new ContratCyberAssurance();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SectionTitle('INFORMATIONS GÉNÉRALES');
$pdf->InfoLine('Numéro du contrat :', $contrat['numero_contrat']);
$pdf->InfoLine('Date de souscription :', date("d/m/Y", strtotime($contrat['date_souscription'])));
$pdf->InfoLine('Date d\'expiration :', date("d/m/Y", strtotime($contrat['date_expiration'])));
$pdf->InfoLine('Prime annuelle :', number_format($contrat['montant_prime'], 2, ',', ' ') . ' DZD');
$pdf->Ln(5);

$pdf->SectionTitle('INFORMATIONS DU SOUSCRIPTEUR');
$pdf->InfoLine('Nom et prénom :', $contrat['nom_client'] . ' ' . $contrat['prenom_client']);
$pdf->InfoLine('Téléphone :', $contrat['telephone']);
$pdf->InfoLine('Email :', $contrat['email']);
$pdf->InfoLine('Date de Naissance :', date('d/m/Y', strtotime($contrat['date_naissance'])));
$pdf->Ln(5);

$pdf->addCyberDetails(
    $contrat['taille_entreprise'],
    $contrat['secteur_activite'],
    $contrat['chiffre_affaires'],
    $contrat['niveau_securite'],
    $contrat['historique_cyberattaques'],
    $contrat['donnees_sensibles']
);

$pdf->addPrimeDetails(
    $primeBase,
    $contrat['reduction'],
    $contrat['surcharge'],
    $contrat['montant_prime'],
    $coefficients
);

$pdf->addGarantiesCyber($contrat['nom_garantie'], $contrat['description']);

$pdf->AddSignatureBlock();
$pdf->SetTitle('Contrat d\'assurance cyberattaque');
$pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
?>