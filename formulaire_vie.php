<?php 
session_start();
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
    <title>Assurance Vie</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'commun/nav.php'; ?>
    <div class="main-content">
        <?php include 'commun/header.php'; ?>
        
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
        
        <form id="formVie" method="POST" action="traitement_vie.php" novalidate>
            <h1>Souscription Assurance Vie</h1>
            
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

            <div class="form-section">
                <h2></h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="profession" class="required">Profession</label>
                        <select id="profession" name="profession" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="bureau">Travail de bureau</option>
                            <option value="manuel">Travail manuel léger</option>
                            <option value="danger">Profession à risque</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="etat_sante" class="required">État de santé</label>
                        <select id="etat_sante" name="etat_sante" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="excellent">Excellent</option>
                            <option value="bon">Bon</option>
                            <option value="moyen">Moyen</option>
                            <option value="mauvais">Mauvais</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fumeur">Statut tabagique</label>
                        <select id="fumeur" name="fumeur">
                            <option value="non">Non-fumeur</option>
                            <option value="occasionnel">Fumeur occasionnel</option>
                            <option value="regulier">Fumeur régulier</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sexe" class="required">Sexe</label>
                        <select id="sexe" name="sexe" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="homme">Homme</option>
                            <option value="femme">Femme</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="capital" class="required">Capital souhaité (DZD)</label>
                        <input type="number" id="capital" name="capital" min="100000" step="10000" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="antecedents">Antécédents médicaux familiaux</label>
                        <textarea id="antecedents" name="antecedents" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- Garanties -->
            <div class="form-section">
                <h2>Garanties</h2>
                <div class="form-group">
                    <label for="id_garantie" class="required">Type de garantie</label>
                    <?php
                        require 'db.php';
                        $query = "SELECT id_garantie, nom_garantie, description FROM garanties WHERE type_assurance = 'vie'";
                        $result = $conn->query($query);
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
                            <option value="<?= htmlspecialchars($garantie['id_garantie']) ?>">
                                <?= htmlspecialchars($garantie['nom_garantie']) ?> - 
                                <?= htmlspecialchars($garantie['description']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Contrat -->
            <div class="form-section">
                <h2>Informations du Contrat</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_souscription" class="required">Date de souscription</label>
                        <input type="date" id="date_souscription" name="date_souscription" required>
                    </div>
                    <div class="form-group">
                        <label for="duree" class="required">Durée (années)</label>
                        <input type="number" id="duree" name="duree" min="5" max="30" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="beneficiaires" class="required">Bénéficiaires</label>
                        
                        <!-- Conteneur pour la liste des bénéficiaires -->
                        <div id="liste_beneficiaires" class="beneficiaires-container"></div>
                        
                        <!-- Formulaire d'ajout -->
                        <div class="ajout-beneficiaire">
                            <input type="text" id="nom_beneficiaire" placeholder="Nom complet" class="beneficiaire-input">
                            <input type="text" id="lien_beneficiaire" placeholder="Lien familial (ex: Épouse)" class="beneficiaire-input">
                            <input type="number" id="part_beneficiaire" placeholder="Part (%)" min="1" max="100" class="beneficiaire-input">
                            <button type="button" id="ajouter_beneficiaire" class="btn-small">+ Ajouter</button>
                        </div>
                        
                        <!-- Champ caché pour stocker les données JSON -->
                        <input type="hidden" id="beneficiaires" name="beneficiaires" required>
                    </div>
                </div>
            </div>

            <div>
                <input type="hidden" name="prime_calculee" id="prime">
            </div>

            <div class="buttons-container">
                <button type="button" id="calculerPrimeBtn">Calculer la prime</button>
                <button type="submit" id="souscrireBtn" class="generate" style="display:none;">Souscrire le contrat</button>
            </div>
        </form>

        <div id="resultatCalcul">
            <h2>Résultat du calcul de prime</h2>
            <div id="detailPrime"></div>
        </div>
    </div>

    <script src="js/validation_vie.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>