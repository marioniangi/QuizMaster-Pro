<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Gerenciar Jogadores';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado


// Configurações de paginação
$itens_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtros
$busca = isset($_GET['busca']) ? filter_var($_GET['busca'], FILTER_SANITIZE_STRING) : '';
$status = isset($_GET['status']) ? filter_var($_GET['status'], FILTER_SANITIZE_STRING) : '';
$ordem = isset($_GET['ordem']) ? filter_var($_GET['ordem'], FILTER_SANITIZE_STRING) : 'pontuacao';

// Construir query base
$where = [];
$params = [];

if ($busca) {
    $where[] = "(j.nome LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

if ($status) {
    $where[] = "j.status = :status";
    $params[':status'] = $status;
}

// Ordenação
$order_by = match($ordem) {
    'nome' => 'j.nome ASC',
    'data' => 'j.data_cadastro DESC',
    'partidas' => 'j.jogos_completados DESC',
    default => 'j.pontuacao_total DESC'
};

try {
    // Contar total de registros
    $sql_count = "
        SELECT COUNT(*) 
        FROM jogadores j 
        " . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
    
    $stmt = $conexao->prepare($sql_count);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $itens_por_pagina);

} catch (PDOException $e) {
    registrar_log('erro', 'Erro ao contar jogadores: ' . $e->getMessage());
    $total_registros = 0;
    $total_paginas = 0;
}
// Incluir cabeçalho
require_once '../includes/header.php';
?>


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
            <a href="index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="perguntas.php" class="menu-item">
                <i class="fas fa-question-circle"></i> Perguntas
            </a>
            <a href="jogadores.php" class="menu-item active">
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
                <h1 class="h3">Gerenciar Jogadores</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportarModal">
                    <i class="fas fa-download me-2"></i> Exportar Dados
                </button>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <?php
                try {
                    // Buscar estatísticas
                    $stmt = $conexao->query("
                        SELECT 
                            COUNT(*) as total_jogadores,
                            COUNT(CASE WHEN ultima_partida >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as jogadores_ativos,
                            ROUND(AVG(pontuacao_total)) as media_pontos,
                            MAX(pontuacao_total) as maior_pontuacao,
                            COUNT(CASE WHEN jogos_completados = 0 THEN 1 END) as jogadores_sem_partida
                        FROM jogadores
                    ");
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    registrar_log('erro', 'Erro ao buscar estatísticas: ' . $e->getMessage());
                    $stats = [
                        'total_jogadores' => 0,
                        'jogadores_ativos' => 0,
                        'media_pontos' => 0,
                        'maior_pontuacao' => 0,
                        'jogadores_sem_partida' => 0
                    ];
                }
                ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Total de Jogadores</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_jogadores']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-check fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Jogadores Ativos (30d)</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['jogadores_ativos']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-trophy fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Maior Pontuação</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['maior_pontuacao']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-line fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Média de Pontos</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['media_pontos']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="busca" 
                                       value="<?php echo htmlspecialchars($busca); ?>" 
                                       placeholder="Buscar jogador...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Todos os Status</option>
                                <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                                <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="ordem">
                                <option value="pontuacao" <?php echo $ordem === 'pontuacao' ? 'selected' : ''; ?>>Maior Pontuação</option>
                                <option value="nome" <?php echo $ordem === 'nome' ? 'selected' : ''; ?>>Nome</option>
                                <option value="data" <?php echo $ordem === 'data' ? 'selected' : ''; ?>>Data de Cadastro</option>
                                <option value="partidas" <?php echo $ordem === 'partidas' ? 'selected' : ''; ?>>Total de Partidas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="jogadores.php" class="btn btn-secondary w-100">
                                <i class="fas fa-undo me-2"></i>Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Jogadores -->
            <div class="card">
                <div class="card-body">
                    <?php
                    // Buscar jogadores
                    try {
                        $sql = "
                            SELECT 
                                j.*,
                                COUNT(DISTINCT p.id) as total_partidas,
                                SUM(p.acertos) as total_acertos,
                                SUM(p.total_perguntas) as total_perguntas,
                                MAX(p.data_partida) as ultima_partida
                            FROM jogadores j
                            LEFT JOIN partidas p ON j.id = p.jogador_id
                            " . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "") . "
                            GROUP BY j.id
                            ORDER BY {$order_by}
                            LIMIT :offset, :limit
                        ";

                        $stmt = $conexao->prepare($sql);
                        foreach ($params as $key => $value) {
                            $stmt->bindValue($key, $value);
                        }
                        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                        $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                        $stmt->execute();
                        $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if(empty($jogadores)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Nenhum jogador encontrado com os filtros selecionados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">Nome</th>
                                            <th width="15%" class="text-end">Pontuação</th>
                                            <th width="10%" class="text-center">Partidas</th>
                                            <th width="10%" class="text-center">% Acertos</th>
                                            <th width="15%" class="text-center">Status</th>
                                            <th width="15%">Último Acesso</th>
                                            <th width="10%" class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($jogadores as $jogador):
                                        // Calcular porcentagem de acertos
                                        $porcentagem_acertos = $jogador['total_perguntas'] > 0 
                                            ? ($jogador['total_acertos'] / $jogador['total_perguntas']) * 100 
                                            : 0;
                                        
                                        // Determinar status
                                        $status = 'inativo';
                                        $status_class = 'danger';
                                        if ($jogador['ultima_partida']) {
                                            $dias_inativo = (time() - strtotime($jogador['ultima_partida'])) / (60 * 60 * 24);
                                            if ($dias_inativo <= 30) {
                                                $status = 'ativo';
                                                $status_class = 'success';
                                            } elseif ($dias_inativo <= 90) {
                                                $status = 'ocasional';
                                                $status_class = 'warning';
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $jogador['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($jogador['nome']); ?>
                                                <?php if($jogador['pontuacao_total'] >= 1000): ?>
                                                    <span class="badge bg-warning ms-1" title="Jogador Veterano">
                                                        <i class="fas fa-star"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <strong><?php echo number_format($jogador['pontuacao_total']); ?></strong>
                                                <?php if($jogador['melhor_pontuacao']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Melhor: <?php echo number_format($jogador['melhor_pontuacao']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo number_format($jogador['total_partidas']); ?>
                                                <?php if($jogador['jogos_completados']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Completas: <?php echo number_format($jogador['jogos_completados']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $cor_progresso = 'bg-danger';
                                                if ($porcentagem_acertos >= 70) $cor_progresso = 'bg-success';
                                                elseif ($porcentagem_acertos >= 50) $cor_progresso = 'bg-warning';
                                                ?>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar <?php echo $cor_progresso; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $porcentagem_acertos; ?>%">
                                                    </div>
                                                </div>
                                                <small><?php echo number_format($porcentagem_acertos, 1); ?>%</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <?php if($status !== 'ativo'): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo ceil($dias_inativo); ?> dias inativo
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($jogador['ultima_partida']): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($jogador['ultima_partida'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca jogou</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-primary"
                                                            onclick="verDetalhes(<?php echo $jogador['id']; ?>)"
                                                            title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if($status === 'inativo'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger"
                                                            onclick="confirmarRemocao(<?php echo $jogador['id']; ?>)"
                                                            title="Remover Jogador">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_paginas > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    Mostrando <?php echo $offset + 1; ?> - 
                                    <?php echo min($offset + $itens_por_pagina, $total_registros); ?> 
                                    de <?php echo $total_registros; ?> jogadores
                                </div>
                                <nav>
                                    <ul class="pagination mb-0">
                                        <?php if ($pagina_atual > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=1&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>&ordem=<?php echo urlencode($ordem); ?>">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php
                                        $inicio = max(1, $pagina_atual - 2);
                                        $fim = min($total_paginas, $pagina_atual + 2);
                                        
                                        for ($i = $inicio; $i <= $fim; $i++):
                                        ?>
                                        <li class="page-item <?php echo $i === $pagina_atual ? 'active' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>&ordem=<?php echo urlencode($ordem); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($pagina_atual < $total_paginas): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>&ordem=<?php echo urlencode($ordem); ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php } catch (PDOException $e) {
                        registrar_log('erro', 'Erro ao listar jogadores: ' . $e->getMessage());
                        ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erro ao carregar lista de jogadores. Por favor, tente novamente mais tarde.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </main>
</div>
<!-- Modal de Detalhes do Jogador -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Jogador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Conteúdo será preenchido via JavaScript -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exportação -->
<div class="modal fade" id="exportarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Dados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formExportar">
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="formato" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <select class="form-select" name="periodo" required>
                            <option value="todos">Todos os tempos</option>
                            <option value="mes">Último mês</option>
                            <option value="semana">Última semana</option>
                            <option value="hoje">Hoje</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="incluir_partidas" id="incluirPartidas">
                            <label class="form-check-label" for="incluirPartidas">
                                Incluir detalhes das partidas
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="exportarDados()">
                    <i class="fas fa-download me-2"></i>Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Necessários -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Scripts da Aplicação -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modais
    window.detalhesModal = new bootstrap.Modal(document.getElementById('detalhesModal'), {
        backdrop: 'static'
    });
    window.exportarModal = new bootstrap.Modal(document.getElementById('exportarModal'));

    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

async function verDetalhes(id) {
    try {
        const modalBody = document.querySelector('#detalhesModal .modal-body');
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        `;
        
        detalhesModal.show();
        
        const response = await fetch(`buscar_jogador.php?id=${id}`);
        if (!response.ok) throw new Error('Erro ao buscar dados do jogador');
        
        const data = await response.json();
        if (!data.sucesso) throw new Error(data.mensagem);

        const jogador = data.jogador;
        
        modalBody.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>${jogador.nome}</h4>
                    <p class="text-muted mb-2">
                        Cadastrado em: ${new Date(jogador.data_cadastro).toLocaleDateString('pt-BR')}
                    </p>
                    <p class="mb-0">
                        Status: <span class="badge bg-${jogador.status_class}">${jogador.status}</span>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <h3 class="mb-0">${jogador.pontuacao_total.toLocaleString()} pontos</h3>
                    <small class="text-muted">
                        Melhor pontuação: ${jogador.melhor_pontuacao.toLocaleString()}
                    </small>
                </div>
            </div>

            <!-- Estatísticas por Modo -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Modo Clássico</h6>
                            <h4>${jogador.stats.classico.partidas} partidas</h4>
                            <small class="text-muted">
                                Média: ${jogador.stats.classico.media_pontos} pontos
                            </small>
                            <canvas id="classicoChart" class="stats-mini"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Contra o Tempo</h6>
                            <h4>${jogador.stats.tempo.partidas} partidas</h4>
                            <small class="text-muted">
                                Média: ${jogador.stats.tempo.media_pontos} pontos
                            </small>
                            <canvas id="tempoChart" class="stats-mini"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Modo Desafio</h6>
                            <h4>${jogador.stats.desafio.partidas} partidas</h4>
                            <small class="text-muted">
                                Média: ${jogador.stats.desafio.media_pontos} pontos
                            </small>
                            <canvas id="desafioChart" class="stats-mini"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Partidas -->
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Modo</th>
                            <th class="text-end">Pontuação</th>
                            <th class="text-center">Acertos</th>
                            <th class="text-end">Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${jogador.ultimas_partidas.map(p => `
                            <tr>
                                <td>${new Date(p.data_partida).toLocaleString('pt-BR')}</td>
                                <td>
                                    <span class="badge bg-${p.modo_class}">${p.modo}</span>
                                </td>
                                <td class="text-end">${p.pontuacao.toLocaleString()}</td>
                                <td class="text-center">${p.acertos}/${p.total_perguntas}</td>
                                <td class="text-end">${formatarTempo(p.tempo_total)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        setTimeout(() => {
            inicializarGraficos(jogador);
        }, 100);

    } catch (erro) {
        console.error('Erro:', erro);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao carregar detalhes do jogador.'
        });
    }
}

function confirmarRemocao(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, remover!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            removerJogador(id);
        }
    });
}

async function removerJogador(id) {
    try {
        const response = await fetch('remover_jogador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        if (!data.sucesso) {
            throw new Error(data.mensagem);
        }

        await Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: 'Jogador removido com sucesso.',
            timer: 1500,
            showConfirmButton: false
        });

        window.location.reload();
        
    } catch (erro) {
        console.error('Erro:', erro);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao remover jogador.'
        });
    }
}

async function exportarDados() {
    const form = document.getElementById('formExportar');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('exportar_jogadores.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) throw new Error('Erro ao exportar dados');

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `jogadores_${formData.get('formato')}_${new Date().toISOString().slice(0,10)}.${formData.get('formato')}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        exportarModal.hide();
        
    } catch (erro) {
        console.error('Erro:', erro);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao exportar dados.'
        });
    }
}

function formatarTempo(segundos) {
    if (!segundos) return '--:--';
    const minutos = Math.floor(segundos / 60);
    const segs = segundos % 60;
    return `${minutos}:${segs.toString().padStart(2, '0')}`;
}

function inicializarGraficos(jogador) {
    ['classico', 'tempo', 'desafio'].forEach(modo => {
        const chartElement = document.getElementById(`${modo}Chart`);
        if (chartElement && jogador.stats && jogador.stats[modo]) {
            new Chart(chartElement, {
                type: 'bar',
                data: {
                    labels: jogador.stats[modo].ultimos.map(() => ''),
                    datasets: [{
                        data: jogador.stats[modo].ultimos,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    }
                }
            });
        }
    });
}
</script>
</body>
</html>