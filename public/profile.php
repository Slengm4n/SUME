<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";
$user_id = $_SESSION["user_id"];

// Consulta os dados do usuário
$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Define a imagem de perfil padrão se não houver uma imagem definida
$profile_picture = $user["profile_picture"] ?: "../assets/img/default.jpg";

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil | SUME</title>
    <link rel="stylesheet" href="../assets/css/pages/profile.css" />
    <script src="https://kit.fontawesome.com/f83240ef60.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header>
        <div class="index-container">
            <nav class="navigation">
                <a href="#" class="logo">S<span>U</span>M<span>E</a>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="https://uniontech.42web.io/" target="_blank">Uniontech</a></li>
                    <li class="nav-item"><a href="home.php">Home</a></li>
                </ul>
                <div class="menu">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </nav>
        </div>
    </header>
    <div class="container light-style flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-4">Configuração de conta</h4>
        <div class="card overflow-hidden">
            <div class="row no-gutters row-bordered row-border-light">
                <div class="col-md-3 pt-0">
                    <div class="list-group list-group-flush account-settings-links">
                        <a class="list-group-item list-group-item-action active" data-toggle="list" href="#account-general">Geral</a>
                        <a class="list-group-item list-group-item-action" data-toggle="list" href="#account-change-password">Trocar senha</a>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="tab-content">
                        <div class="tab-pane fade active show" id="account-general">
                            <div class="card-body media align-items-center">
                                <img src="../uploads/<?= htmlspecialchars($profile_picture) ?>" alt="profile_pic" class="d-block ui-w-80" id="profile-picture">
                                <div class="media-body ml-4">
                                    <form id="profile-picture-form" action="../services/upload.php" method="post" enctype="multipart/form-data">
                                        <input type="file" id="profile-picture-input" name="profile_picture" accept="image/*" style="display:none;">
                                    </form>
                                </div>
                            </div>
                            <hr class="border-light m-0">
                            <div class="card-body">
                                <form method="post" action="../services/update_profile.php">
                                    <div class="form-group">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="name"value="<?= htmlspecialchars($user["name"]) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">E-mail</label>
                                        <input type="text" class="form-control" name="email" value="<?= htmlspecialchars($user["email"]) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Empresa</label>
                                        <input type="text" class="form-control" value="Company Ltd.">
                                    </div>
                                    <div class="text-right mt-3">
                                        <button type="submit" class="btn-send">Salvar mudanças</button>&nbsp;
                                        <button type="button" class="btn btn-default">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="account-change-password">
                            <div class="card-body pb-2">
                                <form method="post" action="../services/update_password.php">
                                    <div class="form-group">
                                        <label class="form-label">Senha atual</label>
                                        <input type="password" class="form-control" name="current_password">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nova senha</label>
                                        <input type="password" class="form-control" name="new_password">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Repita a nova senha</label>
                                        <input type="password" class="form-control" name="confirm_password">
                                    </div>
                                    <button type="submit" class="btn-send">Salvar alterações</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('profile-picture').addEventListener('click', function() {
            document.getElementById('profile-picture-input').click();
        });

        document.getElementById('profile-picture-input').addEventListener('change', function() {
            document.getElementById('profile-picture-form').submit();
        });
    </script>
</body>
</html>