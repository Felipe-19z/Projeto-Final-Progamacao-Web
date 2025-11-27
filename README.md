# 💰 Controle de Gastos - Sistema de Gestão Financeira

Um sistema web completo para controle e análise de gastos pessoais, com dashboard intuitivo, gráficos interativos e painel de administração.

## 📋 Características

### 🌐 Seção Pública
- ✅ **Página de Loading** com animação CSS (5 segundos)
- ✅ **Sistema de Autenticação** (Login/Registro)
- ✅ **Dashboard Dinâmico** com boas-vindas personalizadas
- ✅ **Tutorial Interativo** para novos usuários
- ✅ **Adicionar Gastos Personalizados** (categorias customizáveis)
- ✅ **Gráficos Circulares (Donut)** com análise de gastos vs. saldo
- ✅ **Filtros por Período** (Dia, Semana, Mês, Ano)
- ✅ **Configurações de Tema** (cores personalizáveis)
- ✅ **Sistema de Ajuda** com formulário de suporte

### 👨‍💼 Seção Admin
- ✅ **Painel de Administração** com estatísticas
- ✅ **CRUD Completo de Usuários** (Criar, Ler, Atualizar, Deletar)
- ✅ **Auditoria de Acessos** (data/hora dos logins)
- ✅ **Exclusão com Justificativa** (registro permanente)
- ✅ **Visualização de Histórico** de cada usuário
- ✅ **Gerenciamento de Mensagens** de suporte

## 🚀 Instalação

### Pré-requisitos
- PHP 7.4+
- MySQL/MariaDB
- Apache/Nginx
- Navegador moderno

### Passo 1: Extrair Arquivos
```bash
# Copiar pasta do projeto para seu servidor web
# Ex: C:/xampp/htdocs/ ou /var/www/html/
```

### Passo 2: Criar Banco de Dados
1. Abra o **phpMyAdmin** (geralmente em `http://localhost/phpmyadmin`)
2. Crie um novo banco de dados chamado `controle_gastos`
3. Copie todo o conteúdo do arquivo `database.sql`
4. Cole no phpMyAdmin (aba SQL) e execute

**Ou execute via terminal:**
```bash
mysql -u root -p < database.sql
```

### Passo 3: Configurar Banco de Dados
Edite o arquivo `config.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Sua senha do MySQL
define('DB_NAME', 'controle_gastos');
```

### Passo 4: Acessar o Sistema
1. Acesse `http://localhost/Projeto-Final/` (ou sua URL)
2. Você verá a página de loading
3. Após 5 segundos, será redirecionado para login

## 📁 Estrutura de Pastas

```
Projeto-Final/
├── index.php                    # Dashboard principal
├── login.php                    # Página de login/registro
├── loading.html                 # Página de carregamento
├── configuracoes.php            # Configurações do usuário
├── ajuda.php                    # Formulário de suporte
├── config.php                   # Configuração do banco (EDITAR)
├── database.sql                 # Script SQL do banco
│
├── api/
│   ├── login.php               # API de autenticação
│   ├── registrar.php           # API de registro
│   ├── categorias.php          # API de categorias de gastos
│   ├── gastos.php              # API de gastos
│   ├── grafico.php             # API de gráficos
│   └── logout.php              # API de logout
│
└── admin/
    ├── index.php               # Dashboard admin
    ├── listar-usuarios.php     # Listar todos os usuários
    ├── ler-usuarios.php        # Ver detalhes do usuário
    ├── criar-usuarios.php      # Criar novo usuário
    ├── deletar-usuarios.php    # Deletar usuário com auditoria
    ├── mensagens.php           # Ver mensagens de ajuda
    └── auditoria.php           # Ver auditoria de exclusões
```

## 👤 Contas Padrão

### Admin Padrão
- **Email:** admin@email.com (criar manualmente ou via `criar-usuarios.php`)
- **ID:** 1 (primeiro usuário criado)

> **Nota:** O sistema considera o usuário com ID=1 como admin automaticamente.

## 🔑 Funcionalidades Principais

### 📊 Dashboard
- Visualização de renda mensal
- Gráfico de gastos vs. saldo
- Lista de últimos gastos
- Filtros por período
- Tutorial interativo

### 💸 Adicionar Gastos
- Criar categorias personalizadas
- Registrar valor, data e hora
- Adicionar descrição do gasto
- Categorias com cores customizáveis

### 🎨 Personalização
- Alterar cor de fundo
- Personalizar cores dos gráficos
- Salvar preferências por usuário
- Tema claro/escuro

### 📋 Ajuda
- Formulário de contato
- Registro em banco de dados
- Gerenciamento pelo admin

### 👨‍💼 Admin
- Ver estatísticas gerais
- Gerenciar usuários
- Registrar acessos dos usuários
- Auditoria de exclusões
- Responder mensagens de suporte

## 🔐 Segurança

- ✅ Senhas com hash bcrypt
- ✅ Validação de entrada (sanitização)
- ✅ Verificação de login em todas as páginas protegidas
- ✅ Separação entre usuário comum e admin
- ✅ Logs de acesso
- ✅ Auditoria de exclusões

## 🌐 Endpoints da API

### Autenticação
- `POST /api/login.php` - Fazer login
- `POST /api/registrar.php` - Registrar nova conta
- `POST /api/logout.php` - Fazer logout

### Gastos
- `GET /api/gastos.php?filtro=mes` - Listar gastos (dia/semana/mes/ano)
- `POST /api/gastos.php` - Criar novo gasto

### Categorias
- `GET /api/categorias.php` - Listar categorias
- `POST /api/categorias.php` - Criar/deletar categorias

### Gráficos
- `GET /api/grafico.php?filtro=mes` - Dados para gráfico

## 📱 Responsividade

- ✅ Desktop (1920px+)
- ✅ Tablet (768px - 1024px)
- ✅ Mobile (< 768px)

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Gráficos:** Chart.js
- **Protocolo:** HTTPS (recomendado em produção)

## 📝 Notas Importantes

1. **Primeira Execução:** O primeiro usuário será o admin (ID=1)
2. **Backup:** Faça backup regular do banco de dados
3. **Senha:** Use senhas fortes em produção
4. **HTTPS:** Ative SSL em produção
5. **Permissões:** Certifique-se de que o servidor web tem permissão de escrita na pasta

## 🐛 Troubleshooting

### Erro: "Erro ao conectar ao banco de dados"
- Verifique se MySQL está rodando
- Confira credenciais em `config.php`
- Verifique se banco `controle_gastos` foi criado

### Erro: "Página em branco"
- Verifique logs do PHP (`php_error.log`)
- Certifique-se que PHP está ativado no servidor

### Usuário não consegue fazer login
- Verifique se usuário foi criado no banco
- Confirme se a senha está correta (case-sensitive)

## 📞 Suporte

Para problemas ou dúvidas, utilize o formulário de ajuda dentro do sistema (aba Ajuda).

## 📄 Licença

Este projeto é fornecido como está para fins educacionais e pessoais.

---

**Desenvolvido com ❤️ para gestão financeira pessoal**

**Última atualização:** Novembro 2025
