<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Gerenciar Perguntas';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado


// Definir constantes
if (!defined('DIFICULDADES')) {
    define('DIFICULDADES', [
        'facil' => 'Fácil',
        'medio' => 'Médio',
        'dificil' => 'Difícil'
    ]);
}

// Configurações de paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros
$categoria = isset($_GET['categoria']) ? limparDados($_GET['categoria']) : '';
$dificuldade = isset($_GET['dificuldade']) ? limparDados($_GET['dificuldade']) : '';
$busca = isset($_GET['busca']) ? limparDados($_GET['busca']) : '';

// Construir query base
$query_base = "
    SELECT 
        p.*,
        GROUP_CONCAT(
            CONCAT_WS('::',
                r.id,
                r.resposta,
                r.correta
            ) SEPARATOR '||'
        ) as respostas,
        COUNT(DISTINCT pr.id) as total_usos,
        ROUND(
            SUM(CASE WHEN pr.correta = 1 THEN 1 ELSE 0 END) / 
            COUNT(DISTINCT pr.id) * 100,
            1
        ) as taxa_acerto
    FROM perguntas p
    LEFT JOIN respostas r ON p.id = r.pergunta_id
    LEFT JOIN partidas_respostas pr ON r.id = pr.resposta_id
";

$where = [];
$params = [];

// Aplicar filtros
if ($categoria) {
    $where[] = "p.categoria = :categoria";
    $params[':categoria'] = $categoria;
}

if ($dificuldade) {
    $where[] = "p.dificuldade = :dificuldade";
    $params[':dificuldade'] = $dificuldade;
}

if ($busca) {
    $where[] = "(p.pergunta LIKE :busca OR r.resposta LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

// Montar cláusula WHERE
if (!empty($where)) {
    $query_base .= " WHERE " . implode(" AND ", $where);
}

$query_base .= " GROUP BY p.id";

// Contar total de registros
try {
    $query_count = "SELECT COUNT(DISTINCT p.id) as total FROM perguntas p";
    if (!empty($where)) {
        $query_count .= " LEFT JOIN respostas r ON p.id = r.pergunta_id WHERE " . implode(" AND ", $where);
    }
    
    $stmt = $conexao->prepare($query_count);
    $stmt->execute($params);
    $total_registros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao contar registros: ' . $e->getMessage());
    $total_registros = 0;
}

// Calcular total de páginas
$total_paginas = ceil($total_registros / $itens_por_pagina);
$pagina_atual = min($pagina_atual, $total_paginas);

// Buscar perguntas com paginação
try {
    $query_completa = $query_base . " 
        ORDER BY p.id DESC 
        LIMIT :offset, :limit
    ";
    
    $stmt = $conexao->prepare($query_completa);
    
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar perguntas: ' . $e->getMessage());
    $perguntas = [];
}

// Buscar categorias disponíveis
try {
    $stmt = $conexao->query("
        SELECT DISTINCT categoria 
        FROM perguntas 
        WHERE categoria IS NOT NULL
        ORDER BY categoria
    ");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar categorias: ' . $e->getMessage());
    $categorias = [];
}

// Processar respostas para exibição
foreach ($perguntas as &$pergunta) {
    if (!empty($pergunta['respostas'])) {
        $respostas = array_map(function($resp) {
            list($id, $texto, $correta) = explode('::', $resp);
            return [
                'id' => $id,
                'texto' => $texto,
                'correta' => $correta
            ];
        }, explode('||', $pergunta['respostas']));
        
        $pergunta['total_respostas'] = count($respostas);
        $pergunta['respostas_formatadas'] = $respostas;
    } else {
        $pergunta['total_respostas'] = 0;
        $pergunta['respostas_formatadas'] = [];
    }
}
unset($pergunta);

// Incluir cabeçalho
require_once '../includes/header.php';
?>

<!-- Início da estrutura da página -->
<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-profile">
                <div>
                    <small class="text-muted">Painel de Controle</small>
                    <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador'); ?></h5>  
                </div>
            </div>
        </div>
        
        <nav class="admin-menu">
            <a href="<?php echo BASE_URL; ?>index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>perguntas.php" class="menu-item active">
                <i class="fas fa-question-circle"></i> Perguntas
            </a>
            <a href="<?php echo BASE_URL; ?>jogadores.php" class="menu-item">
                <i class="fas fa-users"></i> Jogadores
            </a>
            <a href="<?php echo BASE_URL; ?>configuracoes.php" class="menu-item">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <a href="<?php echo BASE_URL; ?>logout.php" class="menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="admin-content">
        <div class="container-fluid">
            <!-- Cabeçalho da Página -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo $titulo_pagina; ?></h1>
                    <small class="text-muted">
                        Total: <?php echo number_format($total_registros, 0, ',', '.'); ?> perguntas
                    </small>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPerguntaModal">
                    <i class="fas fa-plus me-1"></i> Nova Pergunta
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control" name="busca" 
                                       placeholder="Buscar pergunta..." 
                                       value="<?php echo htmlspecialchars($busca); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $categoria === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="dificuldade">
                                <option value="">Todas as dificuldades</option>
                                <?php foreach(DIFICULDADES as $key => $value): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo $dificuldade === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Perguntas -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if(empty($perguntas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Nenhuma pergunta encontrada</h5>
                            <p class="text-muted">
                                <?php if($busca || $categoria || $dificuldade): ?>
                                    Tente ajustar os filtros de busca
                                <?php else: ?>
                                    Comece adicionando uma nova pergunta
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 35%">Pergunta</th>
                                    <th style="width: 15%">Categoria</th>
                                    <th style="width: 10%">Dificuldade</th>
                                    <th style="width: 15%">Estatísticas</th>
                                    <th style="width: 10%">Data</th>
                                    <th style="width: 10%">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($perguntas as $pergunta): ?>
                                <tr>
                                    <td><?php echo $pergunta['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="pergunta-texto">
                                                <?php 
                                                $texto_pergunta = htmlspecialchars($pergunta['pergunta']);
                                                echo strlen($texto_pergunta) > 100 
                                                    ? substr($texto_pergunta, 0, 100) . '...' 
                                                    : $texto_pergunta;
                                                ?>
                                            </div>
                                            <?php if($pergunta['pontos'] > 10): ?>
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <?php echo $pergunta['pontos']; ?> pts
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars(ucfirst($pergunta['categoria'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $dificuldade_class = [
                                            'facil' => 'success',
                                            'medio' => 'warning',
                                            'dificil' => 'danger'
                                        ];
                                        $classe = $dificuldade_class[$pergunta['dificuldade']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $classe; ?>">
                                            <?php echo DIFICULDADES[$pergunta['dificuldade']] ?? 'Desconhecida'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="mb-1">
                                                <i class="fas fa-check-circle text-success"></i>
                                                Taxa de acerto: 
                                                <strong><?php echo number_format($pergunta['taxa_acerto'] ?? 0, 1); ?>%</strong>
                                            </div>
                                            <div>
                                                <i class="fas fa-users text-primary"></i>
                                                Total de usos: 
                                                <strong><?php echo number_format($pergunta['total_usos'] ?? 0); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-muted">
                                            <?php echo date('d/m/Y', strtotime($pergunta['data_criacao'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary btn-visualizar"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning btn-editar"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger btn-excluir"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if($total_paginas > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Mostrando <?php echo $offset + 1; ?> - 
                            <?php echo min($offset + $itens_por_pagina, $total_registros); ?> 
                            de <?php echo $total_registros; ?> registros
                        </div>
                        <nav aria-label="Navegação">
                            <ul class="pagination pagination-sm mb-0">
                                <?php if($pagina_atual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=1<?php 
                                        echo $categoria ? '&categoria='.urlencode($categoria) : ''; 
                                        echo $dificuldade ? '&dificuldade='.urlencode($dificuldade) : ''; 
                                        echo $busca ? '&busca='.urlencode($busca) : ''; 
                                    ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);

                                if($inicio > 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif;

                                for($i = $inicio; $i <= $fim; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?><?php 
                                            echo $categoria ? '&categoria='.urlencode($categoria) : ''; 
                                            echo $dificuldade ? '&dificuldade='.urlencode($dificuldade) : ''; 
                                            echo $busca ? '&busca='.urlencode($busca) : ''; 
                                        ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor;

                                if($fim < $total_paginas): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif;

                                if($pagina_atual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php 
                                            echo $categoria ? '&categoria='.urlencode($categoria) : ''; 
                                            echo $dificuldade ? '&dificuldade='.urlencode($dificuldade) : ''; 
                                            echo $busca ? '&busca='.urlencode($busca) : ''; 
                                        ?>">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<!-- Modal Adicionar Pergunta -->
<!-- Modal Adicionar Pergunta - Remover seção de feedback -->
<div class="modal fade" id="addPerguntaModal" tabindex="-1" aria-labelledby="addPerguntaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAddPergunta" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="addPerguntaModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>
                        Nova Pergunta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <!-- Pergunta -->
                    <div class="mb-4">
                        <label class="form-label">
                            Pergunta
                            <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                name="pergunta" 
                                rows="3" 
                                required
                                minlength="10"
                                placeholder="Digite a pergunta..."></textarea>
                        <div class="invalid-feedback">
                            A pergunta deve ter no mínimo 10 caracteres.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categoria -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Categoria
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="nova">+ Nova Categoria</option>
                                </select>
                                <div class="invalid-feedback">
                                    Selecione uma categoria.
                                </div>
                            </div>
                        </div>

                        <!-- Nova Categoria (inicialmente oculto) -->
                        <div class="col-md-4" id="novaCategoriaGroup" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">
                                    Nova Categoria
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="nova_categoria"
                                       placeholder="Nome da nova categoria">
                                <div class="invalid-feedback">
                                    Digite o nome da nova categoria.
                                </div>
                            </div>
                        </div>

                        <!-- Dificuldade -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Dificuldade
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="dificuldade" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach(DIFICULDADES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Selecione a dificuldade.
                                </div>
                            </div>
                        </div>

                        <!-- Pontos -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Pontos
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="pontos" 
                                       value="10" 
                                       min="1" 
                                       max="100">
                                <div class="form-text">
                                    Valor padrão: 10 pontos
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opções de Resposta -->
                    <div class="mb-4">
                        <label class="form-label">
                            Opções de Resposta
                            <span class="text-danger">*</span>
                        </label>
                        <div id="opcoesContainer">
                            <!-- Opções serão adicionadas via JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="btnAddOpcao">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                        <div class="form-text">
                            Selecione o radio button para indicar a resposta correta
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Salvar Pergunta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Pergunta -->
<!-- Modal Visualizar Pergunta -->
<div class="modal fade" id="viewPerguntaModal" tabindex="-1" aria-labelledby="viewPerguntaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPerguntaModalLabel">
                    <i class="fas fa-eye me-2"></i>
                    Detalhes da Pergunta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="view-pergunta-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Fechar
                </button>
                <button type="button" class="btn btn-warning btn-editar-view">
                    <i class="fas fa-edit me-1"></i>
                    Editar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Editar Pergunta -->
<div class="modal fade" id="editPerguntaModal" tabindex="-1" aria-labelledby="editPerguntaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditPergunta" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPerguntaModalLabel">
                        <i class="fas fa-edit me-2"></i>
                        Editar Pergunta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <!-- Pergunta -->
                    <div class="mb-4">
                        <label class="form-label">
                            Pergunta
                            <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                name="pergunta" 
                                rows="3" 
                                required
                                minlength="10"></textarea>
                        <div class="invalid-feedback">
                            A pergunta deve ter no mínimo 10 caracteres.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categoria -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Categoria
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Selecione uma categoria.
                                </div>
                            </div>
                        </div>

                        <!-- Dificuldade -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    Dificuldade
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="dificuldade" required>
                                    <?php foreach(DIFICULDADES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Selecione a dificuldade.
                                </div>
                            </div>
                        </div>

                        <!-- Pontos -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Pontos</label>
                                <input type="number" 
                                       class="form-control" 
                                       name="pontos" 
                                       min="1" 
                                       max="100">
                                <div class="form-text">
                                    Entre 1 e 100 pontos
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opções de Resposta -->
                    <div class="mb-4">
                        <label class="form-label">
                            Opções de Resposta
                            <span class="text-danger">*</span>
                        </label>
                        <div id="opcoesContainerEdit">
                            <!-- Opções serão preenchidas via JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="btnAddOpcaoEdit">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                        <div class="form-text">
                            Selecione o radio button para indicar a resposta correta
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dependências JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
<script src="<?php echo BASE_URL; ?>../assets/js/perguntas.js"></script>

<!-- Scripts específicos e inicializações -->
<script>
// Configurações globais do SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
});

// Inicialização de tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>