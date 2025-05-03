<?php
require_once('contrat_pdf.php');

class ContratScolariteAssurance extends ContratPDF {
    // Titre spécifique pour les contrats scolaires
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE SCOLAIRE';
    }

    // Afficher les garanties spécifiques scolaires
    public function addGarantiesScolaire($nom_garantie, $description, $franchise) {
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
        SELECT c.*, s.*, cl.*, g.nom_garantie, g.description, g.prime_base, g.franchise
        FROM contrats c
        JOIN assurance_scolarite s ON c.id_contrat = s.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        JOIN garanties g ON s.id_garantie = g.id_garantie
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

// Calculer l'âge de l'élève pour les coefficients
$date_naissance_eleve = new DateTime($contrat['date_naissance_eleve']);
$aujourdhui = new DateTime();
$age_eleve = $aujourdhui->diff($date_naissance_eleve)->y;

// Définir les coefficients
$coef_age_eleve = ($age_eleve < 12) ? 0.9 : (($age_eleve > 18) ? 1.2 : 1.0);
$coef_type_etablissement = [
    'primaire' => 0.85,
    'collège' => 0.95,
    'lycée' => 1.0,
    'université' => 1.15,
][$contrat['type_etablissement']] ?? 1.0;
$coef_etat_sante = [
    'bon' => 0.9,
    'fragile' => 1.2,
    'maladie_chronique' => 1.5,
][$contrat['etat_sante']] ?? 1.0;

// Préparer les coefficients pour l'affichage
$coefficients = [
    'Âge de l\'élève' => $coef_age_eleve,
    'Type d\'établissement' => $coef_type_etablissement,
    'État de santé' => $coef_etat_sante
];

// Création du PDF
$pdf = new ContratScolariteAssurance();
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

// Informations de l'élève
$pdf->SectionTitle('ÉLÈVE ASSURÉ');
$pdf->InfoLineDouble('Nom :', $contrat['nom_eleve'], 'Prénom :', $contrat['prenom_eleve']);
$pdf->InfoLineDouble('Date de naissance :', date('d/m/Y', strtotime($contrat['date_naissance_eleve'])), 'Âge :', $age_eleve . ' ans');
$pdf->InfoLineDouble('Type d\'établissement :', ucfirst($contrat['type_etablissement']), 'État de santé :', ucfirst($contrat['etat_sante']));
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
$pdf->addGarantiesScolaire($contrat['nom_garantie'], $contrat['description'], $contrat['franchise']);

// Signatures
$pdf->AddSignatureBlock();
$pdf->SetTitle('Contrat d\'assurance scolaire');
$pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
?>