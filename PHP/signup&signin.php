
<?php

// Flag para verificar se o login é inválido
$is_invalid = false;

// Verifica se o formulário foi enviado usando o método POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Inclui a conexão com o banco de dados
    $mysqli = require __DIR__ . "/database.php";

    // Constrói a consulta SQL para recuperar o usuário com base no e-mail
    $sql = sprintf("SELECT * FROM user
            WHERE email = '%s'",
        $mysqli->real_escape_string($_POST["email"]));

    // Executa a consulta SQL
    $result = $mysqli->query($sql);

    // Obtém os dados do usuário
    $user = $result->fetch_assoc();

    // Verifica se o usuário existe e se a senha está correta
    if ($user && password_verify($_POST["password"], $user["password_hash"])) {

        // Inicia a sessão e regenera o ID da sessão por motivos de segurança
        session_start();
        session_regenerate_id();

        // Armazena o ID do usuário na sessão
        $_SESSION["user_id"] = $user["id"];

        // Redireciona para a página inicial após o login bem-sucedido
        header("Location: ./home.php");
        exit;
    }

    // Define a flag para indicar login inválido
    $is_invalid = true;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/f83240ef60.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../STYLES/style2.css" />
    <title>SUME | Cadastro & Login</title>
</head>

<body>
    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">
                <!-- Formulário de Login -->
                <form method="post" class="sign-in-form">
                    <h2 class="title">Login</h2>
                    <?php if ($is_invalid): ?>
                        <em>Login inválido</em>
                    <?php endif; ?>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="email" name="email" placeholder="Usuário" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Senha" />
                    </div>
                    <input type="submit" value="Login" class="btn solid" />
                </form>

                <!-- Formulário de Cadastro -->
                <form action="process-signup.php" method="post" class="sign-up-form">
                    <h2 class="title">Cadastro</h2>

                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Nome de usuário" />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Email" />
                    </div>

                    <div class="input-field">
                        <i class="fa-regular fa-address-book"></i>
                        <input type="text" id="RM" name="RM" placeholder="RM" />
                    </div>

                    <div class="input-field">
                        <i class="fa-solid fa-school"></i>
                        <input type="text" id="codigo" name="codigo" placeholder="Código Etec" />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Senha" />
                    </div>

                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirme sua senha" />
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
                    <button class="btn transparent" id="sign-up-btn">
                        Cadastre-se!
                    </button>
                </div>
                <img src="../IMG/log.svg" class="image" alt="" />
            </div>

            <div class="panel right-panel">
                <div class="content">
                    <h3>Um de nós?</h3>
                    <p>Entre em sua conta!</p>
                    <button class="btn transparent" id="sign-in-btn">
                        LOGAR
                    </button>
                </div>
                <img src="../IMG/register.svg" class="image" alt="" />
            </div>
        </div>
    </div>

    <!-- Script JavaScript -->
    <script src="../SCRIPTS/app.js"></script>
</body>
</html>
