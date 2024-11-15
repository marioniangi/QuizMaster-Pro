<?php
session_start();
$titulo_pagina = 'Início';

// Incluir arquivos necessários
require_once 'includes/config.php';     // Configurações gerais
require_once 'includes/conexao.php';    // Conexão com o banco de dados
require_once 'includes/funcoes.php';    // Funções auxiliares

// Depois incluir o header
require_once 'includes/header.php';
?>

<div class="quiz-container fade-in">
    <div class="text-center mb-5">
        <h1 class="display-4 mb-3">Bem-vindo ao Quiz Interativo!</h1>
        <p class="lead">Teste seus conhecimentos e divirta-se aprendendo</p>
    </div>

    <!-- Seção de Modos de Jogo -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="categoria-card text-center">
                <i class="fas fa-clock fa-3x mb-3" style="color: var(--cor-primaria)"></i>
                <h3>Modo Clássico</h3>
                <p>Responda perguntas sem limite de tempo</p>
                <a href="jogar.php?modo=classico" class="btn btn-quiz">Jogar Agora</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="categoria-card text-center">
                <i class="fas fa-bolt fa-3x mb-3" style="color: var(--cor-secundaria)"></i>
                <h3>Contra o Tempo</h3>
                <p>Teste sua rapidez com limite de tempo</p>
                <a href="jogar.php?modo=tempo" class="btn btn-quiz">Jogar Agora</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="categoria-card text-center">
                <i class="fas fa-trophy fa-3x mb-3" style="color: var(--cor-acento)"></i>
                <h3>Modo Desafio</h3>
                <p>Perguntas mais difíceis, mais pontos</p>
                <a href="jogar.php?modo=desafio" class="btn btn-quiz">Jogar Agora</a>
            </div>
        </div>
    </div>

    <!-- Seção de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Estatísticas Globais</h2>
        </div>
        <?php
        try {
            // Buscar estatísticas atualizadas
            $stmt = $conexao->query("
                SELECT 
                    COUNT(DISTINCT jogador_id) as total_jogadores,
                    COUNT(*) as total_partidas,
                    COALESCE(AVG(pontuacao), 0) as media_pontos,
                    COALESCE(MAX(pontuacao), 0) as maior_pontuacao,
                    SUM(acertos) as total_acertos,
                    SUM(total_perguntas) as total_perguntas
                FROM partidas 
                WHERE status = 'finalizada'
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular taxa de acertos global
            $taxa_acertos = $stats['total_perguntas'] > 0 
                ? ($stats['total_acertos'] / $stats['total_perguntas']) * 100 
                : 0;

        } catch(PDOException $e) {
            registrar_log('erro', 'Erro ao buscar estatísticas: ' . $e->getMessage());
            $stats = [
                'total_jogadores' => 0,
                'total_partidas' => 0,
                'media_pontos' => 0,
                'maior_pontuacao' => 0
            ];
            $taxa_acertos = 0;
        }
        ?>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-users fa-2x mb-2" style="color: var(--cor-primaria)"></i>
                <h4><?php echo number_format($stats['total_jogadores']); ?></h4>
                <p>Jogadores</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-star fa-2x mb-2" style="color: var(--cor-secundaria)"></i>
                <h4><?php echo number_format($stats['media_pontos'], 1); ?></h4>
                <p>Média de Pontos</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-gamepad fa-2x mb-2" style="color: var(--cor-acento)"></i>
                <h4><?php echo number_format($stats['total_partidas']); ?></h4>
                <p>Partidas Jogadas</p>
            </div>
        </div>
    </div>

    <!-- Seção Como Jogar -->
    <div class="como-jogar mt-5">
        <h2 class="text-center mb-4">Como Jogar</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-1 fa-2x mb-3" style="color: var(--cor-primaria)"></i>
                    <h5>Escolha um Modo</h5>
                    <p>Selecione entre os diferentes modos de jogo disponíveis</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-2 fa-2x mb-3" style="color: var(--cor-primaria)"></i>
                    <h5>Responda</h5>
                    <p>Leia com atenção e escolha a melhor resposta</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-3 fa-2x mb-3" style="color: var(--cor-primaria)"></i>
                    <h5>Pontue</h5>
                    <p>Ganhe pontos por cada resposta correta</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-4 fa-2x mb-3" style="color: var(--cor-primaria)"></i>
                    <h5>Compare</h5>
                    <p>Veja sua posição no ranking global</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>