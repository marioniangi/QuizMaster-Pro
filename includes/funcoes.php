<?php
// Impedir acesso direto ao arquivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acesso proibido');
}

/**
 * Funções relacionadas ao Quiz
 */

function buscar_perguntas($modo, $limite = NUMERO_PERGUNTAS_PADRAO) {
    global $conexao;
    
    try {
        $dificuldade = ($modo === 'desafio') ? 'dificil' : ['facil', 'medio'];
        
        $sql = "SELECT p.*, 
                GROUP_CONCAT(r.id,'::',r.resposta,'::',r.correta SEPARATOR '||') as respostas
                FROM perguntas p
                LEFT JOIN respostas r ON p.id = r.pergunta_id";
        
        if ($modo === 'desafio') {
            $sql .= " WHERE p.dificuldade = 'dificil'";
        } else {
            $sql .= " WHERE p.dificuldade IN ('facil', 'medio')";
        }
        
        $sql .= " GROUP BY p.id ORDER BY RAND() LIMIT :limite";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $perguntas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $respostas = [];
            if (!empty($row['respostas'])) {
                foreach (explode('||', $row['respostas']) as $resp) {
                    list($id, $texto, $correta) = explode('::', $resp);
                    $respostas[] = [
                        'id' => $id,
                        'texto' => $texto,
                        'correta' => $correta
                    ];
                }
                shuffle($respostas); // Embaralha as respostas
            }
            unset($row['respostas']);
            $row['opcoes'] = $respostas;
            $perguntas[] = $row;
        }
        
        return $perguntas;
    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao buscar perguntas: ' . $e->getMessage());
        return false;
    }
}

function calcular_pontuacao($resposta_correta, $tempo_resposta = null, $modo = 'classico') {
    if (!$resposta_correta) {
        return 0;
    }

    $pontos_base = MODOS_JOGO[$modo]['pontos_base'];

    if ($modo === 'tempo' && $tempo_resposta !== null) {
        // Bônus por rapidez no modo tempo
        $bonus = max(0, (TEMPO_LIMITE_RESPOSTA - $tempo_resposta) / TEMPO_LIMITE_RESPOSTA);
        return ceil($pontos_base * (1 + $bonus));
    }

    return $pontos_base;
}

function salvar_partida($jogador_id, $pontuacao, $acertos, $total_perguntas) {
    global $conexao;
    
    try {
        // Inserir nova partida
        $stmt = $conexao->prepare("
            INSERT INTO partidas (jogador_id, pontuacao, acertos, total_perguntas)
            VALUES (:jogador_id, :pontuacao, :acertos, :total_perguntas)
        ");
        
        $stmt->execute([
            ':jogador_id' => $jogador_id,
            ':pontuacao' => $pontuacao,
            ':acertos' => $acertos,
            ':total_perguntas' => $total_perguntas
        ]);
        
        // Atualizar estatísticas do jogador
        $stmt = $conexao->prepare("
            UPDATE jogadores 
            SET pontuacao_total = pontuacao_total + :pontuacao,
                jogos_completados = jogos_completados + 1,
                data_ultimo_jogo = CURRENT_TIMESTAMP
            WHERE id = :jogador_id
        ");
        
        $stmt->execute([
            ':pontuacao' => $pontuacao,
            ':jogador_id' => $jogador_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao salvar partida: ' . $e->getMessage());
        return false;
    }
}

/**
 * Funções relacionadas aos Jogadores
 */

 function criar_jogador($nome) {
    global $conexao;
    
    try {
        // Limpar e validar o nome
        $nome = trim(strip_tags($nome));
        if (strlen($nome) < 3 || strlen($nome) > 50) {
            throw new Exception("Nome deve ter entre 3 e 50 caracteres");
        }

        // Verificar se o nome já existe
        $stmt = $conexao->prepare("
            SELECT id 
            FROM jogadores 
            WHERE nome = ? 
            AND status = 'ativo'
            LIMIT 1
        ");
        $stmt->execute([$nome]);
        
        if ($stmt->fetch()) {
            throw new Exception("Este nome já está em uso");
        }

        // Inserir novo jogador
        $stmt = $conexao->prepare("
            INSERT INTO jogadores 
            (nome, data_cadastro) 
            VALUES 
            (?, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([$nome]);
        $id = $conexao->lastInsertId();

        if (!$id) {
            throw new Exception("Erro ao criar jogador");
        }

        registrar_log('info', "Novo jogador criado: ID $id, Nome: $nome");
        return $id;

    } catch (Exception $e) {
        registrar_log('erro', 'Erro ao criar jogador: ' . $e->getMessage());
        throw $e;
    }
}




function buscar_ranking($limite = 10) {
    global $conexao;
    
    try {
        $stmt = $conexao->prepare("
            SELECT j.*, 
                   COUNT(p.id) as total_partidas,
                   MAX(p.pontuacao) as melhor_pontuacao
            FROM jogadores j
            LEFT JOIN partidas p ON j.id = p.jogador_id
            GROUP BY j.id
            ORDER BY j.pontuacao_total DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao buscar ranking: ' . $e->getMessage());
        return false;
    }
}

/**
 * Funções relacionadas à Administração
 */

function adicionar_pergunta($pergunta, $dificuldade, $categoria, $respostas) {
    global $conexao;
    
    try {
        $conexao->beginTransaction();
        
        // Inserir pergunta
        $stmt = $conexao->prepare("
            INSERT INTO perguntas (pergunta, dificuldade, categoria)
            VALUES (:pergunta, :dificuldade, :categoria)
        ");
        
        $stmt->execute([
            ':pergunta' => $pergunta,
            ':dificuldade' => $dificuldade,
            ':categoria' => $categoria
        ]);
        
        $pergunta_id = $conexao->lastInsertId();
        
        // Inserir respostas
        $stmt = $conexao->prepare("
            INSERT INTO respostas (pergunta_id, resposta, correta)
            VALUES (:pergunta_id, :resposta, :correta)
        ");
        
        foreach ($respostas as $resposta) {
            $stmt->execute([
                ':pergunta_id' => $pergunta_id,
                ':resposta' => $resposta['texto'],
                ':correta' => $resposta['correta']
            ]);
        }
        
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        $conexao->rollBack();
        registrar_log('erro', 'Erro ao adicionar pergunta: ' . $e->getMessage());
        return false;
    }
}

function atualizar_pergunta($id, $pergunta, $dificuldade, $categoria, $respostas) {
    global $conexao;
    
    try {
        $conexao->beginTransaction();
        
        // Atualizar pergunta
        $stmt = $conexao->prepare("
            UPDATE perguntas 
            SET pergunta = :pergunta,
                dificuldade = :dificuldade,
                categoria = :categoria
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':pergunta' => $pergunta,
            ':dificuldade' => $dificuldade,
            ':categoria' => $categoria
        ]);
        
        // Remover respostas antigas
        $stmt = $conexao->prepare("DELETE FROM respostas WHERE pergunta_id = :pergunta_id");
        $stmt->execute([':pergunta_id' => $id]);
        
        // Inserir novas respostas
        $stmt = $conexao->prepare("
            INSERT INTO respostas (pergunta_id, resposta, correta)
            VALUES (:pergunta_id, :resposta, :correta)
        ");
        
        foreach ($respostas as $resposta) {
            $stmt->execute([
                ':pergunta_id' => $id,
                ':resposta' => $resposta['texto'],
                ':correta' => $resposta['correta']
            ]);
        }
        
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        $conexao->rollBack();
        registrar_log('erro', 'Erro ao atualizar pergunta: ' . $e->getMessage());
        return false;
    }
}

/**
 * Funções de Utilidade
 */

function formatar_tempo($segundos) {
    if ($segundos < 60) {
        return $segundos . " segundos";
    }
    
    $minutos = floor($segundos / 60);
    $segundos = $segundos % 60;
    
    if ($minutos < 60) {
        return $minutos . " min " . $segundos . " seg";
    }
    
    $horas = floor($minutos / 60);
    $minutos = $minutos % 60;
    
    return $horas . "h " . $minutos . "min";
}

function get_estatisticas_gerais() {
    global $conexao;
    
    try {
        $stmt = $conexao->query("
            SELECT 
                (SELECT COUNT(*) FROM jogadores) as total_jogadores,
                (SELECT COUNT(*) FROM partidas) as total_partidas,
                (SELECT AVG(pontuacao) FROM partidas) as media_pontuacao,
                (SELECT MAX(pontuacao) FROM partidas) as maior_pontuacao
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        registrar_log('erro', 'Erro ao buscar estatísticas: ' . $e->getMessage());
        return false;
    }
}

// Função para buscar categorias
function buscarCategorias() {
    global $conexao;
    
    try {
        $stmt = $conexao->query("
            SELECT DISTINCT categoria 
            FROM perguntas 
            ORDER BY categoria
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Erro ao buscar categorias: " . $e->getMessage());
        return [];
    }
}

// Função para validar pergunta
function validarPergunta($dados) {
    $erros = [];
    
    if (empty($dados['pergunta']) || strlen($dados['pergunta']) < 10) {
        $erros[] = "A pergunta deve ter no mínimo 10 caracteres.";
    }
    
    if (empty($dados['categoria'])) {
        $erros[] = "Categoria é obrigatória.";
    }
    
    if (!in_array($dados['dificuldade'], ['facil', 'medio', 'dificil'])) {
        $erros[] = "Dificuldade inválida.";
    }
    
    if (empty($dados['opcoes']) || count($dados['opcoes']) < 2) {
        $erros[] = "É necessário pelo menos 2 opções de resposta.";
    }
    
    if (!isset($dados['resposta_correta']) || 
        !isset($dados['opcoes'][$dados['resposta_correta']])) {
        $erros[] = "É necessário indicar a resposta correta.";
    }
    
    return $erros;
}


?>