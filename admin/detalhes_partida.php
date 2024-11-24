<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
//verificarLogin();

// Verificar se o ID da partida foi fornecido
if (!isset($_GET['id'])) {
    $response = [
        'success' => false,
        'message' => 'ID da partida não fornecido.'
    ];
    echo json_encode($response);
    exit;
}

$partida_id = $_GET['id'];

// Buscar os detalhes da partida
try {
    $stmt = $conexao->prepare("
        SELECT 
            p.*, 
            j.nome as jogador_nome,
            ROUND((p.acertos/p.total_perguntas)*100, 1) as taxa_acerto
        FROM partidas p
        JOIN jogadores j ON p.jogador_id = j.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partida_id]);
    $partida = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partida) {
        $response = [
            'success' => false,
            'message' => 'Partida não encontrada.'
        ];
        echo json_encode($response);
        exit;
    }

    $response = [
        'success' => true,
        'data' => $partida
    ];
    echo json_encode($response);
} catch(PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Erro ao buscar detalhes da partida: ' . $e->getMessage()
    ];
    echo json_encode($response);
}