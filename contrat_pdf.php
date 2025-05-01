<?php
require('tfpdf/tfpdf.php');

// Classe de base pour tous les types de contrats

class ContratPDF extends tFPDF {
    // Propriétés communes
    protected $companyName = "Assurance Daman";
    protected $companyLogo = "img/logo.png";
    protected $footerText = "Ce document est un contrat légal. Conservez-le précieusement.";
    
     // Fonction pour convertir UTF-8 en ISO-8859-1
    //  public function customUtf8Decode($str) {
    //     return mb_convert_encoding($str, 'Windows-1252', 'UTF-8');
    // }
    //Constructeur
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        // Appel du constructeur parent
        parent::__construct($orientation, $unit, $format);
        $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
        $this->AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf', true);  // gras
        
        $this->AddFont('DejaVu', 'I', 'DejaVuSansCondensed-Oblique.ttf', true);
        $this->AddFont('DejaVu', 'BI', 'DejaVuSansCondensed-BoldOblique.ttf', true);
        $this->SetFont('DejaVu','',12);
         
        // Configuration commune
        $this->SetMargins(10, 40, 10);
        $this->SetAutoPageBreak(true, 25);
    }
    
    // En-tête commun à tous les contrats
    public function Header() {
        $this->Image($this->companyLogo, 10, 5, 20);
        // Police
        $this->SetFont('DejaVu', 'B', 15);
        $this->SetXY(35, 10); // Positionner le titre à côté du logo
        // Titre encadré - sera surchargé par les classes enfants
        $this->SetFillColor(230, 230, 230);
        $this->Cell(160, 10, $this->companyName . ' - ' . $this->getContractTitle(), 0, 1, 'L', true);
        // Saut de ligne
        $this->Ln(15);
    }
    
    // Retourne le titre du contrat - à surcharger dans les classes enfants
    protected function getContractTitle() {
        return 'CONTRAT';
    }
    
    // Pied de page commun à tous les contrats
    public function Footer() {
        // Position à 1,5 cm du bas
        $this->SetY(-15);
        // Police
        $this->SetFont('DejaVu', 'I', 8);
        // Texte
        $this->Cell(0, 10, $this->footerText, 0, 0, 'C');
        // Numéro de page
        $this->SetX(10);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
    
    // Fonction pour créer un titre de section
    public function SectionTitle($title) {
        $this->SetFont('DejaVu', 'B', 12);
        $this->SetFillColor(70, 130, 180);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('DejaVu', '', 11);
        $this->Ln(2);
    }
    
    // Fonction pour créer une ligne d'information
    public function InfoLine($label, $value) {
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell(60, 6, $label, 0, 0);
        $this->SetFont('DejaVu', '', 10);
        $this->Cell(130, 6, $value, 0, 1);
    }
    
    // Fonction pour créer double ligne d'information
    public function InfoLineDouble($label1, $value1, $label2, $value2, $labelWidth = 45) {
        // Première paire label/valeur
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell($labelWidth, 6, $label1, 0, 0);
        $this->SetFont('DejaVu', '', 10);
        $this->Cell($labelWidth, 6, $value1, 0, 0);
        
        // Seconde paire label/valeur
        $this->SetFont('DejaVu', 'B', 10);
        $this->Cell($labelWidth, 6, $label2, 0, 0);
        $this->SetFont('DejaVu', '', 10);
        $this->Cell($labelWidth, 6, $value2, 0, 1); // Notez le 1 pour le saut de ligne final
    }
    
    // Bloc signature à la fin du contrat
    public function AddSignatureBlock() {
        $this->Cell(0, 6, 'Fait à __________________________, le ' . date('d/m/Y'), 0, 1);
        $this->Ln(10);

        $this->Cell(95, 6, 'Signature de l\'assureur', 0, 0, 'C');
        $this->Cell(95, 6, 'Signature du souscripteur', 0, 1, 'C');
        $this->Ln(20);

        $this->Cell(95, 6, '_______________________', 0, 0, 'C');
        $this->Cell(95, 6, '_______________________', 0, 1, 'C');
    }
    
    public function addPrimeDetails($primeBase, $reduction, $surcharge, $primeFinale, $coefficients = []) {
        $this->SectionTitle('DÉTAIL DU CALCUL DE LA PRIME');
        
        // Style pour les labels
        $this->SetFont('DejaVu', 'B', 10);
        
        // Prime de base
        $this->Cell(100, 6, 'Prime de base:', 0, 0);
        $this->Cell(0, 6, number_format($primeBase, 2, ',', ' ') . ' DZD', 0, 1);
        
        // Affichage des coefficients s'ils sont fournis
        if (!empty($coefficients)) {
            $this->SetFont('DejaVu', 'B', 10);
            $this->Cell(0, 6, 'Coefficients appliqués:', 0, 1);
            $this->SetFont('DejaVu', '', 10);
            
            foreach ($coefficients as $label => $value) {
                $this->Cell(100, 6, '- ' . $label . ':', 0, 0);
                $this->Cell(0, 6, number_format($value, 2, ',', ' '), 0, 1);
            }
            $this->Ln(2);
        }
        
        // Réduction
        if ($reduction > 0) {
            $montantReduction = $primeBase * $reduction / 100;
            $this->Cell(100, 6, 'Réduction (' . $reduction . '%):', 0, 0);
            $this->Cell(0, 6, '- ' . number_format($montantReduction, 2, ',', ' ') . ' DZD', 0, 1);
        }
        
        // Surcharge
        if ($surcharge > 0) {
            $montantSurcharge = $primeBase * $surcharge / 100;
            $this->Cell(100, 6, 'Surcharge (' . $surcharge . '%):', 0, 0);
            $this->Cell(0, 6, '+ ' . number_format($montantSurcharge, 2, ',', ' ') . ' DZD', 0, 1);
        }
        
        // Ligne de séparation
        $this->SetDrawColor(150, 150, 150);
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(2);
        
        // Prime finale
        $this->SetFont('DejaVu', 'B', 11);
        $this->Cell(100, 8, 'Prime finale:', 0, 0);
        $this->Cell(0, 8, number_format($primeFinale, 2, ',', ' ') . ' DZD', 0, 1);
        
        $this->Ln(15);
        // Ajouter un saut de page
        $this->AddPage();
    }
}