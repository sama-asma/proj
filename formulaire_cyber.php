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
    <title>Assurance Cyber Attaque</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
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
        <form id="formCyberAttaque" method="POST" action="traitement_cyber_attaque.php" novalidate>
            <h1>Souscription Assurance Cyber Attaque</h1>
            
            <!-- Recherche client existant -->
            <div class="form-section">
                <h2>Recherche Client</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="recherche_client">Rechercher un client existant</label>
                        <input type="text" id="recherche_client" name="recherche_client" placeholder="Nom, prénom ou entreprise">
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
                        <label for="nom_client" class="required">Nom (ou raison sociale)</label>
                        <input type="text" id="nom_client" name="nom_client" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom_client">Prénom (si particulier)</label>
                        <input type="text" id="prenom_client" name="prenom_client">
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
                        <label for="type_client" class="required">Type de client</label>
                        <select id="type_client" name="type_client" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="particulier">Particulier</option>
                            <option value="entreprise">Entreprise</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Informations sur l'entreprise (si applicable) -->
            <div class="form-section" id="entrepriseSection" style="display:none;">
                <h2>Informations sur l'Entreprise</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="taille_entreprise" class="required">Taille de l'entreprise</label>
                        <select id="taille_entreprise" name="taille_entreprise" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="tpe">TPE (<10 employés)</option>
                            <option value="pme">PME (10-250 employés)</option>
                            <option value="grande">Grande entreprise (>250 employés)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="secteur_activite" class="required">Secteur d'activité</label>
                        <input type="text" id="secteur_activite" name="secteur_activite" placeholder="Ex: Technologie, Finance, etc." required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="chiffre_affaires">Chiffre d'affaires annuel (DZD)</label>
                        <input type="number" id="chiffre_affaires" name="chiffre_affaires" min="0" step="1000">
                    </div>
                </div>
            </div>

            <!-- Infrastructure informatique -->
            <div class="form-section">
                <h2>Infrastructure Informatique</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="niveau_securite" class="required">Niveau de sécurité actuel</label>
                        <select id="niveau_securite" name="niveau_securite" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="basique">Basique (antivirus uniquement)</option>
                            <option value="intermediaire">Intermédiaire (firewall, antivirus)</option>
                            <option value="avance">Avancé (SIEM, audits réguliers)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="historique_attaques">Historique de cyberattaques</label>
                        <select id="historique_attaques" name="historique_attaques">
                            <option value="">-- Sélectionnez --</option>
                            <option value="aucune">Aucune</option>
                            <option value="mineure">Mineure(s)</option>
                            <option value="majeure">Majeure(s)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="donnees_sensibles" class="required">Données sensibles gérées</label>
                        <select id="donnees_sensibles" name="donnees_sensibles" required>
                            <option value="">-- Sélectionnez --</option>
                            <option value="aucune">Aucune</option>
                            <option value="personnelles">Données personnelles</option>
                            <option value="financieres">Données financières</option>
                            <option value="confidentielles">Données confidentielles</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Réductions et surcharges -->
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="reduction">Réduction (%)</label>
                        <input type="number" name="reduction" id="reduction" min="0" max="100" step="1">
                    </div>
                    <div class="form-group">
                        <label for="surcharge" class="required">Surcharge (%)</label>
                        <input type="number" name="surcharge" id="surcharge" min="0" max="100" step="1" required>
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
                        $query = "SELECT id_garantie, type_assurance, nom_garantie, description FROM garanties WHERE nom_garantie = 'cyber'";
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
                            <option value="<?= htmlspecialchars($garantie['id_garantie']) ?>">
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

    <script src="js/validation_cyber.js"></script> 
    <script src="js/script.js"></script> 
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>