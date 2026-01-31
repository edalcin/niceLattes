<?php
/**
 * niceLattes - Painel Administrativo
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/cv-parser.php';

// Processa logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

// Processa login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if ($_POST['pin'] === ADMIN_PIN) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'PIN incorreto. Tente novamente.';
    }
}

// Verifica se está logado
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Processa salvamento de configurações
$saveMessage = '';
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_visibility'])) {
    $hiddenSections = isset($_POST['hidden_sections']) ? $_POST['hidden_sections'] : [];
    $hiddenItems = isset($_POST['hidden_items']) ? $_POST['hidden_items'] : [];

    $visibility = [
        'hidden_sections' => $hiddenSections,
        'hidden_items' => $hiddenItems
    ];

    if (saveVisibility($visibility)) {
        $saveMessage = 'Configurações salvas com sucesso!';
    } else {
        $saveMessage = 'Erro ao salvar configurações.';
    }
}

// Se não estiver logado, mostra formulário de login
if (!$isLoggedIn):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - niceLattes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-container">
    <h1>niceLattes</h1>
    <p style="text-align: center; color: #718096; margin-bottom: 1.5rem;">Painel Administrativo</p>

    <?php if ($loginError): ?>
    <div class="alert alert-error"><?= h($loginError) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin.php">
        <div class="form-group">
            <label for="pin">PIN de Acesso</label>
            <input type="password" id="pin" name="pin" placeholder="Digite o PIN" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Entrar</button>
    </form>

    <p style="text-align: center; margin-top: 1.5rem;">
        <a href="index.php">&larr; Voltar ao CV</a>
    </p>
</div>

</body>
</html>
<?php
exit;
endif;

// Carrega dados para o painel
$visibility = getVisibility();

// Verifica se o arquivo XML existe
if (!file_exists(CV_XML_PATH)) {
    die('Erro: Arquivo XML do CV não encontrado. Verifique o config.ini');
}

$parser = new CVParser(CV_XML_PATH, $visibility);
$sections = $parser->getAllSections();
$dados = $parser->getDadosGerais();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - niceLattes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="admin-header">
    <div class="container admin-container">
        <h1>niceLattes Admin</h1>
        <nav class="admin-nav">
            <a href="index.php" target="_blank">Ver CV</a>
            <a href="admin.php?logout=1">Sair</a>
        </nav>
    </div>
</header>

<main class="container admin-container">

    <?php if ($saveMessage): ?>
    <div class="alert <?= strpos($saveMessage, 'sucesso') !== false ? 'alert-success' : 'alert-error' ?>">
        <?= h($saveMessage) ?>
    </div>
    <?php endif; ?>

    <div class="actions-bar">
        <div>
            <strong>CV:</strong> <?= h($dados['nome']) ?>
        </div>
        <button type="submit" form="visibility-form" class="btn btn-success">Salvar Configurações</button>
    </div>

    <form id="visibility-form" method="POST" action="admin.php">
        <input type="hidden" name="save_visibility" value="1">

        <p style="margin-bottom: 1rem; color: #718096;">
            Desmarque as seções ou itens que deseja ocultar no CV público. Clique no cabeçalho da seção para expandir/recolher.
        </p>

        <?php foreach ($sections as $sectionKey => $section): ?>
        <div class="admin-section">
            <div class="section-header" onclick="toggleSection('<?= h($sectionKey) ?>')">
                <h3>
                    <?= h($section['nome']) ?>
                    <span class="count-badge"><?= count($section['items']) ?></span>
                </h3>
                <div class="toggle-section">
                    <span style="font-size: 0.875rem; color: #718096;">Seção visível</span>
                    <label class="toggle-switch" onclick="event.stopPropagation()">
                        <input type="checkbox"
                               name="section_visible[<?= h($sectionKey) ?>]"
                               <?= !isSectionHidden($sectionKey, $visibility) ? 'checked' : '' ?>
                               onchange="toggleSectionVisibility('<?= h($sectionKey) ?>', this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="section-content" id="section-<?= h($sectionKey) ?>">
                <?php if (empty($section['items'])): ?>
                <p style="color: #a0aec0; font-style: italic;">Nenhum item nesta seção.</p>
                <?php else: ?>
                    <?php foreach ($section['items'] as $item): ?>
                    <div class="admin-item">
                        <div class="admin-item-info">
                            <div class="admin-item-title">
                                <?php
                                // Determina o título baseado no tipo de item
                                $titulo = $item['titulo'] ?? $item['nome'] ?? $item['idioma'] ?? $item['instituicao'] ?? 'Item';
                                $ano = $item['ano'] ?? $item['ano_fim'] ?? '';
                                $tipo = $item['tipo'] ?? '';
                                ?>
                                <?php if ($ano): ?><span class="item-year"><?= h($ano) ?></span><?php endif; ?>
                                <?php if ($tipo): ?><span class="item-type"><?= h($tipo) ?></span><?php endif; ?>
                                <?= h($titulo) ?>
                            </div>
                            <div class="admin-item-meta">
                                <?php
                                // Mostra informações adicionais
                                if (isset($item['autores']) && !empty($item['autores'])) {
                                    echo h(implode('; ', array_slice($item['autores'], 0, 3)));
                                    if (count($item['autores']) > 3) echo ' et al.';
                                } elseif (isset($item['instituicao']) && $item['instituicao']) {
                                    echo h($item['instituicao']);
                                } elseif (isset($item['orientando']) && $item['orientando']) {
                                    echo 'Orientando: ' . h($item['orientando']);
                                } elseif (isset($item['candidato']) && $item['candidato']) {
                                    echo 'Candidato: ' . h($item['candidato']);
                                }
                                ?>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   class="item-checkbox-<?= h($sectionKey) ?>"
                                   name="item_visible[<?= h($item['id']) ?>]"
                                   <?= !isItemHidden($item['id'], $visibility) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Campos ocultos para enviar as seções e itens ocultos -->
        <div id="hidden-fields"></div>
    </form>

</main>

<footer class="cv-footer">
    <div class="container">
        <p>niceLattes - Painel Administrativo</p>
    </div>
</footer>

<script>
// Toggle seção expandida/recolhida
function toggleSection(sectionKey) {
    const content = document.getElementById('section-' + sectionKey);
    if (content) {
        content.classList.toggle('active');
    }
}

// Toggle visibilidade da seção
function toggleSectionVisibility(sectionKey, isVisible) {
    const content = document.getElementById('section-' + sectionKey);
    if (content) {
        content.style.opacity = isVisible ? '1' : '0.5';
    }
}

// Antes de enviar o formulário, prepara os campos ocultos
document.getElementById('visibility-form').addEventListener('submit', function(e) {
    const hiddenFields = document.getElementById('hidden-fields');
    hiddenFields.innerHTML = '';

    // Coleta seções ocultas
    document.querySelectorAll('input[name^="section_visible"]').forEach(function(checkbox) {
        if (!checkbox.checked) {
            const sectionKey = checkbox.name.match(/\[(.*?)\]/)[1];
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hidden_sections[]';
            input.value = sectionKey;
            hiddenFields.appendChild(input);
        }
    });

    // Coleta itens ocultos
    document.querySelectorAll('input[name^="item_visible"]').forEach(function(checkbox) {
        if (!checkbox.checked) {
            const itemId = checkbox.name.match(/\[(.*?)\]/)[1];
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hidden_items[]';
            input.value = itemId;
            hiddenFields.appendChild(input);
        }
    });
});

// Expande a primeira seção por padrão
document.addEventListener('DOMContentLoaded', function() {
    const firstSection = document.querySelector('.section-content');
    if (firstSection) {
        firstSection.classList.add('active');
    }

    // Aplica opacidade inicial para seções ocultas
    document.querySelectorAll('input[name^="section_visible"]').forEach(function(checkbox) {
        const sectionKey = checkbox.name.match(/\[(.*?)\]/)[1];
        toggleSectionVisibility(sectionKey, checkbox.checked);
    });
});
</script>

</body>
</html>
