<?php
/**
 * Teste SQL NOVO - com nomes corretos das colunas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste SQL NOVO (v2)</h1>";

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

try {
    // Carregar .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    echo "<h2>✓ .env carregado</h2>";

    // Conectar ao banco
    $db = Database::getConnection();

    echo "<h2>✓ Conectado ao banco</h2>";

    // Testar query simples primeiro
    echo "<h2>1. Teste básico: COUNT(*)</h2>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM licitacoes");
    $total = $stmt->fetch();
    echo "<p>Total de licitações: {$total['total']}</p>";

    // Testar com filtro SC
    echo "<h2>2. Teste com filtro UF = SC</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM licitacoes WHERE uf = :uf");
    $stmt->execute([':uf' => 'SC']);
    $totalSC = $stmt->fetch();
    echo "<p>Total em SC: {$totalSC['total']}</p>";

    // Testar query exata do controller
    echo "<h2>3. Query CORRIGIDA do controller (com LIMIT)</h2>";

    $uf = 'SC';
    $pagina = 1;
    $limite = 20;
    $offset = ($pagina - 1) * $limite;

    $where = [];
    $params = [];

    if ($uf) {
        $where[] = "uf = :uf";
        $params[':uf'] = strtoupper($uf);
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // NOMES CORRETOS DAS COLUNAS!
    $sql = "
        SELECT
            pncp_id,
            numero,
            modalidade,
            objeto,
            situacao,
            data_publicacao,
            valor_estimado,
            cnpj_orgao,
            municipio,
            uf
        FROM licitacoes
        $whereClause
        ORDER BY sincronizado_em DESC
        LIMIT :limite OFFSET :offset
    ";

    echo "<h3>SQL:</h3>";
    echo "<pre>$sql</pre>";

    echo "<h3>Parâmetros:</h3>";
    echo "<pre>";
    print_r($params);
    echo "Limite: $limite\n";
    echo "Offset: $offset\n";
    echo "</pre>";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    $licitacoes = $stmt->fetchAll();

    echo "<h3 style='color: green;'>✓ Query executada com SUCESSO!</h3>";
    echo "<p>Resultados encontrados: <strong>" . count($licitacoes) . "</strong></p>";

    if (count($licitacoes) > 0) {
        echo "<h3>Primeiros 5 resultados:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #1351b4; color: white;'>";
        echo "<th>PNCP ID</th><th>Número</th><th>UF</th><th>Município</th><th>Modalidade</th><th>Objeto</th>";
        echo "</tr>";

        foreach (array_slice($licitacoes, 0, 5) as $lic) {
            echo "<tr>";
            echo "<td>{$lic['pncp_id']}</td>";
            echo "<td>{$lic['numero']}</td>";
            echo "<td><strong>{$lic['uf']}</strong></td>";
            echo "<td>{$lic['municipio']}</td>";
            echo "<td>{$lic['modalidade']}</td>";
            echo "<td>" . substr($lic['objeto'], 0, 60) . "...</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<h3 style='color: green;'>✅ TESTE BEM-SUCEDIDO!</h3>";
        echo "<p>As consultas no frontend devem funcionar agora.</p>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color: red;'>❌ ERRO:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
