<?php

namespace App\Services;

use App\Config\Database;
use App\Models\ModeloKeywords;
use PDO;

/**
 * Serviço de Extração Inteligente de Palavras-Chave
 * Inspirado em técnicas de NLP para extrair termos relevantes de licitações
 */
class KeywordsExtractionService
{
    private PDO $db;

    // Stop words expandidas (palavras a serem removidas)
    private const STOP_WORDS = [
        'de', 'da', 'do', 'das', 'dos', 'para', 'com', 'sem', 'pelo', 'pela', 'pelos', 'pelas',
        'ao', 'aos', 'na', 'no', 'nas', 'nos', 'em', 'a', 'o', 'e', 'ou', 'por', 'sob', 'sobre',
        'lei', 'ata', 'registro', 'precos', 'preços', 'pregao', 'pregão', 'licitacao', 'licitação',
        'contratacao', 'contratação', 'aquisicao', 'aquisição', 'fornecimento',
        'servico', 'serviço', 'servicos', 'serviços', 'obra', 'obras',
        'processo', 'edital', 'objeto', 'item', 'itens', 'diversos', 'diversas',
        'tipo', 'tipos', 'conforme', 'descricao', 'descrição', 'especificacao', 'especificação',
        'mediante', 'atraves', 'através', 'eventual', 'eventuais', 'futura', 'futuras', 'futuro', 'futuros',
        'visando', 'destinado', 'destinada', 'referente', 'relativo', 'relativa',
        'acordo', 'termos', 'termo', 'condicoes', 'condições', 'anexo', 'anexos',
        'planilha', 'planilhas', 'memorial', 'descritivo', 'orcamentaria', 'orçamentária',
        'cronograma', 'projeto', 'referencia', 'referência',
        'um', 'uma', 'dois', 'duas', 'tres', 'três', 'quatro', 'cinco',
        'atender', 'realizar', 'executar', 'fornecer', 'prestar', 'instalar', 'instalados',
        'empresa', 'empresas', 'ramo', 'pertinente', 'execucao', 'execução',
        'presente', 'serem', 'especificacoes', 'especificações', 'tem', 'objetivo'
    ];

    // Padrões de produtos conhecidos (alta prioridade)
    private const PADROES_PRODUTOS = [
        // Tecnologia
        '/notebook|laptop|computador|desktop|pc|workstation/i',
        '/impressora|scanner|multifuncional|copiadora/i',
        '/tinta|cartucho|toner|ribbon/i',
        '/tablet|ipad|celular|smartphone/i',
        '/roteador|switch|modem|firewall|access\s*point/i',
        '/projetor|datashow|televisor|monitor/i',
        '/servidor|storage|backup|rack/i',
        // Mobiliário
        '/cadeira|mesa|armario|arquivo|estante|balcao|escrivaninha/i',
        '/sofa|poltrona|banco|longarina/i',
        // Material de escritório
        '/papel|sulfite|a4|oficio/i',
        '/caneta|lapis|borracha|grampeador|clips/i',
        // Vestuário
        '/uniforme|camiseta|calca|jaleco|avental|sapato|bota/i',
        // Saúde
        '/medicamento|remedio|farmaco|vacina|soro/i',
        '/equipamento|aparelho|bisturi|estetoscopio/i',
        // Veículos
        '/veiculo|carro|onibus|caminhao|ambulancia|moto/i',
        '/combustivel|gasolina|diesel|etanol/i',
        // Alimentação
        '/alimento|merenda|refeicao|cesta|basica/i',
        // Construção
        '/cimento|areia|brita|tijolo|telha|madeira|ferro/i',
        '/pergolado|cobertura|estrutura|guarita/i',
        '/pavimentacao|asfalto|calcada|calcamento/i',
        // Limpeza
        '/limpeza|higienizacao|desinfeccao/i',
        '/vassoura|rodo|pano|detergente|sabao/i'
    ];

    // Marcas conhecidas (prioridade máxima)
    private const MARCAS_CONHECIDAS = [
        'hp', 'dell', 'lenovo', 'asus', 'acer', 'samsung', 'lg', 'apple', 'positivo',
        'canon', 'epson', 'brother', 'xerox', 'ricoh',
        'intel', 'amd', 'nvidia', 'cisco',
        'microsoft', 'windows', 'office', 'adobe'
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Extrair palavras-chave inteligentes de uma descrição
     *
     * @param string $descricao Texto do objeto da licitação
     * @param int $limite Número máximo de palavras-chave (padrão 4)
     * @return array Array com 'keywords' (string) e 'palavras' (array de objetos)
     */
    public function extrairKeywords(string $descricao, int $limite = 4): array
    {
        // PASSO 0: Consultar CATMAT por similaridade (NOVO!)
        $matchesCatmat = $this->consultarCatmat($descricao, 12);

        // PASSO 1: Extrair conteúdo entre aspas (núcleo)
        $dentroAspas = $this->extrairDentroAspas($descricao);

        // PASSO 2: Identificar núcleo do objeto usando padrões descritivos
        $nucleoDoObjeto = $this->extrairNucleoDoObjeto($descricao);

        // PASSO 3: Normalizar e limpar texto
        // Se encontrou matches no CATMAT, usar a melhor descrição CATMAT como fonte principal
        $textoParaProcessar = $nucleoDoObjeto ?: implode(' ', $dentroAspas) ?: $descricao;

        // Se encontrou no CATMAT, enriquecer com a descrição CATMAT
        if (!empty($matchesCatmat)) {
            $descricaoCatmat = $matchesCatmat[0]['descricao'];
            $textoParaProcessar .= ' ' . $descricaoCatmat;
        }

        $textoLimpo = $this->normalizarTexto($textoParaProcessar);

        // PASSO 4: Extrair e classificar palavras
        $palavras = $this->extrairPalavras($textoLimpo);

        // PASSO 5: Consultar pesos do banco de dados
        $palavrasComPeso = $this->aplicarPesosAprendidos($palavras);

        // PASSO 6: Ordenar por relevância e pegar top N
        $topPalavras = $this->ordenarPorRelevancia($palavrasComPeso, $limite);

        // PASSO 6.5: Remover duplicatas mantendo a primeira ocorrência (maior relevância)
        $palavrasUnicas = [];
        $palavrasVistas = [];
        foreach ($topPalavras as $item) {
            if (!in_array($item['palavra'], $palavrasVistas)) {
                $palavrasUnicas[] = $item;
                $palavrasVistas[] = $item['palavra'];
            }
        }
        $topPalavras = $palavrasUnicas;

        // PASSO 7: Registrar uso no banco de dados
        $this->registrarUsoPalavras(array_column($topPalavras, 'palavra'));

        // Retornar string concatenada e array de objetos
        $keywordsString = implode(' ', array_column($topPalavras, 'palavra'));

        return [
            'keywords' => $keywordsString ?: 'produto',
            'palavras' => $topPalavras,
            'sugestoes_catmat' => $matchesCatmat, // NOVO: retornar sugestões CATMAT
            'metadados' => [
                'nucleo_encontrado' => !empty($nucleoDoObjeto),
                'aspas_encontradas' => count($dentroAspas),
                'total_candidatos' => count($palavras),
                'matches_catmat' => count($matchesCatmat) // NOVO
            ]
        ];
    }

    /**
     * Consultar CATMAT por similaridade usando FULLTEXT
     * Com priorização de match exato da palavra-núcleo
     *
     * @param string $descricao Descrição do objeto da licitação
     * @param int $limite Número de resultados (padrão 12)
     * @return array Array de matches ordenados por relevância
     */
    private function consultarCatmat(string $descricao, int $limite = 12): array
    {
        try {
            // Preparar texto de busca
            $termoBusca = $this->prepararTermoBuscaCatmat($descricao);

            if (empty($termoBusca)) {
                return [];
            }

            // Extrair palavra-núcleo para filtro adicional
            $palavras = preg_split('/\s+/', $termoBusca, -1, PREG_SPLIT_NO_EMPTY);
            $palavraNucleo = $this->identificarPalavraNucleo($palavras);

            // Preparar pattern LIKE para match exato (case insensitive)
            $likePattern = $palavraNucleo ? '%' . $palavraNucleo . '%' : '%';

            // Consulta FULLTEXT com boost para match exato da palavra-núcleo
            // IMPORTANTE: As colunas do MATCH devem estar na MESMA ORDEM do índice
            $sql = "
                SELECT
                    codigo_item,
                    descricao,
                    nome_resumido,
                    nome_grupo,
                    nome_classe,
                    nome_pdm,
                    unidade_fornecimento,
                    MATCH(descricao, nome_resumido, nome_grupo, nome_classe, nome_pdm)
                        AGAINST (? IN NATURAL LANGUAGE MODE) as relevancia_base,
                    CASE
                        WHEN descricao LIKE ? THEN 100
                        WHEN nome_resumido LIKE ? THEN 80
                        WHEN nome_classe LIKE ? THEN 40
                        WHEN nome_grupo LIKE ? THEN 30
                        ELSE 0
                    END as boost_exato
                FROM catmat
                WHERE MATCH(descricao, nome_resumido, nome_grupo, nome_classe, nome_pdm)
                      AGAINST (? IN NATURAL LANGUAGE MODE)
                  AND status = 'Ativo'
                ORDER BY (relevancia_base + boost_exato) DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $termoBusca,        // MATCH relevancia_base
                $likePattern,       // boost descricao
                $likePattern,       // boost nome_resumido
                $likePattern,       // boost nome_classe
                $likePattern,       // boost nome_grupo
                $termoBusca,        // WHERE MATCH
                $limite
            ]);

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processar resultados para retornar informações úteis
            return array_map(function($row) {
                $relevanciaTotal = (float)$row['relevancia_base'] + (float)$row['boost_exato'];
                return [
                    'codigo_item' => $row['codigo_item'],
                    'descricao' => $row['descricao'],
                    'nome_resumido' => $row['nome_resumido'],
                    'categoria' => [
                        'grupo' => $row['nome_grupo'],
                        'classe' => $row['nome_classe'],
                        'pdm' => $row['nome_pdm']
                    ],
                    'unidade' => $row['unidade_fornecimento'],
                    'relevancia' => round($relevanciaTotal, 2)
                ];
            }, $resultados);

        } catch (\Exception $e) {
            error_log("Erro ao consultar CATMAT: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Preparar termo de busca para consulta CATMAT
     * Identifica palavra-núcleo principal e constrói query otimizada
     */
    private function prepararTermoBuscaCatmat(string $descricao): string
    {
        // Extrair núcleo do objeto primeiro
        $nucleo = $this->extrairNucleoDoObjeto($descricao);
        $texto = !empty($nucleo) ? $nucleo : $descricao;

        // Normalizar
        $texto = mb_strtolower($texto);

        // Remover padrões administrativos
        $texto = preg_replace('/lei\s+n?º?\s*\d+[\.\/]\d+/iu', '', $texto);
        $texto = preg_replace('/processo\s+n?º?\s*[\d\-\/\.]+/iu', '', $texto);
        $texto = preg_replace('/edital\s+n?º?\s*[\d\-\/\.]+/iu', '', $texto);
        $texto = preg_replace('/preg[aã]o\s+(eletr[oô]nico\s+)?n?º?\s*[\d\-\/\.]+/iu', '', $texto);

        // Separar em palavras
        $texto = preg_replace('/[^a-záàâãéèêíïóôõöúçñ0-9\s]/u', ' ', $texto);
        $palavras = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrar stop words
        $stopWordsCatmat = [
            'de', 'da', 'do', 'das', 'dos', 'para', 'com', 'sem', 'na', 'no', 'nas', 'nos',
            'registro', 'precos', 'pregao', 'aquisicao', 'contratacao', 'fornecimento',
            'eventual', 'futura', 'servicos', 'servico', 'parcelada'
        ];

        $palavrasFiltradas = array_filter($palavras, function($p) use ($stopWordsCatmat) {
            return mb_strlen($p) >= 3 && !in_array($p, $stopWordsCatmat);
        });

        if (empty($palavrasFiltradas)) {
            return '';
        }

        // Identificar palavra-núcleo principal (substantivo concreto)
        $palavraPrincipal = $this->identificarPalavraNucleo($palavrasFiltradas);

        // Construir query priorizando a palavra principal
        if ($palavraPrincipal) {
            // Palavra principal + outras palavras relevantes (máximo 5 adicionais)
            $palavrasSecundarias = array_filter($palavrasFiltradas, function($p) use ($palavraPrincipal) {
                return $p !== $palavraPrincipal;
            });
            $palavrasSecundarias = array_slice($palavrasSecundarias, 0, 5);

            // Repetir palavra principal para dar mais peso no FULLTEXT
            return $palavraPrincipal . ' ' . $palavraPrincipal . ' ' . implode(' ', $palavrasSecundarias);
        }

        // Fallback: usar top 8 palavras
        return implode(' ', array_slice($palavrasFiltradas, 0, 8));
    }

    /**
     * Identificar palavra-núcleo principal (substantivo concreto mais importante)
     *
     * @param array $palavras Array de palavras candidatas
     * @return string|null Palavra principal ou null
     */
    private function identificarPalavraNucleo(array $palavras): ?string
    {
        // Substantivos concretos comuns em licitações (alta prioridade)
        $substantivosConcretos = [
            // Materiais de escritório
            'papel', 'sulfite', 'caneta', 'lapis', 'caderno', 'grampeador', 'clips', 'envelope',
            'tinta', 'cartucho', 'toner', 'ribbon',

            // Tecnologia
            'computador', 'notebook', 'laptop', 'desktop', 'tablet', 'celular', 'smartphone',
            'impressora', 'scanner', 'monitor', 'teclado', 'mouse', 'webcam',
            'roteador', 'switch', 'servidor', 'nobreak', 'estabilizador',
            'projetor', 'datashow', 'televisor', 'televisao',

            // Móveis
            'cadeira', 'mesa', 'armario', 'estante', 'arquivo', 'balcao', 'escrivaninha',
            'sofa', 'poltrona', 'longarina', 'banco', 'gaveteiro',

            // Vestuário
            'uniforme', 'camiseta', 'calca', 'jaleco', 'avental', 'sapato', 'bota', 'colete',

            // Saúde/Medicamentos
            'medicamento', 'remedio', 'vacina', 'soro', 'luva', 'mascara', 'alcool',
            'seringa', 'agulha', 'gaze', 'atadura', 'estetoscopio', 'termometro',
            'paracetamol', 'dipirona', 'ibuprofeno', 'antibiotico',

            // Veículos
            'veiculo', 'carro', 'automovel', 'onibus', 'caminhao', 'ambulancia', 'moto', 'motocicleta',
            'combustivel', 'gasolina', 'diesel', 'etanol', 'pneu', 'bateria',

            // Alimentação
            'alimento', 'merenda', 'refeicao', 'cesta', 'arroz', 'feijao', 'leite', 'carne',
            'pao', 'macarrao', 'oleo', 'acucar', 'sal', 'cafe',

            // Construção
            'cimento', 'areia', 'brita', 'tijolo', 'telha', 'madeira', 'ferro', 'aco',
            'tinta', 'cal', 'gesso', 'argamassa', 'ceramica', 'piso',
            'porta', 'janela', 'torneira', 'chuveiro', 'vaso', 'pia',

            // Limpeza
            'detergente', 'sabao', 'desinfetante', 'cloro', 'alvejante',
            'vassoura', 'rodo', 'pano', 'balde', 'mop', 'escova',

            // Outros
            'agua', 'energia', 'gas', 'oxigenio', 'ar', 'condicionado'
        ];

        // 1ª prioridade: Verificar se existe substantivo concreto conhecido
        foreach ($palavras as $palavra) {
            if (in_array($palavra, $substantivosConcretos)) {
                return $palavra;
            }
        }

        // 2ª prioridade: Palavras com mais de 5 caracteres (substantivos específicos)
        $palavrasLongas = array_filter($palavras, function($p) {
            return mb_strlen($p) >= 6;
        });

        if (!empty($palavrasLongas)) {
            return reset($palavrasLongas); // Primeira palavra longa
        }

        // 3ª prioridade: Primeira palavra da lista
        return !empty($palavras) ? reset($palavras) : null;
    }

    /**
     * Extrair conteúdo entre aspas
     */
    private function extrairDentroAspas(string $texto): array
    {
        $dentroAspas = [];
        $regexAspas = '/"([^"]+)"|"([^"]+)"|\'([^\']+)\'/u';

        if (preg_match_all($regexAspas, $texto, $matches)) {
            foreach ($matches as $grupo) {
                foreach ($grupo as $conteudo) {
                    if (!empty($conteudo) && mb_strlen($conteudo) > 3) {
                        $dentroAspas[] = mb_strtolower($conteudo);
                    }
                }
            }
        }

        return array_unique($dentroAspas);
    }

    /**
     * Extrair núcleo do objeto usando padrões descritivos
     */
    private function extrairNucleoDoObjeto(string $texto): string
    {
        $padroes = [
            '/tem\s+por\s+(?:objeto|objetivo)\s+(?:a\s+)?(?:contrata[çc][aã]o|aquisi[çc][aã]o|presta[çc][aã]o|fornecimento|execu[çc][aã]o)\s+de\s+(.+?)(?:\s+(?:para|visando|de\s+acordo|conforme|,|\.)|$)/iu',
            '/visa\s+(?:a\s+)?(?:contrata[çc][aã]o|aquisi[çc][aã]o|presta[çc][aã]o|fornecimento)\s+de\s+(.+?)(?:\s+(?:para|visando|de\s+acordo|conforme|,|\.)|$)/iu',
            '/(?:contrata[çc][aã]o|aquisi[çc][aã]o|presta[çc][aã]o|fornecimento)\s+de\s+(.+?)(?:\s+(?:para|visando|de\s+acordo|conforme|destinad|a\s+ser|,|\.)|$)/iu',
            '/registro\s+de\s+pre[çc]os?\s+para\s+(?:eventual\s+)?(?:contrata[çc][aã]o|aquisi[çc][aã]o|fornecimento)\s+de\s+(.+?)(?:\s+(?:conforme|de\s+acordo|,|\.)|$)/iu',
            '/obras?\s+de\s+["\']?([^"\',\.]+)["\']?/iu',
            '/servi[çc]os?\s+de\s+["\']?([^"\',\.]+)["\']?/iu'
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $texto, $matches)) {
                if (!empty($matches[1])) {
                    return trim($matches[1]);
                }
            }
        }

        return '';
    }

    /**
     * Normalizar e limpar texto
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto);

        // Remover padrões comuns
        $texto = preg_replace('/lei\s+n?º?\s*\d+[\.\/]\d+/iu', '', $texto);
        $texto = preg_replace('/processo\s+n?º?\s*[\d\-\/\.]+/iu', '', $texto);
        $texto = preg_replace('/edital\s+n?º?\s*[\d\-\/\.]+/iu', '', $texto);
        $texto = preg_replace('/preg[aã]o\s+(eletr[oô]nico\s+)?n?º?\s*[\d\-\/\.]+/iu', '', $texto);
        $texto = preg_replace('/anexo\s+[ivxlcdm]+/iu', '', $texto);
        $texto = preg_replace('/termo\s+de\s+refer[êe]ncia/iu', '', $texto);
        $texto = preg_replace('/\b20\d{2}\b/', '', $texto);
        $texto = preg_replace('/\b\d{4,}\b/', '', $texto);

        return $texto;
    }

    /**
     * Extrair palavras do texto
     */
    private function extrairPalavras(string $texto): array
    {
        // Remover pontuação mantendo letras e números
        $texto = preg_replace('/[^a-záàâãéèêíïóôõöúçñ0-9\s]/u', ' ', $texto);

        // Separar em palavras
        $palavras = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrar por tamanho mínimo e stop words
        $palavrasFiltradas = [];
        foreach ($palavras as $palavra) {
            $palavra = trim($palavra);
            if (mb_strlen($palavra) >= 3 && !in_array($palavra, self::STOP_WORDS)) {
                $palavrasFiltradas[] = $palavra;
            }
        }

        return $palavrasFiltradas;
    }

    /**
     * Aplicar pesos do banco de dados e classificar por tipo
     */
    private function aplicarPesosAprendidos(array $palavras): array
    {
        $resultado = [];

        foreach ($palavras as $palavra) {
            // Buscar peso no banco
            $pesoAprendido = $this->buscarPesoPalavra($palavra);

            // Calcular relevância base
            $relevancia = 1.0;

            // Marcas têm prioridade máxima
            if (in_array($palavra, self::MARCAS_CONHECIDAS)) {
                $relevancia = 10.0;
            }
            // Produtos conhecidos
            elseif ($this->isProdutoConhecido($palavra)) {
                $relevancia = 5.0;
            }
            // Palavras com números (especificações técnicas)
            elseif (preg_match('/\d/', $palavra) && mb_strlen($palavra) <= 10) {
                $relevancia = 3.0;
            }
            // Substantivos específicos (6+ caracteres)
            elseif (mb_strlen($palavra) >= 6) {
                $relevancia = 2.0;
            }

            // Aplicar peso aprendido (multiplicador)
            $relevanciaFinal = $relevancia * $pesoAprendido;

            $resultado[] = [
                'palavra' => $palavra,
                'relevancia' => $relevanciaFinal,
                'peso_aprendido' => $pesoAprendido,
                'tipo' => $this->getTipoPalavra($palavra)
            ];
        }

        return $resultado;
    }

    /**
     * Buscar peso da palavra no banco de dados
     */
    private function buscarPesoPalavra(string $palavra): float
    {
        try {
            $stmt = $this->db->prepare("SELECT peso FROM modelo_keywords WHERE palavra = ? LIMIT 1");
            $stmt->execute([$palavra]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado ? (float)$resultado['peso'] : 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    /**
     * Verificar se é produto conhecido
     */
    private function isProdutoConhecido(string $palavra): bool
    {
        foreach (self::PADROES_PRODUTOS as $padrao) {
            if (preg_match($padrao, $palavra)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obter tipo da palavra
     */
    private function getTipoPalavra(string $palavra): string
    {
        if (in_array($palavra, self::MARCAS_CONHECIDAS)) return 'marca';
        if ($this->isProdutoConhecido($palavra)) return 'produto';
        if (preg_match('/\d/', $palavra)) return 'especificacao';
        if (mb_strlen($palavra) >= 6) return 'substantivo';
        return 'comum';
    }

    /**
     * Ordenar por relevância e pegar top N
     */
    private function ordenarPorRelevancia(array $palavras, int $limite): array
    {
        // Ordenar por relevância (maior primeiro)
        usort($palavras, function($a, $b) {
            return $b['relevancia'] <=> $a['relevancia'];
        });

        // Pegar top N
        return array_slice($palavras, 0, $limite);
    }

    /**
     * Registrar uso das palavras no banco (incrementar ocorrências)
     */
    private function registrarUsoPalavras(array $palavras): void
    {
        try {
            foreach ($palavras as $palavra) {
                // INSERT ou UPDATE
                $stmt = $this->db->prepare("
                    INSERT INTO modelo_keywords (palavra, peso, ocorrencias, ultima_atualizacao)
                    VALUES (?, 1.0, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        ocorrencias = ocorrencias + 1,
                        ultima_atualizacao = NOW()
                ");
                $stmt->execute([$palavra]);
            }
        } catch (\Exception $e) {
            // Log silencioso (não quebrar fluxo)
            error_log("Erro ao registrar uso de palavras: " . $e->getMessage());
        }
    }

    /**
     * Registrar feedback positivo (palavra útil)
     */
    public function feedbackPositivo(string $palavra): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE modelo_keywords
                SET peso = LEAST(3.0, peso + 0.1),
                    ultima_atualizacao = NOW()
                WHERE palavra = ?
            ");
            return $stmt->execute([$palavra]);
        } catch (\Exception $e) {
            error_log("Erro ao registrar feedback positivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar feedback negativo (palavra não útil)
     */
    public function feedbackNegativo(string $palavra): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE modelo_keywords
                SET peso = GREATEST(0.5, peso - 0.05),
                    ultima_atualizacao = NOW()
                WHERE palavra = ?
            ");
            return $stmt->execute([$palavra]);
        } catch (\Exception $e) {
            error_log("Erro ao registrar feedback negativo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter relatório de palavras aprendidas
     */
    public function getRelatorio(int $limite = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM modelo_keywords
                ORDER BY peso DESC, ocorrencias DESC
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function($row) {
                $keyword = ModeloKeywords::fromArray($row);
                return [
                    'palavra' => $keyword->palavra,
                    'peso' => $keyword->peso,
                    'ocorrencias' => $keyword->ocorrencias,
                    'classificacao' => $keyword->getClassificacao(),
                    'emoji' => $keyword->getEmoji(),
                    'ultima_atualizacao' => $keyword->ultima_atualizacao
                ];
            }, $resultados);
        } catch (\Exception $e) {
            error_log("Erro ao obter relatório: " . $e->getMessage());
            return [];
        }
    }
}
