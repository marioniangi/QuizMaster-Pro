<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
//verificarLogin();

// Buscar estatísticas atualizadas
try {
    // Código para buscar as estatísticas atualizadas, similar ao código já existente no index.php
    // ...

    // Retornar os dados atualizados como JSON
    $response = [
        'success' => true,
        'data' => [
            'total_perguntas' => $total_perguntas,
            'total_jogadores' => $total_jogadores,
            'total_partidas' => $total_partidas,
            'media_pontuacao' => $media_pontuacao,
            'ultimas_partidas' => $ultimas_partidas,
            'categorias' => $categorias,
            'dados_grafico' => $dados_grafico
        ]
    ];
    echo json_encode($response);
} catch(PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Erro ao atualizar as estatísticas: ' . $e->getMessage()
    ];
    echo json_encode($response);
}