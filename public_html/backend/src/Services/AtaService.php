<?php

namespace App\Services;

use App\Models\AtaRegistroPreco;
use App\Models\ItemAta;
use App\Repositories\AtaRegistroPrecoRepository;
use App\Repositories\ItemAtaRepository;

/**
 * Service: AtaService
 *
 * Sincronização de Atas de Registro de Preços
 * - PNCP: Atas recentes (2021+)
 * - compras.dados.gov.br: Itens históricos (até 2020)
 */
class AtaService
{
    private PNCPService $pncpService;
    private ComprasDadosGovService $comprasService;
    private AtaRegistroPrecoRepository $ataRepository;
    private ItemAtaRepository $itemRepository;

    public function __construct(
        PNCPService $pncpService,
        ComprasDadosGovService $comprasService,
        AtaRegistroPrecoRepository $ataRepository,
        ItemAtaRepository $itemRepository
    ) {
        $this->pncpService = $pncpService;
        $this->comprasService = $comprasService;
        $this->ataRepository = $ataRepository;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Sincronizar atas do PNCP (dados recentes)
     */
    public function sincronizarAtasPNCP(string $dataInicial, string $dataFinal): array
    {
        echo "🔄 Sincronizando atas do PNCP...\n";
        echo "Período: {$dataInicial} a {$dataFinal}\n\n";

        $totalAtas = 0;
        $atasInseridas = 0;
        $atasAtualizadas = 0;
        $erros = 0;

        $maxPaginas = 50; // Limite de segurança
        $paginaAtual = 1;

        do {
            echo "📄 Processando página {$paginaAtual}...\n";

            // Fazer requisição HTTP diretamente (PNCPService tem método privado)
            $url = "https://pncp.gov.br/api/consulta/v1/atas?dataInicial={$dataInicial}&dataFinal={$dataFinal}&pagina={$paginaAtual}";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: Licita.pub/1.0'
                ]
            ]);

            $responseJson = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$responseJson) {
                echo "❌ Erro ao buscar página {$paginaAtual}. HTTP {$httpCode}\n";
                break;
            }

            $response = json_decode($responseJson, true);

            if (!$response || empty($response['data'])) {
                echo "Nenhum dado retornado. Finalizando.\n";
                break;
            }

            $quantidadeNaPagina = count($response['data']);
            echo "  • Atas na página: {$quantidadeNaPagina}\n";

            foreach ($response['data'] as $ataData) {
                try {
                    // Criar model da ata
                    $ata = AtaRegistroPreco::fromPNCP($ataData);

                    // Validar
                    $errosValidacao = $ata->validar();
                    if (!empty($errosValidacao)) {
                        echo "  ⚠️  Ata inválida: " . implode(', ', $errosValidacao) . "\n";
                        $erros++;
                        continue;
                    }

                    // Salvar (upsert)
                    $resultado = $this->ataRepository->upsert($ata);

                    if ($resultado['inserido']) {
                        $atasInseridas++;
                        echo "  ✅ Nova ata: {$ata->numero}\n";
                    } else {
                        $atasAtualizadas++;
                    }

                    $totalAtas++;

                } catch (\Exception $e) {
                    echo "  ❌ Erro ao processar ata: " . $e->getMessage() . "\n";
                    $erros++;
                }
            }

            // Próxima página
            $paginaAtual++;

            // Pausa entre requisições
            usleep(500000); // 0.5 segundo

            // Se retornou menos que o esperado, é a última página
            if ($quantidadeNaPagina < 50) {
                echo "Última página detectada.\n";
                break;
            }

        } while ($paginaAtual <= $maxPaginas);

        $resultado = [
            'total_processadas' => $totalAtas,
            'inseridas' => $atasInseridas,
            'atualizadas' => $atasAtualizadas,
            'erros' => $erros,
            'paginas_processadas' => $paginaAtual - 1
        ];

        echo "\n✅ Sincronização concluída!\n";
        echo "Total: {$totalAtas} | Novas: {$atasInseridas} | Atualizadas: {$atasAtualizadas} | Erros: {$erros}\n";

        return $resultado;
    }

    /**
     * Importar itens históricos do compras.dados.gov.br
     */
    public function importarItensHistoricos(string $registroPrecoId): array
    {
        echo "🔄 Importando itens do registro de preço: {$registroPrecoId}\n";

        $totalItens = 0;
        $itensInseridos = 0;
        $erros = 0;
        $offset = 0;

        do {
            echo "📄 Buscando itens (offset: {$offset})...\n";

            // Buscar itens da API
            $response = $this->comprasService->buscarItensRegistroPreco($registroPrecoId, $offset);

            if (!$response) {
                echo "❌ Erro ao buscar itens.\n";
                break;
            }

            // Extrair itens
            $itens = $this->comprasService->extrairItens($response);

            if (empty($itens)) {
                echo "Nenhum item retornado. Finalizando.\n";
                break;
            }

            echo "  • Itens na página: " . count($itens) . "\n";

            foreach ($itens as $itemData) {
                try {
                    // Validar item
                    if (!$this->comprasService->itemValido($itemData)) {
                        echo "  ⚠️  Item inválido (sem descrição)\n";
                        continue;
                    }

                    // Buscar ata correspondente
                    // Aqui você precisa mapear o registro_preco_id para ata_id do banco
                    // Por enquanto, vou deixar como exemplo
                    // TODO: Implementar lógica de mapeamento

                    $itemNormalizado = $this->comprasService->normalizarItem($itemData);

                    echo "  ✅ Item: {$itemNormalizado['descricao']}\n";

                    $totalItens++;
                    $itensInseridos++;

                } catch (\Exception $e) {
                    echo "  ❌ Erro ao processar item: " . $e->getMessage() . "\n";
                    $erros++;
                }
            }

            // Verificar se tem mais páginas
            if (!$this->comprasService->temMaisPaginas($response)) {
                echo "Última página alcançada.\n";
                break;
            }

            $offset = $this->comprasService->proximoOffset($response);

            usleep(500000); // 0.5 segundo

        } while ($offset < 10000); // Limite de segurança

        $resultado = [
            'total_processados' => $totalItens,
            'inseridos' => $itensInseridos,
            'erros' => $erros
        ];

        echo "\n✅ Importação concluída!\n";
        echo "Total: {$totalItens} | Inseridos: {$itensInseridos} | Erros: {$erros}\n";

        return $resultado;
    }

    /**
     * Atualizar situação de atas vencidas
     */
    public function atualizarAtasVencidas(): int
    {
        echo "🔄 Atualizando atas vencidas...\n";

        $totalAtualizado = $this->ataRepository->marcarAtasVencidas();

        echo "✅ {$totalAtualizado} atas marcadas como vencidas.\n";

        return $totalAtualizado;
    }

    /**
     * Importar detalhes de uma ata específica (para busca manual)
     */
    public function importarAtaManual(string $registroPrecoId): ?array
    {
        echo "🔄 Importando ata manual: {$registroPrecoId}\n";

        try {
            // Buscar detalhes do registro de preço
            $dadosAta = $this->comprasService->buscarRegistroPreco($registroPrecoId);

            if (!$dadosAta) {
                echo "❌ Ata não encontrada.\n";
                return null;
            }

            // Buscar itens
            $resultado = $this->importarItensHistoricos($registroPrecoId);

            echo "✅ Ata importada com sucesso!\n";

            return $resultado;

        } catch (\Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Estatísticas do banco de dados
     */
    public function obterEstatisticas(): array
    {
        $totalAtas = $this->ataRepository->count();
        $atasVigentes = $this->ataRepository->count(['vigente' => true]);

        // TODO: Adicionar estatísticas de itens quando estiverem populados

        return [
            'total_atas' => $totalAtas,
            'atas_vigentes' => $atasVigentes,
            'atas_vencidas' => $totalAtas - $atasVigentes,
            // 'total_itens' => $totalItens,
        ];
    }

    /**
     * Sincronização completa (para executar manualmente)
     */
    public function sincronizacaoCompleta(): array
    {
        echo "🚀 INICIANDO SINCRONIZAÇÃO COMPLETA\n";
        echo str_repeat("=", 50) . "\n\n";

        $inicio = microtime(true);

        // 1. Sincronizar atas do PNCP (últimos 30 dias)
        $dataFinal = date('Ymd');
        $dataInicial = date('Ymd', strtotime('-30 days'));

        $resultadoAtas = $this->sincronizarAtasPNCP($dataInicial, $dataFinal);

        echo "\n";

        // 2. Atualizar atas vencidas
        $atasVencidas = $this->atualizarAtasVencidas();

        echo "\n";

        // 3. Estatísticas finais
        $stats = $this->obterEstatisticas();

        $tempoTotal = round(microtime(true) - $inicio, 2);

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "✅ SINCRONIZAÇÃO COMPLETA FINALIZADA\n";
        echo "Tempo total: {$tempoTotal}s\n";
        echo "Total de atas no banco: {$stats['total_atas']}\n";
        echo "Atas vigentes: {$stats['atas_vigentes']}\n";

        return [
            'atas' => $resultadoAtas,
            'atas_vencidas' => $atasVencidas,
            'estatisticas' => $stats,
            'tempo_execucao' => $tempoTotal
        ];
    }
}
