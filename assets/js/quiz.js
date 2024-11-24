class Quiz {
    constructor(config) {
        this.config = config;
        this.pontuacao = 0;
        this.acertos = 0;
        this.perguntaAtual = 0;
        this.totalPerguntas = 0;
        this.tempoInicio = null;
        this.timerInterval = null;
        this.perguntas = [];
        this.partida_id = null;
        this.tempoRestante = config.tempoLimite;
        
        // Elementos do DOM
        this.containerQuiz = document.getElementById('quiz-container');
        this.containerPergunta = document.getElementById('pergunta-container');
        this.containerOpcoes = document.getElementById('opcoes-container');
        this.btnProxima = document.getElementById('btn-proxima');
        this.spanPontuacao = document.getElementById('pontuacao');
        this.spanContador = document.getElementById('contador-perguntas');
        this.containerResultados = document.getElementById('resultados-container');
        
        // Inicializar
        this.iniciarQuiz();
    }

    async iniciarQuiz() {
        try {
            console.log('Iniciando quiz...');
            const response = await fetch('criar_partida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    modo: this.config.modo
                })
            });

            const data = await response.json();
            console.log('Resposta do servidor:', data);

            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            this.partida_id = data.partida_id;
            this.perguntas = data.perguntas;
            this.totalPerguntas = this.perguntas.length;
            this.tempoInicio = new Date();

            console.log('Partida iniciada:', {
                partida_id: this.partida_id,
                total_perguntas: this.totalPerguntas,
                modo: this.config.modo
            });

            this.carregarPergunta();
        } catch (error) {
            console.error('Erro ao iniciar quiz:', error);
            this.mostrarErro('Erro ao iniciar quiz: ' + error.message);
        }
    }

    async carregarPergunta() {
        if (this.perguntaAtual >= this.totalPerguntas) {
            await this.finalizarQuiz();
            return;
        }

        const pergunta = this.perguntas[this.perguntaAtual];
        
        // Atualizar contador
        if (this.spanContador) {
            this.spanContador.textContent = `Pergunta ${this.perguntaAtual + 1} de ${this.totalPerguntas}`;
        }

        // Carregar pergunta
        this.containerPergunta.innerHTML = `
            <h4 class="mb-4">${pergunta.texto}</h4>
        `;

        // Carregar opções
        let opcoesHTML = '';
        pergunta.respostas.forEach((resposta, index) => {
            opcoesHTML += `
                <div class="opcao-resposta" data-id="${resposta.id}" ${resposta.correta ? 'data-correta="true"' : ''} 
                     onclick="window.quiz.verificarResposta(this)">
                    <span class="opcao-letra">${String.fromCharCode(65 + index)}</span>
                    ${resposta.texto}
                </div>
            `;
        });
        this.containerOpcoes.innerHTML = opcoesHTML;

        // Resetar estado
        this.aguardandoProxima = false;
        this.btnProxima.disabled = true;
    }

    async registrarResposta(perguntaId, respostaId, tempoResposta) {
        if (!this.partida_id) {
            console.error('Partida ID não encontrado:', this.partida_id);
            throw new Error('Partida não inicializada corretamente');
        }

        try {
            console.log('Registrando resposta:', {
                partida_id: this.partida_id,
                pergunta_id: perguntaId,
                resposta_id: respostaId,
                tempo_resposta: tempoResposta
            });

            const response = await fetch('registrar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    partida_id: this.partida_id,
                    pergunta_id: perguntaId,
                    resposta_id: respostaId,
                    tempo_resposta: tempoResposta
                })
            });

            const data = await response.json();
            console.log('Resposta do servidor:', data);

            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            return data;
        } catch (error) {
            console.error('Erro ao registrar resposta:', error);
            throw error;
        }
    }

    async verificarResposta(opcaoElement) {
        if (this.aguardandoProxima) return;
    
        const respostaId = opcaoElement.dataset.id;
        const perguntaId = this.perguntas[this.perguntaAtual].id;
        const tempoResposta = this.calcularTempoResposta();
    
        try {
            const resultado = await this.registrarResposta(perguntaId, respostaId, tempoResposta);
            
            if (resultado.correta) {
                this.acertos++;
                this.pontuacao += resultado.pontos;
                this.atualizarPontuacao();
                opcaoElement.classList.add('correta');
                // Mostrar feedback positivo
                Swal.fire({
                    icon: 'success',
                    title: 'Muito bem!',
                    text: resultado.feedback,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                opcaoElement.classList.add('incorreta');
                // Mostrar resposta correta
                const opcaoCorreta = document.querySelector(`[data-correta="true"]`);
                if (opcaoCorreta) {
                    opcaoCorreta.classList.add('correta');
                }
                // Mostrar feedback negativo
                Swal.fire({
                    icon: 'error',
                    title: 'Ops!',
                    text: resultado.feedback,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
    
            // Desabilitar outras opções
            const opcoes = document.querySelectorAll('.opcao-resposta');
            opcoes.forEach(opcao => opcao.style.pointerEvents = 'none');
    
            this.aguardandoProxima = true;
            this.btnProxima.disabled = false;
    
        } catch (error) {
            console.error('Erro ao verificar resposta:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao verificar a resposta'
            });
        }
    }

    async finalizarQuiz() {
        const dadosFinais = {
            modo: this.config.modo,
            pontuacao: this.pontuacao,
            acertos: this.acertos,
            total_perguntas: this.totalPerguntas,
            tempo_total: Math.floor((new Date() - this.tempoInicio) / 1000)
        };
    
        console.log('Salvando resultado...', dadosFinais);
    
        try {
            const response = await fetch('salvar_resultado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosFinais)
            });
    
            const data = await response.json();
            console.log('Resposta do servidor:', data);
    
            // Mostrar resultado final
            const containerResultados = document.getElementById('resultados-container');
            containerResultados.classList.remove('d-none');
            
            let mensagem = `
                <div class="text-center mb-4">
                    <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                    <h2>Quiz Finalizado!</h2>
                    <p class="lead">Você acertou ${this.acertos} de ${this.totalPerguntas} perguntas</p>
                    <h3 class="my-3">Pontuação: ${this.pontuacao} pontos</h3>
                    <p>Tempo total: ${formatarTempo(dadosFinais.tempo_total)}</p>
                </div>
                <div class="text-center mt-4">
                    <button onclick="window.location.reload()" class="btn btn-primary me-2">
                        <i class="fas fa-redo me-2"></i>Jogar Novamente
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-2"></i>Voltar ao Início
                    </a>
                </div>
            `;
            
            containerResultados.innerHTML = mensagem;
            document.getElementById('quiz-container').classList.add('d-none');
    
        } catch (error) {
            console.error('Erro ao finalizar quiz:', error);
            alert('Erro ao salvar resultado: ' + error.message);
        }
    }

    calcularTempoResposta() {
        return Math.floor((new Date() - this.tempoInicio) / 1000);
    }

    atualizarPontuacao() {
        if (this.spanPontuacao) {
            this.spanPontuacao.textContent = this.pontuacao;
        }
    }

    mostrarErro(mensagem) {
        console.error(mensagem);
        Swal.fire({
            icon: 'error',
            title: 'Ops! Ocorreu um erro',
            text: mensagem,
            confirmButtonText: 'Tentar Novamente'
        }).then(() => {
            location.reload();
        });
    }

    proximaPergunta() {
        this.perguntaAtual++;
        this.carregarPergunta();
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    const btnIniciar = document.getElementById('btn-iniciar');
    if (btnIniciar) {
        btnIniciar.addEventListener('click', () => {
            document.getElementById('tela-inicial').classList.add('d-none');
            document.getElementById('quiz-container').classList.remove('d-none');
            window.quiz = new Quiz(QUIZ_CONFIG);
        });
    }

    // Adicionar listener para o botão próxima
    const btnProxima = document.getElementById('btn-proxima');
    if (btnProxima) {
        btnProxima.addEventListener('click', () => {
            if (window.quiz) {
                window.quiz.proximaPergunta();
            }
        });
    }
});