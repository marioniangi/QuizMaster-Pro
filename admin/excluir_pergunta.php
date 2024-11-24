<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Verificar se o usuário está logado
//verificarLogin();

// Verificar se é uma requisição AJAX
if (!is_ajax()) {
    header('HTTP/1.1 400 Bad Request');
    exit('Requisição inválida');
}

// Pegar dados do JSON
$dados = json_decode(file_get_contents('php://input'), true);

// Iniciar resposta
$resposta = [
    'sucesso' => false,
    'mensagem' => ''
];

try {
    // Validar ID da pergunta
    if (!isset($dados['id']) || !is_numeric($dados['id'])) {
        throw new Exception('ID da pergunta inválido.');
    }

    $id = (int)$dados['id'];

    // Verificar se a pergunta existe e obter informações para o log
    $stmt = $conexao->prepare("
        SELECT p.*, 
               COUNT(DISTINCT pr.id) as total_partidas,
               GROUP_CONCAT(r.id) as respostas_ids
        FROM perguntas p
        LEFT JOIN respostas r ON p.id = r.pergunta_id
        LEFT JOIN partidas_respostas pr ON r.id = pr.resposta_id
        WHERE p.id = :id
        GROUP BY p.id
    ");
    
    $stmt->execute([':id' => $id]);
    $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pergunta) {
        throw new Exception('Pergunta não encontrada.');
    }

    // Verificar se a pergunta já foi usada em partidas
    if ($pergunta['total_partidas'] > 0) {
        // Criar backup da pergunta antes de excluir
        $stmt = $conexao->prepare("
            INSERT INTO perguntas_excluidas (
                pergunta_id,
                pergunta,
                categoria,
                dificuldade,
                pontos,
                feedback,
                data_criacao,
                data_exclusao,
                motivo_exclusao,
                admin_id
            ) VALUES (
                :pergunta_id,
                :pergunta,
                :categoria,
                :dificuldade,
                :pontos,
                :feedback,
                :data_criacao,
                NOW(),
                'Exclusão administrativa',
                :admin_id
            )
        ");

        $stmt->execute([
            ':pergunta_id' => $pergunta['id'],
            ':pergunta' => $pergunta['pergunta'],
            ':categoria' => $pergunta['categoria'],
            ':dificuldade' => $pergunta['dificuldade'],
            ':pontos' => $pergunta['pontos'],
            ':feedback' => $pergunta['feedback'],
            ':data_criacao' => $pergunta['data_criacao'],
            ':admin_id' => $_SESSION['admin_id']
        ]);

        // Backup das respostas
        if (!empty($pergunta['respostas_ids'])) {
            $stmt = $conexao->prepare("
                INSERT INTO respostas_excluidas (
                    resposta_id,
                    pergunta_excluida_id,
                    resposta,
                    correta
                )
                SELECT 
                    r.id,
                    pe.id,
                    r.resposta,
                    r.correta
                FROM respostas r
                JOIN perguntas_excluidas pe ON pe.pergunta_id = r.pergunta_id
                WHERE r.pergunta_id = :pergunta_id
            ");
            
            $stmt->execute([':pergunta_id' => $id]);
        }
    }

    // Iniciar transação
    $conexao->beginTransaction();

    // Excluir respostas relacionadas
    $stmt = $conexao->prepare("DELETE FROM respostas WHERE pergunta_id = :id");
    $stmt->execute([':id' => $id]);

    // Excluir a pergunta
    $stmt = $conexao->prepare("DELETE FROM perguntas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Commit da transação
    $conexao->commit();

    // Registrar log
    registrar_log('sucesso', 'Pergunta excluída com sucesso', [
        'id' => $id,
        'pergunta' => $pergunta['pergunta'],
        'categoria' => $pergunta['categoria'],
        'total_partidas' => $pergunta['total_partidas']
    ]);

    // Preparar resposta de sucesso
    $resposta = [
        'sucesso' => true,
        'mensagem' => 'Pergunta excluída com sucesso!'
    ];

} catch (PDOException $e) {
    if (isset($conexao) && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    registrar_log('erro', 'Erro no banco de dados ao excluir pergunta: ' . $e->getMessage());
    $resposta['mensagem'] = 'Erro ao excluir a pergunta. Por favor, tente novamente.';
    
} catch (Exception $e) {
    if (isset($conexao) && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    $resposta['mensagem'] = $e->getMessage();
    
} finally {
    // Enviar resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($resposta);
}

// Função para limpar registros antigos de backup (executar periodicamente)
function limpar_backups_antigos() {
    global $conexao;
    
    try {
        // Manter apenas backups dos últimos 90 dias
        $stmt = $conexao->prepare("
            DELETE pe, re
            FROM perguntas_excluidas pe
            LEFT JOIN respostas_excluidas re ON re.pergunta_excluida_id = pe.id
            WHERE pe.data_exclusao < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        $stmt->execute();
        
        registrar_log('info', 'Limpeza de backups antigos realizada');
        
    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao limpar backups antigos: ' . $e->getMessage());
    }
}

// Executar limpeza de backups com probabilidade de 1%
if (rand(1, 100) === 1) {
    limpar_backups_antigos();
}