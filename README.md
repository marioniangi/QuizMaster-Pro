# QuizMaster Pro 🎯

## Status: 🚧 Em Desenvolvimento

> **Nota:** Este projeto está atualmente em desenvolvimento ativo. Estamos trabalhando na resolução de alguns problemas do sistema e otimização de desempenho. Algumas funcionalidades podem não estar totalmente operacionais.

## Descrição

QuizMaster Pro é uma aplicação de quiz interativa desenvolvida como parte da disciplina de Engenharia de Software I no ISPTEC. O sistema apresenta uma interface dinâmica de quiz com múltiplos modos de jogo, painel administrativo para gestão de perguntas e feedback em tempo real para os jogadores.

## Funcionalidades

- 🎮 Múltiplos modos de jogo (incluindo desafio contra o tempo)
- 👑 Sistema de ranking em tempo real
- 🔐 Painel administrativo seguro para gestão de perguntas
- 📊 Sistema de feedback e pontuação imediata
- 🎯 Seleção aleatória de perguntas
- 📱 Design responsivo para todos os dispositivos

## Tecnologias Utilizadas

- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: PHP
- Banco de Dados: MySQL
- Adicional: AJAX para operações assíncronas

## Estrutura do Projeto

```
Copyquizmaster-pro/
├── admin/               # Painel administrativo
├── assets/             # Recursos estáticos (CSS, JS, imagens)
├── banco/              # Scripts SQL e backups
├── includes/           # Arquivos PHP compartilhados
├── logs/              # Logs do sistema
├── index.php          # Página inicial
├── jogar.php          # Interface do quiz
├── carregar_perguntas.php  # API para carregar perguntas
├── registrar_resposta.php  # API para registrar respostas
├── salvar_resultado.php    # API para salvar resultados
└── ranking.php        # Página de ranking
```
## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/marioniangi/QuizMaster-Pro.git
   ```

2. Importe o banco de dados:
   ```bash
   mysql -u seu_usuario -p seu_banco < banco/quiz_db.sql
   ```

3. Configure a conexão com o banco:
   - Acesse `includes/config.php`
   - Atualize as credenciais do banco

4. Configure o servidor web:
   - Aponte seu servidor web para o diretório do projeto
   - Certifique-se que PHP 7.4+ está instalado
   - Configure as permissões necessárias

5. Acesse a aplicação:
   - Jogo: `http://localhost/quiz`
   - Painel Admin: `http://localhost/quiz/admin`

## Desenvolvimento

Para contribuir com o projeto:

1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/NovaFuncionalidade`)
3. Faça commit das alterações (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/NovaFuncionalidade`)
5. Abra um Pull Request

🐛 Status e Problemas Conhecidos

🔄 Em Desenvolvimento: Correção do sistema de salvamento de respostas
⚠️ Bug Conhecido: Erro ao salvar respostas no banco de dados
📊 Planejado: Otimização das consultas SQL

## Problemas Conhecidos

- Otimização das consultas ao banco de dados para melhor desempenho
- Implementação de medidas adicionais de segurança no painel admin
- Resolução de inconsistências na interface do jogo

## Licença

Este projeto está licenciado sob a Licença MIT - consulte o arquivo [LICENSE](LICENSE) para detalhes.

## Colaboradores

- Mário Niangi - Líder do Projeto & Desenvolvedor

## Agradecimentos
- Todos os colaboradores e testadores
