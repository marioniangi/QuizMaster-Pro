class Quiz {
    constructor(modo) {
        this.modo = modo;
        this.perguntas = [];
        this.perguntaAtual = 0;
        this.pontuacao = 0;
        this.acertos = 0;
        this.temporizador = null;
        this.tempoRestante = modo === 'tempo' ? 30 : null;
        
        // Elementos do DOM
        this.containerQuiz = document.getElementById('quiz-container');
        this.containerPergunta = document.getElementById('pergunta-container');
        this.containerOpcoes = document.getElementById('opcoes-container');
        this.containerPontuacao = document.getElementById('pontuacao');
        this.containerTempo = document.getElementById('tempo-container');
        this.btnProxima = document.getElementById('btn-proxima');

        if (this.btnProxima) {
            this.btnProxima.addEventListener('click', () => this.proximaPergunta());
        }
    }

    async iniciarQuiz() {
        try {
            const telaInicial = document.getElementById('tela-inicial');
            if (telaInicial) {
                telaInicial.classList.add('d-none');
            }

            this.containerQuiz.classList.remove('d-none');
            if (this.modo === 'tempo') {
                this.containerTempo.classList.remove('d-none');
            }

            const response = await fetch('carregar_perguntas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    modo: this.modo,
                    jogador_id: QUIZ_CONFIG.jogadorId
                })
            });

            const data = await response.json();
            
            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro ao carregar perguntas');
            }

            this.perguntas = data.perguntas;
            this.partida_id = data.partida_id;
            this.carregarPergunta();

        } catch (erro) {
            console.error('Erro ao iniciar quiz:', erro);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao iniciar o quiz',
                text: erro.message || 'Tente novamente mais tarde.'
            });
        }
    }

    carregarPergunta() {
        if (this.perguntaAtual >= this.perguntas.length) {
            this.finalizarQuiz();
            return;
        }

        const pergunta = this.perguntas[this.perguntaAtual];

        // Atualizar contador
        document.getElementById('contador-perguntas').textContent = 
            `Pergunta ${this.perguntaAtual + 1} de ${this.perguntas.length}`;

        // Mostrar pergunta
        this.containerPergunta.innerHTML = `<h4>${pergunta.pergunta}</h4>`;

        // Mostrar opções
        this.containerOpcoes.innerHTML = pergunta.opcoes.map((opcao, index) => `
            <div class="opcao-resposta mb-3" onclick="quiz.verificarResposta(${index})">
                ${opcao.texto}
            </div>
        `).join('');

        // Desabilitar botão próxima
        if (this.btnProxima) {
            this.btnProxima.disabled = true;
        }

        // Iniciar temporizador no modo tempo
        if (this.modo === 'tempo') {
            this.pararTemporizador();
            this.tempoRestante = 30;
            this.iniciarTemporizador();
        }
    }

    verificarResposta(index) {
        this.pararTemporizador();

        const pergunta = this.perguntas[this.perguntaAtual];
        const opcoes = this.containerOpcoes.querySelectorAll('.opcao-resposta');
        
        const respostaCorreta = pergunta.opcoes.findIndex(opcao => opcao.correta);

        // Desabilitar todas as opções
        opcoes.forEach(opcao => {
            opcao.style.pointerEvents = 'none';
        });

        // Marcar resposta correta e incorreta
        opcoes[respostaCorreta].classList.add('correta');
        if (index !== respostaCorreta) {
            opcoes[index].classList.add('incorreta');
        }

        // Calcular pontuação
        if (index === respostaCorreta) {
            this.acertos++;
            let pontos = pergunta.pontos;
            
            // Bônus por tempo no modo tempo
            if (this.modo === 'tempo' && this.tempoRestante > 0) {
                const bonus = Math.floor(this.tempoRestante / 2);
                pontos += bonus;
            }
            
            this.pontuacao += pontos;
            this.containerPontuacao.textContent = this.pontuacao;
        }

        // Registrar resposta
        this.registrarResposta(index === respostaCorreta);

        // Habilitar botão próxima
        if (this.btnProxima) {
            this.btnProxima.disabled = false;
            if (this.perguntaAtual === this.perguntas.length - 1) {
                this.btnProxima.textContent = 'Finalizar Quiz';
            }
        }

        // Mostrar feedback
        Swal.fire({
            icon: index === respostaCorreta ? 'success' : 'error',
            title: index === respostaCorreta ? 'Correto!' : 'Incorreto!',
            text: this.modo === 'tempo' && index === respostaCorreta ? 
                  `+${Math.floor(this.tempoRestante / 2)} pontos bônus por rapidez!` : '',
            timer: 1500,
            showConfirmButton: false
        });
    }

    async registrarResposta(correta) {
        try {
            await fetch('registrar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    partida_id: this.partida_id,
                    pergunta_id: this.perguntas[this.perguntaAtual].id,
                    correta: correta,
                    tempo: this.modo === 'tempo' ? this.tempoRestante : null
                })
            });
        } catch (erro) {
            console.error('Erro ao registrar resposta:', erro);
        }
    }

    async finalizarQuiz() {
        this.pararTemporizador();
        
        try {
            console.log('Salvando resultado...', {
                modo: this.modo,
                pontuacao: this.pontuacao,
                acertos: this.acertos,
                total_perguntas: this.perguntas.length
            });

            const response = await fetch('salvar_resultado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    partida_id: this.partida_id,
                    modo: this.modo,
                    pontuacao: this.pontuacao,
                    acertos: this.acertos,
                    total_perguntas: this.perguntas.length
                })
            });

            const data = await response.json();
            console.log('Resposta do servidor:', data);

            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro ao salvar resultado');
            }

            const percentualAcertos = (this.acertos / this.perguntas.length) * 100;

            Swal.fire({
                icon: 'success',
                title: 'Quiz Finalizado!',
                html: `
                    <div class="text-center">
                        <h4 class="mb-3">Resultado Final</h4>
                        <p class="mb-2">Acertos: ${this.acertos} de ${this.perguntas.length} (${percentualAcertos.toFixed(1)}%)</p>
                        <h3 class="mb-3">${this.pontuacao} pontos</h3>
                        ${this.modo === 'tempo' ? '<p><small>Incluindo bônus por rapidez!</small></p>' : ''}
                        ${data.ranking ? `<p class="mt-3">Sua posição no ranking: ${data.ranking}º lugar</p>` : ''}
                    </div>
                `,
                confirmButtonText: 'Jogar Novamente',
                showCancelButton: true,
                cancelButtonText: 'Ver Ranking'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                } else {
                    window.location.href = 'ranking.php';
                }
            });

        } catch (erro) {
            console.error('Erro ao finalizar quiz:', erro);
            Swal.fire({
                icon: 'error',
                title: 'Erro ao salvar resultado',
                text: erro.message || 'Ocorreu um erro ao salvar seu resultado.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'index.php';
            });
        }
    }

    proximaPergunta() {
        this.perguntaAtual++;
        this.carregarPergunta();
    }

    pararTemporizador() {
        if (this.temporizador) {
            clearInterval(this.temporizador);
            this.temporizador = null;
        }
    }

    iniciarTemporizador() {
        if (this.modo !== 'tempo') return;

        this.atualizarBarraTempo();
        this.temporizador = setInterval(() => {
            this.tempoRestante--;
            this.atualizarBarraTempo();

            if (this.tempoRestante <= 0) {
                this.pararTemporizador();
                const opcoes = this.containerOpcoes.querySelectorAll('.opcao-resposta');
                if (opcoes.length > 0) {
                    this.verificarResposta(0);
                }
            }
        }, 1000);
    }

    atualizarBarraTempo() {
        if (!this.containerTempo) return;
        
        const porcentagem = (this.tempoRestante / 30) * 100;
        let corBarra = 'bg-success';
        
        if (porcentagem <= 25) {
            corBarra = 'bg-danger';
        } else if (porcentagem <= 50) {
            corBarra = 'bg-warning';
        }

        this.containerTempo.innerHTML = `
            <div class="progress" style="height: 20px;">
                <div class="progress-bar ${corBarra}" 
                     role="progressbar" 
                     style="width: ${porcentagem}%"
                     aria-valuenow="${this.tempoRestante}"
                     aria-valuemin="0"
                     aria-valuemax="30">
                    ${this.tempoRestante}s
                </div>
            </div>
        `;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    window.quiz = new Quiz(QUIZ_CONFIG.modo);
    
    const btnIniciar = document.getElementById('btn-iniciar');
    if (btnIniciar) {
        btnIniciar.addEventListener('click', () => quiz.iniciarQuiz());
    }
});