<?php
session_start();
require 'db.php';
require 'contrat_pdf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit();
}

// Vérifier si l'ID du contrat est fourni
if (!isset($_GET['contrat']) || !is_numeric($_GET['contrat'])) {
    error_log("Invalid contract ID: " . ($_GET['contrat'] ?? 'not set'));
    die("ID de contrat invalide.");
}
$contrat_id = intval($_GET['contrat']);
error_log("Processing contract ID: $contrat_id");

// Récupérer les détails du contrat
$stmt = $conn->prepare("
    SELECT 
        c.numero_contrat, c.date_souscription, c.date_expiration, c.montant_prime, c.reduction, c.surcharge,
        cl.nom_client, cl.prenom_client, cl.telephone, cl.email, cl.date_naissance,
        s.nom_eleve, s.prenom_eleve, s.date_naissance_eleve, s.type_etablissement, s.etat_sante, s.id_garantie,
        g.nom_garantie, g.description, g.prime_base, g.franchise
    FROM contrats c
    JOIN client cl ON c.id_client = cl.id_client
    JOIN assurance_scolarite s ON c.id_contrat = s.id_contrat
    JOIN garanties g ON s.id_garantie = g.id_garantie
    WHERE c.id_contrat = ?
");
$stmt->bind_param("i", $contrat_id);
if (!$stmt->execute()) {
    error_log("SQL execution error: " . $stmt->error);
    die("Erreur lors de l'exécution de la requête SQL.");
}
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Contract not found for ID: $contrat_id");
    die("Contrat non trouvé.");
}
$contract = $result->fetch_assoc();
error_log("Contract found: " . json_encode($contract));
$stmt->close();

// Ensure all string fields are UTF-8 encoded
foreach ($contract as $key => $value) {
    if (is_string($value)) {
        $contract[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}

// Calculer l'âge de l'élève pour les coefficients
$date_naissance_eleve = new DateTime($contract['date_naissance_eleve']);
$aujourdhui = new DateTime();
$age_eleve = $aujourdhui->diff($date_naissance_eleve)->y;

// Définir les coefficients (même logique que calcul_prime_scol.php)
$coef_age_eleve = ($age_eleve < 12) ? 0.9 : (($age_eleve > 18) ? 1.2 : 1.0);
$coef_type_etablissement = [
    'primaire' => 0.85,
    'collège' => 0.95,
    'lycée' => 1.0,
    'université' => 1.15,
][$contract['type_etablissement']] ?? 1.0;
$coef_etat_sante = [
    'bon' => 0.9,
    'fragile' => 1.2,
    'maladie_chronique' => 1.5,
][$contract['etat_sante']] ?? 1.0;

// Log coefficients for debugging
error_log("Coefficients calculated - coef_age_eleve: $coef_age_eleve, coef_type_etablissement: $coef_type_etablissement, coef_etat_sante: $coef_etat_sante");

// Créer une classe spécifique pour le contrat de scolarité
class ScolariteContratPDF extends ContratPDF {
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE SCOLAIRE';
    }

    // Afficher la garantie scolaire
    public function addGarantieScolaire($nom_garantie, $description, $franchise) {
        $this->SectionTitle('GARANTIE');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Nom de la garantie : ' . $nom_garantie, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . number_format($franchise, 2, ',', ' ') . ' DZD', 0, 1);
        $this->Ln(5);

        // Afficher la description sous forme de liste
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Description :', 0, 1);
        $this->SetFont('DejaVu', '', 10);

        // Découper et afficher les éléments de la description
        $items = explode(',', $description);
        foreach ($items as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $this->Cell(10); // Indentation
                $this->Cell(5, 5, '•', 0, 0); // Puces
                $this->MultiCell(0, 5, $item);
            }
        }
        $this->Ln(10);
    }

    public function generateContract($contract, $coefficients) {
        $this->AliasNbPages();
        $this->AddPage();

        // Section: Informations générales
        $this->SectionTitle('INFORMATIONS GÉNÉRALES');
        $this->InfoLine('Numéro du contrat :', $contract['numero_contrat']);
        $this->InfoLine('Date de souscription :', date('d/m/Y', strtotime($contract['date_souscription'])));
        $this->InfoLine('Date d\'expiration :', date('d/m/Y', strtotime($contract['date_expiration'])));
        $this->InfoLine('Prime annuelle :', number_format($contract['montant_prime'], 2, ',', ' ') . ' DZD');
        $this->Ln(5);

        // Section: Informations du client
        $this->SectionTitle('INFORMATIONS DU SOUSCRIPTEUR');
        $this->InfoLine('Nom et prénom :', $contract['nom_client'] . ' ' . $contract['prenom_client']);
        $this->InfoLine('Téléphone :', $contract['telephone']);
        $this->InfoLine('Email :', $contract['email']);
        $this->InfoLine('Date de naissance :', date('d/m/Y', strtotime($contract['date_naissance'])));
        $this->Ln(5);

        // Section: Informations de l'élève
        $this->SectionTitle('INFORMATIONS DE L\'ÉLÈVE');
        $this->InfoLineDouble('Nom :', $contract['nom_eleve'], 'Prénom :', $contract['prenom_eleve']);
        $this->InfoLineDouble('Date de naissance :', date('d/m/Y', strtotime($contract['date_naissance_eleve'])), 'Âge :', $this->calculateAge($contract['date_naissance_eleve']) . ' ans');
        $this->InfoLineDouble('Type d\'établissement :', ucfirst($contract['type_etablissement']), 'État de santé :', $this->formatEtatSante($contract['etat_sante']));
        $this->Ln(5);

        // Détails de la prime
        $this->addPrimeDetails(
            $contract['prime_base'],
            $contract['reduction'],
            $contract['surcharge'],
            $contract['montant_prime'],
            $coefficients
        );

        // Section: Garantie
        $this->addGarantieScolaire($contract['nom_garantie'], $contract['description'], $contract['franchise']);

        // Section: Signature
        $this->AddSignatureBlock();
    }

    private function calculateAge($date_naissance) {
        $date = new DateTime($date_naissance);
        $today = new DateTime();
        return $today->diff($date)->y;
    }

    private function formatEtatSante($etat_sante) {
        $labels = [
            'bon' => 'Bon',
            'fragile' => 'Fragile',
            'maladie_chronique' => 'Maladie chronique'
        ];
        return $labels[$etat_sante] ?? $etat_sante;
    }
}

// Générer le PDF
$pdf = new ScolariteContratPDF();
error_log("Generating PDF for contract ID: $contrat_id");
$pdf->generateContract($contract, [
    'Âge de l\'élève' => $coef_age_eleve,
    'Type d\'établissement' => $coef_type_etablissement,
    'État de santé' => $coef_etat_sante
]);

// Nom du fichier avec numéro de contrat
$filename = 'contrat_scolarite_' . $contract['numero_contrat'] . '.pdf';
error_log("Outputting PDF: $filename");

// Sortie du PDF
$pdf->SetTitle($pdf->customUtf8Decode('Contrat d\'assurance scolaire'));
$pdf->Output('I', $filename);
?>