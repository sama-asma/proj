<?php
include_once('db.php');
$action = false;
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['save'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($_POST['save'] == "Enregistrer") {
        $save_sql = "INSERT INTO `users`(`username`, `password`, `role`) VALUES ('$username', '$hashed_password', '$role')";
    } else {
        $id = $_POST['id'];
        $save_sql = "UPDATE `users` SET `username`='$username', `password`='$hashed_password', `role`='$role' WHERE id = $id";
    }

    $res_save = mysqli_query($conn, $save_sql);
    if (!$res_save) {
        die(mysqli_error($conn));
    } else {
        $action = isset($_POST['id']) ? "edit" : "add";
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'del') {
    $id = $_GET['id'];
    $del_sql = "DELETE FROM users WHERE id = $id";
    $res_del = mysqli_query($conn, $del_sql);
    if (!$res_del) {
        die(mysqli_error($conn));
    } else {
        $action = "del";
    }
}

$users_sql = "SELECT * FROM users";
$all_user = mysqli_query($conn, $users_sql);
if (!$all_user) {
    die("Erreur dans la requête SQL : " . mysqli_error($conn));
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Application Utilisateurs</title>
</head>
<body>
    <?php include_once('commun/nav.php'); ?>
    <div class="container-fluid main-content">
        <div class="row">
            <div class="col-12">
                <div class="wrapper p-5 m-5">
                    <div class="d-flex p-2 justify-content-between mb-2">
                        <h2>Tous les utilisateurs</h2>
                        <div><a href="add_user.php"><i data-feather="user-plus"></i></a></div>
                    </div>
                    <hr>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nom d'utilisateur</th>
                                <th scope="col">Mot de passe</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Date de création</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $all_user->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td>[Masqué pour sécurité]</td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td>
                                        <div class="d-flex p-2 justify-content-evenly mb-2">
                                            <i onclick="confirm_delete(<?php echo $user['id']; ?>)" class="text-danger" data-feather="trash-2"></i>
                                            <i onclick="edit(<?php echo $user['id']; ?>)" class="text-success" data-feather="edit"></i>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jq.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/icons.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "positionClass": "toast-top-right",
            "timeOut": 3000
        };

        function show_add() {
            toastr.success("Utilisateur ajouté avec succès", "Ajout");
        }

        function show_del() {
            toastr.error("Utilisateur supprimé avec succès", "Suppression");
        }

        function show_update() {
            toastr.info("Utilisateur mis à jour avec succès", "Mise à jour");
        }

        function confirm_delete(id) {
            Swal.fire({
                title: "Êtes-vous sûr ?",
                text: "Cette action est irréversible !",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Oui, supprimer",
                cancelButtonText: "Annuler"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "index1.php?action=del&&id=" + id;
                }
            });
        }

        function edit(id) {
            window.location.href = "add_user.php?action=edit&&id=" + id;
        }

        feather.replace();
    </script>

    <?php if ($action): ?>
        <script>
            <?php if ($action == 'add'): ?>show_add();<?php endif; ?>
            <?php if ($action == 'del'): ?>show_del();<?php endif; ?>
            <?php if ($action == 'edit'): ?>show_update();<?php endif; ?>
        </script>
    <?php endif; ?>
</body>
</html>