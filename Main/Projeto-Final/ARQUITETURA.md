# 🏗️ Arquitetura Técnica - Controle de Gastos

## 📐 Visão Geral

Sistema web de controle de gastos pessoais com arquitetura MVC simplificada em PHP vanilla + MySQL.

```
┌─────────────────────────────────────────────────────────┐
│                   FRONTEND (Browser)                    │
│        HTML5 + CSS3 + JavaScript (Vanilla)              │
└────────────────────┬────────────────────────────────────┘
                     │ HTTPS/HTTP
┌────────────────────▼────────────────────────────────────┐
│              BACKEND (PHP 7.4+)                         │
│   - APIs REST JSON                                      │
│   - Autenticação com Session                            │
│   - Lógica de negócios                                  │
└────────────────────┬────────────────────────────────────┘
                     │ MySQLi
┌────────────────────▼────────────────────────────────────┐
│           DATABASE (MySQL/MariaDB)                      │
│      - Usuários + Logs de Acesso                        │
│      - Gastos + Categorias                              │
│      - Configurações + Mensagens                        │
└─────────────────────────────────────────────────────────┘
```

---

## 📁 Estrutura de Diretórios

```
Projeto-Final/
│
├── 📄 loading.html              # Página de carregamento (5s)
├── 📄 login.php                 # Login/Registro (HTML + JS)
├── 📄 index.php                 # Dashboard principal
├── 📄 configuracoes.php         # Customização de tema
├── 📄 ajuda.php                 # Formulário de suporte
│
├── 🔧 config.php                # Configuração do banco
├── 📊 database.sql              # Script SQL
├── 📋 README.md                 # Documentação
├── 📖 GUIA_USO.md               # Guia de uso
│
├── 📁 api/                      # APIs REST
│   ├── login.php                # POST /api/login.php
│   ├── registrar.php            # POST /api/registrar.php
│   ├── logout.php               # POST /api/logout.php
│   ├── categorias.php           # GET/POST categorias
│   ├── gastos.php               # GET/POST gastos
│   └── grafico.php              # GET dados gráfico
│
└── 👨‍💼 admin/                    # Painel administrativo
    ├── index.php                # Dashboard admin
    ├── listar-usuarios.php      # CRUD - Listar
    ├── ler-usuarios.php         # CRUD - Ver detalhes
    ├── criar-usuarios.php       # CRUD - Criar
    ├── deletar-usuarios.php     # CRUD - Deletar + Auditoria
    ├── mensagens.php            # Gerenciar suporte
    └── auditoria.php            # Auditoria de exclusões
```

---

## 🗄️ Schema do Banco de Dados

### Tabela: `usuarios`
```sql
├── id (INT, PK, AUTO_INCREMENT)
├── nome (VARCHAR 100)
├── email (VARCHAR 100, UNIQUE)
├── senha (VARCHAR 255, bcrypt hash)
├── renda_mensal (DECIMAL 10,2)
├── data_criacao (TIMESTAMP)
└── ativo (BOOLEAN)
```

### Tabela: `categorias`
```sql
├── id (INT, PK)
├── usuario_id (INT, FK → usuarios)
├── nome (VARCHAR 50)
├── cor_hex (VARCHAR 7, ex: #FF6B6B)
├── data_criacao (TIMESTAMP)
└── UNIQUE(usuario_id, nome)
```

### Tabela: `gastos`
```sql
├── id (INT, PK)
├── usuario_id (INT, FK)
├── categoria_id (INT, FK)
├── descricao (VARCHAR 255)
├── valor (DECIMAL 10,2)
├── data_gasto (DATE)
├── hora_gasto (TIME)
└── data_criacao (TIMESTAMP)
```

### Tabela: `logs_acesso`
```sql
├── id (INT, PK)
├── usuario_id (INT, FK)
├── data_acesso (TIMESTAMP)
└── ip_address (VARCHAR 45, IPv6)
```

### Tabela: `configuracoes_usuario`
```sql
├── id (INT, PK)
├── usuario_id (INT, FK, UNIQUE)
├── cor_fundo (VARCHAR 7, default #FFFFFF)
├── cor_gastos (VARCHAR 7, default #FF6B6B)
├── cor_grafico_1 (VARCHAR 7)
├── cor_grafico_2 (VARCHAR 7)
├── cor_grafico_3 (VARCHAR 7)
├── tema (VARCHAR 20)
├── mostrar_tutorial (BOOLEAN)
└── data_atualizacao (TIMESTAMP)
```

### Tabela: `mensagens_ajuda`
```sql
├── id (INT, PK)
├── usuario_id (INT, FK)
├── nome (VARCHAR 100)
├── email (VARCHAR 100)
├── descricao (TEXT)
├── status (VARCHAR 20, default 'pendente')
├── data_criacao (TIMESTAMP)
├── data_resposta (TIMESTAMP, nullable)
└── resposta (TEXT, nullable)
```

### Tabela: `auditoria_exclusao`
```sql
├── id (INT, PK)
├── usuario_id (INT, deletado)
├── nome_usuario (VARCHAR 100)
├── email_usuario (VARCHAR 100)
├── motivo_exclusao (TEXT)
├── data_exclusao (TIMESTAMP)
└── excluido_por (INT, FK, nullable)
```

---

## 🔐 Fluxo de Autenticação

### 1. Registro
```
User Input (nome, email, renda, senha)
    ↓
Validação (email único, senha ≥ 6 chars)
    ↓
Hash senha com bcrypt
    ↓
INSERT INTO usuarios
    ↓
INSERT INTO configuracoes_usuario (defaults)
    ↓
SET $_SESSION + Redirect /index.php
```

### 2. Login
```
User Input (email, senha)
    ↓
SELECT usuario WHERE email
    ↓
password_verify(input, db_hash)
    ↓
SET $_SESSION
    ↓
INSERT INTO logs_acesso
    ↓
Redirect /index.php
```

### 3. Logout
```
session_destroy()
    ↓
Redirect /login.php
```

### 4. Verificação
```
verificar_login()
    └─→ if (!isset($_SESSION['usuario_id']))
            ↓
        header("Location: /login.php")
```

---

## 📊 Fluxo de Gastos

### Adicionar Gasto
```
User: Seleciona categoria + preenche valor
    ↓
POST /api/gastos.php
    ↓
Validação (categoria existe, valor > 0)
    ↓
INSERT INTO gastos (usuario_id, categoria_id, valor, ...)
    ↓
JSON response {success: true}
    ↓
Frontend: carregarGastos() + atualizarGrafico()
```

### Visualizar Gráfico
```
GET /api/grafico.php?filtro=mes
    ↓
Calcular período (data_inicio, data_fim)
    ↓
SELECT SUM(valor) FROM gastos WHERE periodo
    ↓
Calcular renda proporcional ao período
    ↓
JSON {renda, gastos_total, saldo, categorias}
    ↓
Frontend: Chart.js renderiza donut chart
```

---

## 🎨 Componentes Frontend

### Página de Loading
- **Tipo**: HTML estático
- **Duração**: 5 segundos
- **Ação**: Redirect para /login.php
- **Animações**: CSS keyframes (pulse, spin, bounce)

### Página de Login
- **Tipo**: SPA (Single Page Application)
- **Estado**: Toggle entre login/registro
- **API**: Fetch POST
- **Validação**: Cliente + servidor

### Dashboard
- **Tipo**: Dynamic HTML com JavaScript
- **Real-time**: Fetch de dados via API
- **Gráficos**: Chart.js Doughnut
- **Tutorial**: Overlay com posicionamento dinâmico

---

## 🔌 APIs REST

### POST /api/login.php
```
Request:
{
  "email": "user@email.com",
  "senha": "password123"
}

Response:
{
  "success": true,
  "usuario": {
    "id": 1,
    "nome": "João",
    "email": "joao@email.com"
  }
}
```

### POST /api/registrar.php
```
Request:
{
  "nome": "João Silva",
  "email": "joao@email.com",
  "renda": 1500.00,
  "senha": "password123"
}

Response:
{
  "success": true,
  "usuario": {...}
}
```

### GET /api/categorias.php
```
Response:
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "nome": "Alimentação",
      "cor_hex": "#FF6B6B"
    },
    ...
  ]
}
```

### POST /api/categorias.php
```
Request:
{
  "action": "criar",
  "nome": "Alimentação",
  "cor": "#FF6B6B"
}

Response:
{
  "success": true,
  "id": 1
}
```

### POST /api/gastos.php
```
Request:
{
  "action": "criar",
  "categoria_id": 1,
  "descricao": "Supermercado",
  "valor": 150.50,
  "data_gasto": "2025-11-13",
  "hora_gasto": "14:30"
}

Response:
{
  "success": true,
  "id": 1
}
```

### GET /api/grafico.php?filtro=mes
```
Response:
{
  "success": true,
  "renda": 1500.00,
  "gastos_total": 750.00,
  "saldo": 750.00,
  "categorias": [
    {
      "nome": "Alimentação",
      "cor_hex": "#FF6B6B",
      "total": 300.00
    }
  ]
}
```

---

## 🛡️ Segurança

### Proteções Implementadas

1. **Autenticação**
   - Session-based
   - `verificar_login()` em todas as páginas protegidas

2. **Criptografia**
   - Senhas: `password_hash()` + bcrypt
   - Verificação: `password_verify()`

3. **Validação**
   - Input sanitization: `sanitizar()`
   - Email validation: `validar_email()`
   - Prepared statements (MySQLi)

4. **Autorização**
   - Role-based (admin = user ID 1)
   - Verificação de pertencimento

5. **Auditoria**
   - Logs de acesso
   - Registro de exclusões
   - Rastreamento de ações

---

## 📈 Performance

### Otimizações

1. **Database**
   - Índices em campos FK
   - Índices em data_gasto
   - Prepared statements

2. **Frontend**
   - CSS inline (sem requisições extras)
   - Lazy loading de imagens
   - Minimização de requisições

3. **Caching**
   - localStorage para dados locais
   - Session server-side

---

## 🚀 Escalabilidade

### Possíveis Melhorias

1. **Cache**
   - Redis para sessões
   - Memcached para queries frequentes

2. **Database**
   - Particionamento de tabelas grandes
   - Backup automático

3. **API**
   - Rate limiting
   - API key authentication
   - CORS configuration

4. **Frontend**
   - Progressive Web App (PWA)
   - Service Workers
   - Offline mode

---

## 📝 Convenções de Código

### Nomenclatura
- **Funções**: snake_case (ex: `obter_usuario()`)
- **Variáveis**: snake_case
- **Classes**: PascalCase (se usar OOP)
- **Constantes**: UPPER_CASE

### Padrões
- Sempre usar prepared statements
- Always echo htmlspecialchars() para output
- Sempre verificar login em páginas protegidas
- Usar try/catch para conexões

---

## 🐛 Debug e Logs

### Para Debug

1. **Erro de conexão**
   ```php
   error_log("Debug: " . print_r($var, true));
   ```

2. **Ver erros PHP**
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. **Verificar logs**
   ```
   /var/log/apache2/error.log (Linux)
   C:/xampp/logs/php_error.log (Windows)
   ```

---

## ✅ Checklist de Desenvolvimento

- [x] Database schema definido
- [x] APIs REST criadas
- [x] Autenticação implementada
- [x] CRUD de gastos
- [x] Gráficos funcionando
- [x] Painel admin completo
- [x] Auditoria de exclusões
- [x] Sistema de ajuda
- [x] Customização de tema
- [x] Tutorial interativo
- [x] Documentação completa
- [x] Segurança básica
- [ ] Testes unitários (future)
- [ ] Testes E2E (future)
- [ ] Deploy em produção (future)

---

**Documentação Técnica - Controle de Gastos**  
*Última atualização: Novembro 2025*
