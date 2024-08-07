<!DOCTYPE html>
<html lang="pt_br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/pages/form.css">

    <title>Contato</title>
</head>
<body>
    <h1>Contato</h1>
    
    <form method="post" action="../services/send_email.php">
        <label for="name">Nome</label>
        <input type="text" name="name" id="name" required>

        <label for="email">E-mail</label>
        <input type="email" name="email" id="email" required>
        
        <label for="subject">Assunto</label>
        <input type="text" name="subject" id="subject" required>

        <label for="message">Mensagem</label>
        <textarea name="message" id="message" required></textarea>
        
        <br>

        <div class="main_btn">
            <button class="submit_btn">Enviar</button>
            <button class="cancel_btn" onclick="goBack()">Cancelar</button>
         </div>
         
    </form>
    <script>
    function goBack() {
      window.history.back();
    }
  </script>
</body>
</html>