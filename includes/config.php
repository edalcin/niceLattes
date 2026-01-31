<?php
/**
 * Configurações do sistema
 */

// Carrega configurações do arquivo config.ini na raiz
$configFile = __DIR__ . '/../config.ini';
if (!file_exists($configFile)) {
    die('Erro: Arquivo config.ini não encontrado na raiz do aplicativo.');
}
$config = parse_ini_file($configFile);

// PIN para acesso administrativo (definido no config.ini)
define('ADMIN_PIN', $config['admin_pin'] ?? '0000');

// Caminho do arquivo XML do CV Lattes (definido no config.ini)
$cvXmlFile = $config['cv_xml_file'] ?? 'cv.xml';
define('CV_XML_PATH', __DIR__ . '/../' . $cvXmlFile);

// Caminho do arquivo de visibilidade
define('VISIBILITY_PATH', __DIR__ . '/../data/visibility.json');

// Mapeamento de nomes de seções para português
$SECTION_NAMES = [
    'DADOS-GERAIS' => 'Dados Gerais',
    'RESUMO-CV' => 'Resumo',
    'FORMACAO-ACADEMICA-TITULACAO' => 'Formação Acadêmica',
    'ATUACOES-PROFISSIONAIS' => 'Atuações Profissionais',
    'AREAS-DE-ATUACAO' => 'Áreas de Atuação',
    'IDIOMAS' => 'Idiomas',
    'PRODUCAO-BIBLIOGRAFICA' => 'Produção Bibliográfica',
    'ARTIGOS-PUBLICADOS' => 'Artigos Publicados',
    'LIVROS-E-CAPITULOS' => 'Livros e Capítulos',
    'TRABALHOS-EM-EVENTOS' => 'Trabalhos em Eventos',
    'TEXTOS-EM-JORNAIS-OU-REVISTAS' => 'Textos em Jornais ou Revistas',
    'DEMAIS-TIPOS-DE-PRODUCAO-BIBLIOGRAFICA' => 'Outras Produções Bibliográficas',
    'PRODUCAO-TECNICA' => 'Produção Técnica',
    'SOFTWARE' => 'Software',
    'TRABALHO-TECNICO' => 'Trabalho Técnico',
    'DEMAIS-TIPOS-DE-PRODUCAO-TECNICA' => 'Outras Produções Técnicas',
    'OUTRA-PRODUCAO' => 'Outra Produção',
    'ORIENTACOES-CONCLUIDAS' => 'Orientações Concluídas',
    'DEMAIS-TRABALHOS' => 'Demais Trabalhos',
    'DADOS-COMPLEMENTARES' => 'Dados Complementares',
    'FORMACAO-COMPLEMENTAR' => 'Formação Complementar',
    'PARTICIPACAO-EM-BANCA-TRABALHOS-CONCLUSAO' => 'Participação em Bancas',
    'PARTICIPACAO-EM-EVENTOS-CONGRESSOS' => 'Participação em Eventos e Congressos'
];

/**
 * Lê o arquivo de configuração de visibilidade
 * @return array Configurações de visibilidade
 */
function getVisibility() {
    if (!file_exists(VISIBILITY_PATH)) {
        return ['hidden_sections' => [], 'hidden_items' => []];
    }
    $content = file_get_contents(VISIBILITY_PATH);
    $data = json_decode($content, true);
    if (!$data) {
        return ['hidden_sections' => [], 'hidden_items' => []];
    }
    return $data;
}

/**
 * Salva as configurações de visibilidade
 * @param array $visibility Configurações de visibilidade
 * @return bool Sucesso ou falha
 */
function saveVisibility($visibility) {
    $json = json_encode($visibility, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(VISIBILITY_PATH, $json) !== false;
}

/**
 * Verifica se uma seção está oculta
 * @param string $section Nome da seção
 * @param array $visibility Configurações de visibilidade
 * @return bool
 */
function isSectionHidden($section, $visibility) {
    return in_array($section, $visibility['hidden_sections'] ?? []);
}

/**
 * Verifica se um item está oculto
 * @param string $itemId Identificador do item
 * @param array $visibility Configurações de visibilidade
 * @return bool
 */
function isItemHidden($itemId, $visibility) {
    return in_array($itemId, $visibility['hidden_items'] ?? []);
}

/**
 * Formata data do formato Lattes (DDMMAAAA) para legível
 * @param string $date Data no formato DDMMAAAA
 * @return string Data formatada
 */
function formatLattesDate($date) {
    if (empty($date) || strlen($date) < 4) {
        return '';
    }

    // Alguns campos podem ter apenas o ano (AAAA)
    if (strlen($date) == 4) {
        return $date;
    }

    // Formato DDMMAAAA
    if (strlen($date) == 8) {
        $day = substr($date, 0, 2);
        $month = substr($date, 2, 2);
        $year = substr($date, 4, 4);

        $months = [
            '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
        ];

        $monthName = $months[$month] ?? $month;
        return "$day $monthName $year";
    }

    return $date;
}

/**
 * Formata ano do formato Lattes
 * @param string $year Ano
 * @return string Ano formatado
 */
function formatYear($year) {
    if (empty($year)) {
        return 'atual';
    }
    return $year;
}

/**
 * Escapa HTML para prevenir XSS
 * @param string $text Texto a ser escapado
 * @return string Texto escapado
 */
function h($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formata texto do Lattes (CAIXA_ALTA_COM_UNDERSCORE → Caixa Alta Com Underscore)
 * @param string $text Texto a ser formatado
 * @return string Texto formatado
 */
function formatLattesText($text) {
    if (empty($text)) return '';

    // Substitui underscores por espaços
    $text = str_replace('_', ' ', $text);

    // Converte para minúsculas e capitaliza cada palavra
    $text = mb_convert_case(mb_strtolower($text, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    // Corrige preposições e artigos que devem ficar em minúsculas
    $lowercase = ['Da', 'Das', 'De', 'Do', 'Dos', 'E', 'Em', 'Na', 'Nas', 'No', 'Nos', 'Para', 'Por', 'Com', 'Sem'];
    foreach ($lowercase as $word) {
        $text = preg_replace('/\b' . $word . '\b/u', mb_strtolower($word, 'UTF-8'), $text);
    }

    // Garante que a primeira letra seja maiúscula
    $text = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');

    return $text;
}
