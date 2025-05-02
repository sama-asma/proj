<?php
require_once('contrat_pdf.php');
require_once('calcul_coef_auto.php'); // Inclure le fichier de fonctions de calcul

class ContratAutoAssurance extends ContratPDF {
    //Titre spécifique pour les contrats auto
    
    protected function getContractTitle() {
        return 'CONTRAT D\'ASSURANCE AUTOMOBILE';
    }
    
    // Afficher les garanties spécifiques auto
    
    public function addGarantiesAuto($formuleNom,$description,$franchise) {
        $this->SectionTitle('FORMULE ET GARANTIES INCLUSES');
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(0, 6, 'Formule : ' . $formuleNom, 0, 1);
        $this->Ln(5);
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(0, 6, 'Franchise : ' . $franchise . '%', 0, 1); 
        // Ajout de la phrase après la franchise
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
    // Vérification de l'ID du contra
        if (!isset($_GET['contrat']) || !is_numeric($_GET['contrat'])) {
            die("Numéro de contrat invalide.");
        }
        $id_contrat = $_GET['contrat'];

        require 'db.php';
        
        // Connexion à la base de données et récupération des données
    try {
        // assurer que la connexion utilise UTF-8
            mysqli_set_charset($conn, "utf8");
        // Récupération des informations du contrat
        $stmt = $conn->prepare("
        SELECT c.*, v.*, cl.*
        FROM contrats c
        JOIN assurance_automobile v ON c.id_contrat = v.id_contrat
        JOIN client cl ON c.id_client = cl.id_client
        WHERE c.id_contrat = ? ");
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
            JOIN assurance_automobile v ON g.id_garantie = v.id_garantie
            WHERE v.id_contrat = ?
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
            'marque_vehicule' => $contrat['marque_vehicule'],
            'type_vehicule' => $contrat['type_vehicule'],
            'puissance' => $contrat['puissance_vehicule'],
            'annee_vehicule' => $contrat['annee_vehicule'],
            'stationnement' => $contrat['condition_stationnement'],
            'date_naissance' => $contrat['date_naissance'],
            'experience' => $contrat['experience_conducteur'],
            'usage' => $contrat['type_usage'],
            'environnement' => $contrat['environnement'],
            'bonus_malus' => $contrat['bonus_malus']
        ];

        // Calculer les coefficients en utilisant la fonction commune
        $coefficients = calculerCoefficients($data);

        // Création du PDF
        $pdf = new ContratAutoAssurance();
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
        
        // Véhicule (méthode spécifique)
        $pdf->SectionTitle('VÉHICULE ASSURÉ');
        $pdf->InfoLineDouble('Marque :', $contrat['marque_vehicule'],
            'Immatriculation :', $contrat['immatriculation'], );
        
        $pdf->InfoLineDouble('Modèle :', $contrat['type_vehicule'],'N° série :', $contrat['numero_serie'],);
        
        $pdf->InfoLineDouble('Année :', $contrat['annee_vehicule'],
            'Puissance :', $contrat['puissance_vehicule'] . ' CV',  );
        $pdf->ln(5);
        
        // Après les informations générales et avant les garanties
        $pdf->addPrimeDetails(
            $garanties['prime_base'],
            $contrat['reduction'],
            $contrat['surcharge'],
            $contrat['montant_prime'],
            $coefficients
        );
        
        // Garanties (méthode spécifique)
        $pdf->addGarantiesAuto($garanties['nom_garantie'],$garanties['description'],$garanties['franchise']);
        
        // Signatures (méthode communes)
        $pdf->AddSignatureBlock();
        $pdf->SetTitle('Contrat d\'assurance véhicule');
        $pdf->Output('Contrat_' . $contrat['numero_contrat'] . '.pdf', 'I');
       
?>