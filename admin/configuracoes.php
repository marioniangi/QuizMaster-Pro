<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Configurações do Sistema';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
//verificarLogin();

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conexao->beginTransaction();

        // Configurações do Quiz
        if (isset($_POST['config_quiz'])) {
            $stmt = $conexao->prepare("
                UPDATE configuracoes 
                SET valor = :valor 
                WHERE chave = :chave
            ");

            $configs = [
                'perguntas_por_partida' => (int)$_POST['perguntas_por_partida'],
                'tempo_resposta' => (int)$_POST['tempo_resposta'],
                'pontos_resposta_normal' => (int)$_POST['pontos_resposta_normal'],
                'pontos_resposta_tempo' => (int)$_POST['pontos_resposta_tempo'],
                'pontos_resposta_desafio' => (int)$_POST['pontos_resposta_desafio'],
                'minimo_acertos_aprovacao' => (int)$_POST['minimo_acertos_aprovacao'],
                'mostrar_ranking_global' => isset($_POST['mostrar_ranking_global']) ? 1 : 0,
                'permitir_pular_pergunta' => isset($_POST['permitir_pular_pergunta']) ? 1 : 0
            ];

            foreach ($configs as $chave => $valor) {
                $stmt->execute([':chave' => $chave, ':valor' => $valor]);
            }

            registrar_log('info', 'Configurações do quiz atualizadas', ['admin_id' => $_SESSION['admin_id']]);
        }

        $conexao->commit();
        $mensagem_sucesso = "Configurações atualizadas com sucesso!";

    } catch (Exception $e) {
        $conexao->rollBack();
        registrar_log('erro', 'Erro ao atualizar configurações: ' . $e->getMessage());
        $erro = "Erro ao salvar configurações: " . $e->getMessage();
    }
}

// Buscar configurações atuais
try {
    $stmt = $conexao->query("SELECT chave, valor FROM configuracoes");
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['chave']] = $row['valor'];
    }
} catch (PDOException $e) {
    registrar_log('erro', 'Erro ao buscar configurações: ' . $e->getMessage());
    $erro = "Erro ao carregar configurações.";
}

require_once '../includes/header.php';
?>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
    <div class="sidebar-header">
            <div class="sidebar-profile">
    
                <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['admin_nome'] ?? 'Administrador'); ?></h5>
                    <small class="text-muted">Painel de Controle</small>
                </div>
            </div>
        </div>
        
        <nav class="admin-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="perguntas.php" class="menu-item">
                <i class="fas fa-question-circle"></i> Perguntas
            </a>
            <a href="jogadores.php" class="menu-item">
                <i class="fas fa-users"></i> Jogadores
            </a>
            <a href="configuracoes.php" class="menu-item active">
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
                <h1 class="h3">Configurações do Sistema</h1>
            </div>

            <!-- Mensagens -->
            <?php if (isset($mensagem_sucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $mensagem_sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Configurações do Quiz -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-gamepad me-2"></i>Configurações do Quiz
                        </h5>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetarConfiguracoes('quiz')">
                                <i class="fas fa-undo me-1"></i>Resetar Padrões
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="config_quiz" value="1">
                        
                        <!-- Configurações Básicas -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Perguntas por Partida</label>
                                    <input type="number" class="form-control" name="perguntas_por_partida" 
                                           value="<?php echo $configs['perguntas_por_partida'] ?? 10; ?>"
                                           min="5" max="50" required>
                                    <div class="form-text">
                                        Número de perguntas em cada partida do quiz.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tempo para Resposta (segundos)</label>
                                    <input type="number" class="form-control" name="tempo_resposta"
                                           value="<?php echo $configs['tempo_resposta'] ?? 30; ?>"
                                           min="10" max="120" required>
                                    <div class="form-text">
                                        Tempo limite para responder cada pergunta no modo tempo.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configurações de Pontuação -->
                        <h6 class="mb-3">Pontuação</h6>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Modo Normal</label>
                                    <input type="number" class="form-control" name="pontos_resposta_normal"
                                           value="<?php echo $configs['pontos_resposta_normal'] ?? 10; ?>"
                                           min="1" max="100" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Modo Tempo</label>
                                    <input type="number" class="form-control" name="pontos_resposta_tempo"
                                           value="<?php echo $configs['pontos_resposta_tempo'] ?? 15; ?>"
                                           min="1" max="100" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Modo Desafio</label>
                                    <input type="number" class="form-control" name="pontos_resposta_desafio"
                                           value="<?php echo $configs['pontos_resposta_desafio'] ?? 20; ?>"
                                           min="1" max="100" required>
                                </div>
                            </div>
                        </div>

                        <!-- Outras Configurações -->
                        <h6 class="mb-3">Outras Configurações</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Mínimo de Acertos para Aprovação (%)</label>
                                    <input type="number" class="form-control" name="minimo_acertos_aprovacao"
                                           value="<?php echo $configs['minimo_acertos_aprovacao'] ?? 70; ?>"
                                           min="0" max="100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="mostrar_ranking_global"
                                               id="mostrarRanking" <?php echo ($configs['mostrar_ranking_global'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mostrarRanking">
                                            Mostrar Ranking Global
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="permitir_pular_pergunta"
                                               id="permitirPular" <?php echo ($configs['permitir_pular_pergunta'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="permitirPular">
                                            Permitir Pular Pergunta
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botão Salvar -->
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>


<!-- Scripts específicos -->
<script>
// Função para testar configurações de e-mail


// Função para enviar e-mail de teste


// Função para fazer backup manual


// Função para baixar backup


// Função para resetar configurações
function resetarConfiguracoes(tipo) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "As configurações serão restauradas para os valores padrão.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, resetar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            resetarConfiguracoesConfirmado(tipo);
        }
    });
}

// Função para executar reset de configurações
async function resetarConfiguracoesConfirmado(tipo) {
    try {
        const response = await fetch('resetar_configuracoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tipo: tipo })
        });
        
        const data = await response.json();
        
        if (!data.sucesso) {
            throw new Error(data.mensagem);
        }

        Swal.fire({
            icon: 'success',
            title: 'Configurações Resetadas!',
            text: 'As configurações foram restauradas com sucesso.',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            window.location.reload();
        });
        
    } catch (erro) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao resetar configurações.'
        });
    }
}

// Validação de formulários
document.addEventListener('DOMContentLoaded', function() {
    // Formulários com validação
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Toggle de campos dependentes
    const backupAutomatico = document.getElementById('backupAutomatico');
    if (backupAutomatico) {
        const toggleCamposBackup = () => {
            const campos = document.querySelectorAll('[name="backup_frequencia"], [name="backup_retencao"], [name="backup_email_notificacao"]');
            campos.forEach(campo => {
                campo.disabled = !backupAutomatico.checked;
                campo.required = backupAutomatico.checked;
            });
        };
        backupAutomatico.addEventListener('change', toggleCamposBackup);
        toggleCamposBackup(); // Executar no carregamento
    }
});

// Função para formatar tamanho de arquivo
function formatarTamanhoArquivo(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php 
// Função auxiliar para formatar tamanho de arquivo
function formatarTamanhoArquivo($bytes) {
    if ($bytes == 0) return "0 Bytes";
    $k = 1024;
    $sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

require_once '../includes/footer.php'; 
?>