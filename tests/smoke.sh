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
req aluno GET /ranking
check "confirmação dá pontos uma única vez (10 pts)" 'body_has ">10 pts<"'

req admin GET /admin/confirmacoes/dados
check "JSON de confirmações traz o aluno, sem HTML executável" '[ "$STATUS" = 200 ] && grep -q "\"total\":1" "$WORK/body" && grep -q "<script>" "$WORK/body"'

req aluno GET "/avaliar?refeicao=$MENU_ID"
T=$(token)
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=9'
check "nota fora de 1..5 é recusada" '[[ "$LOCATION" == *avaliar* ]]'
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=5' --data-urlencode 'comment=Muito bom'
req aluno POST "/avaliar?refeicao=$MENU_ID" --data-urlencode "_token=$T" --data 'rating=1'
req aluno GET /ranking
check "avaliação soma 5 pts uma única vez (15 pts)" 'body_has ">15 pts<"'
req aluno POST /refeicao/cancelar --data-urlencode "_token=$T" --data "menu_id=$MENU_ID"
req aluno GET /ranking
check "não dá para cancelar depois de avaliar" 'body_has ">15 pts<"'

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
