<?php
session_start();
$titulo_pagina = 'Ranking';
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

try {
    // Buscar top jogadores
    $stmt = $conexao->query("
        SELECT 
            j.id,
            j.nome,
            j.pontuacao_total,
            j.jogos_completados,
            COUNT(DISTINCT p.id) as total_partidas,
            MAX(p.pontuacao) as melhor_partida,
            AVG(p.pontuacao) as media_pontuacao,
            MAX(p.data_fim) as ultima_partida,
            SUM(p.acertos) as total_acertos,
            SUM(p.total_perguntas) as total_perguntas
        FROM jogadores j
        LEFT JOIN partidas p ON j.id = p.jogador_id
        WHERE p.status = 'finalizada'
        GROUP BY j.id
        ORDER BY j.pontuacao_total DESC
        LIMIT 100
    ");
    
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar estatísticas gerais
    $stmt = $conexao->query("
        SELECT 
            COUNT(DISTINCT jogador_id) as total_jogadores,
            COUNT(*) as total_partidas,
            AVG(pontuacao) as media_pontuacao,
            MAX(pontuacao) as maior_pontuacao,
            AVG(acertos * 100.0 / total_perguntas) as media_acertos
        FROM partidas
        WHERE status = 'finalizada'
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    registrar_log('erro', 'Erro ao buscar ranking: ' . $e->getMessage());
    $ranking = [];
    $stats = [
        'total_jogadores' => 0,
        'total_partidas' => 0,
        'media_pontuacao' => 0,
        'maior_pontuacao' => 0,
        'media_acertos' => 0
    ];
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">🏆 Ranking Global</h2>
            
            <!-- Estatísticas Gerais -->
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="h2 text-primary"><?php echo number_format($stats['total_jogadores']); ?></div>
                    <div class="text-muted">Jogadores</div>
                </div>
                <div class="col-md-3">
                    <div class="h2 text-success"><?php echo number_format($stats['total_partidas']); ?></div>
                    <div class="text-muted">Partidas</div>
                </div>
                <div class="col-md-3">
                    <div class="h2 text-warning"><?php echo number_format($stats['media_pontuacao'], 1); ?></div>
                    <div class="text-muted">Média de Pontos</div>
                </div>
                <div class="col-md-3">
                    <div class="h2 text-info"><?php echo number_format($stats['media_acertos'], 1); ?>%</div>
                    <div class="text-muted">Média de Acertos</div>
                </div>
            </div>

            <!-- Tabela de Ranking -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">Posição</th>
                            <th>Jogador</th>
                            <th class="text-end">Pontuação</th>
                            <th class="text-center">Jogos</th>
                            <th class="text-center">Acertos</th>
                            <th class="text-end">Última Partida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ranking)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>Nenhum jogador encontrado
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($ranking as $pos => $jogador): 
                                $posicao = $pos + 1;
                                $taxa_acertos = $jogador['total_perguntas'] > 0 
                                    ? ($jogador['total_acertos'] / $jogador['total_perguntas']) * 100 
                                    : 0;
                            ?>
                            <tr class="<?php echo isset($_SESSION['jogador_id']) && $_SESSION['jogador_id'] == $jogador['id'] ? 'table-primary' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($posicao <= 3): ?>
                                        <?php
                                        $medalha = match($posicao) {
                                            1 => '🥇',
                                            2 => '🥈',
                                            3 => '🥉'
                                        };
                                        echo "<span style='font-size: 1.2em;'>$medalha</span>";
                                        ?>
                                    <?php else: ?>
                                        <?php echo $posicao; ?>º
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($jogador['nome']); ?>
                                    <?php if ($jogador['pontuacao_total'] >= 1000): ?>
                                        <i class="fas fa-star text-warning ms-1" title="Jogador Veterano"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($jogador['pontuacao_total']); ?></strong>
                                    <small class="text-muted d-block">
                                        Melhor: <?php echo number_format($jogador['melhor_partida']); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php echo $jogador['total_partidas']; ?>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: <?php echo $taxa_acertos; ?>%">
                                        </div>
                                    </div>
                                    <small><?php echo number_format($taxa_acertos, 1); ?>%</small>
                                </td>
                                <td class="text-end text-muted">
                                    <?php 
                                        $data = new DateTime($jogador['ultima_partida']);
                                        echo $data->format('d/m/Y H:i');
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Link para jogar -->
    <div class="text-center">
        <a href="index.php" class="btn btn-primary btn-lg">
            <i class="fas fa-gamepad me-2"></i>Jogar Agora
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>