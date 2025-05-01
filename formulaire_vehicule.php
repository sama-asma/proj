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
    <title>Assurance véhicule</title>
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
        <form id="formPrim" method="POST" action="traitement_auto.php" novalidate> <!-- novalidate: désactiver validation html -->
            <h1>Souscription Assurance Véhicule</h1>
            
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

          <!-- Informations client (sera pré-rempli après recherche) -->
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
            <!-- Données véhicule -->
            <div class="form-section">
                <h2>Informations du Véhicule</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="marque_vehicule" class="required">Marque du véhicule</label>
                            <select id="marque_vehicule" name="marque_vehicule" required>
                                <option value="">-- Sélectionnez --</option>
                                <option value="renault">Renault</option>
                                <option value="peugeot">Peugeot</option>
                                <option value="citroen">Citroën</option>
                                <option value="volkswagen">Volkswagen</option>
                                <option value="bmw">BMW</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type_vehicule" class="required">Type de véhicule</label>
                            <select id="type_vehicule" name="type_vehicule" required>
                                <option value="">-- Sélectionnez une marque d'abord --</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero_serie" class="required">Numéro de série</label>
                            <input type="text" id="numero_serie" name="numero_serie" required>
                        </div>
                        <div class="form-group">
                            <label for="immatriculation" class="required">Immatriculation</label>
                            <input type="text" id="immatriculation" name="immatriculation"  placeholder="Ex: 12345-321-16 (5-3-2 chiffres)" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="puissance_vehicule" class="required">Puissance (CV)</label>
                            <input type="number" id="puissance_vehicule" name="puissance_vehicule" step="0.01" min="50" required>
                        </div>
                        <div class="form-group">
                            <label for="annee_vehicule" class="required">Année du véhicule</label>
                            <input type="number" id="annee_vehicule" name="annee_vehicule" required>
                        </div>
                        <div class="form-group">
                            <label for="condition_stationnement" class="required">Condition de stationnement</label>
                            <select id="condition_stationnement" name="condition_stationnement" required>
                                <option value="">-- Sélectionnez --</option>
                                <option value="garage">Garage</option>
                                <option value="parking privé">Parking privé</option>
                                <option value="rue">Rue</option>
                            </select>
                        </div>
                    </div>
                   
            </div>
            <!-- Profil Conducteur -->
            <div class="form-section">
                <h2>Profil du Conducteur</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="experience_conducteur" class="required">Expérience (années)</label>
                        <input type="number" id="experience_conducteur" name="experience_conducteur" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="bonus_malus" class="required">Coefficient Bonus-Malus</label>
                        <input type="number" id="bonus_malus" name="bonus_malus" step="0.01" min="0.5" value="1.00" max="3.5" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="usage" class="required">Usage du véhicule</label>
                        <select id="usage" name="usage" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="personnel">Personnel</option>
                            <option value="professionnel">Professionnel</option>
                            <option value="mixte">Mixte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="environnement" class="required">Environnement</label>
                        <select id="environnement" name="environnement" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="urbain">Urbain</option>
                            <option value="rural">Rural</option>
                            <option value="mixte">Mixte</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Réductions et surcharges-->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reduction">Réduction: (En %)</label>
                        <input type="number" name="reduction" id="reduction">
                    </div>
                    <div class="form-group">
                        <label for="surcharge" class="required" >Surcharge: (En %)</label>
                        <input type="number" name="surcharge" id="surcharge" required>
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
                        $query = "SELECT id_garantie, type_assurance, nom_garantie, description FROM garanties WHERE type_assurance = 'automobile'";
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

    <script src="js/validation_auto.js"></script> 
    <script src="js/script.js"></script> 
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </body>
</html>