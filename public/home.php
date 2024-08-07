<?php
session_start();

// Verifica se o usuário está logado, caso não esteja logado e tente executar um acesso forçado pela URL ira retornar para página de login
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
$profile_picture = "../assets/img/default.jpg";
if (!empty($user["profile_picture"])) {
    $profile_picture_path = "../uploads/" . $user["profile_picture"];
// Caso o usuário já tenha uma imagem cadastrada no banco será exibida
    if (file_exists($profile_picture_path)) {
        $profile_picture = $profile_picture_path;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUME | Home </title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link href="../assets/css/pages/home.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/f83240ef60.js" crossorigin="anonymous"></script>
</head>

<body>

<nav class="navbar">
    <img src="../assets/img/SUME.png" class="navbar-logo" alt="logo" />
    <ul class="navbar-list">
        <li class="nav-item"><a href="http://uniontech.42web.io/" target="_blank" class="nav-link">Uniontech</a></li>
        <li class="nav-item"><a href="../public/form.php" class="nav-link">Contato</a></li>
    </ul>
    
    <div class="profile-dropdown">
    <div onclick="toggle()" class="profile-dropdown-btn">
        <div class="profile-img">
            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Foto do perfil">
        </div>
    </div>
        <ul class="profile-dropdown-list">
            <li class="profile-dropdown-list-item">
                <a href="../public/profile.php">
                    <i class="fa-regular fa-user"></i>
                    Editar Perfil
                </a>
            </li>
            <li class="profile-dropdown-list-item">
                <a href="../admin/points.php">
                    <i class="fa-solid fa-chart-line"></i>
                    Ranking
                </a>
            </li>
            <li class="profile-dropdown-list-item">
                <a href="../public/terms.php">
                    <i class="fa-solid fa-sliders"></i>
                    Termos de Uso
                </a>
            </li>
            <li class="profile-dropdown-list-item">
                <a href="../services/logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Log out
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="home_container">
<?php if (isset($user) && $user): ?>
    <div class="wellcome">
        <div class="text">
            <h1>Sume | Home</h1>
        </div>
        <div class="user_name">
            <!--Exibe o nome de usuário cadastrado-->
            <p>Olá, <?= htmlspecialchars($user["name"]) ?></p>
        </div>
    </div>

 
    <!--Opções de usuário normal, caso o usuário logado seja "is_master=0"-->
        <?php if (!$user["is_master"]): ?>
            <div class="cards_user">
                 <div class="card">
                    <img src="../assets/img/confirmar-votos-white.svg">
                    <div>
                        <div class="card-title">
                            <h1>Confirmar presença</h1>
                        </div>
                        <br>
                        <div class="card-link">
                            <a href="../public/food.php">ACESSE</a>
                        </div>
                    </div>
                </div>

        <?php endif; ?>
    </div>

    
    <!--Opções de admin caso o usuário cadastrado seja "is_master=1"-->
    <?php if ($user["is_master"] == 1): ?>
        <div class="cards_admin">
            <!--Card Cadastrar Refeição-->
            <div class="card">
                <img src="../assets/img/cardapio-white.svg">
                <div>
                    <div class="card-title">
                        <h1>Cadastrar Refeição</h1>
                    </div>
                    <br>
                    <div class="card-link">
                        <a href="../admin/registration.php">ACESSE</a>
                    </div>
                </div>
            </div>
            <!--Card Visualizar Confirmações -->
            <div class="card">
                <img src="../assets/img/confirmar-votos-white.svg">
                <div>
                    <div class="card-title">
                        <h1>Confirmações</h1>
                    </div>
                    <br>
                    <div class="card-link">
                        <a href="../admin/confirmations.php">ACESSE</a>
                    </div>
                </div>
            </div>
            <!--Cards Usuarios Cadastrados -->
            <div class="card">
                <img src="../assets/img/editar-perfil-white.svg">
                <div>
                    <div class="card-title">
                        <h1>Usuários Cadastrados</h1>
                    </div>
                    <br>
                    <div class="card-link">
                        <a href="../admin/users.php">ACESSE</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="auth-links">
        <a href="signup&signin.php" class="btn">Log in</a>
        <span class="separator">ou</span>
        <a href="signup&signin.php" class="btn">Cadastro</a>
    </div>
<?php endif; ?>
</div>
<script src="../assets/js/drop_down_menu.js"></script>
</body>
</html>
