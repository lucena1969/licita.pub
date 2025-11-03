#!/bin/bash

# ============================================================
# SCRIPT DE TESTE COMPLETO - BUSCA POR PALAVRA-CHAVE
# Execute após aplicar as correções
# ============================================================

API_URL="https://licita.pub/backend/api/licitacoes"

echo "============================================================"
echo "TESTE COMPLETO - BUSCA DE LICITAÇÕES"
echo "============================================================"
echo ""

# Teste 1: Buscar por palavra-chave "computador"
echo "1. Buscar por 'computador':"
curl -s "${API_URL}/buscar.php?q=computador&limite=5" | jq '.success, .paginacao.total, .data[0].objeto'
echo ""

# Teste 2: Buscar por "serviço"
echo "2. Buscar por 'serviço':"
curl -s "${API_URL}/buscar.php?q=servico&limite=5" | jq '.success, .paginacao.total'
echo ""

# Teste 3: Buscar por "material escritório"
echo "3. Buscar por 'material escritorio':"
curl -s "${API_URL}/buscar.php?q=material+escritorio&limite=5" | jq '.success, .paginacao.total'
echo ""

# Teste 4: Buscar com filtro de UF
echo "4. Buscar 'computador' em SP:"
curl -s "${API_URL}/buscar.php?q=computador&uf=SP&limite=5" | jq '.success, .paginacao.total, .filtros'
echo ""

# Teste 5: Buscar com múltiplos filtros
echo "5. Buscar com múltiplos filtros:"
curl -s "${API_URL}/buscar.php?q=pregao&uf=SP&modalidade=PREGAO_ELETRONICO&limite=5" | jq '.success, .paginacao.total'
echo ""

# Teste 6: Listar sem filtros (baseline)
echo "6. Listar sem filtros (primeiras 5):"
curl -s "${API_URL}/listar.php?limite=5" | jq '.success, .paginacao.total, .data[0].pncp_id'
echo ""

# Teste 7: Buscar termo que não existe
echo "7. Buscar termo inexistente:"
curl -s "${API_URL}/buscar.php?q=xyzabc123&limite=5" | jq '.success, .paginacao.total'
echo ""

# Teste 8: Buscar palavra curta (< 3 chars)
echo "8. Buscar palavra curta 'pc':"
curl -s "${API_URL}/buscar.php?q=pc&limite=5" | jq '.success, .paginacao.total'
echo ""

# Teste 9: Estatísticas gerais
echo "9. Estatísticas gerais:"
curl -s "${API_URL}/estatisticas.php" | jq '.success, .data.total_licitacoes, .data.total_ufs'
echo ""

echo "============================================================"
echo "TESTES CONCLUÍDOS"
echo "============================================================"
echo ""
echo "RESULTADOS ESPERADOS:"
echo "- Todos os success devem ser 'true'"
echo "- Buscas por palavras comuns devem retornar resultados"
echo "- Buscas com filtros devem reduzir o total"
echo "- Buscas inexistentes devem retornar total: 0"
echo ""
echo "Se algum teste falhar, verifique:"
echo "1. Índices FULLTEXT foram criados? (execute corrigir_busca.sql)"
echo "2. Controller foi atualizado? (substitua pelo _FIXED.php)"
echo "3. API está acessível? (verifique URLs)"
echo "============================================================"
