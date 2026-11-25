#!/usr/bin/env bash
# Teste de fumaça ponta a ponta: sobe contra um servidor rodando e percorre
# os fluxos principais (cadastro, login, cardápio, confirmação, avaliação,
# permissões e proteções de segurança).
#
# Uso:  BASE_URL=http://127.0.0.1:8765 ADMIN_EMAIL=admin@teste.com ADMIN_PASSWORD=Admin1234 bash tests/smoke.sh
# Requer um banco vazio (apenas com o admin criado por bin/create-admin.php).

set -u

BASE_URL="${BASE_URL:-http://127.0.0.1:8765}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@teste.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-Admin1234}"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
PASS=0
FAIL=0

ok()   { PASS=$((PASS + 1)); printf '  \033[32m✔\033[0m %s\n' "$1"; }
fail() { FAIL=$((FAIL + 1)); printf '  \033[31m✘ %s\033[0m\n' "$1"; }
check() { if eval "$2"; then ok "$1"; else fail "$1"; fi; }

# req <jar> <method> <path> [curl args...]  -> grava corpo em $WORK/body, status em $STATUS, Location em $LOCATION
req() {
  local jar="$1" method="$2" path="$3"; shift 3
  local out
  out=$(curl -s -o "$WORK/body" -D "$WORK/headers" -w '%{http_code}' -b "$WORK/$jar" -c "$WORK/$jar" -X "$method" "$BASE_URL$path" "$@")
  STATUS="$out"
  LOCATION=$(grep -i '^location:' "$WORK/headers" | tr -d '\r' | sed 's/^[Ll]ocation: //')
}
token() { grep -o 'name="_token" value="[^"]*"' "$WORK/body" | head -1 | sed 's/.*value="//; s/"$//'; }
body_has() { grep -q -- "$1" "$WORK/body"; }
# has_points <jar> <n>: o perfil do aluno logado em <jar> mostra exatamente <n> pontos
has_points() { curl -s -b "$WORK/$1" "$BASE_URL/perfil" | grep -q "<dt>Pontos</dt><dd>$2</dd>"; }
TODAY=$(date +%Y-%m-%d)
TOMORROW=$(date -d tomorrow +%Y-%m-%d 2>/dev/null || date -v+1d +%Y-%m-%d)

echo "Páginas públicas"
req anon GET /
check "início responde 200" '[ "$STATUS" = 200 ] && body_has "desperdício"'
req anon GET /painel
check "painel exige login (302 -> /entrar)" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */entrar ]]'
req anon GET /nao-existe
check "rota inexistente dá 404" '[ "$STATUS" = 404 ]'
req anon GET /termos
check "termos responde 200" '[ "$STATUS" = 200 ]'
req anon GET /.env
check ".env não é servido" '[ "$STATUS" = 404 ]'

echo "Cadastro"
req aluno GET /cadastro
T=$(token)
req aluno POST /cadastro --data-urlencode "_token=$T" --data-urlencode 'name=<script>alert(1)</script> Silva' \
  --data-urlencode 'email=aluno@teste.com' --data-urlencode 'rm=12345' --data-urlencode 'codigo=101' \
  --data-urlencode 'password=Senha1234' --data-urlencode 'password_confirmation=Senha1234' --data-urlencode 'terms=1'
check "cadastro válido redireciona ao painel" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'

req outro GET /cadastro
T=$(token)
req outro POST /cadastro --data-urlencode "_token=$T" --data-urlencode 'name=Outro' --data-urlencode 'email=aluno@teste.com' \
  --data-urlencode 'rm=12345' --data-urlencode 'codigo=101' --data-urlencode 'password=curta' \
  --data-urlencode 'password_confirmation=curta' --data-urlencode 'terms=1'
req outro GET /cadastro
check "cadastro inválido mostra erros (e-mail duplicado / senha fraca)" 'body_has "já está cadastrado" && body_has "pelo menos 8"'

echo "Segurança"
req aluno POST /refeicao/confirmar --data 'menu_id=1'
check "POST sem token CSRF é recusado (419)" '[ "$STATUS" = 419 ]'
req aluno GET /admin/usuarios
check "aluno não acessa /admin/usuarios (403)" '[ "$STATUS" = 403 ]'
req aluno GET /painel
T=$(token)
req aluno POST /admin/pontos/zerar --data-urlencode "_token=$T" --data 'confirmation=ZERAR'
check "aluno não consegue zerar pontos (403)" '[ "$STATUS" = 403 ]'
req aluno GET /admin/confirmacoes/dados
check "aluno não lê JSON de confirmações (403)" '[ "$STATUS" = 403 ]'

echo "Login"
req admin GET /entrar
T=$(token)
req admin POST /entrar --data-urlencode "_token=$T" --data-urlencode 'identifier=admin@teste.com' --data-urlencode 'password=errada'
check "senha errada volta para /entrar" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */entrar ]]'
req admin GET /entrar
T=$(token)
req admin POST /entrar --data-urlencode "_token=$T" --data-urlencode "identifier=$ADMIN_EMAIL" --data-urlencode "password=$ADMIN_PASSWORD"
check "admin faz login" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'
req admin GET /painel
check "painel do admin carrega" '[ "$STATUS" = 200 ] && body_has "Painel da cozinha"'

echo "Cardápio"
req admin GET /admin/cardapio
T=$(token)
req admin POST /admin/cardapio --data-urlencode "_token=$T" --data-urlencode "menu_date=$TODAY" --data-urlencode 'dish=Arroz, feijão & <b>frango</b>'
check "admin cadastra refeição de hoje" '[ "$STATUS" = 302 ]'
req admin POST /admin/cardapio --data-urlencode "_token=$T" --data-urlencode "menu_date=$TOMORROW" --data-urlencode 'dish=Macarrão'
req admin POST /admin/cardapio --data-urlencode "_token=$T" --data-urlencode 'menu_date=2000-01-01' --data-urlencode 'dish=Velha'
req admin GET /admin/cardapio
check "refeição aparece escapada e data passada é recusada" 'body_has "&lt;b&gt;frango&lt;/b&gt;" && ! body_has "<b>frango</b>" && body_has "datas passadas"'

echo "Fluxo do aluno"
req aluno GET /painel
check "painel do aluno mostra a refeição de hoje" 'body_has "Refeição de hoje" && body_has "&lt;b&gt;frango"'
check "nome com HTML é exibido escapado" 'body_has "&lt;script&gt;" || ! body_has "<script>alert"'
T=$(token)
MENU_ID=$(grep -o 'name="menu_id" value="[0-9]*"' "$WORK/body" | head -1 | grep -o '[0-9]*')
req aluno POST /refeicao/confirmar --data-urlencode "_token=$T" --data "menu_id=$MENU_ID"
req aluno POST /refeicao/confirmar --data-urlencode "_token=$T" --data "menu_id=$MENU_ID"
check "confirmar sozinho não dá pontos (só a retirada dá)" 'has_points aluno 0'

req admin GET /admin/confirmacoes/dados
check "JSON de confirmações traz o aluno, sem HTML executável" '[ "$STATUS" = 200 ] && grep -q "\"total\":1" "$WORK/body" && grep -q "<script>" "$WORK/body"'

req aluno GET "/avaliar?refeicao=$MENU_ID"
check "não dá para avaliar antes de retirar" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'

echo "Retirada"
req aluno GET /painel
T=$(token)
ALUNO_UID=$(curl -s -b "$WORK/admin" "$BASE_URL/admin/confirmacoes/dados" | grep -o '"people":\[{"id":[0-9]*' | grep -o '[0-9]*$')
req aluno POST /admin/confirmacoes/retirada --data-urlencode "_token=$T" --data "menu_id=$MENU_ID" --data "user_id=$ALUNO_UID" --data 'picked_up=1'
check "aluno não consegue marcar a própria retirada (403)" '[ "$STATUS" = 403 ]'
req admin GET /admin/confirmacoes
check "tela de confirmações mostra o botão de retirada" '[ "$STATUS" = 200 ] && body_has "Marcar retirada" && body_has "Encerrar retirada"'
TA=$(token)
pickup() { req admin POST /admin/confirmacoes/retirada --data-urlencode "_token=$TA" --data "menu_id=$1" --data "user_id=$2" --data "picked_up=$3" "${@:4}"; }
pickup "$MENU_ID" "$ALUNO_UID" 1
check "cozinha marca a retirada (volta para a lista)" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == *admin/confirmacoes* ]]'
pickup "$MENU_ID" "$ALUNO_UID" 1
check "retirada dá +10 pontos uma única vez" 'has_points aluno 10'
pickup "$MENU_ID" "$ALUNO_UID" 0
check "desmarcar a retirada devolve os pontos" 'has_points aluno 0'
pickup "$MENU_ID" "$ALUNO_UID" 1 -H 'Accept: application/json'
check "marcar pela tela (JSON) responde com a lista atualizada" '[ "$STATUS" = 200 ] && grep -q "\"pickedUp\":1" "$WORK/body"'
check "e os pontos voltam (10)" 'has_points aluno 10'
pickup "$((MENU_ID + 1))" "$ALUNO_UID" 1 -H 'Accept: application/json'
check "não marca retirada de refeição futura" '[ "$STATUS" = 422 ]'
pickup "$MENU_ID" 999999 1 -H 'Accept: application/json'
check "não marca retirada de quem não confirmou" '[ "$STATUS" = 404 ]'
req admin GET "/admin/confirmacoes/exportar?data=$TODAY"
check "CSV de confirmados mostra quem retirou" 'grep -q "Sim (" "$WORK/body"'
req aluno GET /painel
check "painel do aluno mostra a refeição como retirada" 'body_has "Retirada ·"'

req aluno GET "/avaliar?refeicao=$MENU_ID"
T=$(token)
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=9'
check "nota fora de 1..5 é recusada" '[[ "$LOCATION" == *avaliar* ]]'
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=5' --data-urlencode 'comment=Muito bom'
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=1'
check "avaliação soma 5 pts uma única vez (15 pts)" 'has_points aluno 15'
req aluno POST /refeicao/cancelar --data-urlencode "_token=$T" --data "menu_id=$MENU_ID"
check "não dá para cancelar depois de retirar/avaliar" 'has_points aluno 15'

echo "Encerrar retirada (quem confirmou e não retirou)"
req faltou GET /cadastro
T=$(token)
req faltou POST /cadastro --data-urlencode "_token=$T" --data-urlencode 'name=Zeca Faltoso' \
  --data-urlencode 'email=faltou@teste.com' --data-urlencode 'rm=55555' --data-urlencode 'codigo=101' \
  --data-urlencode 'password=Senha1234' --data-urlencode 'password_confirmation=Senha1234' --data-urlencode 'terms=1'
req faltou GET /painel
T=$(token)
req faltou POST /refeicao/confirmar --data-urlencode "_token=$T" --data "menu_id=$MENU_ID"
FALTOU_UID=$(curl -s -b "$WORK/admin" "$BASE_URL/admin/confirmacoes/dados" | grep -o '"id":[0-9]*,"name":"Zeca' | grep -o '[0-9]*')
# Zeca retira e avalia (15), depois a cozinha percebe o engano e desmarca a retirada (fica com os 5 da avaliação)
pickup "$MENU_ID" "$FALTOU_UID" 1
req faltou GET "/avaliar?refeicao=$MENU_ID"
T=$(token)
req faltou POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=3'
check "segundo aluno retirou e avaliou (15)" 'has_points faltou 15'
pickup "$MENU_ID" "$FALTOU_UID" 0
check "retirada desmarcada: sobram os 5 da avaliação" 'has_points faltou 5'
req admin POST /admin/confirmacoes/encerrar --data-urlencode "_token=$TA" --data "menu_id=$MENU_ID" --data 'closed=1'
req admin GET "/admin/confirmacoes?data=$TODAY"
check "encerrar informa quantos não retiraram" 'body_has "1 aluno(s) confirmaram e não retiraram" && body_has "Retirada encerrada"'
check "quem não retirou perde 5 pontos (5 → 0)" 'has_points faltou 0'
check "quem retirou não perde nada" 'has_points aluno 15'
req admin POST /admin/confirmacoes/encerrar --data-urlencode "_token=$TA" --data "menu_id=$MENU_ID" --data 'closed=1'
check "encerrar duas vezes não tira pontos de novo (nem fica negativo)" 'has_points faltou 0'
req admin POST /admin/confirmacoes/encerrar --data-urlencode "_token=$TA" --data "menu_id=$MENU_ID" --data 'closed=0'
check "reabrir devolve os pontos perdidos (5)" 'has_points faltou 5'
req admin POST /admin/confirmacoes/encerrar --data-urlencode "_token=$TA" --data "menu_id=$MENU_ID" --data 'closed=1'
check "encerrar de novo aplica a perda uma única vez (0)" 'has_points faltou 0'
pickup "$MENU_ID" "$FALTOU_UID" 1
check "marcar a retirada depois de encerrar corrige tudo (15)" 'has_points faltou 15'
pickup "$MENU_ID" "$FALTOU_UID" 0
check "e desmarcar volta à perda (0)" 'has_points faltou 0'
req faltou GET "/avaliar?refeicao=$MENU_ID"
check "quem não retirou não avalia" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'

req admin GET /admin/avaliacoes
check "admin vê a avaliação" 'body_has "Muito bom"'

echo "Desperdício e previsão"
req aluno GET /admin/desperdicio
check "aluno não acessa o relatório de desperdício (403)" '[ "$STATUS" = 403 ]'
req admin GET /painel
check "painel do admin sugere quantas porções preparar" 'body_has "Prepare cerca de"'
req admin GET "/admin/desperdicio/registrar?id=$MENU_ID"
check "tela de registro abre com a sugestão" '[ "$STATUS" = 200 ] && body_has "Sugestão do sistema"'
T=$(token)
req admin POST "/admin/desperdicio/registrar?id=$MENU_ID" --data-urlencode "_token=$T" --data 'prepared_portions=10' --data 'leftover_portions=12'
req admin GET "/admin/desperdicio/registrar?id=$MENU_ID"
check "sobras maiores que o preparado são recusadas" 'body_has "não podem ser maiores"'
T=$(token)
req admin POST "/admin/desperdicio/registrar?id=$MENU_ID" --data-urlencode "_token=$T" --data 'prepared_portions=10' --data 'leftover_portions=2' --data-urlencode 'waste_kg=1,5'
check "registro válido redireciona ao relatório" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */admin/desperdicio ]]'
req admin GET /admin/desperdicio
check "relatório mostra 20% de desperdício e 1,5 kg" 'body_has "20,0%" && body_has "1,5 kg"'
req admin GET "/admin/desperdicio/registrar?id=$((MENU_ID + 1))"
check "não registra sobras de refeição futura" '[ "$STATUS" = 302 ]'
req admin GET /admin/desperdicio/exportar
check "exporta o relatório em CSV para Excel" 'grep -qi "^content-type: text/csv" "$WORK/headers" && grep -q "Desperdício (%)" "$WORK/body" && grep -q "20,0" "$WORK/body"'
req admin GET "/admin/confirmacoes/exportar?data=$TODAY"
check "exporta a lista de confirmados em CSV" 'grep -qi "^content-type: text/csv" "$WORK/headers" && grep -q "12345" "$WORK/body"'
check "CSV neutraliza fórmulas (nome iniciado por =/+/-/@ não vira fórmula)" '! grep -q ";=" "$WORK/body"'

echo "Recuperação de senha"
MAIL_LOG="${MAIL_LOG:-storage/logs/mail.log}"
: > "$WORK/mail.before"; [ -f "$MAIL_LOG" ] && cp "$MAIL_LOG" "$WORK/mail.before"
req reset GET /esqueci-senha
check "tela 'esqueci minha senha' abre" '[ "$STATUS" = 200 ] && body_has "Enviar link"'
T=$(token)
req reset POST /esqueci-senha --data-urlencode "_token=$T" --data-urlencode 'email=ninguem@teste.com'
req reset GET /entrar
check "e-mail inexistente recebe a mesma resposta (não revela contas)" 'body_has "Se existir uma conta"'
check "e nenhum e-mail é enviado para conta inexistente" '! grep -q "ninguem@teste.com" "$MAIL_LOG" 2>/dev/null'
req reset2 GET /esqueci-senha
T=$(token)
req reset2 POST /esqueci-senha --data-urlencode "_token=$T" --data-urlencode 'email=aluno@teste.com'
RESET_LINK=$(grep -o "$BASE_URL/redefinir-senha?token=[a-f0-9]*" "$MAIL_LOG" 2>/dev/null | tail -1)
check "link de redefinição chega por e-mail" '[ -n "$RESET_LINK" ]'
RESET_PATH="${RESET_LINK#$BASE_URL}"
req reset2 GET "$RESET_PATH"
check "link abre a tela de nova senha" 'body_has "Crie uma nova senha"'
T=$(token); RTOKEN="${RESET_PATH##*token=}"
req reset2 POST /redefinir-senha --data-urlencode "_token=$T" --data "token=$RTOKEN" --data 'password=fraca' --data 'password_confirmation=fraca'
req reset2 GET "$RESET_PATH"
check "senha fraca é recusada na redefinição" 'body_has "pelo menos 8"'
T=$(token)
req reset2 POST /redefinir-senha --data-urlencode "_token=$T" --data "token=$RTOKEN" --data 'password=NovaSenha123' --data 'password_confirmation=NovaSenha123'
check "nova senha salva e volta para o login" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */entrar ]]'
req reset2 GET "$RESET_PATH"
check "o mesmo link não funciona duas vezes" 'body_has "Link inválido"'
req reset2 GET "/redefinir-senha?token=$(printf 'a%.0s' {1..64})"
check "token inventado é recusado" 'body_has "Link inválido"'
req novo GET /entrar
T=$(token)
req novo POST /entrar --data-urlencode "_token=$T" --data 'identifier=12345' --data 'password=NovaSenha123'
check "entra com a nova senha (pelo RM)" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'

echo "Login com Google"
# google_login <jar> <sub> <email> <nome>: percorre app -> Google falso -> callback
google_login() {
  req "$1" GET /auth/google
  local auth_url="$LOCATION"
  local cb
  cb=$(curl -s -o /dev/null -w '%{redirect_url}' -G "$auth_url" --data-urlencode "fake_sub=$2" --data-urlencode "fake_email=$3" --data-urlencode "fake_name=$4")
  CALLBACK_PATH="${cb#$BASE_URL}"
  req "$1" GET "$CALLBACK_PATH"
}
req g1 GET /entrar
check "botão 'Entrar com Google' aparece quando configurado" 'body_has "Entrar com Google"'
google_login g1 g-999 'novo.google@gmail.com' 'Gabi Google'
check "pessoa nova vai completar o cadastro" '[[ "$LOCATION" == */cadastro/google ]]'
req g1 GET /cadastro/google
check "cadastro traz nome e e-mail vindos do Google" 'body_has "novo.google@gmail.com" && body_has "Gabi Google"'
T=$(token)
req g1 POST /cadastro/google --data-urlencode "_token=$T" --data-urlencode 'name=Gabi Google' --data 'rm=77777' --data 'codigo=101' --data 'terms=1'
check "cadastro pelo Google cria a conta e entra" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'
req g1 GET /perfil
check "conta Google aparece como conectada no perfil" 'body_has "Conectada" && body_has "Criar senha"'
T=$(token)
req g1 POST /perfil/senha --data-urlencode "_token=$T" --data 'new_password=Senha9999' --data 'new_password_confirmation=Senha9999'
req g1 GET /perfil
check "conta só-Google cria senha sem pedir a atual" 'body_has "Senha criada" && body_has "Alterar senha"'
req g1 POST /sair --data-urlencode "_token=$T"
google_login g1b g-999 'novo.google@gmail.com' 'Gabi Google'
check "mesma conta Google entra direto no painel" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'
req g1b GET "$CALLBACK_PATH"
check "retorno do Google não pode ser reaproveitado (state de uso único)" '[[ "$LOCATION" == */entrar ]] || [[ "$LOCATION" == */perfil ]]'
google_login g2 g-123 'admin@teste.com' 'Cozinha'
check "e-mail já cadastrado é vinculado e entra" '[ "$STATUS" = 302 ] && [[ "$LOCATION" == */painel ]]'
req g2 GET /painel
check "entrou na conta certa (admin)" 'body_has "Painel da cozinha"'
req g3 GET /auth/google
FAKE_STATE=$(printf 'b%.0s' {1..32})
req g3 GET "/auth/google/callback?state=$FAKE_STATE&code=qualquer"
req g3 GET /entrar
check "state adulterado é recusado" 'body_has "não é válida"'
req g3 GET /painel
check "e não fica logado" '[ "$STATUS" = 302 ]'
req g4 GET /auth/google
cb=$(curl -s -o /dev/null -w '%{redirect_url}' -G "$LOCATION" --data-urlencode 'fake_error=access_denied')
req g4 GET "${cb#$BASE_URL}"
check "cancelar no Google volta ao login com aviso" '[[ "$LOCATION" == */entrar ]]'
req g5 GET /auth/google
cb=$(curl -s -o /dev/null -w '%{redirect_url}' -G "$LOCATION" --data-urlencode 'fake_sub=g-555' --data-urlencode 'fake_email=nao.verificado@gmail.com' --data 'fake_unverified=1')
req g5 GET "${cb#$BASE_URL}"
req g5 GET /entrar
check "e-mail Google não verificado é recusado" 'body_has "não foi verificado"'

echo "Administração de usuários"
req admin GET /admin/usuarios
check "lista de usuários escapa nomes" 'body_has "&lt;script&gt;alert(1)&lt;/script&gt;" && ! body_has "<script>alert(1)"'
T=$(token)
ALUNO_ID=$(grep -o 'name="id" value="[0-9]*"' "$WORK/body" | head -1 | grep -o '[0-9]*')
req admin POST /admin/pontos/deduzir --data-urlencode "_token=$T" --data 'points=100'
req aluno GET /ranking
check "deduzir pontos nunca deixa negativo" 'body_has ">0 pts<"'
req admin POST /admin/usuarios/excluir --data-urlencode "_token=$T" --data "id=$ALUNO_ID"
req admin GET /admin/usuarios
check "admin exclui aluno" '! body_has "aluno@teste.com"'
req aluno GET /painel
check "sessão do aluno excluído deixa de valer" '[ "$STATUS" = 302 ]'

echo "Logout"
req admin GET /painel
T=$(token)
req admin POST /sair --data-urlencode "_token=$T"
req admin GET /painel
check "após sair, painel volta a exigir login" '[ "$STATUS" = 302 ]'

echo
echo "Resultado: $PASS ok, $FAIL falha(s)"
[ "$FAIL" -eq 0 ]
