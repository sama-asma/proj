<?php
require_once('contrat_pdf.php');
require_once('calcul_coef_vie.php');

class ContratVieAssurance extends ContratPDF {
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE VIE';
    }

    public function addGarantiesVie($formuleNom, $description, $capital_garanti) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $formuleNom, 0, 1);
        $this->Ln(5);
        
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Capital garanti : ' . number_format($capital_garanti, 2, ',', ' ') . ' DZD', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 6, 'Le capital garanti correspond au montant qui sera versé aux bénéficiaires en cas de décès ou d\'invalidité selon les termes du contrat.');
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
    
    public function addDetailsVie($data) {
        $this->SectionTitle('PROFIL ASSURÉ');
        
        $this->SetFont('DejaVu', '', 10);
        $this->InfoLineDouble('Âge :', $data['age'] . ' ans', 'État de santé :', ucfirst($data['etat_sante']));
        $this->InfoLineDouble('Sexe :', ucfirst($data['sexe']), 'Fumeur :', ucfirst($data['fumeur']));
        $this->InfoLineDouble('Capital souhaité :', number_format($data['capital_souhaite'], 2, ',', ' ') . ' DZD', 
                             'Profession :', ucfirst(str_replace('_', ' ', $data['profession'])));
                 
        $this->Ln(5);
        
        $this->SetFont('DejaVu', 'B', 10);
        // Affichage des antécédents
        if ($data['antecedents_medicaux'] == 'aucun'){
            $this->InfoLine('Antécédents médicaux :', 'aucun');    
        }
        else{
            $this->Cell(0, 6, 'Antécédents médicaux :', 0, 1);
            $this->SetFont('DejaVu', '', 10);
            
            foreach ($data['antecedents_medicaux'] as $antecedent) {
                if (!empty($antecedent) && $antecedent !== 'aucun') {
                    $this->Cell(10);
                    $this->Cell(5, 5, '-', 0, 0);
                    $this->MultiCell(0, 5, ucfirst($antecedent));
                }
            }
        }
        $this->Ln(5);
        
        // Affichage des bénéficiaires
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Bénéficiaires :', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        
        if (!empty($data['beneficiaires'])) {
            foreach ($data['beneficiaires'] as $benef) {
                $this->Cell(10);
                $this->Cell(5, 5, '-', 0, 0);
                $this->MultiCell(0, 5, $benef['nom'] . ' (' . $benef['lien'] . ') - ' . $benef['part'] . '%');
            }
        } else {
            $this->Cell(10);
            $this->Cell(5, 5, '-', 0, 0);
            $this->MultiCell(0, 5, 'Héritiers légaux (selon les dispositions légales en vigueur)');
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
        SELECT c.*, v.*, cl.*
        FROM contrats c
        JOIN assurance_vie v ON c.id_contrat = v.id_contrat
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
        SELECT *
        FROM garanties g
        JOIN assurance_vie v ON g.id_garantie = v.id_garantie
        WHERE v.id_contrat = ?
    ");
    $stmt_garanties->bind_param("i", $id_contrat);
    $stmt_garanties->execute();
    $result_garanties = $stmt_garanties->get_result();
    $garanties = $result_garanties->fetch_assoc();

    // Préparation des données pour l'affichage
    $data_vie = [
        'age' => calculerAge($contrat['date_naissance']),
        'sexe' => $contrat['sexe'],
        'etat_sante' => $contrat['etat_sante'],
        'fumeur' => $contrat['fumeur'],
        'profession' => $contrat['emploi'],
        'capital_souhaite' => $contrat['capital_souhaite'],
        'antecedents_medicaux' => json_decode($contrat['antecedents_medicaux'], true),
        'beneficiaires' => json_decode($contrat['beneficiaires'], true)
    ];

    // Création du PDF
    $pdf = new ContratVieAssurance();
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

    // Détails assurance vie
    $pdf->addDetailsVie($data_vie);

    // Calcul des coefficients
    $coefficients = calculerCoeff([
        'date_naissance' => $contrat['date_naissance'],
        'etat_sante' => $contrat['etat_sante'],
        'antecedents' => $data_vie['antecedents_medicaux'],
        'fumeur' => $contrat['fumeur'],
        'profession' => $contrat['emploi'],
        'sexe' => $contrat['sexe'],
        'capital' => $contrat['capital_souhaite']
    ]);
    //prime de base calcule selon le capital
    $primeBaseCalcule = calculerPrimeBase($contrat['capital_souhaite'],  $garanties['prime_base'], $garanties['franchise']);
    // Détails de la prime
    $pdf->addPrimeDetails(
        $primeBaseCalcule['primeBaseCalcule'],
        $contrat['reduction'],
        $contrat['surcharge'],
        $contrat['montant_prime'],
        $coefficients
    );

    // Garanties
    $pdf->addGarantiesVie($garanties['nom_garantie'], $garanties['description'], $contrat['capital_garanti']);
    //exposition d'expiration
    $pdf->SectionTitle('DISPOSITIONS D\'EXPIRATION');
    
    $pdf->SetFont('DejaVu', 'B', 10);
    $pdf->Cell(0, 6, 'Article - Effets de l\'expiration', 0, 1);
    $pdf->SetFont('DejaVu', '', 9);
    
    $text = "En cas de non-renouvellement du présent contrat à son terme :\n";
    $text .= "1) La garantie décès cesse immédiatement - aucun capital ne sera versé pour un décès survenant après la date d'expiration\n\n";
    $text .= "2) La garantie Invalidité Absolue Définitive (IAD) s'éteint définitivement - aucune indemnisation ne sera due pour une invalidité survenant après expiration\n\n";
    $text .= "3) Aucun remboursement de primes ne sera effectué, quel que soit l'historique du contrat\n\n";
    $text .= "Le renouvellement nécessite une nouvelle souscription et peut être soumis à un nouveau questionnaire médical.";
    
    $pdf->MultiCell(0, 5, $text);
    $pdf->Ln(8);
    // Signatures
    $pdf->AddSignatureBlock();
    $pdf->SetTitle('Contrat d\'assurance vie');
    $pdf->Output('Contrat_Vie_' . $contrat['numero_contrat'] . '.pdf', 'I');

} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>