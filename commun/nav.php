<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();}
?>
<head>
    <link rel="stylesheet" href="css/style.css">
</head>
<!-- navigation -->
<div class="navigation">
        <ul>
            <li>
                <a href="#">
                    <span class="icon">
                       <img src="./img/logo.png" alt="logo">
                    </span>
                    <span class="title_logo">Assurance Daman</span>
                </a>
            </li>
            <li>
                <a href="./dashboard.php">
                    <span class="icon">
                       <i class="fas fa-home"></i> 
                    </span>
                    <span class="title">Accueil</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon">
                       <i class="fas fa-file-contract"></i>
                    </span>
                    <span class="title">Contrats</span>
                </a>
            </li> 
            <li>
                <a href="./creation_contrat.php">
                    <span class="icon">
                       <i class="fas fa-file-signature"></i>
                    </span>
                    <span class="title">Créer un contrat</span>
                </a>
            </li>
            <?php
            if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
            echo <<<HTML
            <li>
                <a href="#">
                    <span class="icon">
                       <i class="fas fa-cog"></i>
                    </span>
                    <span class="title">Paramètres</span>
                </a>
            </li>
            HTML;}
            ?>
            <li>
                <a href="./logout.php">
                    <span class="icon">
                       <i class="fas fa-sign-out-alt"></i>
                    </span>
                    <span class="title">Déconnexion</span>
                </a>
            </li>
        </ul>
    </div>