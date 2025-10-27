#!/bin/bash

###############################################################################
# Script de Testes: Sistema de Limites e Licitações
###############################################################################

BASE_URL="http://localhost:8000/api"
TOKEN=""

echo "======================================================================"
echo "TESTES: Sistema de Limites Freemium - Licita.pub"
echo "======================================================================"
echo ""

###############################################################################
# 1. TESTAR ENDPOINTS SEM AUTENTICAÇÃO (Anônimo)
###############################################################################

echo "1. Testando endpoints sem autenticação (usuário anônimo)..."
echo ""

# 1.1. Listar licitações
echo "  [1.1] GET /licitacoes/listar.php"
curl -s -X GET "$BASE_URL/licitacoes/listar.php?limite=5" \
  -H "Content-Type: application/json" | jq -r '.success, .paginacao.total'
echo ""

# 1.2. Buscar licitações
echo "  [1.2] GET /licitacoes/buscar.php"
curl -s -X GET "$BASE_URL/licitacoes/buscar.php?uf=SP&limite=5" \
  -H "Content-Type: application/json" | jq -r '.success, .filtros.uf'
echo ""

# 1.3. Verificar limite (sem consumir)
echo "  [1.3] GET /licitacoes/limite.php"
curl -s -X GET "$BASE_URL/licitacoes/limite.php" \
  -H "Content-Type: application/json" | jq '.data'
echo ""

# 1.4. Testar consulta detalhada (CONSOME limite)
echo "  [1.4] GET /licitacoes/detalhes.php (CONSOME LIMITE)"
echo "  Primeira consulta (deve funcionar):"
curl -s -X GET "$BASE_URL/licitacoes/detalhes.php?id=teste-123" \
  -H "Content-Type: application/json" | jq -r '.success, .limite.restantes'
echo ""

###############################################################################
# 2. TESTAR AUTENTICAÇÃO
###############################################################################

echo ""
echo "2. Testando sistema de autenticação..."
echo ""

# 2.1. Cadastrar usuário
echo "  [2.1] POST /auth/register.php"
RANDOM_EMAIL="teste_$(date +%s)@licita.pub"
curl -s -X POST "$BASE_URL/auth/register.php" \
  -H "Content-Type: application/json" \
  -d "{
    \"nome\": \"Usuário Teste\",
    \"email\": \"$RANDOM_EMAIL\",
    \"senha\": \"Teste@123\",
    \"cpf_cnpj\": \"12345678901\"
  }" | jq -r '.success, .message'
echo ""

# 2.2. Login
echo "  [2.2] POST /auth/login.php"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login.php" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$RANDOM_EMAIL\",
    \"senha\": \"Teste@123\"
  }")

echo "$LOGIN_RESPONSE" | jq -r '.success, .message'
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.access_token')
echo "  Token: ${TOKEN:0:20}..."
echo ""

# 2.3. Verificar dados do usuário
echo "  [2.3] GET /auth/me.php"
curl -s -X GET "$BASE_URL/auth/me.php" \
  -H "Authorization: Bearer $TOKEN" | jq -r '.success, .data.email, .data.plano'
echo ""

###############################################################################
# 3. TESTAR LIMITES COM USUÁRIO AUTENTICADO (FREE = 10 consultas/dia)
###############################################################################

echo ""
echo "3. Testando limites com usuário autenticado (FREE)..."
echo ""

# 3.1. Verificar limite inicial
echo "  [3.1] Verificar limite inicial (FREE = 10/dia)"
curl -s -X GET "$BASE_URL/licitacoes/limite.php" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.tipo, .data.limite_diario, .data.restantes'
echo ""

# 3.2. Fazer consultas até atingir limite
echo "  [3.2] Fazendo consultas detalhadas (até atingir limite)..."
for i in {1..5}; do
  echo "    Consulta $i:"
  curl -s -X GET "$BASE_URL/licitacoes/detalhes.php?id=teste-$i" \
    -H "Authorization: Bearer $TOKEN" | jq -r '.success, .limite.consultas_hoje, .limite.restantes'
done
echo ""

# 3.3. Verificar limite após consultas
echo "  [3.3] Verificar limite após consultas"
curl -s -X GET "$BASE_URL/licitacoes/limite.php" \
  -H "Authorization: Bearer $TOKEN" | jq '.data | {tipo, consultas_hoje, restantes, atingiu_limite}'
echo ""

###############################################################################
# 4. TESTAR LIMITE EXCEDIDO (429 Too Many Requests)
###############################################################################

echo ""
echo "4. Testando limite excedido..."
echo ""

echo "  [4.1] Fazendo mais 6 consultas (deve atingir limite no meio)..."
for i in {6..11}; do
  echo "    Consulta $i:"
  RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/licitacoes/detalhes.php?id=teste-$i" \
    -H "Authorization: Bearer $TOKEN")

  HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
  BODY=$(echo "$RESPONSE" | head -n-1)

  echo "      HTTP $HTTP_CODE: $(echo "$BODY" | jq -r '.message // .success')"

  if [ "$HTTP_CODE" = "429" ]; then
    echo "      ✓ Limite atingido corretamente!"
    break
  fi
done
echo ""

###############################################################################
# 5. TESTAR RATE LIMIT HEADERS
###############################################################################

echo ""
echo "5. Verificando headers de rate limiting..."
echo ""

curl -s -v -X GET "$BASE_URL/licitacoes/limite.php" \
  -H "Authorization: Bearer $TOKEN" 2>&1 | grep -i "x-ratelimit"
echo ""

###############################################################################
# 6. TESTAR LOGOUT
###############################################################################

echo ""
echo "6. Testando logout..."
echo ""

curl -s -X POST "$BASE_URL/auth/logout.php" \
  -H "Authorization: Bearer $TOKEN" | jq -r '.success, .message'
echo ""

# Tentar acessar após logout (deve falhar)
echo "  [6.1] Tentar acessar após logout (deve falhar)"
curl -s -X GET "$BASE_URL/auth/me.php" \
  -H "Authorization: Bearer $TOKEN" | jq -r '.success, .error'
echo ""

###############################################################################
# 7. ESTATÍSTICAS
###############################################################################

echo ""
echo "7. Estatísticas gerais..."
echo ""

curl -s -X GET "$BASE_URL/licitacoes/estatisticas.php" | jq '.data'
echo ""

###############################################################################
# FIM
###############################################################################

echo "======================================================================"
echo "TESTES CONCLUÍDOS"
echo "======================================================================"
