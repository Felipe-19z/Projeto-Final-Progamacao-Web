# 🎯 GUIA COMPLETO - Controle de Gastos

## 📌 Índice
1. [Instalação Rápida](#instalação-rápida)
2. [Primeiro Acesso](#primeiro-acesso)
3. [Como Usar](#como-usar)
4. [Painel Admin](#painel-admin)
5. [Resolução de Problemas](#resolução-de-problemas)

---

## 🚀 Instalação Rápida

### Opção 1: Setup Automático (Recomendado)

1. **Copie os arquivos** para seu servidor web:
   ```
   C:/xampp/htdocs/Projeto-Final/
   ```
2. **Crie o banco de dados**:
   - Abra phpMyAdmin
   - Crie banco `controle_gastos`
   - copie e cole no phpMyAdmin os arquivos da `database.sql`

3. **Acesse a página de setup**:
   ```
   http://localhost/Projeto-Final/setup.php
   - CRIE O PRIMEIRO USUÁRIO ADMIN
   -AUTOMATICAMENTE SERÁ CRIADO O PRIMEIRO ADMIN E A SETUP.PHP ENTRARÁ EM LOCK,
   APÓS ISSO, QUALQUER OUTRO ADMIN PRECISARÁ SER CRIADO PELO O ADMIN QUE JÁ EXISTE
   ```


4. **Preencha o formulário** e clique em "Inicializar Sistema"


## 👤 Primeiro Acesso

### Para Usuários

1. Acesse: `http://localhost/Projeto-Final/`
2. Verá página de **Loading** por 5 segundos
3. Será redirecionado para **Login/Registro**
4. **Registre sua conta** (nome, email, renda mensal, senha)
5. Após registrar, faça login
6. Você verá o **Dashboard** com tutorial opcional

### Para Admin

1. Admin é o primeiro usuário criado (ID = 1)
2. Faça login normalmente
3. Clique na opção admin no site ou Acesse: `http://localhost/Projeto-Final/admin/`
4. Você terá acesso ao painel completo

---

## 📖 Como Usar

### 1️⃣ Dashboard Principal

**Componentes:**
- **Boas-vindas**: Mensagem personalizada com seu nome
- **Tutorial**: Clique para ver como usar o sistema
- **Adicionar Gasto**: À esquerda
- **Gráfico**: À direita com seu resumo financeiro

### 2️⃣ Adicionar Gastos

**Passo a passo:**
1. Na seção "Adicionar Gasto", digite uma categoria (ex: "Alimentação")
2. Clique no botão "+" para criar a categoria
3. Clique na categoria criada para selecioná-la
4. Preencha:
   - **Descrição**: Ex "Supermercado"
   - **Valor**: Ex "150.50"
   - **Data**: Selecione a data
   - **Hora**: Selecione a hora (opcional)
5. Clique em "Registrar Gasto"

**Dica:** Você pode criar quantas categorias quiser!

### 3️⃣ Visualizar Gráficos

**Filtros disponíveis:**
- **Dia**: Últimas 24 horas
- **Semana**: Últimos 7 dias
- **Mês**: Mês atual
- **Ano**: Ano atual

**O gráfico mostra:**
- Renda proporcional ao período
- Total de gastos
- Saldo (renda - gastos)

**Exemplo:**
- Renda mensal: R$ 1.500
- Gastos do mês: R$ 750
- Saldo: R$ 750

### 4️⃣ Configurar Aparência

1. Clique em **⚙️ Configurações**
2. Atualize sua **renda mensal** se necessário
3. Personalize as cores:
   - Fundo
   - Gastos
   - Gráficos
4. Clique em "Salvar Configurações"

**Essas cores são salvas apenas para sua conta!**

### 5️⃣ Enviar Mensagem de Ajuda

1. Clique em **❓ Ajuda**
2. Preencha:
   - Nome
   - Email
   - Descrição do problema
3. Clique em "Enviar Mensagem"
4. O admin responderá em até 24 horas

---

## 👨‍💼 Painel Admin

### Acessar Admin

- URL: `http://localhost/Projeto-Final/admin/`
- Apenas usuários com ID = 1 podem acessar
- Login com conta de admin

### Dashboard Admin

**Estatísticas visíveis:**
- Total de usuários
- Total de gastos no sistema
- Mensagens pendentes
- Status do sistema

### Gerenciar Usuários

#### Listar Usuários
- Menu: **👥 Usuários**
- Veja todos os usuários cadastrados
- Ver informações como:
  - Email
  - Status (ativo/inativo)
  - Total de acessos
  - Último acesso
  - Data de criação

#### Criar Usuário
- Menu: **➕ Novo Usuário**
- Preencha dados do novo usuário
- Sistema criará conta automaticamente

#### Ver Detalhes
- Clique em **Ver** ao lado do usuário
- Informações disponíveis:
  - Estatísticas (acessos, gastos)
  - Últimos gastos registrados
  - Data de cadastro

#### Deletar Usuário
- Clique em **Deletar**
- Será solicitado **motivo da exclusão**
- O motivo será registrado em auditoria
- Ação é **irreversível**

### Mensagens de Ajuda

- Menu: **💬 Mensagens**
- Filtro por status:
  - **Pendentes**: Não respondidas
  - **Respondidas**: Já respondidas
  - **Todas**: Todas as mensagens

**Para responder:**
1. Clique em **Responder**
2. Digite sua resposta
3. Clique em "Enviar"

### Auditoria

- Menu: **📋 Auditoria**
- Registro de todas as exclusões de usuários
- Informações:
  - Nome e email do deletado
  - Motivo da exclusão
  - Data e hora
  - Quem deletou

---

## 🔒 Segurança

### Boas Práticas

1. **Senhas Fortes**
   - Mínimo 6 caracteres
   - Use letras, números e caracteres especiais

2. **Proteja Admin**
   - Guarde bem a senha do admin
   - Não compartilhe credenciais

3. **Backups Regulares**
   - Faça backup do banco de dados
   - Pelo menos 1x por semana

4. **Atualize Regularmente**
   - Verifique atualizações do PHP
   - Mantenha MySQL atualizado

---

## 🔧 Resolução de Problemas

### ❌ "Erro ao conectar ao banco de dados"

**Solução:**
1. Verifique se MySQL está rodando
2. Edite `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'controle_gastos');
   ```
3. Teste conexão no phpMyAdmin

### ❌ "Página em branco"

**Solução:**
1. Verifique se PHP está habilitado
2. Procure por erros em `php_error.log`
3. Certifique-se que arquivos estão no servidor

### ❌ "Não consegue fazer login"

**Solução:**
1. Verifique se o usuário foi criado
2. Confirme email e senha (case-sensitive)
3. Tente criar novo usuário

### ❌ "Admin não consegue acessar painel"

**Solução:**
1. Admin é apenas o usuário com ID = 1
2. Se deletou o primeiro usuário, crie novo com ID = 1
3. Edite banco direto:
   ```sql
   INSERT INTO usuarios (id, nome, email, senha, renda_mensal, ativo) 
   VALUES (1, 'Admin', 'admin@email.com', '[hash]', 5000, TRUE);
   ```

### ❌ "Gráfico não aparece"

**Solução:**
1. Limpe cache do navegador (Ctrl + Shift + Delete)
2. Verifique console do navegador (F12)
3. Certifique-se que tem gastos registrados

---

## 📊 Exemplos de Uso

### Exemplo 1: Controle Mensal

**Cenário:** João recebe R$ 2.000/mês

```
1. Cria categorias:
   - Alimentação (verde)
   - Transporte (azul)
   - Diversão (rosa)

2. Registra gastos:
   - Supermercado: R$ 500
   - Uber: R$ 200
   - Cinema: R$ 50

3. Ao final do mês:
   - Renda: R$ 2.000
   - Gastos: R$ 750
   - Saldo: R$ 1.250
```

### Exemplo 2: Análise por Semana

**Cenário:** Maria quer controlar gastos semanais

```
1. Filtra por "Semana"
2. Vê renda semanal proporcionalmente
3. Identifica padrões de gasto
4. Planeja melhor sua orçamento
```

---

## 💡 Dicas Úteis

1. **Use categorias lógicas**
   - Não crie muitas categorias
   - Use nomes descritivos

2. **Registre tudo**
   - Quanto mais completo, melhor a análise
   - Não esqueça de anotar gastos

3. **Revise regularmente**
   - Analise seus gráficos mensalmente
   - Identifique onde está gastando mais

4. **Personalize cores**
   - Use cores que fazem sentido para você
   - Deixe tema confortável para os olhos

---

## 📞 Suporte

**Para problemas:**
1. Use o formulário de ajuda no sistema
2. Descreva o problema detalhadamente
3. Aguarde resposta do admin

**Para bugs:**
1. Anote exatamente quando o erro ocorreu
2. Descreva passos para reproduzir
3. Envie prints se possível

---

## ✅ Checklist de Configuração

- [ ] Banco de dados criado
- [ ] `config.php` configurado
- [ ] Admin criado (ID = 1)
- [ ] Primeiro login bem-sucedido
- [ ] Dashboard acessível
- [ ] Gráficos funcionando
- [ ] Admin consegue acessar painel
- [ ] Categorias personalizadas criadas

---

**Divirta-se controlando seus gastos! 💰**

*Última atualização: Novembro 2025*
