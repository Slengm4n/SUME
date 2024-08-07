<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUME | Confirmações</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link href="../assets/css/pages/confirmations.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a81368914c.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        
        function fetchConfirmations() {
            $.ajax({
                url: '../services/fetch_confirmations.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Atualiza o prato do dia
                    $('.container-content p').text(data.meal ? data.meal.dish : 'Nenhuma refeição cadastrada para hoje.');

                    // Atualiza o total de confirmações
                    $('.total_confirmations span').text(data.total_confirmations);

                    // Atualiza a tabela de confirmações
                    var tbody = $('#confirmations_table tbody');
                    tbody.empty();
                    if (data.confirmations.length > 0) {
                        data.confirmations.forEach(function(user) {
                            var row = $('<tr>');
                            row.append('<td><img class="img_table" src="' + user.profile_picture + '"></td>');
                            row.append('<td>' + user.id + '</td>');
                            row.append('<td>' + user.RM + '</td>');
                            row.append('<td>' + user.name + '</td>');
                            tbody.append(row);
                        });
                    } else {
                        tbody.append('<tr><td colspan="4">Nenhuma confirmação para hoje.</td></tr>');
                    }
                }
            });
        }

        $(document).ready(function() {
            // Carrega as confirmações a cada 5 segundos
            fetchConfirmations();
            setInterval(fetchConfirmations, 5000);
        });
    </script>
</head>

<body>
<nav class="navbar">
        <ul class="navbar-list">
            <li class="nav-item"><a href="../public/home.php" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="../admin/points.php" class="nav-link">Ranking</a></li>
            <li class="nav-item"><a href="../admin/view_feedbacks.php" class="nav-link">Feedbacks</a></li>
        </ul>
    </nav>


    <div class="animation_confirmations">
        <h1 class="main-title">Confirmações de Participação</h1>

        <div class="container-content">
            <h2>Refeição do Dia:</h2>
            <p>Nenhuma refeição cadastrada para hoje.</p>
        </div>

        <div class="total_confirmations">
            <p>Total de confirmações: <span>0</span></p>
        </div>

        <div class="container-confirmations">
            <table class="table_confirmations" id="confirmations_table">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>RM</th>
                        <th>Nome do Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4">Nenhuma confirmação para hoje.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="loader"></div>
</body>
<script src="../assets/js/menu.js"></script>
<script src="../assets/js/loader.js"></script>
</html>
