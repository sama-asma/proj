<?php
require_once('contrat_pdf.php');
require_once('calcul_coef_sante.php');

class ContratSanteAssurance extends ContratPDF {
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE SANTÉ';
    }

    public function addGarantiesSante($formuleNom, $description, $franchise) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $formuleNom, 0, 1);
        $this->Ln(5);
        
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . $franchise . ' %', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 6, 'La franchise correspond au montant à la charge du souscripteur par acte médical.');
        $this->Ln(5);
        
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
    
    public function addDetailsSante($data) {
        $this->SectionTitle('PROFIL DE SANTÉ');
        
        $this->SetFont('DejaVu', '', 10);
        $this->InfoLineDouble('Âge :', $data['age'] . ' ans', 'État de santé :', ucfirst($data['etat_sante']));
        $this->InfoLineDouble('Sexe :', ucfirst($data['sexe']),'Fumeur :', ucfirst($data['fumeur']));
        $this->InfoLineDouble('IMC :', number_format($data['imc'], 2), 'Profession :',
         ucfirst(str_replace('_', ' ', $data['profession'])));
                 
        $this->Ln(5);
        
        // Affichage des antécédents
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Antécédents médicaux :', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        
        $antecedents = explode(',', $data['antecedents_medicaux']);
        foreach ($antecedents as $antecedent) {
            if (!empty($antecedent) && $antecedent !== 'aucun') {
                $this->Cell(10);
                $this->Cell(5, 5, '-', 0, 0);
                $this->MultiCell(0, 5, ucfirst($antecedent));
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

try {
    mysqli_set_charset($conn, "utf8");
    
    // Récupération des informations du contrat
    $stmt = $conn->prepare("
        SELECT c.*, s.*, cl.*
        FROM contrats c
        JOIN assurance_sante s ON c.id_contrat = s.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        WHERE c.id_contrat = ?
    ");
    $stmt->bind_param("i", $id_contrat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Contrat introuvable.");
    }

    $contrat = $result->fetch_assoc();

    // Récupération des garanties
    $stmt_garanties = $conn->prepare("
        SELECT g.nom_garantie, g.description, g.prime_base, g.franchise
        FROM garanties g
        JOIN assurance_sante s ON g.id_garantie = s.id_garantie
        WHERE s.id_contrat = ?
    ");
    $stmt_garanties->bind_param("i", $id_contrat);
    $stmt_garanties->execute();
    $result_garanties = $stmt_garanties->get_result();
    $garanties = $result_garanties->fetch_assoc();

    // Préparation des données pour l'affichage
    $data_sante = [
        'age' => calculerAgeAssure($contrat['date_naissance']),
        'sexe' => $contrat['sexe'],
        'imc' => calculerIMC($contrat['poids'], $contrat['taille']),
        'etat_sante' => $contrat['etat_sante'],
        'fumeur' => $contrat['fumeur'],
        'profession' => $contrat['profession'],
        'antecedents_medicaux' => $contrat['antecedents_medicaux']
    ];

    // Création du PDF
    $pdf = new ContratSanteAssurance();
    $pdf->AliasNbPages();
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

    // Détails santé
    $pdf->addDetailsSante($data_sante);

    // Calcul des coefficients
    $coefficients = calculerCoefficientsSante([
        'date_naissance' => $contrat['date_naissance'],
        'poids' => $contrat['poids'],
        'taille' => $contrat['taille'],
        'etat_sante' => $contrat['etat_sante'],
        'antecedents' => explode(',', $contrat['antecedents_medicaux']),
        'fumeur' => $contrat['fumeur'],
        'profession' => $contrat['profession'],
        'sexe' => $contrat['sexe']
    ]);

    // Détails de la prime
    $pdf->addPrimeDetails(
        $garanties['prime_base'],
        $contrat['reduction'],
        $contrat['surcharge'],
        $contrat['montant_prime'],
        $coefficients
    );

    // Garanties
    $pdf->addGarantiesSante($garanties['nom_garantie'], $garanties['description'], $garanties['franchise']);

    // Signatures
    $pdf->AddSignatureBlock();
    $pdf->SetTitle('Contrat d\'assurance santé');
    $pdf->Output('Contrat_Sante_' . $contrat['numero_contrat'] . '.pdf', 'I');

} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>