// Classe para gerenciar as perguntas
class GerenciadorPerguntas {
    constructor() {
        // Modais
        this.modalAdd = new bootstrap.Modal('#addPerguntaModal');
        this.modalView = new bootstrap.Modal('#viewPerguntaModal');
        this.modalEdit = new bootstrap.Modal('#editPerguntaModal');
        
        // Forms
        this.formAdd = document.getElementById('formAddPergunta');
        this.formEdit = document.getElementById('formEditPergunta');
        
        // Containers de opções
        this.opcoesContainer = document.getElementById('opcoesContainer');
        this.opcoesContainerEdit = document.getElementById('opcoesContainerEdit');
        
        // Elementos de categoria
        this.selectCategoria = document.querySelector('select[name="categoria"]');
        this.divNovaCategoria = document.getElementById('novaCategoriaGroup');
        
        // Bindings
        this.addOpcao = this.addOpcao.bind(this);
        this.removeOpcao = this.removeOpcao.bind(this);
        this.handleSubmitAdd = this.handleSubmitAdd.bind(this);
        this.handleSubmitEdit = this.handleSubmitEdit.bind(this);
        this.carregarPergunta = this.carregarPergunta.bind(this);
        this.confirmarExclusao = this.confirmarExclusao.bind(this);
        this.toggleNovaCategoria = this.toggleNovaCategoria.bind(this);
        
        this.inicializarEventListeners();
    }

    // Inicializa todos os event listeners
    inicializarEventListeners() {
        // Botões de adicionar opção
        document.getElementById('btnAddOpcao').addEventListener('click', () => this.addOpcao(this.opcoesContainer));
        document.getElementById('btnAddOpcaoEdit').addEventListener('click', () => this.addOpcao(this.opcoesContainerEdit));
        
        // Forms
        this.formAdd.addEventListener('submit', this.handleSubmitAdd);
        this.formEdit.addEventListener('submit', this.handleSubmitEdit);
        
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
        
        // Categoria
        this.selectCategoria.addEventListener('change', this.toggleNovaCategoria);
        
        // Delegação de eventos para remover opções
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-remove-opcao') || e.target.closest('.btn-remove-opcao')) {
                const btn = e.target.matches('.btn-remove-opcao') ? e.target : e.target.closest('.btn-remove-opcao');
                this.removeOpcao(btn);
            }
        });
    }

    // Adiciona nova opção de resposta
    addOpcao(container) {
        const opcoesCount = container.children.length;
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
                    <button type="button" class="btn btn-danger btn-remove-opcao">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="invalid-feedback">
                    Por favor, preencha a opção de resposta.
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', opcaoHtml);
    }

    // Remove opção de resposta
    removeOpcao(btn) {
        const container = btn.closest('.opcao-group').parentElement;
        btn.closest('.opcao-group').remove();
        this.reordenarOpcoes(container);
    }

    // Reordena os valores dos radios após remoção
    reordenarOpcoes(container) {
        container.querySelectorAll('input[type="radio"]').forEach((radio, index) => {
            radio.value = index;
        });
    }

    // Toggle campo de nova categoria
    toggleNovaCategoria() {
        this.divNovaCategoria.style.display = 
            this.selectCategoria.value === 'nova' ? 'block' : 'none';
            
        const inputNovaCategoria = this.divNovaCategoria.querySelector('input');
        inputNovaCategoria.required = this.selectCategoria.value === 'nova';
    }

    // Submit do formulário de adicionar
    async handleSubmitAdd(e) {
        e.preventDefault();
        
        if (!this.formAdd.checkValidity()) {
            this.formAdd.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(this.formAdd);
            const response = await fetch('adicionar_pergunta.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Pergunta adicionada com sucesso.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
            
        } catch (erro) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao adicionar pergunta.'
            });
        }
    }

    // Carrega pergunta para visualização ou edição
    async carregarPergunta(id, modo) {
        try {
            const response = await fetch(`buscar_pergunta.php?id=${id}`);
            const data = await response.json();
            
            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            if (modo === 'view') {
                this.preencherModalVisualizacao(data.pergunta);
                this.modalView.show();
            } else {
                this.preencherModalEdicao(data.pergunta);
                this.modalEdit.show();
            }
            
        } catch (erro) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao carregar pergunta.'
            });
        }
    }

    // Preenche modal de visualização
    preencherModalVisualizacao(pergunta) {
        const container = document.querySelector('.view-pergunta-content');
        
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
            
            ${pergunta.feedback ? `
                <div class="mb-3">
                    <h5>Feedback:</h5>
                    <p class="text-muted">${pergunta.feedback}</p>
                </div>
            ` : ''}
            
            <small class="text-muted">
                Criada em: ${new Date(pergunta.data_criacao).toLocaleDateString('pt-BR')}
            </small>
        `;
    }

    // Preenche modal de edição
    preencherModalEdicao(pergunta) {
        const form = this.formEdit;
        
        // Preencher campos básicos
        form.querySelector('input[name="id"]').value = pergunta.id;
        form.querySelector('textarea[name="pergunta"]').value = pergunta.pergunta;
        form.querySelector('select[name="categoria"]').value = pergunta.categoria;
        form.querySelector('select[name="dificuldade"]').value = pergunta.dificuldade;
        form.querySelector('input[name="pontos"]').value = pergunta.pontos;
        form.querySelector('textarea[name="feedback"]').value = pergunta.feedback || '';
        
        // Limpar e preencher opções
        this.opcoesContainerEdit.innerHTML = '';
        pergunta.opcoes.forEach((opcao, index) => {
            this.addOpcao(this.opcoesContainerEdit);
            const ultimaOpcao = this.opcoesContainerEdit.lastElementChild;
            ultimaOpcao.querySelector('input[type="text"]').value = opcao.texto;
            ultimaOpcao.querySelector('input[type="radio"]').checked = opcao.correta;
        });
    }

    // Submit do formulário de edição
    async handleSubmitEdit(e) {
        e.preventDefault();
        
        if (!this.formEdit.checkValidity()) {
            this.formEdit.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(this.formEdit);
            const response = await fetch('editar_pergunta.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.sucesso) {
                throw new Error(data.mensagem);
            }

            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Pergunta atualizada com sucesso.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
            
        } catch (erro) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: erro.message || 'Erro ao atualizar pergunta.'
            });
        }
    }

    // Confirma e executa exclusão
    async confirmarExclusao(id) {
        const result = await Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('excluir_pergunta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (!data.sucesso) {
                    throw new Error(data.mensagem);
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Excluída!',
                    text: 'Pergunta excluída com sucesso.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
                
            } catch (erro) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: erro.message || 'Erro ao excluir pergunta.'
                });
            }
        }
    }
}

// Inicialização quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    const gerenciador = new GerenciadorPerguntas();
});