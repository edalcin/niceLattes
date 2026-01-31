<?php
/**
 * niceLattes - Página Pública do CV
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cv-parser.php';

// Verifica se o arquivo XML existe
if (!file_exists(CV_XML_PATH)) {
    die('Erro: Arquivo XML do CV não encontrado. Verifique o config.ini');
}

// Carrega visibilidade e parser
$visibility = getVisibility();
$parser = new CVParser(CV_XML_PATH, $visibility);

// Dados gerais
$dados = $parser->getDadosGerais();

// Carrega todas as seções
$formacaoAcademica = $parser->getFormacaoAcademica();
$atuacoes = $parser->getAtuacoesProfissionais();
$areas = $parser->getAreasAtuacao();
$idiomas = $parser->getIdiomas();
$artigos = $parser->getArtigos();
$livros = $parser->getLivrosCapitulos();
$trabalhosEventos = $parser->getTrabalhosEventos();
$textosJornais = $parser->getTextosJornaisRevistas();
$softwares = $parser->getSoftware();
$trabalhosTecnicos = $parser->getTrabalhosTecnicos();
$demaisProducoes = $parser->getDemaisProducoesTecnicas();
$orientacoes = $parser->getOrientacoes();
$bancas = $parser->getBancas();
$eventos = $parser->getEventos();
$formacaoComplementar = $parser->getFormacaoComplementar();

// Função auxiliar para filtrar itens ocultos
function filterItems($items, $visibility) {
    return array_filter($items, function($item) use ($visibility) {
        return !isItemHidden($item['id'] ?? '', $visibility);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($dados['nome']) ?> - Curriculum Vitae</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Header -->
<header class="cv-header">
    <div class="container">
        <h1><?= h($dados['nome']) ?></h1>
        <div class="contact-info">
            <?php if ($dados['instituicao']): ?>
                <span><?= h($dados['instituicao']) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($dados['email']): ?>
        <div class="contact-email">
            <a href="mailto:<?= h($dados['email']) ?>"><?= h($dados['email']) ?></a>
        </div>
        <?php endif; ?>
    </div>
</header>

<main class="container">

    <!-- Resumo -->
    <?php if ($dados['resumo_pt'] && !isSectionHidden('RESUMO-CV', $visibility)): ?>
    <section class="resumo-section">
        <h2>Resumo</h2>
        <p><?= nl2br(h($dados['resumo_pt'])) ?></p>
    </section>
    <?php endif; ?>

    <!-- Formação Acadêmica -->
    <?php
    $formacaoFiltrada = filterItems($formacaoAcademica, $visibility);
    if (!empty($formacaoFiltrada) && !isSectionHidden('FORMACAO-ACADEMICA-TITULACAO', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Formação Acadêmica</h2>
        <div class="timeline">
            <?php foreach ($formacaoFiltrada as $formacao): ?>
            <div class="timeline-item">
                <span class="timeline-period"><?= h(formatYear($formacao['ano_inicio'])) ?> - <?= h(formatYear($formacao['ano_fim'])) ?></span>
                <div class="timeline-title"><?= h($formacao['tipo']) ?><?php if ($formacao['curso']): ?> em <?= h($formacao['curso']) ?><?php endif; ?></div>
                <div class="timeline-institution"><?= h($formacao['instituicao']) ?></div>
                <?php if ($formacao['titulo']): ?>
                <div class="timeline-details">
                    <strong>Título:</strong> <?= h($formacao['titulo']) ?>
                    <?php if ($formacao['orientador']): ?>
                    <br><strong>Orientador:</strong> <?= h($formacao['orientador']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Atuações Profissionais -->
    <?php
    $atuacoesFiltradas = filterItems($atuacoes, $visibility);
    if (!empty($atuacoesFiltradas) && !isSectionHidden('ATUACOES-PROFISSIONAIS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Atuações Profissionais</h2>
        <ul class="simple-list">
            <?php foreach ($atuacoesFiltradas as $atuacao): ?>
            <li><?= h($atuacao['instituicao']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Áreas de Atuação -->
    <?php
    $areasFiltradas = filterItems($areas, $visibility);
    if (!empty($areasFiltradas) && !isSectionHidden('AREAS-DE-ATUACAO', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Áreas de Atuação</h2>
        <ul class="grid-list">
            <?php foreach ($areasFiltradas as $area): ?>
            <li>
                <div class="item-title"><?= h(formatLattesText($area['grande_area'])) ?></div>
                <div class="item-details">
                    <?php if ($area['area']): ?><?= h(formatLattesText($area['area'])) ?><?php endif; ?>
                    <?php if ($area['subarea']): ?> / <?= h(formatLattesText($area['subarea'])) ?><?php endif; ?>
                    <?php if ($area['especialidade']): ?> / <?= h(formatLattesText($area['especialidade'])) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Idiomas -->
    <?php
    $idiomasFiltrados = filterItems($idiomas, $visibility);
    if (!empty($idiomasFiltrados) && !isSectionHidden('IDIOMAS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Idiomas</h2>
        <ul class="grid-list">
            <?php foreach ($idiomasFiltrados as $idioma): ?>
            <li>
                <div class="item-title"><?= h($idioma['idioma']) ?></div>
                <div class="proficiency">
                    <span>Compreende: <?= h($idioma['compreende']) ?></span>
                    <span>Fala: <?= h($idioma['fala']) ?></span>
                    <span>Lê: <?= h($idioma['le']) ?></span>
                    <span>Escreve: <?= h($idioma['escreve']) ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Artigos Publicados -->
    <?php
    $artigosFiltrados = filterItems($artigos, $visibility);
    if (!empty($artigosFiltrados) && !isSectionHidden('ARTIGOS-PUBLICADOS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Artigos Publicados</h2>
        <ul class="item-list">
            <?php foreach ($artigosFiltrados as $artigo): ?>
            <li>
                <span class="item-year"><?= h($artigo['ano']) ?></span>
                <span class="item-title"><?= h($artigo['titulo']) ?></span>
                <?php if (!empty($artigo['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $artigo['autores'])) ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?= h($artigo['periodico']) ?>
                    <?php if ($artigo['volume']): ?>, v. <?= h($artigo['volume']) ?><?php endif; ?>
                    <?php if ($artigo['paginas'] !== '-'): ?>, p. <?= h($artigo['paginas']) ?><?php endif; ?>
                    <?php if ($artigo['doi']): ?>
                        - <a href="https://doi.org/<?= h($artigo['doi']) ?>" target="_blank">DOI</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Livros e Capítulos -->
    <?php
    $livrosFiltrados = filterItems($livros, $visibility);
    if (!empty($livrosFiltrados) && !isSectionHidden('LIVROS-E-CAPITULOS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Livros e Capítulos</h2>
        <ul class="item-list">
            <?php foreach ($livrosFiltrados as $item): ?>
            <li>
                <span class="item-year"><?= h($item['ano']) ?></span>
                <span class="item-type"><?= h($item['tipo']) ?></span>
                <span class="item-title"><?= h($item['titulo']) ?></span>
                <?php if (!empty($item['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $item['autores'])) ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?php if (isset($item['livro']) && $item['livro']): ?>
                        In: <?= h($item['livro']) ?>.
                    <?php endif; ?>
                    <?php if ($item['editora']): ?><?= h($item['editora']) ?><?php endif; ?>
                    <?php if (isset($item['paginas']) && $item['paginas'] && $item['paginas'] !== '-'): ?>, p. <?= h($item['paginas']) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Trabalhos em Eventos -->
    <?php
    $trabalhosFiltrados = filterItems($trabalhosEventos, $visibility);
    if (!empty($trabalhosFiltrados) && !isSectionHidden('TRABALHOS-EM-EVENTOS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Trabalhos em Eventos</h2>
        <ul class="item-list">
            <?php foreach ($trabalhosFiltrados as $trabalho): ?>
            <li>
                <span class="item-year"><?= h($trabalho['ano']) ?></span>
                <?php if ($trabalho['natureza']): ?>
                <span class="item-type"><?= h($trabalho['natureza']) ?></span>
                <?php endif; ?>
                <span class="item-title"><?= h($trabalho['titulo']) ?></span>
                <?php if (!empty($trabalho['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $trabalho['autores'])) ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?= h($trabalho['evento']) ?>
                    <?php if ($trabalho['cidade']): ?>, <?= h($trabalho['cidade']) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Textos em Jornais ou Revistas -->
    <?php
    $textosFiltrados = filterItems($textosJornais, $visibility);
    if (!empty($textosFiltrados) && !isSectionHidden('TEXTOS-EM-JORNAIS-OU-REVISTAS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Textos em Jornais ou Revistas</h2>
        <ul class="item-list">
            <?php foreach ($textosFiltrados as $texto): ?>
            <li>
                <span class="item-year"><?= h($texto['ano']) ?></span>
                <span class="item-title"><?= h($texto['titulo']) ?></span>
                <?php if (!empty($texto['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $texto['autores'])) ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?= h($texto['veiculo']) ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Software -->
    <?php
    $softwaresFiltrados = filterItems($softwares, $visibility);
    if (!empty($softwaresFiltrados) && !isSectionHidden('SOFTWARE', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Software</h2>
        <ul class="item-list">
            <?php foreach ($softwaresFiltrados as $software): ?>
            <li>
                <span class="item-year"><?= h($software['ano']) ?></span>
                <span class="item-title"><?= h($software['titulo']) ?></span>
                <?php if (!empty($software['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $software['autores'])) ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?php if ($software['finalidade']): ?>Finalidade: <?= h($software['finalidade']) ?><?php endif; ?>
                    <?php if ($software['plataforma']): ?> | Plataforma: <?= h($software['plataforma']) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Trabalhos Técnicos -->
    <?php
    $tecnicosFiltrados = filterItems($trabalhosTecnicos, $visibility);
    if (!empty($tecnicosFiltrados) && !isSectionHidden('TRABALHO-TECNICO', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Trabalhos Técnicos</h2>
        <ul class="item-list">
            <?php foreach ($tecnicosFiltrados as $trabalho): ?>
            <li>
                <span class="item-year"><?= h($trabalho['ano']) ?></span>
                <span class="item-title"><?= h($trabalho['titulo']) ?></span>
                <?php if (!empty($trabalho['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $trabalho['autores'])) ?></div>
                <?php endif; ?>
                <?php if ($trabalho['finalidade']): ?>
                <div class="item-details">Finalidade: <?= h($trabalho['finalidade']) ?></div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Outras Produções Técnicas -->
    <?php
    $demaisFiltrados = filterItems($demaisProducoes, $visibility);
    if (!empty($demaisFiltrados) && !isSectionHidden('DEMAIS-TIPOS-DE-PRODUCAO-TECNICA', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Outras Produções Técnicas</h2>
        <ul class="item-list">
            <?php foreach ($demaisFiltrados as $producao): ?>
            <li>
                <span class="item-year"><?= h($producao['ano']) ?></span>
                <span class="item-type"><?= h($producao['tipo']) ?></span>
                <span class="item-title"><?= h($producao['titulo']) ?></span>
                <?php if (!empty($producao['autores'])): ?>
                <div class="item-authors"><?= h(implode('; ', $producao['autores'])) ?></div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Orientações Concluídas -->
    <?php
    $orientacoesFiltradas = filterItems($orientacoes, $visibility);
    if (!empty($orientacoesFiltradas) && !isSectionHidden('ORIENTACOES-CONCLUIDAS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Orientações Concluídas</h2>
        <ul class="item-list">
            <?php foreach ($orientacoesFiltradas as $orientacao): ?>
            <li>
                <span class="item-year"><?= h($orientacao['ano']) ?></span>
                <span class="item-type"><?= h($orientacao['tipo']) ?></span>
                <span class="item-title"><?= h($orientacao['titulo']) ?></span>
                <div class="item-details">
                    Orientando: <?= h($orientacao['orientando']) ?>
                    <?php if ($orientacao['instituicao']): ?> - <?= h($orientacao['instituicao']) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Participação em Bancas -->
    <?php
    $bancasFiltradas = filterItems($bancas, $visibility);
    if (!empty($bancasFiltradas) && !isSectionHidden('PARTICIPACAO-EM-BANCA-TRABALHOS-CONCLUSAO', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Participação em Bancas</h2>
        <ul class="item-list">
            <?php foreach ($bancasFiltradas as $banca): ?>
            <li>
                <span class="item-year"><?= h($banca['ano']) ?></span>
                <span class="item-type"><?= h($banca['tipo']) ?></span>
                <span class="item-title"><?= h($banca['titulo']) ?></span>
                <div class="item-details">
                    Candidato: <?= h($banca['candidato']) ?>
                    <?php if ($banca['instituicao']): ?> - <?= h($banca['instituicao']) ?><?php endif; ?>
                </div>
                <?php if (!empty($banca['participantes'])): ?>
                <div class="item-authors">Banca: <?= h(implode('; ', $banca['participantes'])) ?></div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Participação em Eventos -->
    <?php
    $eventosFiltrados = filterItems($eventos, $visibility);
    if (!empty($eventosFiltrados) && !isSectionHidden('PARTICIPACAO-EM-EVENTOS-CONGRESSOS', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Participação em Eventos</h2>
        <ul class="item-list">
            <?php foreach ($eventosFiltrados as $evento): ?>
            <li>
                <span class="item-year"><?= h($evento['ano']) ?></span>
                <span class="item-type"><?= h($evento['tipo']) ?></span>
                <?php if ($evento['titulo']): ?>
                <span class="item-title"><?= h($evento['titulo']) ?></span>
                <?php endif; ?>
                <div class="item-details">
                    <?= h($evento['nome_evento']) ?>
                    <?php if ($evento['cidade']): ?>, <?= h($evento['cidade']) ?><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Formação Complementar -->
    <?php
    $formacaoCompFiltrada = filterItems($formacaoComplementar, $visibility);
    if (!empty($formacaoCompFiltrada) && !isSectionHidden('FORMACAO-COMPLEMENTAR', $visibility)):
    ?>
    <section class="cv-section">
        <h2>Formação Complementar</h2>
        <ul class="item-list">
            <?php foreach ($formacaoCompFiltrada as $formacao): ?>
            <li>
                <span class="item-year"><?= h($formacao['ano_fim'] ?: $formacao['ano_inicio']) ?></span>
                <span class="item-type"><?= h($formacao['tipo']) ?></span>
                <span class="item-title"><?= h($formacao['titulo']) ?></span>
                <div class="item-details">
                    <?= h($formacao['instituicao']) ?>
                    <?php if ($formacao['carga_horaria']): ?> (<?= h($formacao['carga_horaria']) ?>h)<?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Outras Informações Relevantes -->
    <?php if ($dados['outras_info'] && !isSectionHidden('OUTRAS-INFORMACOES-RELEVANTES', $visibility)): ?>
    <div class="outras-info">
        <h3>Outras Informações Relevantes</h3>
        <p><?= nl2br(h(str_replace('&#10;', "\n", $dados['outras_info']))) ?></p>
    </div>
    <?php endif; ?>

</main>

<footer class="cv-footer">
    <div class="container">
        <p>Currículo gerado a partir da <a href="https://lattes.cnpq.br/" target="_blank">Plataforma Lattes</a></p>
    </div>
</footer>

</body>
</html>
