<?php
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se está logado
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso não autorizado']);
    exit;
}

// Verificar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Buscar dados básicos do jogador
    $stmt = $conexao->prepare("
        SELECT 
            j.*,
            COUNT(DISTINCT p.id) as total_partidas,
            SUM(p.acertos) as total_acertos,
            SUM(p.total_perguntas) as total_perguntas,
            MAX(p.data_partida) as ultima_partida
        FROM jogadores j
        LEFT JOIN partidas p ON j.id = p.jogador_id
        WHERE j.id = :id
        GROUP BY j.id
    ");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $jogador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jogador) {
        throw new Exception('Jogador não encontrado');
    }

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
    $jogador['status'] = $status;
    $jogador['status_class'] = $status_class;

    // Buscar estatísticas por modo de jogo
    $modos = ['classico', 'tempo', 'desafio'];
    $stats = [];
    foreach ($modos as $modo) {
        $stmt = $conexao->prepare("
            SELECT 
                COUNT(*) as partidas,
                AVG(pontuacao) as media_pontos,
                GROUP_CONCAT(pontuacao ORDER BY data_partida DESC LIMIT 5) as ultimos_pontos
            FROM partidas 
            WHERE jogador_id = :id AND modo = :modo AND status = 'finalizada'
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':modo', $modo);
        $stmt->execute();
        $modo_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stats[$modo] = [
            'partidas' => (int)$modo_stats['partidas'],
            'media_pontos' => round($modo_stats['media_pontos'] ?? 0),
            'ultimos' => $modo_stats['ultimos_pontos'] ? 
                array_map('intval', explode(',', $modo_stats['ultimos_pontos'])) : 
                []
        ];
    }
    $jogador['stats'] = $stats;

    // Buscar últimas partidas
    $stmt = $conexao->prepare("
        SELECT 
            p.*,
            CASE 
                WHEN modo = 'classico' THEN 'primary'
                WHEN modo = 'tempo' THEN 'warning'
                ELSE 'info'
            END as modo_class
        FROM partidas p
        WHERE jogador_id = :id AND status = 'finalizada'
        ORDER BY data_partida DESC
        LIMIT 10
    ");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $jogador['ultimas_partidas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retornar dados
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => true,
        'jogador' => $jogador
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}