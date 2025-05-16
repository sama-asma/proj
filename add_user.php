<?php
include_once('db.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$btn_title = "Enregistrer";
$current_user = [];

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_sql = "SELECT * FROM users WHERE id = $id";
    $res_user = mysqli_query($conn, $user_sql);
    if ($res_user) {
        $current_user = $res_user->fetch_assoc();
        $username = $current_user['username'];
        $role = $current_user['role'];
        $btn_title = "Mettre à jour";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title><?php echo $btn_title == "Enregistrer" ? "Ajouter un utilisateur" : "Modifier un utilisateur"; ?></title>
</head>
<body>
    <?php include_once('commun/nav.php'); ?>
    <div class="container-fluid main-content">
        <div class="row">
            <div class="col-12">
                <div class="wrapper p-5 m-5">
                    <div class="d-flex p-2 justify-content-between mb-2">
                        <h2><?php echo $btn_title == "Enregistrer" ? "Ajouter un utilisateur" : "Modifier un utilisateur"; ?></h2>
                        <div><a href="index1.php"><i data-feather="list"></i></a></div>
                    </div>
                    <form action="index1.php" method="post">
                        <?php if (isset($current_user['id'])): ?>
                            <input type="hidden" name="id" value="<?php echo $current_user['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" name="username" value="<?php echo isset($username) ? $username : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-control" name="role" required>
                                <option value="admin" <?php echo isset($role) && $role == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                <option value="employee" <?php echo isset($role) && $role == 'employee' ? 'selected' : ''; ?>>Employé</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input type="submit" class="btn btn-primary" value="<?php echo $btn_title; ?>" name="save">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jq.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/icons.js"></script>
    <script src="js/script.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>