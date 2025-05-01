<?php
require_once('contrat_pdf.php');

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
        // Calcul des coefficients
        $current_year = date('Y');
        $coef_age_vehicule = ($current_year - $contrat['annee_vehicule'] < 3) ? 0.9 : (($current_year - $contrat['annee_vehicule'] > 10) ? 1.2 : 1.0);

        // Facteurs de calcul pour le modèle (vous devez avoir cette structure quelque part)
        $facteurs_vehicule = [
            'Renault' => [
                'Clio' => 0.85,
                'Megane' => 0.95,
                'Kadjar' => 1.1
            ],
            'Peugeot' => [
                '208' => 0.9,
                '308' => 1.0,
                '3008' => 1.15
            ],
            'Citroën' => [
                'C3' => 0.88,
                'C4' => 0.98,
                'C5 Aircross' => 1.12
            ],
            'Volkswagen' => [
                'Golf' => 0.92,
                'Passat' => 1.05,
                'Tiguan' => 1.18
            ],
            'BMW' => [
                'Série 1' => 1.1,
                'Série 3' => 1.25,
                'X3' => 1.4
            ]
        ];

        $coef_modele = $facteurs_vehicule[$contrat['marque_vehicule']][$contrat['type_vehicule']] ?? 1.0;
        $coef_puissance = ($contrat['puissance_vehicule'] < 80) ? 0.8 : (($contrat['puissance_vehicule'] > 150) ? 1.3 : 1.0);
        $ceof_stationnement = ($contrat['condition_stationnement'] == 'garage') ? 0.8 : (($contrat['condition_stationnement'] == 'parking privé') ? 1.1 : 1.3);

        // Calculer l'âge du conducteur
        $date_naissance = new DateTime($contrat['date_naissance']);
        $aujourdhui = new DateTime();
        $age_conducteur = $aujourdhui->diff($date_naissance)->y;

        $coef_age_conducteur = ($age_conducteur < 26) ? 1.4 : (($age_conducteur > 65) ? 1.3 : 1.0);
        $coef_experience = ($contrat['experience_conducteur'] < 2) ? 1.5 : (($contrat['experience_conducteur'] > 10) ? 0.8 : 1.0);
        $coef_usage = ($contrat['type_usage'] == 'professionnel') ? 1.4 : (($contrat['type_usage'] == 'mixte') ? 1.2 : 1.0);
        $coef_environnement = ($contrat['environnement'] == 'urbain') ? 1.3 : (($contrat['environnement'] == 'rural') ? 0.9 : 1.0);
        $bonus_malus = $contrat['bonus_malus'];

        // Créer un tableau des coefficients à afficher
        $coefficients = [
            'Coefficient Modèle' => $coef_modele,
            'Coefficient Puissance' => $coef_puissance,
            'Coefficient Âge Véhicule' => $coef_age_vehicule,
            'Coefficient Stationnement' => $ceof_stationnement,
            'Coefficient Âge Conducteur' => $coef_age_conducteur,
            'Coefficient Expérience' => $coef_experience,
            'Coefficient Usage' => $coef_usage,
            'Coefficient Environnement' => $coef_environnement,
            'Bonus/Malus' => $bonus_malus
        ];

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
