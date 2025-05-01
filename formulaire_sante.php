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
    <title>Assurance santé</title>
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
        <form id="formPrim" method="POST" action="traitement_sante.php" novalidate>
            <h1>Souscription Assurance Santé</h1>
            
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

            <!-- Informations santé -->
            <div class="form-section">
                <h2>Informations Santé</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="profession" class="required">Profession</label>
                        <input type="text" id="profession" name="profession" required>
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
                        <label for="poids" class="required">Poids (kg)</label>
                        <input type="number" id="poids" name="poids" min="30" max="300" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label for="taille" class="required">Taille (cm)</label>
                        <input type="number" id="taille" name="taille" min="100" max="250" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                            <label for="sexe" class="required">Sexe</label>
                            <select id="sexe" name="sexe" required>
                                <option value="">-- Sélectionnez --</option>
                                <option value="homme">Homme</option>
                                <option value="femme">Femme</option>
                            </select>
                    </div>
                    <div class="form-group">
                        <label for="antecedents">Antécédents médicaux</label>
                        <textarea id="antecedents" name="antecedents" rows="3"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fumeur">Fumeur</label>
                        <select id="fumeur" name="fumeur">
                            <option value="non">Non</option>
                            <option value="occasionnel">Occasionnel</option>
                            <option value="regulier">Régulier</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nb_personnes">Nombre de personnes à couvrir</label>
                        <input type="number" id="nb_personnes" name="nb_personnes" min="1" value="1">
                    </div>
                </div>
</div>

            <!-- Réductions et surcharges -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reduction">Réduction: (En %)</label>
                        <input type="number" name="reduction" id="reduction">
                    </div>
                    <div class="form-group">
                        <label for="surcharge" class="required">Surcharge: (En %)</label>
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
                        // Récupération des garanties santé depuis la base de données
                        $query = "SELECT id_garantie, type_assurance, nom_garantie, description FROM garanties WHERE type_assurance = 'sante'";
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
                                <option value="<?= (string)htmlspecialchars($garantie['id_garantie']) ?>">
                                    <?= htmlspecialchars($garantie['nom_garantie']) ?> - 
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

    <script src="js/validation_sante.js"></script> 
    <script src="js/script.js"></script> 
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>