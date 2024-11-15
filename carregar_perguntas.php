<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir headers
header('Content-Type: application/json');

try {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Obter dados do POST
    $dados = json_decode(file_get_contents('php://input'), true);
    if (!$dados || !isset($dados['modo'])) {
        throw new Exception('Dados inválidos');
    }

    // Validar modo
    $modo = $dados['modo'];
    $modos_validos = ['classico', 'tempo', 'desafio'];
    if (!in_array($modo, $modos_validos)) {
        throw new Exception('Modo inválido');
    }

    // Buscar perguntas baseado no modo
    $sql = "SELECT p.*, GROUP_CONCAT(r.id, ':', r.resposta, ':', r.correta SEPARATOR '||') as respostas 
            FROM perguntas p 
            LEFT JOIN respostas r ON p.id = r.pergunta_id ";

    switch ($modo) {
        case 'desafio':
            $sql .= "WHERE p.dificuldade = 'dificil' ";
            $limite = 5;
            break;
        case 'tempo':
            $sql .= "WHERE p.dificuldade IN ('facil', 'medio') ";
            $limite = 15;
            break;
        default:
            $limite = 10;
    }

    $sql .= "GROUP BY p.id ORDER BY RAND() LIMIT :limite";

    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $perguntas = [];
    while ($row = $stmt->fetch()) {
        // Processar respostas
        $opcoes = [];
        if ($row['respostas']) {
            foreach (explode('||', $row['respostas']) as $resp) {
                list($id, $texto, $correta) = explode(':', $resp);
                $opcoes[] = [
                    'id' => (int)$id,
                    'texto' => $texto,
                    'correta' => (bool)$correta
                ];
            }
            shuffle($opcoes); // Embaralhar opções
        }

        $perguntas[] = [
            'id' => (int)$row['id'],
            'pergunta' => $row['pergunta'],
            'categoria' => $row['categoria'],
            'dificuldade' => $row['dificuldade'],
            'pontos' => (int)$row['pontos'],
            'opcoes' => $opcoes
        ];
    }

    if (empty($perguntas)) {
        throw new Exception('Não há perguntas disponíveis para este modo.');
    }

    // Resposta de sucesso
    echo json_encode([
        'sucesso' => true,
        'perguntas' => $perguntas
    ]);

} catch (Exception $e) {
    // Log do erro
    error_log('Erro no carregar_perguntas.php: ' . $e->getMessage());
    
    // Resposta de erro
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>