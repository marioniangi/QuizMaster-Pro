// Constantes
const DIFICULDADES = {
    'facil': 'Fácil',
    'medio': 'Médio',
    'dificil': 'Difícil'
};

// Classe para gerenciar as perguntas
class GerenciadorPerguntas {
    constructor() {
        // Modais
        this.modalAdd = new bootstrap.Modal(document.getElementById('addPerguntaModal'));
        this.modalView = new bootstrap.Modal(document.getElementById('viewPerguntaModal'));
        this.modalEdit = new bootstrap.Modal(document.getElementById('editPerguntaModal'));
        
        // Formulários
        this.formAdd = document.getElementById('formAddPergunta');
        this.formEdit = document.getElementById('formEditPergunta');
        
        // Containers de opções
        this.opcoesContainer = document.getElementById('opcoesContainer');
        this.opcoesContainerEdit = document.getElementById('opcoesContainerEdit');
        
        // Elementos de categoria
        this.selectCategoria = document.querySelector('select[name="categoria"]');
        this.divNovaCategoria = document.getElementById('novaCategoriaGroup');

        // Referência à pergunta atual sendo visualizada
        this.perguntaAtual = null;
        
        // Adicionar opções iniciais aos containers
        if (this.opcoesContainer) {
            this.addOpcao(this.opcoesContainer);
            this.addOpcao(this.opcoesContainer);
        }
        
        this.inicializarEventListeners();
    }

    inicializarEventListeners() {
        // Botões de adicionar opção
        const btnAddOpcao = document.getElementById('btnAddOpcao');
        const btnAddOpcaoEdit = document.getElementById('btnAddOpcaoEdit');
        
        if (btnAddOpcao) {
            btnAddOpcao.addEventListener('click', () => this.addOpcao(this.opcoesContainer));
        }
        
        if (btnAddOpcaoEdit) {
            btnAddOpcaoEdit.addEventListener('click', () => this.addOpcao(this.opcoesContainerEdit));
        }
        
        // Forms
        if (this.formAdd) {
            this.formAdd.addEventListener('submit', (e) => this.handleSubmitAdd(e));
        }
        
        if (this.formEdit) {
            this.formEdit.addEventListener('submit', (e) => this.handleSubmitEdit(e));
        }
        
        // Botões de ação da tabela
        document.querySelectorAll('.btn-visualizar').forEach(btn => {
            btn.addEventListener('click', () => this.carregarPergunta(btn.dataset.id, 'view'));
        });
        
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => this.carregarPergunta(btn.dataset.id, 'edit'));
        });
        
        document.querySelectorAll('.btn-excluir').forEach(btn => {
            btn.addEventListener('click', () => this.confirmarExclusao(btn.dataset.id));
        });

        // Botão editar no modal de visualização
        const btnEditarView = document.querySelector('.btn-editar-view');
        if (btnEditarView) {
            btnEditarView.addEventListener('click', () => this.editarDaVisualizacao());
        }
        
        // Categoria
        if (this.selectCategoria) {
            this.selectCategoria.addEventListener('change', () => this.toggleNovaCategoria());
        }
        
        // Delegação de eventos para remover opções
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-remove-opcao') || e.target.closest('.btn-remove-opcao')) {
                const btn = e.target.matches('.btn-remove-opcao') ? e.target : e.target.closest('.btn-remove-opcao');
                this.removeOpcao(btn);
            }
        });
    }
    // Método para adicionar opção de resposta
    addOpcao(container) {
        if (!container) return;
        
        const opcoesCount = container.querySelectorAll('.opcao-group').length;
        if (opcoesCount >= 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Limite de opções',
                text: 'Máximo de 6 opções permitidas.'
            });
            return;
        }

        const opcaoHtml = `
            <div class="opcao-group mb-3">
                <div class="input-group">
                    <div class="input-group-text">
                        <input type="radio" name="resposta_correta" value="${opcoesCount}" required>
                    </div>
                    <input type="text" class="form-control" name="opcoes[]" 
                           placeholder="Digite a opção de resposta" required>
                    ${opcoesCount > 1 ? `
                        <button type="button" class="btn btn-danger btn-remove-opcao">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="invalid-feedback">
                    Por favor, preencha a opção de resposta.
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', opcaoHtml);

        // Garantir que pelo menos uma opção esteja selecionada
        const radios = container.querySelectorAll('input[type="radio"]');
        if (radios.length === 1) {
            radios[0].checked = true;
        }
    }

    // Método para remover opção de resposta
    removeOpcao(btn) {
        const container = btn.closest('.opcao-group').parentElement;
        if (!container) return;

        const totalOpcoes = container.querySelectorAll('.opcao-group').length;
        if (totalOpcoes <= 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Mínimo de opções',
                text: 'É necessário ter pelo menos 2 opções de resposta.'
            });
            return;
        }

        const grupo = btn.closest('.opcao-group');
        const radioSelecionado = grupo.querySelector('input[type="radio"]').checked;
        
        grupo.remove();
        
        // Se removeu a opção selecionada, selecionar a primeira
        if (radioSelecionado) {
            const primeiroRadio = container.querySelector('input[type="radio"]');
            if (primeiroRadio) {
                primeiroRadio.checked = true;
            }
        }
        
        this.reordenarOpcoes(container);
    }

    // Reordena os valores dos radios após remoção
    reordenarOpcoes(container) {
        if (!container) return;
        
        container.querySelectorAll('.opcao-group').forEach((grupo, index) => {
            const radio = grupo.querySelector('input[type="radio"]');
            if (radio) {
                radio.value = index;
            }
        });
    }

    // Toggle campo de nova categoria
    toggleNovaCategoria() {
        if (!this.divNovaCategoria || !this.selectCategoria) return;
        
        const showNovaCategoria = this.selectCategoria.value === 'nova';
        this.divNovaCategoria.style.display = showNovaCategoria ? 'block' : 'none';
        
        const inputNovaCategoria = this.divNovaCategoria.querySelector('input');
        if (inputNovaCategoria) {
            inputNovaCategoria.required = showNovaCategoria;
            if (!showNovaCategoria) {
                inputNovaCategoria.value = '';
            }
        }
    }

    // Método para fazer requisições AJAX
    async fazerRequisicao(url, opcoes = {}) {
        try {
            const response = await fetch(url, {
                ...opcoes,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...opcoes.headers
                }
            });
            
            if (!response.ok) {
                throw new Error(`Erro HTTP! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.sucesso && data.mensagem) {
                throw new Error(data.mensagem);
            }
            
            return data;
            
        } catch (erro) {
            console.error('Erro na requisição:', erro);
            throw erro;
        }
    }

    // Método para carregar pergunta (visualização ou edição)
    async carregarPergunta(id, modo) {
        try {
            const response = await fetch(`buscar_pergunta.php?id=${id}`);
            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro ao carregar pergunta.');
            }

            // Armazenar a pergunta atual
            this.perguntaAtual = data.pergunta;

            if (modo === 'view') {
                this.preencherModalVisualizacao(data.pergunta);
                this.modalView.show();
            } else {
                this.preencherModalEdicao(data.pergunta);
                this.modalEdit.show();
            }
        } catch (erro) {
            console.error('Erro ao carregar pergunta:', erro);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao carregar pergunta.'
            });
        }
    }

    // Nova função para lidar com a transição de visualização para edição
    editarDaVisualizacao() {
        if (this.perguntaAtual) {
            // Fechar modal de visualização
            this.modalView.hide();
            
            // Pequeno delay para evitar sobreposição de modais
            setTimeout(() => {
                // Preencher e abrir modal de edição
                this.preencherModalEdicao(this.perguntaAtual);
                this.modalEdit.show();
            }, 300);
        }
    }
    // Método para preencher modal de visualização
    preencherModalVisualizacao(pergunta) {
        if (!pergunta) return;

        const container = document.querySelector('.view-pergunta-content');
        if (!container) return;

        const dificuldadeClasses = {
            'facil': 'success',
            'medio': 'warning',
            'dificil': 'danger'
        };

        container.innerHTML = `
            <div class="mb-4">
                <h4>${pergunta.pergunta}</h4>
                <div class="mt-2">
                    <span class="badge bg-primary">${pergunta.categoria}</span>
                    <span class="badge bg-${dificuldadeClasses[pergunta.dificuldade]}">
                        ${DIFICULDADES[pergunta.dificuldade]}
                    </span>
                    <span class="badge bg-info">${pergunta.pontos} pontos</span>
                </div>
            </div>
            
            <div class="mb-4">
                <h5>Opções de Resposta:</h5>
                <div class="list-group">
                    ${pergunta.opcoes.map(opcao => `
                        <div class="list-group-item ${opcao.correta ? 'list-group-item-success' : ''}">
                            <i class="fas fa-${opcao.correta ? 'check text-success' : 'times text-muted'} me-2"></i>
                            ${opcao.texto}
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="mb-4">
                <h5>Estatísticas:</h5>
                <div class="list-group">
                    <div class="list-group-item">
                        <i class="fas fa-chart-bar me-2"></i>
                        Taxa de Acerto: <strong>${pergunta.taxa_acerto || 0}%</strong>
                    </div>
                    <div class="list-group-item">
                        <i class="fas fa-users me-2"></i>
                        Total de Usos: <strong>${pergunta.total_usos || 0}</strong>
                    </div>
                </div>
            </div>
            
            <small class="text-muted">
                Criada em: ${new Date(pergunta.data_criacao).toLocaleDateString('pt-BR')}
            </small>
        `;
    }

    // Método para preencher modal de edição
    preencherModalEdicao(pergunta) {
        if (!this.formEdit || !pergunta) return;
        
        // Preencher campos básicos
        this.formEdit.querySelector('input[name="id"]').value = pergunta.id;
        this.formEdit.querySelector('textarea[name="pergunta"]').value = pergunta.pergunta;
        this.formEdit.querySelector('select[name="categoria"]').value = pergunta.categoria;
        this.formEdit.querySelector('select[name="dificuldade"]').value = pergunta.dificuldade;
        this.formEdit.querySelector('input[name="pontos"]').value = pergunta.pontos;
        
        // Limpar e preencher opções
        if (this.opcoesContainerEdit) {
            this.opcoesContainerEdit.innerHTML = '';
            pergunta.opcoes.forEach((opcao, index) => {
                this.addOpcao(this.opcoesContainerEdit);
                const ultimaOpcao = this.opcoesContainerEdit.lastElementChild;
                if (ultimaOpcao) {
                    const inputTexto = ultimaOpcao.querySelector('input[type="text"]');
                    const inputRadio = ultimaOpcao.querySelector('input[type="radio"]');
                    
                    if (inputTexto) {
                        inputTexto.value = opcao.texto;
                    }
                    if (inputRadio) {
                        inputRadio.value = index;
                        if (opcao.correta) {
                            inputRadio.checked = true;
                        }
                    }
                }
            });
        }
    }

    // Submit do formulário de adicionar
    async handleSubmitAdd(e) {
        e.preventDefault();
        
        try {
            // Verificar validade do formulário
            if (!this.formAdd.checkValidity()) {
                this.formAdd.classList.add('was-validated');
                return;
            }

            // Verificar se uma resposta correta foi selecionada
            const formData = new FormData(this.formAdd);
            if (!formData.get('resposta_correta')) {
                throw new Error('É necessário selecionar uma resposta correta.');
            }

            // Verificar se todas as opções estão preenchidas
            const opcoes = formData.getAll('opcoes[]');
            if (opcoes.length < 2) {
                throw new Error('São necessárias pelo menos 2 opções de resposta.');
            }

            if (opcoes.some(opcao => !opcao.trim())) {
                throw new Error('Todas as opções devem ser preenchidas.');
            }

            // Se categoria for 'nova', validar nova_categoria
            if (formData.get('categoria') === 'nova') {
                const novaCategoria = formData.get('nova_categoria');
                if (!novaCategoria || !novaCategoria.trim()) {
                    throw new Error('O nome da nova categoria é obrigatório.');
                }
            }

            // Enviar requisição
            const response = await this.fazerRequisicao('adicionar_pergunta.php', {
                method: 'POST',
                body: formData
            });

            // Mostrar mensagem de sucesso
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: response.mensagem || 'Pergunta adicionada com sucesso!',
                timer: 1500,
                showConfirmButton: false
            });

            // Fechar modal e recarregar página
            this.modalAdd.hide();
            window.location.reload();
            
        } catch (erro) {
            console.error('Erro ao adicionar pergunta:', erro);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao adicionar pergunta.'
            });
        }
    }
    // Submit do formulário de edição
    async handleSubmitEdit(e) {
        e.preventDefault();
        
        try {
            // Verificar validade do formulário
            if (!this.formEdit.checkValidity()) {
                this.formEdit.classList.add('was-validated');
                return;
            }

            // Verificar se uma resposta correta foi selecionada
            const formData = new FormData(this.formEdit);
            if (!formData.get('resposta_correta')) {
                throw new Error('É necessário selecionar uma resposta correta.');
            }

            // Verificar se todas as opções estão preenchidas
            const opcoes = formData.getAll('opcoes[]');
            if (opcoes.length < 2) {
                throw new Error('São necessárias pelo menos 2 opções de resposta.');
            }

            if (opcoes.some(opcao => !opcao.trim())) {
                throw new Error('Todas as opções devem ser preenchidas.');
            }

            // Enviar requisição
            const response = await this.fazerRequisicao('editar_pergunta.php', {
                method: 'POST',
                body: formData
            });

            // Mostrar mensagem de sucesso
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: response.mensagem || 'Pergunta atualizada com sucesso!',
                timer: 1500,
                showConfirmButton: false
            });

            // Fechar modal e recarregar página
            this.modalEdit.hide();
            window.location.reload();
            
        } catch (erro) {
            console.error('Erro ao editar pergunta:', erro);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao atualizar pergunta.'
            });
        }
    }

    // Confirmar e executar exclusão
    async confirmarExclusao(id) {
        if (!id) return;

        const result = await Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const response = await this.fazerRequisicao('excluir_pergunta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                await Swal.fire({
                    icon: 'success',
                    title: 'Excluída!',
                    text: response.mensagem || 'Pergunta excluída com sucesso!',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Recarregar página
                window.location.reload();
                
            } catch (erro) {
                console.error('Erro ao excluir pergunta:', erro);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: erro.message || 'Erro ao excluir pergunta.'
                });
            }
        }
    }

    // Método para mostrar mensagens/alertas
    mostrarMensagem(mensagem, tipo = 'info') {
        Swal.fire({
            icon: tipo,
            title: tipo.charAt(0).toUpperCase() + tipo.slice(1),
            text: mensagem,
            timer: tipo === 'success' ? 1500 : undefined,
            showConfirmButton: tipo !== 'success'
        });
    }
}

// Inicialização quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Criar instância do gerenciador
    const gerenciador = new GerenciadorPerguntas();

    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Configurações globais do SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Expor gerenciador globalmente para debug se necessário
    window.gerenciadorPerguntas = gerenciador;
});