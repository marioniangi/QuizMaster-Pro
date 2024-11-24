<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Painel Administrativo';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado/verificarLogin();

// Buscar estatísticas gerais
try {
    // Total de perguntas
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM perguntas");
    $total_perguntas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de jogadores
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM jogadores");
    $total_jogadores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de partidas
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM partidas");
    $total_partidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Média de pontuação
    $stmt = $conexao->query("SELECT AVG(pontuacao) as media FROM partidas");
    $media_pontuacao = round($stmt->fetch(PDO::FETCH_ASSOC)['media'], 1);

    // Últimas partidas
    $stmt = $conexao->query("
        SELECT p.*, j.nome as jogador_nome 
        FROM partidas p 
        JOIN jogadores j ON p.jogador_id = j.id 
        ORDER BY p.data_partida DESC 
        LIMIT 5
    ");
    $ultimas_partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas por categoria
    $stmt = $conexao->query("
        SELECT categoria, COUNT(*) as total 
        FROM perguntas 
        GROUP BY categoria 
        ORDER BY total DESC
    ");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar estatísticas: ' . $e->getMessage());
}

require_once '../includes/header.php';
?>

<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Painel Administrativo';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
//verificarLogin();

// Buscar estatísticas gerais
try {
    // Total de perguntas e distribuição por dificuldade
    $stmt = $conexao->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN dificuldade = 'facil' THEN 1 ELSE 0 END) as total_facil,
            SUM(CASE WHEN dificuldade = 'medio' THEN 1 ELSE 0 END) as total_medio,
            SUM(CASE WHEN dificuldade = 'dificil' THEN 1 ELSE 0 END) as total_dificil
        FROM perguntas
    ");
    $stats_perguntas = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_perguntas = $stats_perguntas['total'];

    // Total de jogadores e estatísticas
    $stmt = $conexao->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
            COUNT(CASE WHEN data_cadastro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as novos_hoje,
            AVG(pontuacao_total) as media_pontos,
            MAX(melhor_pontuacao) as melhor_pontuacao
        FROM jogadores
    ");
    $stats_jogadores = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_jogadores = $stats_jogadores['total'];

    // Estatísticas de partidas
    $stmt = $conexao->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'finalizada' THEN 1 END) as finalizadas,
            AVG(CASE WHEN status = 'finalizada' THEN pontuacao END) as media_pontuacao,
            AVG(CASE WHEN status = 'finalizada' THEN (acertos/total_perguntas)*100 END) as taxa_acerto,
            COUNT(CASE WHEN data_partida >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as partidas_hoje,
            SUM(CASE WHEN status = 'finalizada' THEN acertos END) as total_acertos,
            SUM(CASE WHEN status = 'finalizada' THEN total_perguntas END) as total_questoes
        FROM partidas
    ");
    $stats_partidas = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_partidas = $stats_partidas['total'];
    $media_pontuacao = round($stats_partidas['media_pontuacao'], 1);
    $taxa_acerto_geral = round($stats_partidas['taxa_acerto'], 1);

    // Últimas partidas com mais detalhes
    $stmt = $conexao->query("
        SELECT 
            p.*,
            j.nome as jogador_nome,
            j.pontuacao_total as jogador_pontuacao_total,
            ROUND((p.acertos/p.total_perguntas)*100, 1) as taxa_acerto
        FROM partidas p 
        JOIN jogadores j ON p.jogador_id = j.id 
        WHERE p.status = 'finalizada'
        ORDER BY p.data_partida DESC 
        LIMIT 5
    ");
    $ultimas_partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distribuição por categoria com estatísticas
    $stmt = $conexao->query("
        SELECT 
            categoria,
            COUNT(*) as total,
            AVG(CASE 
                WHEN dificuldade = 'facil' THEN 1
                WHEN dificuldade = 'medio' THEN 2
                WHEN dificuldade = 'dificil' THEN 3
            END) as dificuldade_media,
            SUM(CASE WHEN dificuldade = 'facil' THEN 1 ELSE 0 END) as total_facil,
            SUM(CASE WHEN dificuldade = 'medio' THEN 1 ELSE 0 END) as total_medio,
            SUM(CASE WHEN dificuldade = 'dificil' THEN 1 ELSE 0 END) as total_dificil
        FROM perguntas 
        GROUP BY categoria 
        ORDER BY total DESC
    ");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dados para o gráfico de últimos 7 dias
    $stmt = $conexao->query("
        SELECT 
            DATE(data_partida) as data,
            COUNT(*) as total_partidas,
            AVG(pontuacao) as media_pontuacao,
            COUNT(CASE WHEN modo = 'classico' THEN 1 END) as modo_classico,
            COUNT(CASE WHEN modo = 'tempo' THEN 1 END) as modo_tempo,
            COUNT(CASE WHEN modo = 'desafio' THEN 1 END) as modo_desafio,
            AVG(acertos/total_perguntas)*100 as taxa_acerto
        FROM partidas
        WHERE data_partida >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        GROUP BY DATE(data_partida)
        ORDER BY data_partida
    ");
    $dados_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar estatísticas do dashboard: ' . $e->getMessage());
    // Definir valores padrão em caso de erro
    $total_perguntas = 0;
    $total_jogadores = 0;
    $total_partidas = 0;
    $media_pontuacao = 0;
    $ultimas_partidas = [];
    $categorias = [];
    $dados_grafico = [];
}

?>

<!-- Depois dos requires e consultas SQL -->

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">,
        <div class="sidebar-header">
            <div class="sidebar-profile">
                <div>
                    <small class="text-muted">Painel de Controle</small>
                    <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador'); ?></h5>  
                </div>
            </div>
        </div>
        
        <nav class="admin-menu">
            <a href="index.php" class="menu-item active">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="perguntas.php" class="menu-item">
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
                <div>
                    <h1 class="h3 mb-0">Dashboard</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                
            </div>
            <!-- Cards de Estatísticas -->
            <div class="row g-3 mb-4">
                <!-- Card de Perguntas -->
                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="number"><?php echo number_format($total_perguntas); ?></div>
                                <div class="label">Perguntas Cadastradas</div>
                                <div class="stats mt-2">
                                    <small class="text-success">
                                        <?php echo number_format($stats_perguntas['total_facil']); ?> Fáceis
                                    </small> •
                                    <small class="text-warning">
                                        <?php echo number_format($stats_perguntas['total_medio']); ?> Médias
                                    </small> •
                                    <small class="text-danger">
                                        <?php echo number_format($stats_perguntas['total_dificil']); ?> Difíceis
                                    </small>
                                </div>
                            </div>
                            <div class="icon-box bg-primary-subtle">
                                <i class="fas fa-brain text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Jogadores -->
                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="number"><?php echo number_format($total_jogadores); ?></div>
                                <div class="label">Jogadores Registrados</div>
                                <div class="stats mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-user-check"></i>
                                        <?php echo number_format($stats_jogadores['ativos']); ?> ativos
                                    </small>
                                    <?php if($stats_jogadores['novos_hoje'] > 0): ?>
                                    <small class="text-primary ms-2">
                                        <i class="fas fa-user-plus"></i>
                                        <?php echo number_format($stats_jogadores['novos_hoje']); ?> hoje
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="icon-box bg-success-subtle">
                                <i class="fas fa-users text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Partidas -->
                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="number"><?php echo number_format($total_partidas); ?></div>
                                <div class="label">Partidas Realizadas</div>
                                <div class="stats mt-2">
                                    <small class="text-success">
                                        <?php echo number_format($taxa_acerto_geral); ?>% taxa de acerto
                                    </small>
                                    <?php if($stats_partidas['partidas_hoje'] > 0): ?>
                                    <small class="text-primary ms-2">
                                        <i class="fas fa-gamepad"></i>
                                        <?php echo number_format($stats_partidas['partidas_hoje']); ?> hoje
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="icon-box bg-info-subtle">
                                <i class="fas fa-trophy text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Pontuação -->
                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="number"><?php echo number_format($media_pontuacao, 1); ?></div>
                                <div class="label">Média de Pontuação</div>
                                <div class="stats mt-2">
                                    <small class="text-warning">
                                        <i class="fas fa-star"></i>
                                        Melhor: <?php echo number_format($stats_jogadores['melhor_pontuacao']); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="icon-box bg-warning-subtle">
                                <i class="fas fa-chart-line text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos e Estatísticas Detalhadas -->
            <div class="row g-3 mb-4">
                <!-- Gráfico Principal -->
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Desempenho dos Últimos Dias</h5>
                                <div class="chart-filters">
                                    <button class="btn btn-sm btn-outline-secondary period-btn active" data-period="7">7 dias</button>
                                    <button class="btn btn-sm btn-outline-secondary period-btn" data-period="15">15 dias</button>
                                    <button class="btn btn-sm btn-outline-secondary period-btn" data-period="30">30 dias</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper position-relative" style="height: 300px;">
                                <canvas id="mainChart"></canvas>
                            </div>
                            <!-- Estatísticas Rápidas -->
                            <div class="row g-3 mt-3">
                                <div class="col-md-3">
                                    <div class="quick-stat">
                                        <div class="stat-label">Total de Partidas</div>
                                        <div class="stat-value text-primary">
                                            <?php echo number_format($stats_partidas['partidas_hoje']); ?>
                                        </div>
                                        <div class="stat-subtitle">nas últimas 24h</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="quick-stat">
                                        <div class="stat-label">Taxa de Conclusão</div>
                                        <div class="stat-value text-success">
                                            <?php 
                                            $taxa_conclusao = ($stats_partidas['finalizadas'] / $total_partidas) * 100;
                                            echo number_format($taxa_conclusao, 1) . '%';
                                            ?>
                                        </div>
                                        <div class="stat-subtitle">partidas finalizadas</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="quick-stat">
                                        <div class="stat-label">Taxa de Acerto</div>
                                        <div class="stat-value text-warning">
                                            <?php echo number_format($taxa_acerto_geral, 1); ?>%
                                        </div>
                                        <div class="stat-subtitle">média geral</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="quick-stat">
                                        <div class="stat-label">Jogadores Ativos</div>
                                        <div class="stat-value text-info">
                                            <?php echo number_format($stats_jogadores['ativos']); ?>
                                        </div>
                                        <div class="stat-subtitle">no último mês</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribuição por Categoria -->
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Distribuição por Categoria</h5>
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" id="refreshCategories">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Gráfico de Pizza -->
                            <div class="chart-wrapper mb-4" style="height: 200px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                            <!-- Lista de Categorias -->
                            <div class="categories-list" style="max-height: 250px; overflow-y: auto;">
                                <?php foreach($categorias as $categoria): ?>
                                <div class="category-item mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-medium">
                                            <?php echo htmlspecialchars($categoria['categoria']); ?>
                                        </span>
                                        <span class="badge bg-primary">
                                            <?php echo number_format($categoria['total']); ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $percentual = ($categoria['total'] / $total_perguntas) * 100;
                                        ?>
                                        <div class="progress-bar" 
                                             style="width: <?php echo $percentual; ?>%"
                                             role="progressbar"
                                             aria-valuenow="<?php echo $percentual; ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="difficulty-distribution mt-1">
                                        <small class="text-success">
                                            <?php echo number_format($categoria['total_facil']); ?> fáceis
                                        </small>
                                        <small class="text-warning mx-2">
                                            <?php echo number_format($categoria['total_medio']); ?> médias
                                        </small>
                                        <small class="text-danger">
                                            <?php echo number_format($categoria['total_dificil']); ?> difíceis
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Partidas -->
            <div class="card">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Últimas Partidas</h5>
                        <a href="partidas.php" class="btn btn-sm btn-outline-primary">
                            Ver Todas <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Jogador</th>
                                    <th>Modo</th>
                                    <th>Pontuação</th>
                                    <th>Desempenho</th>
                                    <th>Data/Hora</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultimas_partidas)): ?>
                                    <?php foreach($ultimas_partidas as $partida): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-mini me-2 bg-<?php echo $partida['taxa_acerto'] >= 70 ? 'success' : ($partida['taxa_acerto'] >= 50 ? 'warning' : 'danger'); ?>">
                                                    <?php echo strtoupper(substr($partida['jogador_nome'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($partida['jogador_nome']); ?></div>
                                                    <small class="text-muted">
                                                        Total: <?php echo number_format($partida['jogador_pontuacao_total']); ?> pts
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $modo_classes = [
                                                'classico' => 'primary',
                                                'tempo' => 'warning',
                                                'desafio' => 'danger'
                                            ];
                                            $modo_class = $modo_classes[$partida['modo']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $modo_class; ?>-subtle text-<?php echo $modo_class; ?>">
                                                <?php echo ucfirst($partida['modo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?php echo number_format($partida['pontuacao']); ?></div>
                                            <?php if(isset($partida['tempo_total']) && $partida['tempo_total'] > 0): ?>
                                            <small class="text-muted">
                                                <?php echo gmdate("i:s", $partida['tempo_total']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <?php echo "{$partida['acertos']}/{$partida['total_perguntas']}"; ?>
                                                </div>
                                                <div style="width: 100px;">
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-<?php 
                                                            echo $partida['taxa_acerto'] >= 70 ? 'success' : 
                                                                ($partida['taxa_acerto'] >= 50 ? 'warning' : 'danger'); 
                                                        ?>" style="width: <?php echo $partida['taxa_acerto']; ?>%"></div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo number_format($partida['taxa_acerto'], 1); ?>%
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo date('d/m/Y', strtotime($partida['data_partida'])); ?></div>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($partida['data_partida'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'em_andamento' => 'info',
                                                'finalizada' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $status_class = $status_classes[$partida['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>-subtle text-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $partida['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-icon btn-outline-secondary"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detalhesPartidaModal"
                                                data-partida-id="<?php echo $partida['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-gamepad fa-2x mb-2"></i>
                                                <p class="mb-0">Nenhuma partida registrada ainda.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>



<!-- Modal Detalhes da Partida -->
<div class="modal fade" id="detalhesPartidaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Partida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesPartidaContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dados para os gráficos
const chartData = {
    labels: <?php echo json_encode(array_map(function($d) { 
        return date('d/m', strtotime($d['data'])); 
    }, $dados_grafico)); ?>,
    datasets: {
        partidas: <?php echo json_encode(array_map(function($d) {
            return $d['total_partidas'];
        }, $dados_grafico)); ?>,
        pontuacao: <?php echo json_encode(array_map(function($d) {
            return round($d['media_pontuacao'], 1);
        }, $dados_grafico)); ?>,
        modos: {
            classico: <?php echo json_encode(array_map(function($d) {
                return $d['modo_classico'];
            }, $dados_grafico)); ?>,
            tempo: <?php echo json_encode(array_map(function($d) {
                return $d['modo_tempo'];
            }, $dados_grafico)); ?>,
            desafio: <?php echo json_encode(array_map(function($d) {
                return $d['modo_desafio'];
            }, $dados_grafico)); ?>
        }
    }
};

// Configuração dos gráficos
const mainChartConfig = {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'Partidas',
                data: chartData.datasets.partidas,
                borderColor: 'rgba(67, 97, 238, 1)',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: 'rgba(67, 97, 238, 1)',
                pointHoverRadius: 5,
                tension: 0.3,
                fill: true
            },
            {
                label: 'Pontuação Média',
                data: chartData.datasets.pontuacao,
                borderColor: 'rgba(76, 201, 240, 1)',
                backgroundColor: 'rgba(76, 201, 240, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: 'rgba(76, 201, 240, 1)',
                pointHoverRadius: 5,
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Inter'
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Inter'
                    }
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Inter'
                    }
                }
            }
        }
    }
};

const categoryChartConfig = {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($categorias, 'categoria')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($categorias, 'total')); ?>,
            backgroundColor: [
                '#4361ee',
                '#3f37c9',
                '#4cc9f0',
                '#4895ef',
                '#f72585',
                '#7209b7'
            ],
            borderWidth: 1,
            borderColor: '#fff',
            hoverBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    font: {
                        family: 'Inter'
                    }
                }
            }
        },
        animation: {
            animateScale: true
        }
    }
};
// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gráficos
    const mainChart = new Chart(
        document.getElementById('mainChart'),
        mainChartConfig
    );

    const categoryChart = new Chart(
        document.getElementById('categoryChart'),
        categoryChartConfig
    );

    // Gestão do formulário de nova pergunta
    const formAddPergunta = document.getElementById('formAddPergunta');
    if (formAddPergunta) {
        formAddPergunta.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            try {
                const formData = new FormData(this);
                const response = await fetch('adicionar_pergunta.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.mensagem,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    throw new Error(data.mensagem);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message || 'Erro ao adicionar pergunta.'
                });
            }
        });
    }

    // Atualização dos gráficos
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(b => 
                b.classList.remove('active'));
            this.classList.add('active');
            atualizarGraficos(this.dataset.period);
        });
    });

    // Função para buscar os detalhes da partida
function buscarDetalhesPartida(partida_id) {
    fetch(`detalhes_partida.php?id=${partida_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const detalhesPartidaContent = document.getElementById('detalhesPartidaContent');
                detalhesPartidaContent.innerHTML = `
                    <p><strong>Jogador:</strong> ${data.data.jogador_nome}</p>
                    <p><strong>Modo:</strong> ${data.data.modo}</p>
                    <p><strong>Pontuação:</strong> ${data.data.pontuacao}</p>
                    <p><strong>Acertos:</strong> ${data.data.acertos}/${data.data.total_perguntas}</p>
                    <p><strong>Taxa de Acerto:</strong> ${data.data.taxa_acerto}%</p>
                    <p><strong>Data:</strong> ${new Date(data.data.data_partida).toLocaleString()}</p>
                    <p><strong>Status:</strong> ${data.data.status}</p>
                `;
            } else {
                console.error('Erro ao buscar detalhes da partida:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
        });
}

// Evento de clique no botão "Visualizar"
document.querySelectorAll('[data-bs-target="#detalhesPartidaModal"]').forEach(button => {
    button.addEventListener('click', function() {
        const partida_id = this.dataset.partidaId;
        buscarDetalhesPartida(partida_id);
    });
});


});
</script>

