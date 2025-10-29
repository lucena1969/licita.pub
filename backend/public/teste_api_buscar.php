<?php
/**
 * Teste direto do endpoint buscar.php
 */

echo "<h1>Teste do Endpoint /api/licitacoes/buscar.php</h1>";

// Simular requisição GET com filtro SC
$_GET['uf'] = 'SC';

// Incluir o endpoint
require_once __DIR__ . '/api/licitacoes/buscar.php';
