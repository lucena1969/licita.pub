<?php
/**
 * Teste SQL direto - testar a query exata do controller
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste SQL Direto</h1>";

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
    echo "<h2>3. Query exata do controller (com LIMIT)</h2>";

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

    $sql = "
        SELECT
            pncp_id,
            numero_controle,
            modalidade,
            objeto_simplificado,
            situacao,
            data_publicacao,
            valor_estimado,
            orgao_cnpj,
            municipio,
            uf
        FROM licitacoes
        $whereClause
        ORDER BY created_at DESC
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

    echo "<h3>✓ Query executada com sucesso!</h3>";
    echo "<p>Resultados encontrados: " . count($licitacoes) . "</p>";

    if (count($licitacoes) > 0) {
        echo "<h3>Primeiros 3 resultados:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>PNCP ID</th><th>UF</th><th>Município</th><th>Objeto</th></tr>";

        foreach (array_slice($licitacoes, 0, 3) as $lic) {
            echo "<tr>";
            echo "<td>{$lic['pncp_id']}</td>";
            echo "<td>{$lic['uf']}</td>";
            echo "<td>{$lic['municipio']}</td>";
            echo "<td>" . substr($lic['objeto_simplificado'], 0, 80) . "...</td>";
            echo "</tr>";
        }

        echo "</table>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color: red;'>ERRO:</h2>";
    echo "<pre>";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
