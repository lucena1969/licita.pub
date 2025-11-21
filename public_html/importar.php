<?php
// Conexão com o banco
$host = "localhost";
$db = "u590097272_licitapub";
$user = "root";
$pass = "Numse!2020";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

function importarCSV($conn, $arquivoTemporario, $tabela) {
    $handle = fopen($arquivoTemporario, "r");
    if (!$handle) {
        echo "<p>Erro ao abrir o arquivo.</p>";
        return;
    }

    // Ignora a primeira linha (cabeçalho)
    fgetcsv($handle, 1000, ";");

    $linhasInseridas = 0;

    while (($dados = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if ($tabela === "TabelaItens") {
            // Mapeia colunas do CSV para campos da tabela
            $stmt = $conn->prepare("INSERT INTO TabelaItens (nome_classe, codigo_pdm, nome_pdm, codigo_item, descricao_item, codigo_ncm) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $dados[0], $dados[1], $dados[2], $dados[3], $dados[4], $dados[5]);
        } elseif ($tabela === "catser") {
            $stmt = $conn->prepare("INSERT INTO catser (tipo_material_servico, grupo_servico, classe_material, codigo_material_servico, situacao_atual) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $dados[0], $dados[1], $dados[2], $dados[3], $dados[4]);
        } else {
            echo "<p>Tabela desconhecida.</p>";
            return;
        }

        if ($stmt->execute()) {
            $linhasInseridas++;
        }
    }

    fclose($handle);
    echo "<p>$linhasInseridas registros importados para a tabela <strong>$tabela</strong>.</p>";
}

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["csv_itens"]) && $_FILES["csv_itens"]["error"] == 0) {
        importarCSV($conn, $_FILES["csv_itens"]["tmp_name"], "TabelaItens");
    }

    if (isset($_FILES["csv_catser"]) && $_FILES["csv_catser"]["error"] == 0) {
        importarCSV($conn, $_FILES["csv_catser"]["tmp_name"], "catser");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Importador de CSV</title>
</head>
<body>
    <h2>Importar Dados para o Banco</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <p>
            <label for="csv_itens">CSV da TabelaItens:</label>
            <input type="file" name="csv_itens" accept=".csv" required>
        </p>
        <p>
            <label for="csv_catser">CSV da Tabela catser:</label>
            <input type="file" name="csv_catser" accept=".csv" required>
        </p>
        <button type="submit">Importar</button>
    </form>
</body>
</html>
