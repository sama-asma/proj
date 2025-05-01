<?php 
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assurance habitation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- navigation -->
    <?php include 'commun/nav.php'; ?>
    <!-- Contenu principal -->
    <div class="main-content">
        <!-- En-tête -->
       <?php include 'commun/header.php'; ?>
        <!-- Contenu -->
        <?php if (isset($_SESSION['form_errors'])): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($_SESSION['form_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['form_errors']); ?>
            </div>
        <?php endif; ?>
        <form id="formPrim" method="POST" action="traitement_habitation.php" novalidate> <!-- novalidate: désactiver validation html -->
            <h1>Souscription Assurance Habitation</h1>
            
            <!-- Recherche client existant -->
            <div class="form-section">
                <h2>Recherche Client</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="recherche_client">Rechercher un client existant</label>
                        <input type="text" id="recherche_client" name="recherche_client" placeholder="Nom, prénom">
                        <button type="button" id="btnRechercheClient">Rechercher</button>
                    </div>
                </div>
                <div id="resultatsClient" style="display:none;">
                    <h3>Résultats de la recherche</h3>
                    <div id="listeClients"></div>
                </div>
            </div>

            <!-- Informations client -->
            <div class="form-section">
                <h2>Informations du Client</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_client" class="required">Nom</label>
                        <input type="text" id="nom_client" name="nom_client" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom_client" class="required">Prénom</label>
                        <input type="text" id="prenom_client" name="prenom_client" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="telephone" class="required">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="required">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance" class="required">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" max="<?= date('Y-m-d'); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Informations sur le logement -->
            <div class="form-section">
                <h2>Informations du Logement</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="statut_logement" class="required">Statut du logement</label>
                        <select id="statut_logement" name="statut_logement" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="propriétaire">Propriétaire</option>
                            <option value="locataire">Locataire</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type_logement" class="required">Type de logement</label>
                        <select id="type_logement" name="type_logement" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="maison">Maison</option>
                            <option value="appartement">Appartement</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                <div class="form-group">
                    <label for="wilaya" class="required">Wilaya</label>
                    <select id="wilaya" name="wilaya" required>
                        <option value="">-- Sélectionnez --</option>
                        <!-- Rempli dynamiquement par JavaScript -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="commune" class="required">Commune</label>
                    <select id="commune" name="commune" required disabled>
                        <option value="">-- Sélectionnez d'abord une wilaya --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="adresse_detail" class="required">Adresse détaillée</label>
                <input type="text" id="adresse_detail" name="adresse_detail" required 
                    placeholder="N°, Rue, Quartier">
            </div>
           
                <div class="form-row">
                    <div class="form-group">
                        <label for="superficie" class="required">Superficie (m²)</label>
                        <input type="number" id="superficie" name="superficie" min="10" required>
                    </div>
                    <div class="form-group">
                        <label for="annee_construction" class="required">Année de construction</label>
                        <input type="number" id="annee_construction" name="annee_construction" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="localisation" class="required">Localisation</label>
                        <select id="localisation" name="localisation" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="urbain">Zone urbaine</option>
                            <option value="rural">Zone rurale</option>
                            <option value="risque">Zone à risque</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="materiaux" class="required">Matériaux de construction</label>
                        <select id="materiaux" name="materiaux" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="resistant">Matériaux résistants</option>
                            <option value="standard">Matériaux standards</option>
                            <option value="fragile">Matériaux fragiles</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="etat_toiture" class="required">État de la toiture</label>
                        <select id="etat_toiture" name="etat_toiture" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="neuf">Neuf</option>
                            <option value="bon">Bon</option>
                            <option value="moyen">Moyen</option>
                            <option value="mauvais">Mauvais</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="occupation" class="required">Occupation</label>
                        <select id="occupation" name="occupation" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="principale">Résidence principale</option>
                            <option value="secondaire">Résidence secondaire</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nb_occupants" class="required">Nombre d'occupants</label>
                        <input type="number" id="nb_occupants" name="nb_occupants" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="capital_mobilier" class="required">Capital mobilier assuré (DZD)</label>
                        <input type="number" id="capital_mobilier" name="capital_mobilier" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Mesures de sécurité -->
            <div class="form-section">
                <h2>Mesures de Sécurité</h2>
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>Systèmes de sécurité installés</label>
                        <div>
                            <input type="checkbox" id="alarme" name="securite[]" value="alarme">
                            <label for="alarme">Alarme</label>
                        </div>
                        <div>
                            <input type="checkbox" id="detecteur_fumee" name="securite[]" value="detecteur_fumee">
                            <label for="detecteur_fumee">Détecteur de fumée</label>
                        </div>
                        <div>
                            <input type="checkbox" id="surveillance" name="securite[]" value="surveillance">
                            <label for="surveillance">Système de surveillance</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Antécédents d'assurance -->
            <div class="form-section">
                <h2>Antécédents d'Assurance</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="antecedents" class="required">Nombre de sinistres déclarés (5 dernières années)</label>
                        <input type="number" id="antecedents" name="antecedents" min="0" required>
                    </div>
                </div>
            </div>

            <!-- Réductions et surcharges -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reduction">Réduction: (En %)</label>
                        <input type="number" name="reduction" id="reduction" min="0" max="50">
                    </div>
                    <div class="form-group">
                        <label for="surcharge" class="required">Surcharge: (En %)</label>
                        <input type="number" name="surcharge" id="surcharge" required >
                    </div>
                </div>
            </div>

            <!-- Section Garanties -->
            <div class="form-section">
                <h2>Garanties</h2>
                <div class="form-group">
                    <label for="id_garantie" class="required">Type de garantie</label>
                    <?php
                        require 'db.php';
                        // Récupération des garanties depuis la base de données
                        $query = "SELECT id_garantie, type_assurance, nom_garantie, description FROM garanties WHERE type_assurance = 'habitation'";
                        $result = $conn->query($query);

                        // Vérifier si des données existent
                        $garanties = [];
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $garanties[] = $row;
                            }
                        }
                        ?>
                      
                        <select id="id_garantie" name="id_garantie" required>
                            <option value="">-- Sélectionnez --</option>
                            <?php foreach ($garanties as $garantie) : ?>
                                <option value="<?= (string)htmlspecialchars($garantie['id_garantie']) ?>">
                                    <?= htmlspecialchars($garantie['nom_garantie'] ) ?> - 
                                    <?= htmlspecialchars($garantie['description']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>
            </div>

            <!-- Section Contrat -->
            <div class="form-section">
                <h2>Informations de Contrat</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_souscription" class="required">Date de souscription</label>
                        <input type="date" id="date_souscription" name="date_souscription" required>
                    </div>
                    <div class="form-group">
                        <label for="date_expiration" class="required">Date d'expiration</label>
                        <input type="date" id="date_expiration" name="date_expiration" required>
                    </div>
                </div>
            </div>
            <div>
                <input type="hidden" name="prime_calculee" id="prime">
                <input type="hidden" name="franchise" id="franchise">
            </div>
            <div class="buttons-container">
                <button type="button" id="calculerPrimeBtn">Calculer la prime</button>
                <button type="submit" id="souscrireBtn" class="generate" style="display:none;">Souscrire le contrat</button>
            </div>
        </form>

        <div id="resultatCalcul">
            <h2>Résultat du calcul de prime</h2>
            <div id="detailPrime"></div>
            <div id="resultatPrime" style="display:none;"></div>
        </div>
    </div>

    <script src="js/validation_habitation.js"></script> 
    <script src="js/script.js"></script> 
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>