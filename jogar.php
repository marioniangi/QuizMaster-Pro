<?php
session_start();
$titulo_pagina = 'Jogar Quiz';
require_once 'includes/config.php';
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';

// Se não houver nome do jogador, pedir para cadastrar
if (!isset($_SESSION['jogador_id'])) {
    if (isset($_POST['nome_jogador'])) {
        try {
            $nome = limparDados($_POST['nome_jogador']);
            
            if (strlen($nome) < 3) {
                throw new Exception("Nome deve ter pelo menos 3 caracteres.");
            }

            $jogador_id = criar_jogador($nome);
            
            if ($jogador_id) {
                $_SESSION['jogador_id'] = $jogador_id;
                $_SESSION['jogador_nome'] = $nome;
                
                // Redirecionar para evitar reenvio do formulário
                header("Location: jogar.php?modo=" . urlencode($modo));
                exit;
            } else {
                $erro = "Erro ao criar jogador. Tente novamente.";
            }
        } catch (Exception $e) {
            registrar_log('erro', 'Erro ao criar jogador: ' . $e->getMessage());
            $erro = $e->getMessage();
        }
    }
}

// Verificar modo de jogo
$modos_validos = ['classico', 'tempo', 'desafio'];
$modo = isset($_GET['modo']) && in_array($_GET['modo'], $modos_validos) ? $_GET['modo'] : 'classico';

// Se não houver nome do jogador, pedir para cadastrar
if (!isset($_SESSION['jogador_id'])) {
    if (isset($_POST['nome_jogador'])) {
        $nome = limparDados($_POST['nome_jogador']);
        if (strlen($nome) >= 3) {
            try {
                $jogador_id = criar_jogador($nome);
                if ($jogador_id) {
                    $_SESSION['jogador_id'] = $jogador_id;
                    $_SESSION['jogador_nome'] = $nome;
                } else {
                    $erro = "Erro ao criar jogador. Tente novamente.";
                }
            } catch (Exception $e) {
                registrar_log('erro', 'Erro ao criar jogador: ' . $e->getMessage());
                $erro = "Erro ao processar cadastro.";
            }
        } else {
            $erro = "Nome deve ter pelo menos 3 caracteres.";
        }
    }
}

// Buscar configurações do modo
try {
    $stmt = $conexao->prepare("
        SELECT valor 
        FROM configuracoes 
        WHERE chave IN ('pontos_resposta_normal', 'pontos_resposta_tempo', 'pontos_resposta_desafio', 'tempo_resposta')
    ");
    $stmt->execute();
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[] = $row['valor'];
    }
} catch (PDOException $e) {
    registrar_log('erro', 'Erro ao buscar configurações: ' . $e->getMessage());
    $configs = [10, 15, 20, 30]; // valores padrão
}

require_once 'includes/header.php';
?>

<!-- Container Principal do Jogo -->
<div class="container py-4">
    <div class="quiz-container fade-in">
        <!-- Cabeçalho do Quiz -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">
                    <?php
                    switch ($modo) {
                        case 'tempo':
                            echo '<i class="fas fa-bolt me-2"></i>Modo Contra o Tempo';
                            break;
                        case 'desafio':
                            echo '<i class="fas fa-trophy me-2"></i>Modo Desafio';
                            break;
                        default:
                            echo '<i class="fas fa-clock me-2"></i>Modo Clássico';
                    }
                    ?>
                </h4>
                <?php if(isset($_SESSION['jogador_nome'])): ?>
                    <small class="text-muted">Jogador: <?php echo htmlspecialchars($_SESSION['jogador_nome']); ?></small>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <div class="h4 mb-0">
                    <i class="fas fa-star text-warning me-2"></i>
                    <span id="pontuacao">0</span> pontos
                </div>
                <small id="contador-perguntas" class="text-muted">
                    Pergunta 0 de 0
                </small>
            </div>
        </div>

        <!-- Tela Inicial -->
        <div id="tela-inicial" class="text-center mb-5">
            <h2 class="mb-4">
                <?php if($modo === 'tempo'): ?>
                    <div class="mb-3">
                        <i class="fas fa-bolt fa-3x text-warning"></i>
                    </div>
                    <div>Modo Contra o Tempo</div>
                    <p class="lead">Responda rápido para ganhar mais pontos!</p>
                <?php elseif($modo === 'desafio'): ?>
                    <div class="mb-3">
                        <i class="fas fa-trophy fa-3x text-danger"></i>
                    </div>
                    <div>Modo Desafio</div>
                    <p class="lead">Perguntas difíceis, pontuação dobrada!</p>
                <?php else: ?>
                    <div class="mb-3">
                        <i class="fas fa-clock fa-3x text-primary"></i>
                    </div>
                    <div>Modo Clássico</div>
                    <p class="lead">Responda com calma e atenção!</p>
                <?php endif; ?>
            </h2>

            <?php if(isset($_SESSION['jogador_id'])): ?>
                <button type="button" class="btn btn-primary btn-lg" id="btn-iniciar">
                    <i class="fas fa-play me-2"></i>Iniciar Quiz
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#cadastroModal">
                    <i class="fas fa-user-plus me-2"></i>Cadastrar para Jogar
                </button>
            <?php endif; ?>
        </div>

        <!-- Container do Quiz (inicialmente oculto) -->
        <div id="quiz-container" class="d-none">
            <!-- Barra de Progresso (para modo tempo) -->
            <div id="tempo-container" class="mb-4 d-none">
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%">
                        <span id="tempo-restante">30s</span>
                    </div>
                </div>
            </div>

            <!-- Container da Pergunta -->
            <div id="pergunta-container" class="pergunta-container mb-4">
                <!-- Será preenchido via JavaScript -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>

            <!-- Container das Opções -->
            <div id="opcoes-container" class="mb-4">
                <!-- Será preenchido via JavaScript -->
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-home me-2"></i>Voltar ao Início
                </button>
                <button type="button" class="btn btn-primary" id="btn-proxima" disabled>
                    Próxima Pergunta <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Container de Resultados (inicialmente oculto) -->
        <div id="resultados-container" class="d-none">
            <!-- Será preenchido via JavaScript -->
        </div>
    </div>
</div>

<!-- Modal de Cadastro -->
<div class="modal fade" id="cadastroModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Antes de começar...</h5>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <?php if(isset($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Como devemos te chamar?</label>
                        <input type="text" class="form-control" name="nome_jogador" 
                               required minlength="3" maxlength="50"
                               placeholder="Digite seu nome ou apelido">
                        <div class="form-text">
                            Este nome será usado no ranking.
                        </div>
                        <div class="invalid-feedback">
                            Por favor, digite um nome com pelo menos 3 caracteres.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Começar a Jogar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>
<!-- Configurações do Quiz para JavaScript -->
<script>
    // Configurações do modo de jogo
    const QUIZ_CONFIG = {
        modo: '<?php echo $modo; ?>',
        tempoLimite: <?php echo $modo === 'tempo' ? ($configs[3] ?? 30) : 'null'; ?>,
        pontosBase: <?php 
            echo $modo === 'desafio' ? ($configs[2] ?? 20) : 
                ($modo === 'tempo' ? ($configs[1] ?? 15) : ($configs[0] ?? 10)); 
        ?>,
        jogadorId: <?php echo isset($_SESSION['jogador_id']) ? $_SESSION['jogador_id'] : 'null'; ?>,
        jogadorNome: '<?php echo isset($_SESSION['jogador_nome']) ? addslashes($_SESSION['jogador_nome']) : ''; ?>'
    };

    // Textos e mensagens
    const QUIZ_TEXTOS = {
        classico: {
            titulo: 'Modo Clássico',
            descricao: 'Responda com calma e atenção!',
            icone: 'clock'
        },
        tempo: {
            titulo: 'Contra o Tempo',
            descricao: 'Seja rápido para ganhar mais pontos!',
            icone: 'bolt'
        },
        desafio: {
            titulo: 'Modo Desafio',
            descricao: 'Questões difíceis, pontuação dobrada!',
            icone: 'trophy'
        }
    };

    // Estados do jogo
    const QUIZ_ESTADOS = {
        AGUARDANDO: 'aguardando',
        JOGANDO: 'jogando',
        RESPONDIDO: 'respondido',
        FINALIZADO: 'finalizado'
    };
</script>

<!-- Scripts específicos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário de cadastro
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Mostrar modal de cadastro se necessário
    <?php if (!isset($_SESSION['jogador_id']) && !isset($_POST['nome_jogador'])): ?>
    new bootstrap.Modal(document.getElementById('cadastroModal')).show();
    <?php endif; ?>

    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Funções auxiliares
function formatarTempo(segundos) {
    if (segundos < 60) {
        return `${segundos}s`;
    }
    const minutos = Math.floor(segundos / 60);
    segundos = segundos % 60;
    return `${minutos}m ${segundos}s`;
}

function formatarPontuacao(pontos) {
    return pontos.toLocaleString('pt-BR');
}

function embaralharArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

// Função para atualizar progresso
function atualizarProgresso(atual, total) {
    const porcentagem = (atual / total) * 100;
    return `
        <div class="progress" style="height: 5px;">
            <div class="progress-bar" role="progressbar" 
                 style="width: ${porcentagem}%" 
                 aria-valuenow="${atual}" 
                 aria-valuemin="0" 
                 aria-valuemax="${total}">
            </div>
        </div>
        <small class="text-muted">
            Pergunta ${atual} de ${total}
        </small>
    `;
}

// Função para feedback visual
function mostrarFeedback(tipo, mensagem, detalhes = '') {
    const icone = tipo === 'sucesso' ? 'check-circle' : 'times-circle';
    const classe = tipo === 'sucesso' ? 'success' : 'danger';
    
    return `
        <div class="text-center">
            <i class="fas fa-${icone} fa-3x text-${classe} mb-3"></i>
            <h4>${mensagem}</h4>
            ${detalhes ? `<p class="text-muted">${detalhes}</p>` : ''}
        </div>
    `;
}

// Handler de erro global
window.addEventListener('error', function(e) {
    console.error('Erro no quiz:', e);
    Swal.fire({
        icon: 'error',
        title: 'Ops! Algo deu errado',
        text: 'Ocorreu um erro inesperado. Por favor, tente novamente.',
        confirmButtonText: 'Reiniciar Quiz',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.reload();
        } else {
            window.location.href = 'index.php';
        }
    });
});
</script>

<!-- Estilos específicos -->
<style>
.quiz-container {
    max-width: 800px;
    margin: 0 auto;
}

.pergunta-container {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.opcao-resposta {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.opcao-resposta:hover {
    border-color: var(--cor-primaria);
    transform: translateY(-2px);
}

.opcao-resposta.correta {
    border-color: #28a745;
    background-color: rgba(40, 167, 69, 0.1);
}

.opcao-resposta.incorreta {
    border-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

.opcao-resposta.selecionada {
    border-color: var(--cor-primaria);
    background-color: rgba(var(--cor-primaria-rgb), 0.1);
}

.tempo-container .progress {
    height: 10px;
    border-radius: 5px;
}

.feedback {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
}

.feedback.sucesso {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.feedback.erro {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 1s infinite;
}
</style>

<?php require_once 'includes/footer.php'; ?>jogar.php