<?php
session_start();
$pagina_admin = true;
$titulo_pagina = 'Configurações do Sistema';

require_once '../includes/config.php';
require_once '../includes/conexao.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
verificarLogin();

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

        // Configurações de E-mail
        if (isset($_POST['config_email'])) {
            $stmt = $conexao->prepare("
                UPDATE configuracoes 
                SET valor = :valor 
                WHERE chave = :chave
            ");

            $configs = [
                'email_host' => $_POST['email_host'],
                'email_porta' => (int)$_POST['email_porta'],
                'email_usuario' => $_POST['email_usuario'],
                'email_remetente' => $_POST['email_remetente'],
                'email_nome_remetente' => $_POST['email_nome_remetente']
            ];

            // Se senha foi preenchida, atualizar
            if (!empty($_POST['email_senha'])) {
                $configs['email_senha'] = password_hash($_POST['email_senha'], PASSWORD_DEFAULT);
            }

            foreach ($configs as $chave => $valor) {
                $stmt->execute([':chave' => $chave, ':valor' => $valor]);
            }

            registrar_log('info', 'Configurações de e-mail atualizadas', ['admin_id' => $_SESSION['admin_id']]);
        }

        // Configurações de Backup
        if (isset($_POST['config_backup'])) {
            $stmt = $conexao->prepare("
                UPDATE configuracoes 
                SET valor = :valor 
                WHERE chave = :chave
            ");

            $configs = [
                'backup_automatico' => isset($_POST['backup_automatico']) ? 1 : 0,
                'backup_frequencia' => $_POST['backup_frequencia'],
                'backup_retencao' => (int)$_POST['backup_retencao'],
                'backup_email_notificacao' => $_POST['backup_email_notificacao']
            ];

            foreach ($configs as $chave => $valor) {
                $stmt->execute([':chave' => $chave, ':valor' => $valor]);
            }

            registrar_log('info', 'Configurações de backup atualizadas', ['admin_id' => $_SESSION['admin_id']]);
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
            <img src="<?php echo BASE_URL; ?>assets/img/admin-avatar.png" alt="Admin">
            <h5><?php echo $_SESSION['admin_nome']; ?></h5>
            <small class="text-white-50">Administrador</small>
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
                <button type="button" class="btn btn-admin-success" onclick="fazerBackup()">
                    <i class="fas fa-database me-2"></i>Fazer Backup
                </button>
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
<!-- Configurações de E-mail -->
<div class="card mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope me-2"></i>Configurações de E-mail
                        </h5>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="testarEmail()">
                                <i class="fas fa-paper-plane me-1"></i>Testar E-mail
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="config_email" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control" name="email_host"
                                           value="<?php echo $configs['email_host'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Porta</label>
                                    <input type="number" class="form-control" name="email_porta"
                                           value="<?php echo $configs['email_porta'] ?? 587; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usuário</label>
                                    <input type="text" class="form-control" name="email_usuario"
                                           value="<?php echo $configs['email_usuario'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Senha</label>
                                    <input type="password" class="form-control" name="email_senha"
                                           placeholder="Digite para alterar">
                                    <div class="form-text">
                                        Deixe em branco para manter a senha atual.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">E-mail do Remetente</label>
                                    <input type="email" class="form-control" name="email_remetente"
                                           value="<?php echo $configs['email_remetente'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nome do Remetente</label>
                                    <input type="text" class="form-control" name="email_nome_remetente"
                                           value="<?php echo $configs['email_nome_remetente'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Configurações de Backup -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>Configurações de Backup
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="config_backup" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="backup_automatico"
                                               id="backupAutomatico" <?php echo ($configs['backup_automatico'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="backupAutomatico">
                                            Ativar Backup Automático
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Frequência de Backup</label>
                                    <select class="form-select" name="backup_frequencia" required>
                                        <option value="diario" <?php echo ($configs['backup_frequencia'] ?? '') === 'diario' ? 'selected' : ''; ?>>Diário</option>
                                        <option value="semanal" <?php echo ($configs['backup_frequencia'] ?? '') === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                        <option value="mensal" <?php echo ($configs['backup_frequencia'] ?? '') === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Retenção de Backups (dias)</label>
                                    <input type="number" class="form-control" name="backup_retencao"
                                           value="<?php echo $configs['backup_retencao'] ?? 30; ?>"
                                           min="1" max="365" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">E-mail para Notificação</label>
                                    <input type="email" class="form-control" name="backup_email_notificacao"
                                           value="<?php echo $configs['backup_email_notificacao'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Histórico de Backups -->
                        <div class="table-responsive mt-4">
                            <h6>Últimos Backups</h6>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Tamanho</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $conexao->query("
                                            SELECT * FROM backups 
                                            ORDER BY data_backup DESC 
                                            LIMIT 5
                                        ");
                                        while ($backup = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($backup['data_backup'])); ?></td>
                                        <td><?php echo ucfirst($backup['tipo']); ?></td>
                                        <td><?php echo formatarTamanhoArquivo($backup['tamanho']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $backup['status'] === 'sucesso' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($backup['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($backup['status'] === 'sucesso'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="baixarBackup('<?php echo $backup['id']; ?>')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar histórico de backups.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal de Teste de E-mail -->
<div class="modal fade" id="testeEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Testar Configurações de E-mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">E-mail para Teste</label>
                    <input type="email" class="form-control" id="emailTeste" required>
                    <div class="form-text">
                        Um e-mail de teste será enviado para este endereço.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarEmailTeste()">
                    <i class="fas fa-paper-plane me-2"></i>Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos -->
<script>
// Função para testar configurações de e-mail
function testarEmail() {
    const modal = new bootstrap.Modal(document.getElementById('testeEmailModal'));
    modal.show();
}

// Função para enviar e-mail de teste
async function enviarEmailTeste() {
    const email = document.getElementById('emailTeste').value;
    
    if (!email) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Por favor, informe um e-mail válido.'
        });
        return;
    }

    try {
        const response = await fetch('testar_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (!data.sucesso) {
            throw new Error(data.mensagem);
        }

        bootstrap.Modal.getInstance(document.getElementById('testeEmailModal')).hide();
        
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: 'E-mail de teste enviado com sucesso.'
        });
        
    } catch (erro) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao enviar e-mail de teste.'
        });
    }
}

// Função para fazer backup manual
async function fazerBackup() {
    try {
        Swal.fire({
            title: 'Realizando backup...',
            text: 'Por favor, aguarde.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('backup_manual.php', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (!data.sucesso) {
            throw new Error(data.mensagem);
        }

        Swal.fire({
            icon: 'success',
            title: 'Backup Realizado!',
            text: 'O backup foi concluído com sucesso.',
            confirmButtonText: 'Download',
            showCancelButton: true,
            cancelButtonText: 'Fechar'
        }).then((result) => {
            if (result.isConfirmed) {
                baixarBackup(data.backup_id);
            }
        });
        
    } catch (erro) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: erro.message || 'Erro ao realizar backup.'
        });
    }
}

// Função para baixar backup
function baixarBackup(id) {
    window.location.href = `download_backup.php?id=${id}`;
}

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