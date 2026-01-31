<?php
/**
 * Parser do XML Lattes
 */

class CVParser {
    private $xml;
    private $visibility;

    public function __construct($xmlPath, $visibility = null) {
        $content = file_get_contents($xmlPath);
        // Converte de ISO-8859-1 para UTF-8 e atualiza a declaração do XML
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        $content = preg_replace('/encoding=["\']ISO-8859-1["\']/i', 'encoding="UTF-8"', $content);
        $this->xml = simplexml_load_string($content);
        $this->visibility = $visibility ?? ['hidden_sections' => [], 'hidden_items' => []];
    }

    /**
     * Retorna o XML completo
     */
    public function getXml() {
        return $this->xml;
    }

    /**
     * Obtém dados gerais do currículo
     */
    public function getDadosGerais() {
        $dados = $this->xml->{'DADOS-GERAIS'};
        return [
            'nome' => (string) $dados['NOME-COMPLETO'],
            'citacoes' => (string) $dados['NOME-EM-CITACOES-BIBLIOGRAFICAS'],
            'resumo_pt' => (string) $dados->{'RESUMO-CV'}['TEXTO-RESUMO-CV-RH'],
            'resumo_en' => (string) $dados->{'RESUMO-CV'}['TEXTO-RESUMO-CV-RH-EN'],
            'outras_info' => (string) $dados->{'OUTRAS-INFORMACOES-RELEVANTES'}['OUTRAS-INFORMACOES-RELEVANTES'],
            'email' => $this->extractEmail((string) $dados->{'ENDERECO'}['ELETRONICO']),
            'instituicao' => (string) $dados->{'ENDERECO'}->{'ENDERECO-PROFISSIONAL'}['NOME-INSTITUICAO-EMPRESA'],
            'orgao' => (string) $dados->{'ENDERECO'}->{'ENDERECO-PROFISSIONAL'}['NOME-ORGAO'],
            'cidade' => (string) $dados->{'ENDERECO'}->{'ENDERECO-PROFISSIONAL'}['CIDADE'],
            'uf' => (string) $dados->{'ENDERECO'}->{'ENDERECO-PROFISSIONAL'}['UF'],
            'pais' => (string) $dados->{'ENDERECO'}->{'ENDERECO-PROFISSIONAL'}['PAIS'],
        ];
    }

    private function extractEmail($emailField) {
        // Formato: "I: email1; R: email2"
        if (preg_match('/I:\s*([^;]+)/', $emailField, $matches)) {
            return trim($matches[1]);
        }
        return $emailField;
    }

    /**
     * Obtém formação acadêmica
     */
    public function getFormacaoAcademica() {
        $formacoes = [];
        $dados = $this->xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'};

        if (!$dados) return $formacoes;

        // Doutorado
        foreach ($dados->{'DOUTORADO'} as $item) {
            $formacoes[] = [
                'tipo' => 'Doutorado',
                'ano_inicio' => (string) $item['ANO-DE-INICIO'],
                'ano_fim' => (string) $item['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $item['NOME-INSTITUICAO'],
                'curso' => (string) $item['NOME-CURSO'],
                'titulo' => (string) $item['TITULO-DA-DISSERTACAO-TESE'],
                'orientador' => (string) $item['NOME-COMPLETO-DO-ORIENTADOR'],
                'status' => (string) $item['STATUS-DO-CURSO'],
                'id' => 'formacao-doutorado-' . (string) $item['SEQUENCIA-FORMACAO']
            ];
        }

        // Mestrado
        foreach ($dados->{'MESTRADO'} as $item) {
            $formacoes[] = [
                'tipo' => 'Mestrado',
                'ano_inicio' => (string) $item['ANO-DE-INICIO'],
                'ano_fim' => (string) $item['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $item['NOME-INSTITUICAO'],
                'curso' => (string) $item['NOME-CURSO'],
                'titulo' => (string) $item['TITULO-DA-DISSERTACAO-TESE'],
                'orientador' => (string) $item['NOME-COMPLETO-DO-ORIENTADOR'],
                'status' => (string) $item['STATUS-DO-CURSO'],
                'id' => 'formacao-mestrado-' . (string) $item['SEQUENCIA-FORMACAO']
            ];
        }

        // Graduação
        foreach ($dados->{'GRADUACAO'} as $item) {
            $formacoes[] = [
                'tipo' => 'Graduação',
                'ano_inicio' => (string) $item['ANO-DE-INICIO'],
                'ano_fim' => (string) $item['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $item['NOME-INSTITUICAO'],
                'curso' => (string) $item['NOME-CURSO'],
                'titulo' => (string) $item['TITULO-DO-TRABALHO-DE-CONCLUSAO-DE-CURSO'],
                'orientador' => '',
                'status' => (string) $item['STATUS-DO-CURSO'],
                'id' => 'formacao-graduacao-' . (string) $item['SEQUENCIA-FORMACAO']
            ];
        }

        // Especialização
        foreach ($dados->{'ESPECIALIZACAO'} as $item) {
            $formacoes[] = [
                'tipo' => 'Especialização',
                'ano_inicio' => (string) $item['ANO-DE-INICIO'],
                'ano_fim' => (string) $item['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $item['NOME-INSTITUICAO'],
                'curso' => (string) $item['NOME-CURSO'],
                'titulo' => (string) $item['TITULO-DA-MONOGRAFIA'],
                'orientador' => '',
                'status' => (string) $item['STATUS-DO-CURSO'],
                'id' => 'formacao-especializacao-' . (string) $item['SEQUENCIA-FORMACAO']
            ];
        }

        // Ordenar por ano de início (mais recente primeiro)
        usort($formacoes, function($a, $b) {
            return ($b['ano_inicio'] ?? 0) - ($a['ano_inicio'] ?? 0);
        });

        return $formacoes;
    }

    /**
     * Obtém atuações profissionais
     */
    public function getAtuacoesProfissionais() {
        $atuacoes = [];
        $dados = $this->xml->{'DADOS-GERAIS'}->{'ATUACOES-PROFISSIONAIS'};

        if (!$dados) return $atuacoes;

        foreach ($dados->{'ATUACAO-PROFISSIONAL'} as $atuacao) {
            $vinculos = [];
            foreach ($atuacao->{'VINCULOS'} as $vinculo) {
                $vinculos[] = [
                    'ano_inicio' => (string) $vinculo['ANO-INICIO'],
                    'ano_fim' => (string) $vinculo['ANO-FIM'],
                    'tipo' => (string) $vinculo['TIPO-DE-VINCULO'],
                    'enquadramento' => (string) $vinculo['ENQUADRAMENTO-FUNCIONAL'],
                    'carga_horaria' => (string) $vinculo['CARGA-HORARIA-SEMANAL'],
                    'dedicacao_exclusiva' => (string) $vinculo['FLAG-DEDICACAO-EXCLUSIVA'],
                ];
            }

            $atividades = [];
            foreach ($atuacao->{'ATIVIDADES-DE-PESQUISA-E-DESENVOLVIMENTO'} as $ativ) {
                $linhas = [];
                foreach ($ativ->{'LINHA-DE-PESQUISA'} as $linha) {
                    $linhas[] = (string) $linha['TITULO-DA-LINHA-DE-PESQUISA'];
                }
                $atividades[] = [
                    'tipo' => 'Pesquisa e Desenvolvimento',
                    'ano_inicio' => (string) $ativ['ANO-INICIO'],
                    'ano_fim' => (string) $ativ['ANO-FIM'],
                    'linhas' => $linhas
                ];
            }

            $atuacoes[] = [
                'instituicao' => (string) $atuacao['NOME-INSTITUICAO'],
                'vinculos' => $vinculos,
                'atividades' => $atividades,
                'id' => 'atuacao-' . (string) $atuacao['SEQUENCIA-ATIVIDADE']
            ];
        }

        return $atuacoes;
    }

    /**
     * Obtém áreas de atuação
     */
    public function getAreasAtuacao() {
        $areas = [];
        $dados = $this->xml->{'DADOS-GERAIS'}->{'AREAS-DE-ATUACAO'};

        if (!$dados) return $areas;

        foreach ($dados->{'AREA-DE-ATUACAO'} as $area) {
            $areas[] = [
                'grande_area' => (string) $area['NOME-GRANDE-AREA-DO-CONHECIMENTO'],
                'area' => (string) $area['NOME-DA-AREA-DO-CONHECIMENTO'],
                'subarea' => (string) $area['NOME-DA-SUB-AREA-DO-CONHECIMENTO'],
                'especialidade' => (string) $area['NOME-DA-ESPECIALIDADE'],
                'id' => 'area-' . (string) $area['SEQUENCIA-AREA-DE-ATUACAO']
            ];
        }

        return $areas;
    }

    /**
     * Obtém idiomas
     */
    public function getIdiomas() {
        $idiomas = [];
        $dados = $this->xml->{'DADOS-GERAIS'}->{'IDIOMAS'};

        if (!$dados) return $idiomas;

        foreach ($dados->{'IDIOMA'} as $idioma) {
            $idiomas[] = [
                'idioma' => (string) $idioma['DESCRICAO-DO-IDIOMA'],
                'compreende' => (string) $idioma['PROFICIENCIA-DE-COMPREENSAO'],
                'fala' => (string) $idioma['PROFICIENCIA-DE-FALA'],
                'le' => (string) $idioma['PROFICIENCIA-DE-LEITURA'],
                'escreve' => (string) $idioma['PROFICIENCIA-DE-ESCRITA'],
                'id' => 'idioma-' . md5((string) $idioma['DESCRICAO-DO-IDIOMA'])
            ];
        }

        return $idiomas;
    }

    /**
     * Obtém artigos publicados
     */
    public function getArtigos() {
        $artigos = [];
        $dados = $this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'ARTIGOS-PUBLICADOS'};

        if (!$dados) return $artigos;

        foreach ($dados->{'ARTIGO-PUBLICADO'} as $artigo) {
            $autores = [];
            foreach ($artigo->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $artigos[] = [
                'titulo' => (string) $artigo->{'DADOS-BASICOS-DO-ARTIGO'}['TITULO-DO-ARTIGO'],
                'ano' => (string) $artigo->{'DADOS-BASICOS-DO-ARTIGO'}['ANO-DO-ARTIGO'],
                'doi' => (string) $artigo->{'DADOS-BASICOS-DO-ARTIGO'}['DOI'],
                'periodico' => (string) $artigo->{'DETALHAMENTO-DO-ARTIGO'}['TITULO-DO-PERIODICO-OU-REVISTA'],
                'volume' => (string) $artigo->{'DETALHAMENTO-DO-ARTIGO'}['VOLUME'],
                'paginas' => (string) $artigo->{'DETALHAMENTO-DO-ARTIGO'}['PAGINA-INICIAL'] . '-' . (string) $artigo->{'DETALHAMENTO-DO-ARTIGO'}['PAGINA-FINAL'],
                'issn' => (string) $artigo->{'DETALHAMENTO-DO-ARTIGO'}['ISSN'],
                'autores' => $autores,
                'id' => 'artigo-' . (string) $artigo->{'DADOS-BASICOS-DO-ARTIGO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Ordenar por ano (mais recente primeiro)
        usort($artigos, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $artigos;
    }

    /**
     * Obtém livros e capítulos
     */
    public function getLivrosCapitulos() {
        $items = [];
        $dados = $this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'};

        if (!$dados) return $items;

        // Livros publicados
        foreach ($dados->{'LIVROS-PUBLICADOS-OU-ORGANIZADOS'}->{'LIVRO-PUBLICADO-OU-ORGANIZADO'} ?? [] as $livro) {
            $autores = [];
            foreach ($livro->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $items[] = [
                'tipo' => 'Livro',
                'titulo' => (string) $livro->{'DADOS-BASICOS-DO-LIVRO'}['TITULO-DO-LIVRO'],
                'ano' => (string) $livro->{'DADOS-BASICOS-DO-LIVRO'}['ANO'],
                'editora' => (string) $livro->{'DETALHAMENTO-DO-LIVRO'}['NOME-DA-EDITORA'],
                'isbn' => (string) $livro->{'DETALHAMENTO-DO-LIVRO'}['ISBN'],
                'paginas' => (string) $livro->{'DETALHAMENTO-DO-LIVRO'}['NUMERO-DE-PAGINAS'],
                'autores' => $autores,
                'id' => 'livro-' . (string) $livro->{'DADOS-BASICOS-DO-LIVRO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Capítulos
        foreach ($dados->{'CAPITULOS-DE-LIVROS-PUBLICADOS'}->{'CAPITULO-DE-LIVRO-PUBLICADO'} ?? [] as $cap) {
            $autores = [];
            foreach ($cap->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $items[] = [
                'tipo' => 'Capítulo',
                'titulo' => (string) $cap->{'DADOS-BASICOS-DO-CAPITULO'}['TITULO-DO-CAPITULO-DO-LIVRO'],
                'ano' => (string) $cap->{'DADOS-BASICOS-DO-CAPITULO'}['ANO'],
                'livro' => (string) $cap->{'DETALHAMENTO-DO-CAPITULO'}['TITULO-DO-LIVRO'],
                'editora' => (string) $cap->{'DETALHAMENTO-DO-CAPITULO'}['NOME-DA-EDITORA'],
                'isbn' => (string) $cap->{'DETALHAMENTO-DO-CAPITULO'}['ISBN'],
                'paginas' => (string) $cap->{'DETALHAMENTO-DO-CAPITULO'}['PAGINA-INICIAL'] . '-' . (string) $cap->{'DETALHAMENTO-DO-CAPITULO'}['PAGINA-FINAL'],
                'autores' => $autores,
                'id' => 'capitulo-' . (string) $cap->{'DADOS-BASICOS-DO-CAPITULO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($items, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $items;
    }

    /**
     * Obtém trabalhos em eventos
     */
    public function getTrabalhosEventos() {
        $trabalhos = [];
        $dados = $this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'TRABALHOS-EM-EVENTOS'};

        if (!$dados) return $trabalhos;

        foreach ($dados->{'TRABALHO-EM-EVENTOS'} as $trabalho) {
            $autores = [];
            foreach ($trabalho->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $trabalhos[] = [
                'titulo' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO'}['TITULO-DO-TRABALHO'],
                'ano' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO'}['ANO-DO-TRABALHO'],
                'natureza' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO'}['NATUREZA'],
                'evento' => (string) $trabalho->{'DETALHAMENTO-DO-TRABALHO'}['NOME-DO-EVENTO'],
                'cidade' => (string) $trabalho->{'DETALHAMENTO-DO-TRABALHO'}['CIDADE-DO-EVENTO'],
                'autores' => $autores,
                'id' => 'trabalho-evento-' . (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($trabalhos, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $trabalhos;
    }

    /**
     * Obtém textos em jornais ou revistas
     */
    public function getTextosJornaisRevistas() {
        $textos = [];
        $dados = $this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'TEXTOS-EM-JORNAIS-OU-REVISTAS'};

        if (!$dados) return $textos;

        foreach ($dados->{'TEXTO-EM-JORNAL-OU-REVISTA'} as $texto) {
            $autores = [];
            foreach ($texto->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $textos[] = [
                'titulo' => (string) $texto->{'DADOS-BASICOS-DO-TEXTO'}['TITULO-DO-TEXTO'],
                'ano' => (string) $texto->{'DADOS-BASICOS-DO-TEXTO'}['ANO-DO-TEXTO'],
                'natureza' => (string) $texto->{'DADOS-BASICOS-DO-TEXTO'}['NATUREZA'],
                'veiculo' => (string) $texto->{'DETALHAMENTO-DO-TEXTO'}['TITULO-DO-JORNAL-OU-REVISTA'],
                'autores' => $autores,
                'id' => 'texto-jornal-' . (string) $texto->{'DADOS-BASICOS-DO-TEXTO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($textos, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $textos;
    }

    /**
     * Obtém softwares
     */
    public function getSoftware() {
        $softwares = [];
        $dados = $this->xml->{'PRODUCAO-TECNICA'}->{'SOFTWARE'};

        if (!$dados) return $softwares;

        foreach ($dados->{'SOFTWARE'} as $software) {
            $autores = [];
            foreach ($software->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $softwares[] = [
                'titulo' => (string) $software->{'DADOS-BASICOS-DO-SOFTWARE'}['TITULO-DO-SOFTWARE'],
                'ano' => (string) $software->{'DADOS-BASICOS-DO-SOFTWARE'}['ANO'],
                'natureza' => (string) $software->{'DADOS-BASICOS-DO-SOFTWARE'}['NATUREZA'],
                'finalidade' => (string) $software->{'DETALHAMENTO-DO-SOFTWARE'}['FINALIDADE'],
                'plataforma' => (string) $software->{'DETALHAMENTO-DO-SOFTWARE'}['PLATAFORMA'],
                'autores' => $autores,
                'id' => 'software-' . (string) $software->{'DADOS-BASICOS-DO-SOFTWARE'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($softwares, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $softwares;
    }

    /**
     * Obtém trabalhos técnicos
     */
    public function getTrabalhosTecnicos() {
        $trabalhos = [];
        $dados = $this->xml->{'PRODUCAO-TECNICA'}->{'TRABALHO-TECNICO'};

        if (!$dados) return $trabalhos;

        foreach ($dados->{'TRABALHO-TECNICO'} as $trabalho) {
            $autores = [];
            foreach ($trabalho->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            $trabalhos[] = [
                'titulo' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO-TECNICO'}['TITULO-DO-TRABALHO-TECNICO'],
                'ano' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO-TECNICO'}['ANO'],
                'natureza' => (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO-TECNICO'}['NATUREZA'],
                'finalidade' => (string) $trabalho->{'DETALHAMENTO-DO-TRABALHO-TECNICO'}['FINALIDADE'],
                'autores' => $autores,
                'id' => 'trabalho-tecnico-' . (string) $trabalho->{'DADOS-BASICOS-DO-TRABALHO-TECNICO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($trabalhos, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $trabalhos;
    }

    /**
     * Obtém demais produções técnicas
     */
    public function getDemaisProducoesTecnicas() {
        $producoes = [];
        $dados = $this->xml->{'PRODUCAO-TECNICA'}->{'DEMAIS-TIPOS-DE-PRODUCAO-TECNICA'};

        if (!$dados) return $producoes;

        foreach ($dados->children() as $producao) {
            $tipo = $producao->getName();
            $basicos = null;
            $titulo = '';
            $ano = '';

            // Tenta encontrar os dados básicos dependendo do tipo
            foreach ($producao->children() as $child) {
                $childName = $child->getName();
                if (strpos($childName, 'DADOS-BASICOS') !== false) {
                    $basicos = $child;
                    break;
                }
            }

            if ($basicos) {
                foreach ($basicos->attributes() as $attrName => $attrValue) {
                    if (strpos($attrName, 'TITULO') !== false) {
                        $titulo = (string) $attrValue;
                    }
                    if ($attrName === 'ANO') {
                        $ano = (string) $attrValue;
                    }
                }
            }

            $autores = [];
            foreach ($producao->{'AUTORES'} as $autor) {
                $autores[] = (string) $autor['NOME-COMPLETO-DO-AUTOR'];
            }

            if ($titulo) {
                $producoes[] = [
                    'tipo' => $this->formatTipoProducao($tipo),
                    'titulo' => $titulo,
                    'ano' => $ano,
                    'autores' => $autores,
                    'id' => 'demais-producao-' . md5($titulo . $ano)
                ];
            }
        }

        usort($producoes, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $producoes;
    }

    private function formatTipoProducao($tipo) {
        $tipos = [
            'APRESENTACAO-DE-TRABALHO' => 'Apresentação de Trabalho',
            'CURSO-DE-CURTA-DURACAO-MINISTRADO' => 'Curso de Curta Duração',
            'RELATORIO-DE-PESQUISA' => 'Relatório de Pesquisa',
            'CARTA-MAPA-OU-SIMILAR' => 'Carta, Mapa ou Similar',
            'DESENVOLVIMENTO-DE-MATERIAL-DIDATICO-OU-INSTRUCIONAL' => 'Material Didático',
            'ORGANIZACAO-DE-EVENTO' => 'Organização de Evento',
            'OUTRA-PRODUCAO-TECNICA' => 'Outra Produção Técnica',
            'EDITORACAO' => 'Editoração',
            'MANUTENCAO-DE-OBRA-ARTISTICA' => 'Manutenção de Obra Artística',
            'PROGRAMA-DE-RADIO-OU-TV' => 'Programa de Rádio ou TV',
        ];

        return $tipos[$tipo] ?? str_replace('-', ' ', ucfirst(strtolower($tipo)));
    }

    /**
     * Obtém orientações concluídas
     */
    public function getOrientacoes() {
        $orientacoes = [];
        $dados = $this->xml->{'OUTRA-PRODUCAO'}->{'ORIENTACOES-CONCLUIDAS'};

        if (!$dados) return $orientacoes;

        // Orientações de mestrado
        foreach ($dados->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'} ?? [] as $orient) {
            $orientacoes[] = [
                'tipo' => 'Mestrado',
                'titulo' => (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['TITULO'],
                'ano' => (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['ANO'],
                'orientando' => (string) $orient->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['NOME-DO-ORIENTADO'],
                'instituicao' => (string) $orient->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['NOME-DA-INSTITUICAO'],
                'id' => 'orientacao-mestrado-' . (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Orientações de doutorado
        foreach ($dados->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'} ?? [] as $orient) {
            $orientacoes[] = [
                'tipo' => 'Doutorado',
                'titulo' => (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['TITULO'],
                'ano' => (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['ANO'],
                'orientando' => (string) $orient->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['NOME-DO-ORIENTADO'],
                'instituicao' => (string) $orient->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['NOME-DA-INSTITUICAO'],
                'id' => 'orientacao-doutorado-' . (string) $orient->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Outras orientações
        foreach ($dados->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}->{'OUTRAS-ORIENTACOES-CONCLUIDAS'} ?? [] as $orient) {
            $orientacoes[] = [
                'tipo' => (string) $orient->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['NATUREZA'],
                'titulo' => (string) $orient->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['TITULO'],
                'ano' => (string) $orient->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['ANO'],
                'orientando' => (string) $orient->{'DETALHAMENTO-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['NOME-DO-ORIENTADO'],
                'instituicao' => (string) $orient->{'DETALHAMENTO-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['NOME-DA-INSTITUICAO'],
                'id' => 'orientacao-outra-' . (string) $orient->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($orientacoes, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $orientacoes;
    }

    /**
     * Obtém participação em bancas
     */
    public function getBancas() {
        $bancas = [];
        $dados = $this->xml->{'DADOS-COMPLEMENTARES'}->{'PARTICIPACAO-EM-BANCA-TRABALHOS-CONCLUSAO'};

        if (!$dados) return $bancas;

        // Bancas de mestrado
        foreach ($dados->{'PARTICIPACAO-EM-BANCA-DE-MESTRADO'}->{'PARTICIPACAO-EM-BANCA-DE-MESTRADO'} ?? [] as $banca) {
            $participantes = [];
            foreach ($banca->{'PARTICIPANTE-BANCA'} as $part) {
                $participantes[] = (string) $part['NOME-COMPLETO-DO-PARTICIPANTE-DA-BANCA'];
            }

            $bancas[] = [
                'tipo' => 'Mestrado',
                'titulo' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-MESTRADO'}['TITULO'],
                'ano' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-MESTRADO'}['ANO'],
                'candidato' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-MESTRADO'}['NOME-DO-CANDIDATO'],
                'instituicao' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-MESTRADO'}['NOME-INSTITUICAO'],
                'participantes' => $participantes,
                'id' => 'banca-mestrado-' . (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-MESTRADO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Bancas de doutorado
        foreach ($dados->{'PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}->{'PARTICIPACAO-EM-BANCA-DE-DOUTORADO'} ?? [] as $banca) {
            $participantes = [];
            foreach ($banca->{'PARTICIPANTE-BANCA'} as $part) {
                $participantes[] = (string) $part['NOME-COMPLETO-DO-PARTICIPANTE-DA-BANCA'];
            }

            $bancas[] = [
                'tipo' => 'Doutorado',
                'titulo' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}['TITULO'],
                'ano' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}['ANO'],
                'candidato' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}['NOME-DO-CANDIDATO'],
                'instituicao' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}['NOME-INSTITUICAO'],
                'participantes' => $participantes,
                'id' => 'banca-doutorado-' . (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-DOUTORADO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        // Bancas de graduação
        foreach ($dados->{'PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}->{'PARTICIPACAO-EM-BANCA-DE-GRADUACAO'} ?? [] as $banca) {
            $participantes = [];
            foreach ($banca->{'PARTICIPANTE-BANCA'} as $part) {
                $participantes[] = (string) $part['NOME-COMPLETO-DO-PARTICIPANTE-DA-BANCA'];
            }

            $bancas[] = [
                'tipo' => 'Graduação',
                'titulo' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}['TITULO'],
                'ano' => (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}['ANO'],
                'candidato' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}['NOME-DO-CANDIDATO'],
                'instituicao' => (string) $banca->{'DETALHAMENTO-DA-PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}['NOME-INSTITUICAO'],
                'participantes' => $participantes,
                'id' => 'banca-graduacao-' . (string) $banca->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-BANCA-DE-GRADUACAO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($bancas, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $bancas;
    }

    /**
     * Obtém participação em eventos
     */
    public function getEventos() {
        $eventos = [];
        $dados = $this->xml->{'DADOS-COMPLEMENTARES'}->{'PARTICIPACAO-EM-EVENTOS-CONGRESSOS'};

        if (!$dados) return $eventos;

        foreach ($dados->{'PARTICIPACAO-EM-CONGRESSO'} as $evento) {
            $eventos[] = [
                'tipo' => 'Congresso',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-CONGRESSO'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-CONGRESSO'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-CONGRESSO'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-CONGRESSO'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-CONGRESSO'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-congresso-' . (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-CONGRESSO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        foreach ($dados->{'PARTICIPACAO-EM-SEMINARIO'} ?? [] as $evento) {
            $eventos[] = [
                'tipo' => 'Seminário',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SEMINARIO'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SEMINARIO'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SEMINARIO'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-SEMINARIO'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-SEMINARIO'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-seminario-' . (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SEMINARIO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        foreach ($dados->{'PARTICIPACAO-EM-SIMPOSIO'} ?? [] as $evento) {
            $eventos[] = [
                'tipo' => 'Simpósio',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SIMPOSIO'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SIMPOSIO'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SIMPOSIO'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-SIMPOSIO'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-SIMPOSIO'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-simposio-' . (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-SIMPOSIO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        foreach ($dados->{'PARTICIPACAO-EM-ENCONTRO'} ?? [] as $evento) {
            $eventos[] = [
                'tipo' => 'Encontro',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-ENCONTRO'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-ENCONTRO'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-ENCONTRO'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-ENCONTRO'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-ENCONTRO'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-encontro-' . (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-ENCONTRO'}['SEQUENCIA-PRODUCAO']
            ];
        }

        foreach ($dados->{'PARTICIPACAO-EM-OFICINA'} ?? [] as $evento) {
            $eventos[] = [
                'tipo' => 'Oficina',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-OFICINA'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-OFICINA'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-OFICINA'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-OFICINA'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DA-PARTICIPACAO-EM-OFICINA'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-oficina-' . (string) $evento->{'DADOS-BASICOS-DA-PARTICIPACAO-EM-OFICINA'}['SEQUENCIA-PRODUCAO']
            ];
        }

        foreach ($dados->{'OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'} ?? [] as $evento) {
            $eventos[] = [
                'tipo' => 'Outro',
                'titulo' => (string) $evento->{'DADOS-BASICOS-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['TITULO'],
                'ano' => (string) $evento->{'DADOS-BASICOS-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['ANO'],
                'natureza' => (string) $evento->{'DADOS-BASICOS-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['NATUREZA'],
                'nome_evento' => (string) $evento->{'DETALHAMENTO-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['NOME-DO-EVENTO'],
                'cidade' => (string) $evento->{'DETALHAMENTO-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['CIDADE-DO-EVENTO'],
                'id' => 'evento-outro-' . (string) $evento->{'DADOS-BASICOS-DE-OUTRA-PARTICIPACAO-EM-EVENTOS-CONGRESSOS'}['SEQUENCIA-PRODUCAO']
            ];
        }

        usort($eventos, function($a, $b) {
            return ($b['ano'] ?? 0) - ($a['ano'] ?? 0);
        });

        return $eventos;
    }

    /**
     * Obtém formação complementar
     */
    public function getFormacaoComplementar() {
        $formacoes = [];
        $dados = $this->xml->{'DADOS-COMPLEMENTARES'}->{'FORMACAO-COMPLEMENTAR'};

        if (!$dados) return $formacoes;

        foreach ($dados->{'FORMACAO-COMPLEMENTAR-DE-EXTENSAO-UNIVERSITARIA'} ?? [] as $form) {
            $formacoes[] = [
                'tipo' => 'Extensão Universitária',
                'titulo' => (string) $form['TITULO-DA-FORMACAO-COMPLEMENTAR'],
                'ano_inicio' => (string) $form['ANO-DE-INICIO'],
                'ano_fim' => (string) $form['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $form['NOME-INSTITUICAO'],
                'carga_horaria' => (string) $form['CARGA-HORARIA'],
                'id' => 'formacao-ext-' . (string) $form['SEQUENCIA-FORMACAO-COMPLEMENTAR']
            ];
        }

        foreach ($dados->{'FORMACAO-COMPLEMENTAR-CURSO-DE-CURTA-DURACAO'} ?? [] as $form) {
            $formacoes[] = [
                'tipo' => 'Curso de Curta Duração',
                'titulo' => (string) $form['TITULO-DA-FORMACAO-COMPLEMENTAR'],
                'ano_inicio' => (string) $form['ANO-DE-INICIO'],
                'ano_fim' => (string) $form['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $form['NOME-INSTITUICAO'],
                'carga_horaria' => (string) $form['CARGA-HORARIA'],
                'id' => 'formacao-curta-' . (string) $form['SEQUENCIA-FORMACAO-COMPLEMENTAR']
            ];
        }

        foreach ($dados->{'OUTRAS-FORMACOES-COMPLEMENTARES'} ?? [] as $form) {
            $formacoes[] = [
                'tipo' => 'Outra',
                'titulo' => (string) $form['TITULO-DA-FORMACAO-COMPLEMENTAR'],
                'ano_inicio' => (string) $form['ANO-DE-INICIO'],
                'ano_fim' => (string) $form['ANO-DE-CONCLUSAO'],
                'instituicao' => (string) $form['NOME-INSTITUICAO'],
                'carga_horaria' => (string) $form['CARGA-HORARIA'],
                'id' => 'formacao-outra-' . (string) $form['SEQUENCIA-FORMACAO-COMPLEMENTAR']
            ];
        }

        usort($formacoes, function($a, $b) {
            return ($b['ano_inicio'] ?? 0) - ($a['ano_inicio'] ?? 0);
        });

        return $formacoes;
    }

    /**
     * Obtém outras informações relevantes
     */
    public function getOutrasInformacoes() {
        $dados = $this->xml->{'DADOS-GERAIS'};
        $texto = (string) $dados->{'OUTRAS-INFORMACOES-RELEVANTES'}['OUTRAS-INFORMACOES-RELEVANTES'];

        if (empty($texto)) {
            return [];
        }

        return [[
            'titulo' => 'Outras Informações Relevantes',
            'texto' => $texto,
            'id' => 'outras-informacoes'
        ]];
    }

    /**
     * Retorna todas as seções disponíveis para o admin
     */
    public function getAllSections() {
        return [
            'RESUMO-CV' => [
                'nome' => 'Resumo',
                'items' => [[
                    'titulo' => 'Resumo do Currículo',
                    'id' => 'resumo-cv'
                ]]
            ],
            'FORMACAO-ACADEMICA-TITULACAO' => [
                'nome' => 'Formação Acadêmica',
                'items' => $this->getFormacaoAcademica()
            ],
            'ATUACOES-PROFISSIONAIS' => [
                'nome' => 'Atuações Profissionais',
                'items' => $this->getAtuacoesProfissionais()
            ],
            'AREAS-DE-ATUACAO' => [
                'nome' => 'Áreas de Atuação',
                'items' => $this->getAreasAtuacao()
            ],
            'IDIOMAS' => [
                'nome' => 'Idiomas',
                'items' => $this->getIdiomas()
            ],
            'ARTIGOS-PUBLICADOS' => [
                'nome' => 'Artigos Publicados',
                'items' => $this->getArtigos()
            ],
            'LIVROS-E-CAPITULOS' => [
                'nome' => 'Livros e Capítulos',
                'items' => $this->getLivrosCapitulos()
            ],
            'TRABALHOS-EM-EVENTOS' => [
                'nome' => 'Trabalhos em Eventos',
                'items' => $this->getTrabalhosEventos()
            ],
            'TEXTOS-EM-JORNAIS-OU-REVISTAS' => [
                'nome' => 'Textos em Jornais ou Revistas',
                'items' => $this->getTextosJornaisRevistas()
            ],
            'SOFTWARE' => [
                'nome' => 'Software',
                'items' => $this->getSoftware()
            ],
            'TRABALHO-TECNICO' => [
                'nome' => 'Trabalho Técnico',
                'items' => $this->getTrabalhosTecnicos()
            ],
            'DEMAIS-TIPOS-DE-PRODUCAO-TECNICA' => [
                'nome' => 'Outras Produções Técnicas',
                'items' => $this->getDemaisProducoesTecnicas()
            ],
            'ORIENTACOES-CONCLUIDAS' => [
                'nome' => 'Orientações Concluídas',
                'items' => $this->getOrientacoes()
            ],
            'PARTICIPACAO-EM-BANCA-TRABALHOS-CONCLUSAO' => [
                'nome' => 'Participação em Bancas',
                'items' => $this->getBancas()
            ],
            'PARTICIPACAO-EM-EVENTOS-CONGRESSOS' => [
                'nome' => 'Participação em Eventos',
                'items' => $this->getEventos()
            ],
            'FORMACAO-COMPLEMENTAR' => [
                'nome' => 'Formação Complementar',
                'items' => $this->getFormacaoComplementar()
            ],
            'OUTRAS-INFORMACOES-RELEVANTES' => [
                'nome' => 'Outras Informações Relevantes',
                'items' => $this->getOutrasInformacoes()
            ]
        ];
    }
}
