#!/bin/bash

# Script de teste para API de Autenticação
# Execute: chmod +x test_auth_api.sh && ./test_auth_api.sh

BASE_URL="http://localhost/api/auth"
EMAIL="teste$(date +%s)@licita.pub"
SENHA="Teste123"
NOME="Usuário Teste"

echo "=========================================="
echo "Teste da API de Autenticação - Licita.pub"
echo "=========================================="
echo ""

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Testar Registro
echo -e "${YELLOW}[1/4] Testando registro de usuário...${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/register.php" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"senha\": \"$SENHA\",
    \"nome\": \"$NOME\"
  }")

echo "Resposta: $REGISTER_RESPONSE"

if echo "$REGISTER_RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Registro bem-sucedido${NC}"
else
  echo -e "${RED}✗ Falha no registro${NC}"
fi
echo ""

# 2. Testar Login
echo -e "${YELLOW}[2/4] Testando login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login.php" \
  -c /tmp/cookies.txt \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"senha\": \"$SENHA\"
  }")

echo "Resposta: $LOGIN_RESPONSE"

SESSION_ID=$(echo "$LOGIN_RESPONSE" | grep -o '"session_id":"[^"]*' | cut -d'"' -f4)

if [ -n "$SESSION_ID" ]; then
  echo -e "${GREEN}✓ Login bem-sucedido${NC}"
  echo "Session ID: $SESSION_ID"
else
  echo -e "${RED}✗ Falha no login${NC}"
fi
echo ""

# 3. Testar Me (com cookie)
echo -e "${YELLOW}[3/4] Testando endpoint /me (com cookie)...${NC}"
ME_RESPONSE=$(curl -s -X GET "$BASE_URL/me.php" \
  -b /tmp/cookies.txt)

echo "Resposta: $ME_RESPONSE"

if echo "$ME_RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Endpoint /me funcionando${NC}"
else
  echo -e "${RED}✗ Falha no endpoint /me${NC}"
fi
echo ""

# 4. Testar Logout
echo -e "${YELLOW}[4/4] Testando logout...${NC}"
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout.php" \
  -b /tmp/cookies.txt)

echo "Resposta: $LOGOUT_RESPONSE"

if echo "$LOGOUT_RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Logout bem-sucedido${NC}"
else
  echo -e "${RED}✗ Falha no logout${NC}"
fi
echo ""

# 5. Verificar se sessão foi invalidada
echo -e "${YELLOW}[Bonus] Verificando se sessão foi invalidada...${NC}"
ME_AFTER_LOGOUT=$(curl -s -X GET "$BASE_URL/me.php" \
  -b /tmp/cookies.txt)

if echo "$ME_AFTER_LOGOUT" | grep -q '"success":false'; then
  echo -e "${GREEN}✓ Sessão invalidada corretamente${NC}"
else
  echo -e "${RED}✗ Sessão ainda ativa${NC}"
fi
echo ""

# Limpar cookies
rm -f /tmp/cookies.txt

echo "=========================================="
echo "Testes concluídos!"
echo "=========================================="
