<?php
    // Iniciar a sessão
session_start();

    // Verificar se o usuário está logado, e se estiver redireciona para a home
if (isset($_SESSION["user_id"])) {
    header("Location: ../public/home.php");
    exit;
}

    // Verificar se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mysqli = require __DIR__ . "/../database/db.php";

 
    $identifier = $_POST["identifier"];
    $password = $_POST["password"];
    
    // Preparar a consulta
    $stmt = $mysqli->prepare("SELECT id, name, email, password_hash FROM user WHERE email = ? OR name = ?");
    
    // Vincular os parâmetros
    $stmt->bind_param("ss", $identifier, $identifier);
    
    // Executar a consulta
    $stmt->execute();
    
    // Obter o resultado
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verificar a senha
        if (password_verify($password, $user["password_hash"])) {
            // Iniciar a sessão e definir o ID do usuário
            session_start();
            $_SESSION["user_id"] = $user["id"];
            
            // Redirecionar para a página inicial
            header("Location: ../public/home.php");
            exit;
        } else {
            $invalid_login = true;
        }
    } else {
        $invalid_login = true;
    }
    
    // Fechar a consulta
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SUME | Cadastro & Login</title>
    <script src="https://kit.fontawesome.com/f83240ef60.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/pages/login.css" />
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">

                <!-- Formulário de Login -->
                <form method="post" class="sign-in-form">
                    <h2 class="title">Login</h2>

                    <!--Caso o login esteja inválido-->
                    <?php if (isset($invalid_login)): ?>
                        <em>Login inválido</em><br>
                    <?php endif; ?>

                    <!--Método identifier utilizado para que o usuário possa fazer login tanto com o email quanto com o username cadastrado-->
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="login_identifier" name="identifier" placeholder="Login" value="<?= htmlspecialchars($_POST["identifier"] ?? "") ?>" required>
                    </div>

                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="login_password" name="password" placeholder="Senha" required>
                    </div>

                    <input type="submit" value="Login" class="btn solid" />
                </form>


                <!-- Formulário de Cadastro -->
                <form action="../services/signup.php" method="post" class="sign-up-form">
                    <h2 class="title">Cadastro</h2>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="signup_name" name="name" placeholder="Nome de usuário" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="signup_email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-field">
                        <i class="fa-sharp fa-solid fa-clipboard"></i>
                        <input type="text" id="signup_RM" name="RM" placeholder="RM" required>
                    </div>
                    <div class="input-field">
                        <i class="fa-solid fa-school"></i>
                        <input type="text" id="signup_codigo" name="codigo" placeholder="Código ETEC" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="signup_password" name="password" placeholder="Senha" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="signup_password_confirmation" name="password_confirmation" placeholder="Confirme sua senha" required>
                    </div>
                    <input type="submit" class="btn" value="Cadastrar" />
                </form>
            </div>
        </div>

        <!-- Painéis de Navegação -->
        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>Novo por aqui?</h3>
                    <p>Faça já o cadastro da sua conta!</p>
                    <button class="btn transparent" id="sign-up-btn">Cadastre-se!</button>
                </div>
                <img src="../assets/img/login.svg" class="image" alt="img_login" />
            </div>

            <div class="panel right-panel">
                <div class="content">
                    <h3>Um de nós?</h3>
                    <p>Entre em sua conta!</p>
                    <button class="btn transparent" id="sign-in-btn">LOGAR</button>
                </div>
                <img src="../assets/img/register.svg" class="image" alt="img_cadastro" />
            </div>
        </div>
    </div>
     <script src="../assets/js/login_signup.js"></script>
</body>
</html>