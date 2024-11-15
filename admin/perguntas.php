<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Gerenciar Perguntas';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
verificarLogin();

// Configurações de paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros
$categoria = isset($_GET['categoria']) ? limparDados($_GET['categoria']) : '';
$dificuldade = isset($_GET['dificuldade']) ? limparDados($_GET['dificuldade']) : '';
$busca = isset($_GET['busca']) ? limparDados($_GET['busca']) : '';

// Construir query base
$query = "SELECT p.*, 
          GROUP_CONCAT(r.id,'::',r.resposta,'::',r.correta SEPARATOR '||') as respostas
          FROM perguntas p
          LEFT JOIN respostas r ON p.id = r.pergunta_id";

$where = [];
$params = [];

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

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY p.id";

// Contar total de registros
try {
    $stmt = $conexao->prepare("SELECT COUNT(DISTINCT p.id) as total FROM perguntas p LEFT JOIN respostas r ON p.id = r.pergunta_id" . 
        (!empty($where) ? " WHERE " . implode(" AND ", $where) : ""));
    $stmt->execute($params);
    $total_registros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao contar registros: ' . $e->getMessage());
    $total_registros = 0;
}

// Calcular total de páginas
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar perguntas com paginação
try {
    $query .= " ORDER BY p.id DESC LIMIT :offset, :limit";
    $stmt = $conexao->prepare($query);
    
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
    $stmt = $conexao->query("SELECT DISTINCT categoria FROM perguntas ORDER BY categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar categorias: ' . $e->getMessage());
    $categorias = [];
}

require_once '../includes/header.php';
?>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <img src="<?php echo BASE_URL; ?>assets/img/admin-avatar.png" alt="Admin">
            <h5><?php echo $_SESSION['admin_nome']; ?></h5>
            <small class="text-white-50">Administrador</small>
        </div>
        
        <nav class="admin-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="perguntas.php" class="menu-item active">
                <i class="fas fa-question-circle"></i> Perguntas
            </a>
            <a href="jogadores.php" class="menu-item">
                <i class="fas fa-users"></i> Jogadores
            </a>
            <a href="configuracoes.php" class="menu-item">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <a href="logout.php" class="menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="admin-content">
        <div class="container-fluid">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Gerenciar Perguntas</h1>
                <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addPerguntaModal">
                    <i class="fas fa-plus"></i> Nova Pergunta
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="busca" 
                                   placeholder="Buscar pergunta..." value="<?php echo $busca; ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $categoria == $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="dificuldade">
                                <option value="">Todas as dificuldades</option>
                                <?php foreach(DIFICULDADES as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $dificuldade == $key ? 'selected' : ''; ?>>
                                    <?php echo $value; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-admin-primary w-100">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Tabela de Perguntas -->
            <div class="card">
                <div class="card-body">
                    <?php if(empty($perguntas)): ?>
                    <div class="alert alert-admin alert-admin-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Nenhuma pergunta encontrada com os filtros selecionados.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="35%">Pergunta</th>
                                    <th width="15%">Categoria</th>
                                    <th width="10%">Dificuldade</th>
                                    <th width="15%">Respostas</th>
                                    <th width="10%">Data</th>
                                    <th width="10%">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($perguntas as $pergunta): ?>
                                <tr data-id="<?php echo $pergunta['id']; ?>">
                                    <td><?php echo $pergunta['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($pergunta['pergunta']); ?>
                                        <?php if($pergunta['pontos'] > 10): ?>
                                        <span class="badge badge-admin badge-warning ms-2">
                                            +<?php echo $pergunta['pontos']; ?> pontos
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst($pergunta['categoria']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $dificuldade_class = [
                                            'facil' => 'success',
                                            'medio' => 'warning',
                                            'dificil' => 'danger'
                                        ];
                                        $classe = $dificuldade_class[$pergunta['dificuldade']];
                                        ?>
                                        <span class="badge bg-<?php echo $classe; ?>">
                                            <?php echo DIFICULDADES[$pergunta['dificuldade']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($pergunta['respostas'])) {
                                            $respostas = array_map(function($resp) {
                                                list($id, $texto, $correta) = explode('::', $resp);
                                                return [
                                                    'id' => $id,
                                                    'texto' => $texto,
                                                    'correta' => $correta
                                                ];
                                            }, explode('||', $pergunta['respostas']));
                                            
                                            $total_respostas = count($respostas);
                                            $resposta_correta = array_filter($respostas, function($r) {
                                                return $r['correta'] == 1;
                                            });
                                            
                                            echo "<span class='text-muted'>{$total_respostas} opções</span>";
                                            echo "<br><small class='text-success'>1 correta</small>";
                                        } else {
                                            echo "<span class='text-danger'>Sem respostas</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($pergunta['data_criacao'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-admin-primary btn-visualizar"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Visualizar Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-admin-warning btn-editar"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Editar Pergunta">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-admin-danger btn-excluir"
                                                    data-id="<?php echo $pergunta['id']; ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Excluir Pergunta">
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
                        <div class="text-muted">
                            Mostrando <?php echo $offset + 1; ?> - 
                            <?php echo min($offset + $itens_por_pagina, $total_registros); ?> 
                            de <?php echo $total_registros; ?> registros
                        </div>
                        <nav aria-label="Navegação das páginas">
                            <ul class="pagination mb-0">
                                <?php if($pagina_atual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=1<?php 
                                        echo $categoria ? '&categoria='.$categoria : ''; 
                                        echo $dificuldade ? '&dificuldade='.$dificuldade : ''; 
                                        echo $busca ? '&busca='.$busca : ''; 
                                    ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?><?php 
                                        echo $categoria ? '&categoria='.$categoria : ''; 
                                        echo $dificuldade ? '&dificuldade='.$dificuldade : ''; 
                                        echo $busca ? '&busca='.$busca : ''; 
                                    ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);
                                
                                if($inicio > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                for($i = $inicio; $i <= $fim; $i++):
                                ?>
                                <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php 
                                        echo $categoria ? '&categoria='.$categoria : ''; 
                                        echo $dificuldade ? '&dificuldade='.$dificuldade : ''; 
                                        echo $busca ? '&busca='.$busca : ''; 
                                    ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>

                                <?php if($fim < $total_paginas): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>

                                <?php if($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?><?php 
                                        echo $categoria ? '&categoria='.$categoria : ''; 
                                        echo $dificuldade ? '&dificuldade='.$dificuldade : ''; 
                                        echo $busca ? '&busca='.$busca : ''; 
                                    ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php 
                                        echo $categoria ? '&categoria='.$categoria : ''; 
                                        echo $dificuldade ? '&dificuldade='.$dificuldade : ''; 
                                        echo $busca ? '&busca='.$busca : ''; 
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
<div class="modal fade modal-admin" id="addPerguntaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAddPergunta" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Nova Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Pergunta -->
                    <div class="mb-4">
                        <label class="form-label">Pergunta <span class="text-danger">*</span></label>
                        <textarea class="form-control editor-admin" name="pergunta" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            Por favor, insira a pergunta.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categoria -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Categoria <span class="text-danger">*</span></label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat; ?>">
                                        <?php echo ucfirst($cat); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="nova">+ Nova Categoria</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione uma categoria.
                                </div>
                            </div>
                        </div>

                        <!-- Nova Categoria (inicialmente oculto) -->
                        <div class="col-md-4" id="novaCategoriaGroup" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Nova Categoria <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nova_categoria">
                                <div class="invalid-feedback">
                                    Por favor, insira o nome da nova categoria.
                                </div>
                            </div>
                        </div>

                        <!-- Dificuldade -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Dificuldade <span class="text-danger">*</span></label>
                                <select class="form-select" name="dificuldade" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach(DIFICULDADES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione a dificuldade.
                                </div>
                            </div>
                        </div>

                        <!-- Pontos -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Pontos</label>
                                <input type="number" class="form-control" name="pontos" value="10" min="1" max="100">
                                <div class="form-text">
                                    Valor padrão: 10 pontos
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opções de Resposta -->
                    <div class="mb-4">
                        <label class="form-label">Opções de Resposta <span class="text-danger">*</span></label>
                        <div id="opcoesContainer">
                            <!-- Opções serão adicionadas via JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="btnAddOpcao">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                        <div class="form-text">
                            Marque o radio button para indicar a resposta correta
                        </div>
                    </div>

                    <!-- Feedback -->
                    <div class="mb-3">
                        <label class="form-label">Feedback (opcional)</label>
                        <textarea class="form-control" name="feedback" rows="2" 
                                placeholder="Explicação que será mostrada após o jogador responder..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-primary">
                        <i class="fas fa-save"></i> Salvar Pergunta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Pergunta -->
<div class="modal fade modal-admin" id="viewPerguntaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="view-pergunta-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-admin-primary btn-editar-view">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Pergunta -->
<div class="modal fade modal-admin" id="editPerguntaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditPergunta" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Pergunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Mesmo conteúdo do modal de adicionar, mas com dados preenchidos via JavaScript -->
                    <div class="mb-4">
                        <label class="form-label">Pergunta <span class="text-danger">*</span></label>
                        <textarea class="form-control editor-admin" name="pergunta" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            Por favor, insira a pergunta.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Categoria -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Categoria <span class="text-danger">*</span></label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat; ?>">
                                        <?php echo ucfirst($cat); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Dificuldade -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Dificuldade <span class="text-danger">*</span></label>
                                <select class="form-select" name="dificuldade" required>
                                    <?php foreach(DIFICULDADES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Pontos -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Pontos</label>
                                <input type="number" class="form-control" name="pontos" min="1" max="100">
                            </div>
                        </div>
                    </div>

                    <!-- Opções de Resposta -->
                    <div class="mb-4">
                        <label class="form-label">Opções de Resposta <span class="text-danger">*</span></label>
                        <div id="opcoesContainerEdit">
                            <!-- Opções serão preenchidas via JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="btnAddOpcaoEdit">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div class="mb-3">
                        <label class="form-label">Feedback (opcional)</label>
                        <textarea class="form-control" name="feedback" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts específicos da página -->
<?php 
$scripts_pagina = ['assets/js/perguntas.js'];
require_once '../includes/footer.php'; 
?>