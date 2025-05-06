<?php
require_once('contrat_pdf.php');

class ContratProtectionJuridiqueAssurance extends ContratPDF {
    // Titre spécifique pour les contrats de protection juridique
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE PROTECTION JURIDIQUE';
    }

    // Override pour afficher le titre avec MultiCell et police plus petite
    public function AddTitle() {
        $this->SetMargins(20, 15, 20); // Marges ajustées
        $this->SetFont('DejaVu', 'B', 12); // Réduire la taille de la police à 12
        $this->MultiCell(0, 10, $this->getContractTitle(), 0, 'C'); // Utiliser MultiCell pour le retour à la ligne
        $this->Ln(10);
    }

    // Afficher les garanties spécifiques à la protection juridique
    public function addGarantiesProtectionJuridique($nom_garantie, $description, $franchise) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $nom_garantie, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . number_format($franchise, 2, ',', ' ') . ' DZD', 0, 1);
        // Ajout de la phrase après la franchise
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 6, 'La franchise correspond au montant à la charge du souscripteur en cas de sinistre.');
        $this->Ln(5);
        // Afficher les garanties sous forme de liste
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Garanties incluses :', 0, 1);
        $this->SetFont('DejaVu', '', 10);

        // Découper et afficher les garanties
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
$id_contrat = $_GET['contrat'];

require 'db.php';

// Connexion à la base de données et récupération des données
try {
    // Assurer que la connexion utilise UTF-8
    mysqli_set_charset($conn, "utf8");
    // Récupération des informations du contrat
    $stmt = $conn->prepare("
        SELECT c.*, p.*, cl.*, g.nom_garantie, g.description, g.prime_base, g.franchise
        FROM contrats c
        JOIN assurance_protection_juridique p ON c.id_contrat = p.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        JOIN garanties g ON p.id_garantie = g.id_garantie
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

// Définir les coefficients
$coef_situation_pro = [
    'salarie' => 1.0,
    'independant' => 1.2,
    'retraite' => 0.9,
    'sans_emploi' => 1.1,
][$contrat['situation_pro']] ?? 1.0;

$coef_secteur_activite = [
    'agriculture' => 0.9,
    'industries_extractives' => 1.4,
    'industrie_manufacturiere' => 1.2,
    'commerce' => 0.95,
    'information_communication' => 1.1,
    'sante_humaine' => 1.5,
    'activites_extra_territoriales' => 1.3,
    'education' => 0.9,
][$contrat['secteur_activite']] ?? 1.0;

$coef_type_litige = [
    'personnel' => 1.0,
    'professionnel' => 1.3,
    'mixte' => 1.5,
][$contrat['type_litige']] ?? 1.0;

$coef_frequence_litige = [
    'rare' => 0.9,
    'occasionnel' => 1.0,
    'frequent' => 1.2,
][$contrat['frequence_litige']] ?? 1.0;

// Préparer les coefficients pour l'affichage
$coefficients = [
    'Situation professionnelle' => $coef_situation_pro,
    'Secteur d\'activité' => $coef_secteur_activite,
    'Type de litige' => $coef_type_litige,
    'Fréquence des litiges' => $coef_frequence_litige
];

// Création du PDF
$pdf = new ContratProtectionJuridiqueAssurance();
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
$pdf->InfoLine('Téléphone :', $contrat['telephone']);
$pdf->InfoLine('Email :', $contrat['email']);
$pdf->InfoLine('Date de Naissance :', date('d/m/Y', strtotime($contrat['date_naissance'])));
$pdf->Ln(5);

// Informations spécifiques à l'assurance protection juridique
$pdf->SectionTitle('DÉTAILS DE L\'ASSURANCE');
$pdf->InfoLineDouble('Situation professionnelle :', ucfirst($contrat['situation_pro']), 'Secteur d\'activité :', ucfirst($contrat['secteur_activite']));
$pdf->InfoLineDouble('Type de litige :', ucfirst($contrat['type_litige']), 'Fréquence des litiges :', ucfirst($contrat['frequence_litige']));
$pdf->Ln(5);

// Détails de la prime
$pdf->addPrimeDetails(
    $contrat['prime_base'],
    $contrat['reduction'],
    $contrat['surcharge'],
    $contrat['montant_prime'],
    $coefficients
);

// Garanties
$pdf->addGarantiesProtectionJuridique($contrat['nom_garantie'], $contrat['description'], $contrat['franchise']);

// Signatures
$pdf->AddSignatureBlock();
$pdf->SetTitle('Contrat d\'assurance protection juridique');
$pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
?>