<?php
require_once('contrat_pdf.php');
require_once('calcul_coef_emprunt.php'); // Inclure le fichier de fonctions de calcul pour l'emprunt

class ContratEmpruntAssurance extends ContratPDF {
    // Titre spécifique pour les contrats d'emprunt
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE EMPRUNT';
    }

    // Afficher les garanties spécifiques pour l'emprunt
    public function addGarantiesEmprunt($formuleNom, $description, $franchise) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $formuleNom, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . $franchise . ' DZD', 0, 1);
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

    // Afficher les détails du prêt
    public function addPretDetails($montant_emprunt, $duree_emprunt, $type_pret, $taux_interet, $etat_sante, $fumeur, $situation_professionnelle, $revenu_mensuel) {
        $this->SectionTitle('DÉTAILS DU PRÊT');
        $this->InfoLineDouble('Montant du prêt :', number_format($montant_emprunt, 2, ',', ' ') . ' DZD', 
                             'Durée du prêt :', $duree_emprunt . ' ans');
        $this->InfoLineDouble('Type de prêt :', ucfirst($type_pret), 
                             'Taux d\'intérêt :', $taux_interet . '%');
        $this->InfoLineDouble('État de santé :', ucfirst($etat_sante), 
                             'Fumeur :', ucfirst($fumeur));
        $this->InfoLineDouble('Situation professionnelle :', ucfirst($situation_professionnelle), 
                             'Revenu mensuel :', number_format($revenu_mensuel, 2, ',', ' ') . ' DZD');
        $this->Ln(5);
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
        SELECT c.*, e.*, cl.*
        FROM contrats c
        JOIN assurance_emprunt e ON c.id_contrat = e.id_contrat
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

    // Récupération des garanties du contrat
    $stmt_garanties = $conn->prepare("
        SELECT g.nom_garantie, g.description, g.prime_base, g.franchise
        FROM garanties g
        JOIN assurance_emprunt e ON g.id_garantie = e.id_garantie
        WHERE e.id_contrat = ?
    ");
    $stmt_garanties->bind_param("i", $id_contrat);
    $stmt_garanties->execute();
    $result_garanties = $stmt_garanties->get_result();

    $garanties = $result_garanties->fetch_assoc();

} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Préparer les données pour le calcul des coefficients
$data = [
    'date_naissance' => $contrat['date_naissance'],
    'etat_sante' => $contrat['etat_sante'],
    'fumeur' => $contrat['fumeur'],
    'situation_professionnelle' => $contrat['situation_professionnelle'],
    'revenu_mensuel' => $contrat['revenu_mensuel'],
    'type_pret' => $contrat['type_pret'],
    'montant_emprunt' => $contrat['montant_emprunt'],
    'duree_emprunt' => $contrat['duree_emprunt'],
    'taux_interet' => $contrat['taux_interet']
];

// Calculer les coefficients en utilisant la fonction commune
$coefficients = calculerCoefficientsEmprunt($data);

// Création du PDF
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
$pdf->InfoLine('Téléphone :', $contrat['telephone']);
$pdf->InfoLine('Email :', $contrat['email']);
$pdf->InfoLine('Date de naissance :', date('d/m/Y', strtotime($contrat['date_naissance'])));
$pdf->Ln(5);

// Détails du prêt
$pdf->addPretDetails(
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
    $garanties['prime_base'],
    $contrat['reduction'],
    $contrat['surcharge'],
    $contrat['montant_prime'],
    $coefficients
);

// Garanties
$pdf->addGarantiesEmprunt($garanties['nom_garantie'], $garanties['description'], $garanties['franchise']);

// Signatures
$pdf->AddSignatureBlock();
$pdf->SetTitle('Contrat d\'assurance emprunt');
$pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
?>