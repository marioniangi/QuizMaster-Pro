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

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <img src="<?php echo BASE_URL; ?>assets/img/admin-avatar.png" alt="Admin">
            <h5><?php echo $_SESSION['admin_nome']; ?></h5>
            <small class="text-white-50">Administrador</small>
        </div>
        
        <nav class="admin-menu">
            <a href="index.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
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
                <h1 class="h3">Dashboard</h1>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addPerguntaModal">
                        <i class="fas fa-plus"></i> Nova Pergunta
                    </button>
                    <button class="btn btn-admin-success" onclick="window.location.href='relatorios.php'">
                        <i class="fas fa-download"></i> Exportar Relatório
                    </button>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <i class="fas fa-question-circle icon text-primary"></i>
                        <div class="number"><?php echo $total_perguntas; ?></div>
                        <div class="label">Perguntas Cadastradas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <i class="fas fa-users icon text-success"></i>
                        <div class="number"><?php echo $total_jogadores; ?></div>
                        <div class="label">Jogadores Registrados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <i class="fas fa-gamepad icon text-info"></i>
                        <div class="number"><?php echo $total_partidas; ?></div>
                        <div class="label">Partidas Jogadas</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <i class="fas fa-star icon text-warning"></i>
                        <div class="number"><?php echo $media_pontuacao; ?></div>
                        <div class="label">Média de Pontuação</div>
                    </div>
                </div>
            </div>

            <!-- Gráficos e Tabelas -->
            <div class="row">
                <!-- Gráfico de Estatísticas -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Estatísticas de Jogo</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="quizStats" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Distribuição por Categoria -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Perguntas por Categoria</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($categorias as $categoria): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo $categoria['categoria']; ?></span>
                                    <span class="text-muted"><?php echo $categoria['total']; ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <?php 
                                    $percentual = ($categoria['total'] / $total_perguntas) * 100;
                                    ?>
                                    <div class="progress-bar bg-primary" style="width: <?php echo $percentual; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Partidas -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Últimas Partidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table admin-table">
                                    <thead>
                                        <tr>
                                            <th>Jogador</th>
                                            <th>Pontuação</th>
                                            <th>Acertos</th>
                                            <th>Data</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ultimas_partidas as $partida): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($partida['jogador_nome']); ?></td>
                                            <td><?php echo $partida['pontuacao']; ?></td>
                                            <td>
                                                <?php 
                                                $percentual = ($partida['acertos'] / $partida['total_perguntas']) * 100;
                                                echo "{$partida['acertos']}/{$partida['total_perguntas']} ({$percentual}%)";
                                                ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($partida['data_partida'])); ?></td>
                                            <td>
                                                <?php if($percentual >= 70): ?>
                                                    <span class="badge badge-admin badge-success">Ótimo</span>
                                                <?php elseif($percentual >= 50): ?>
                                                    <span class="badge badge-admin badge-warning">Regular</span>
                                                <?php else: ?>
                                                    <span class="badge badge-admin badge-danger">Precisa Melhorar</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Adicionar Pergunta -->
<div class="modal fade" id="addPerguntaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Nova Pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="adicionar_pergunta.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Pergunta</label>
                        <input type="text" class="form-control" name="pergunta" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria" required>
                            <option value="">Selecione...</option>
                            <option value="geral">Geral</option>
                            <option value="historia">História</option>
                            <option value="ciencias">Ciências</option>
                            <option value="geografia">Geografia</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dificuldade</label>
                        <select class="form-select" name="dificuldade" required>
                            <option value="facil">Fácil</option>
                            <option value="medio">Médio</option>
                            <option value="dificil">Difícil</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Opções de Resposta</label>
                        <div id="optionsContainer">
                            <!-- Opções serão adicionadas aqui via JavaScript -->
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" id="addOptionBtn">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-admin-primary">Salvar Pergunta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>