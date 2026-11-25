![Logo](public/assets/img/logo-sume.png)

# SUME — Sistema Único de Merenda Escolar

O SUME ajuda escolas a **reduzir o desperdício da merenda**: cada aluno confirma se vai comer, a cozinha vê em tempo real quantas refeições preparar e, depois, os alunos avaliam o que comeram.

## Funcionalidades

**Alunos**
- Confirmar (e cancelar) presença na refeição do dia e dos próximos dias, até um horário limite configurável
- Avaliar com 1 a 5 estrelas e comentário as refeições que retirou
- Ganhar pontos e acompanhar o ranking da escola e o próprio comparecimento (veja [Pontos](#pontos))
- Editar perfil, foto e senha
- Entrar com a conta Google ou com e-mail/RM e senha; recuperar a senha por e-mail

**Administradores (cozinha/gestão)**
- Cadastrar o cardápio por data
- Acompanhar as confirmações ao vivo (atualiza sozinho), exportar para Excel ou imprimir/salvar em PDF
- **Registro de retirada:** na hora de servir, marcar quem pegou o prato com um toque (ou digitar o RM e apertar Enter); ao terminar, encerrar a retirada do dia
- **Previsão de demanda:** o sistema sugere quantas porções preparar com base nas confirmações e no comparecimento histórico (inclusive por dia da semana)
- **Controle de desperdício:** registrar porções preparadas, sobras e kg descartados; relatório com taxa de desperdício, comparação com o período anterior, gráfico semanal e exportação para Excel
- Ver avaliações com média e distribuição das notas
- Gerenciar usuários (editar, promover a admin, excluir)
- Deduzir ou zerar pontos do ranking

## Stack

- **PHP 8.1+** puro (sem framework), PDO/MySQL
- **MySQL 5.7+ / MariaDB 10.3+**
- HTML + CSS + JavaScript sem dependências (tema claro/escuro automático)
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) para o formulário de contato

## Instalação (XAMPP / WampServer)

1. **Clone** dentro da pasta `htdocs` do XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Slengm4n/SUME.git
   ```
2. **Inicie** o Apache e o MySQL no painel do XAMPP.
3. **Crie o banco** (no terminal ou colando o arquivo na aba SQL do phpMyAdmin):
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < database/schema.sql
   ```
4. **Configure**: copie `.env.example` para `.env` e ajuste se precisar (usuário/senha do banco, SMTP, horário limite).
5. **Crie o primeiro administrador**:
   ```bash
   C:\xampp\php\php.exe bin/create-admin.php "Nome da Cozinha" cozinha@escola.com
   ```
6. Acesse **http://localhost/SUME** (ou `http://localhost:8080/SUME`, conforme a porta do seu Apache).

> O `.htaccess` da raiz encaminha tudo para `public/`, então o código, o `.env` e o banco nunca ficam acessíveis pelo navegador. Em produção, prefira apontar o *DocumentRoot* direto para `public/`.

### Sem XAMPP (servidor embutido do PHP)

```bash
php -S localhost:8000 -t public public/index.php
```

## Atualizando a partir da versão antiga

1. **Faça backup do banco**: `mysqldump -u root sume_db > backup.sql`
2. Rode as migrações em ordem (feitas para MariaDB, que vem no XAMPP):
   ```bash
   mysql -u root sume_db < database/migrations/001_atualiza_banco_legado.sql
   mysql -u root sume_db < database/migrations/002_controle_desperdicio.sql
   mysql -u root sume_db < database/migrations/003_login_google_e_recuperacao_senha.sql
   mysql -u root sume_db < database/migrations/004_registro_de_retirada.sql
   ```
3. Mova as fotos antigas de `uploads/` para `public/uploads/avatars/`.
4. Crie o `.env` (passo 4 da instalação).

O que muda para os usuários:
- O login agora é por **e-mail ou RM** (antes aceitava o nome, que não é único).
- Senhas novas precisam ter **8+ caracteres com letras e números**.

## Estrutura do projeto

```
SUME/
├── app/
│   ├── Controllers/        # Recebem a requisição e escolhem a view
│   │   └── Admin/          # Telas restritas a administradores
│   ├── Core/               # Roteador, sessão, CSRF, autenticação, validação, banco
│   ├── Repositories/       # TODO o SQL fica aqui (consultas preparadas)
│   ├── bootstrap.php       # Inicialização (autoload, .env, sessão, erros)
│   └── helpers.php         # Funções usadas nas views: e(), url(), icon()...
├── bin/create-admin.php    # Cria/promove administradores pelo terminal
├── config/app.php          # Configurações (lidas do .env)
├── database/
│   ├── schema.sql          # Estrutura completa do banco
│   └── migrations/         # Atualizações para bancos existentes
├── public/                 # Única pasta exposta ao navegador
│   ├── index.php           # Front controller (toda requisição passa aqui)
│   ├── assets/             # CSS, JS, imagens, PDF dos termos
│   └── uploads/avatars/    # Fotos de perfil (fora do git)
├── views/                  # Templates PHP: layouts, páginas e parciais
├── routes.php              # Todas as URLs da aplicação
└── tests/smoke.sh          # Teste ponta a ponta
```

**Fluxo de uma requisição:** `public/index.php` → `Router` (verifica CSRF e permissão da rota) → `Controller` → `Repository` (SQL) → `view()` (template + layout).

Para adicionar uma página: crie a rota em `routes.php`, o método no controller e o template em `views/pages/`.

## Banco de dados

| Tabela | Para que serve |
|---|---|
| `user` | Alunos e administradores (`is_master = 1`), pontos e foto |
| `menu` | Uma refeição por data (`menu_date` é único) + porções preparadas, sobras, kg descartados e quando a retirada foi encerrada |
| `confirmations` | Quem confirmou presença em cada refeição (único por aluno+refeição), quando retirou e quantos pontos aquela confirmação já rendeu |
| `feedbacks` | Nota (1–5) e comentário de cada aluno por refeição (único por aluno+refeição) |

As regras "uma refeição por dia", "uma confirmação por aluno" e "uma avaliação por aluno" são garantidas por índices únicos no próprio banco. Veja os detalhes em [`database/schema.sql`](database/schema.sql).

## Pontos

Os pontos premiam quem **aparece**, não só quem confirma, porque confirmar e faltar é justamente o que gera desperdício.

| Ação | Pontos | Configuração no `.env` |
|---|---|---|
| Confirmar presença | 0 | — |
| A cozinha marca que o aluno retirou | +10 | `POINTS_PICKUP` |
| Avaliar uma refeição retirada | +5 | `POINTS_FEEDBACK` |
| Confirmou, não retirou e a cozinha encerrou a retirada | −5 | `POINTS_NO_SHOW` |

Tudo pode ser desfeito sem distorcer o ranking: desmarcar uma retirada ou reabrir o dia devolve exatamente o que foi dado ou tirado. Cada confirmação guarda quanto já rendeu (`points_awarded`) e toda mudança aplica só a diferença. O saldo nunca fica negativo. A lógica está em [`app/Repositories/ConfirmationRepository.php`](app/Repositories/ConfirmationRepository.php).

> Se a cozinha não encerrar a retirada, ninguém perde pontos. Assim, uma escola que ainda não usa o registro de retirada não pune os alunos por engano.

## E-mail e login com Google

Os dois são opcionais: sem configuração, o SUME funciona com e-mail/RM e senha, e as telas avisam o que falta.

### Enviar e-mails pelo Gmail (recuperação de senha e contato)

1. Na conta Gmail que vai enviar os e-mails, ative a **verificação em duas etapas**: https://myaccount.google.com/security
2. Crie uma **senha de app**: https://myaccount.google.com/apppasswords (nome: "SUME")
3. No `.env`, preencha (a senha de app tem 16 letras, cole sem espaços):
   ```
   MAIL_USERNAME=suaconta@gmail.com
   MAIL_PASSWORD=abcdefghijklmnop
   MAIL_FROM_ADDRESS=suaconta@gmail.com
   MAIL_SUPPORT_ADDRESS=suaconta@gmail.com
   ```

Para testar sem enviar nada, use `MAIL_DRIVER=log`: os e-mails vão para `storage/logs/mail.log`.

### Entrar com Google

1. Acesse https://console.cloud.google.com e crie um projeto (ex.: "SUME").
2. **APIs e serviços → Tela de consentimento OAuth**: tipo **Externo**, nome "SUME", seu e-mail de suporte. Em "Público-alvo", adicione seu e-mail como **usuário de teste** (enquanto o app estiver "Em teste", só usuários de teste conseguem entrar).
3. **APIs e serviços → Credenciais → Criar credenciais → ID do cliente OAuth → Aplicativo da Web.**
   Em **URIs de redirecionamento autorizados**, coloque exatamente:
   ```
   http://localhost:8080/SUME/auth/google/callback
   ```
   (ajuste porta/pasta se o seu XAMPP for diferente; em produção, use o endereço público com https)
4. Copie o **ID do cliente** e a **chave secreta** para o `.env`:
   ```
   APP_URL=http://localhost:8080/SUME
   GOOGLE_CLIENT_ID=123456-abc.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-...
   ```
5. Opcional: `GOOGLE_ALLOWED_DOMAINS=etec.sp.gov.br` para aceitar só e-mails da escola.

Como funciona: quem já tem conta com o mesmo e-mail é vinculado automaticamente; quem é novo completa o cadastro com RM e código da ETEC. Pelo perfil dá para conectar/desconectar o Google e criar uma senha.

> **Em produção, sempre defina `APP_URL`.** Ele é usado nos links enviados por e-mail e no retorno do Google; sem ele, o endereço viria do cabeçalho da requisição, que pode ser forjado.

## Como funciona a previsão de demanda

Nem todo aluno que confirma aparece. Para cada refeição com sobras registradas, o SUME calcula a **taxa de comparecimento** = porções servidas (preparadas − sobras) ÷ confirmados. A sugestão de preparo é:

```
porções = confirmados × mediana das taxas × 1,05 (margem de segurança)
```

Se houver pelo menos 3 registros do **mesmo dia da semana**, usa só eles (ex.: às sextas costuma vir menos gente). Sem histórico, sugere confirmados + 5%. A lógica está em [`app/Services/DemandForecast.php`](app/Services/DemandForecast.php).

## Segurança

- Consultas 100% preparadas (sem SQL injection)
- Todo dado exibido passa por `e()` (sem XSS)
- Token CSRF obrigatório em todo POST
- Permissão por rota (`auth` / `admin`) aplicada no roteador
- Sessão com cookie `HttpOnly` + `SameSite`, ID regenerado no login, limite de tentativas de login
- Login com Google via OpenID Connect com `state`, `nonce` e PKCE; só aceita e-mail verificado
- Links de redefinição de senha: aleatórios, de uso único, expiram em 60 min e ficam só como hash no banco; a resposta não revela se o e-mail existe
- Upload de foto validado pelo conteúdo real do arquivo; scripts não executam em `uploads/`
- Credenciais só no `.env` (fora do git)

## Testes

Com o servidor rodando e um banco vazio contendo só o admin de teste:

```bash
php bin/create-admin.php "Admin" admin@teste.com      # senha: Admin1234
php -S 127.0.0.1:8766 tests/fake-google.php &            # Google falso
MAIL_DRIVER=log GOOGLE_CLIENT_ID=test-client GOOGLE_CLIENT_SECRET=test-secret \
  GOOGLE_AUTH_URL=http://127.0.0.1:8766/auth GOOGLE_TOKEN_URL=http://127.0.0.1:8766/token \
  php -S 127.0.0.1:8765 -t public public/index.php &
BASE_URL=http://127.0.0.1:8765 bash tests/smoke.sh
```

O GitHub Actions roda lint + esse teste a cada push/PR (`.github/workflows/php.yml`).

## Autores

- [@Slengm4n](https://www.github.com/Slengm4n)
- [@Ardoh4in](https://github.com/Ardoh4in)
- [@HeitorZinn](https://github.com/HeitorZinn)
- [@ManenttiiBR](https://github.com/ManenttiiBR)

## Licença

[MIT](https://choosealicense.com/licenses/mit/)
