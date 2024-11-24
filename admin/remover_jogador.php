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

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

// Obter e validar dados
$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (!isset($dados['id']) || !is_numeric($dados['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
    exit;
}

$id = (int)$dados['id'];

try {
    // Iniciar transação
    $conexao->beginTransaction();

    // Verificar se o jogador existe e está inativo
    $stmt = $conexao->prepare("
        SELECT id, status, ultima_partida 
        FROM jogadores 
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $jogador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jogador) {
        throw new Exception('Jogador não encontrado');
    }

    // Verificar se está inativo há mais de 90 dias
    $dias_inativo = null;
    if ($jogador['ultima_partida']) {
        $dias_inativo = (time() - strtotime($jogador['ultima_partida'])) / (60 * 60 * 24);
    }

    if ($dias_inativo === null || $dias_inativo <= 90) {
        throw new Exception('Apenas jogadores inativos por mais de 90 dias podem ser removidos');
    }

    // Remover registros relacionados
    $stmt = $conexao->prepare("DELETE FROM respostas_jogador WHERE jogador_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $stmt = $conexao->prepare("DELETE FROM partidas WHERE jogador_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Remover jogador
    $stmt = $conexao->prepare("DELETE FROM jogadores WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Registrar log
    registrar_log('info', 'Jogador removido', [
        'jogador_id' => $id,
        'admin_id' => $_SESSION['admin_id']
    ]);

    // Confirmar transação
    $conexao->commit();

    // Retornar sucesso
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }

    // Registrar erro
    registrar_log('erro', 'Erro ao remover jogador: ' . $e->getMessage(), [
        'jogador_id' => $id ?? null,
        'admin_id' => $_SESSION['admin_id'] ?? null
    ]);

    // Retornar erro
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}