<?php
require_once('contrat_pdf.php');
require 'calcul_coef_habit.php';
class ContratHabitationAssurance extends ContratPDF {
    // Titre spécifique pour les contrats habitation
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE HABITATION';
    }
    
    // Afficher les caractéristiques du logement
    public function addDetailsLogement($statut, $type, $superficie, $annee, $localisation, $materiaux, $etat_toiture, $occupation, $nb_occupants, $capital_mobilier) {
        $this->SectionTitle('CARACTÉRISTIQUES DU LOGEMENT');

        $this->SetFont('', '', 10);
        $this->InfoLineDouble('Statut:', $statut, 'Type:', $type);
        $this->InfoLineDouble('Superficie:', $superficie . ' m²', 'Année construction:', $annee);
        $this->InfoLineDouble('Localisation:', $localisation, 'Matériaux:', $materiaux);
        $this->InfoLineDouble('État toiture:', $etat_toiture, 'Occupation:', $occupation);
        $this->InfoLineDouble('Nombre occupants:', $nb_occupants, 'Capital mobilier:', number_format($capital_mobilier, 2, ',', ' ') . ' DZD');
        $this->Ln(10);
    }
    
    // Afficher les garanties spécifiques habitation
    public function addGarantiesHabitation($formuleNom, $description, $franchise) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $formuleNom, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . $franchise . '%', 0, 1);
        $this->SetFont('DejaVu', '', 10);
        $this->MultiCell(0, 6,'La franchise correspond au montant à la charge du souscripteur en cas de sinistre.');
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

try {
    // Assurer que la connexion utilise UTF-8
    mysqli_set_charset($conn, "utf8");
    
    // Récupération des informations du contrat habitation
    $stmt = $conn->prepare("
        SELECT c.*, h.*, cl.*
        FROM contrats c
        JOIN assurance_habitation h ON c.id_contrat = h.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        WHERE c.id_contrat = ?");
    $stmt->bind_param("i", $id_contrat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Contrat introuvable.");
    }

    $contrat = $result->fetch_assoc();

    // Récupération des garanties du contrat
    $stmt_garanties = $conn->prepare("
        SELECT g.nom_garantie, g.description, g.prime_base, g.franchise
        FROM garanties g
        JOIN assurance_habitation h ON g.id_garantie = h.id_garantie
        WHERE h.id_contrat = ?
    ");
    $stmt_garanties->bind_param("i", $id_contrat);
    $stmt_garanties->execute();
    $result_garanties = $stmt_garanties->get_result();
    
    $garanties = $result_garanties->fetch_assoc();
    
    // Calcul des coefficients en utilisant la nouvelle fonction
    $coefficients = calculerCoefficientsHabitation([
        'type_logement' => $contrat['type_logement'],
        'materiaux' => $contrat['materiaux_construction'],
        'etat_toiture' => $contrat['etat_toiture'],
        'statut_occupation' => $contrat['statut'],
        'localisation' => $contrat['localisation'],
        'securite' => !empty($contrat['mesures_securite']) ? explode(',', $contrat['mesures_securite']) : [],
        'nb_occupants' => $contrat['nb_occupants'],
        'annee_construction' => $contrat['annee_construction'],
        'superficie' => $contrat['superficie'],
        'capital_mobilier' => $contrat['capital_mobilier'],
        'antecedents' => $contrat['antecedents']
    ]);


    // Création du PDF
    $pdf = new ContratHabitationAssurance();
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
    
    // Adresse du logement
    $pdf->SectionTitle('ADRESSE DU LOGEMENT');
    $pdf->InfoLine('Adresse :', $contrat['adresse_detail'] . ', ' . $contrat['commune_nom'] . ', ' . $contrat['wilaya_nom']);
    $pdf->Ln(5);
    
    // Détails du logement
    $pdf->addDetailsLogement(
        $contrat['statut'],
        $contrat['type_logement'],
        $contrat['superficie'],
        $contrat['annee_construction'],
        $contrat['localisation'],
        $contrat['materiaux_construction'],
        $contrat['etat_toiture'],
        $contrat['occupation'],
        $contrat['nb_occupants'],
        $contrat['capital_mobilier']
    );
    
    // Détails de la prime
    $pdf->addPrimeDetails(
        $garanties['prime_base'],
        $contrat['reduction'],
        $contrat['surcharge'],
        $contrat['montant_prime'],
        $coefficients
    );
    
    // Garanties
    $pdf->addGarantiesHabitation(
        $garanties['nom_garantie'],
        $garanties['description'],
        $garanties['franchise']
    );
    
    // Mesures de sécurité
    if (!empty($contrat['mesures_securite'])) {
        $pdf->SectionTitle('MESURES DE SÉCURITÉ');
        $pdf->SetFont('DejaVu', '', 10);
        $mesures = explode(',', $contrat['mesures_securite']);
        foreach ($mesures as $mesure) {
            $pdf->Cell(10);
            $pdf->Cell(5, 5, '-', 0, 0);
            $pdf->MultiCell(0, 5, $mesure . '.');
        }
        $pdf->Ln(5);
    }
    
    // Signatures
    $pdf->AddSignatureBlock();
    $pdf->SetTitle('Contrat d\'assurance habitation');
    $pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
    
} catch (Exception $e) {
    die("Erreur lors de la génération du contrat : " . $e->getMessage());
}
?>