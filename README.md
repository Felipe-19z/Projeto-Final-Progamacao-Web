# Gerenciador de Tabelas - Projeto PHP

Este é um sistema simples para gerenciar tabelas do banco de dados usando apenas PHP.

## Requisitos
- XAMPP instalado
- Visual Studio Code
- PHP configurado no XAMPP

## Como Usar

1. **Iniciar o XAMPP**
   - Abra o XAMPP Control Panel
   - Inicie Apache e MySQL

2. **Configurar o Projeto**
   -  no `C:\xampp\htdocs`, dentro do htdocs, crie a pasta `projeto1`
   Copie a pasta do projeto para `C:\xampp\htdocs\projeto1`
   - Os arquivos principais estão na pasta `admin`:
     - `config.php`: configurações do banco de dados
     - `functions.php`: funções para gerenciar tabelas

3. **Funções Disponíveis**

- `criarTabela($nome, $campos)`: Cria uma nova tabela
- `excluirTabela($nome)`: Exclui uma tabela
- `listarTabelas()`: Lista todas as tabelas
- `adicionarColuna($tabela, $nome, $tipo)`: Adiciona uma coluna
- `removerColuna($tabela, $nome)`: Remove uma coluna
- `mostrarEstrutura($tabela)`: Mostra a estrutura da tabela
- `tabelaExiste($nome)`: Verifica se uma tabela existe


     # Guia rápido — Criar/Ler/Atualizar/Deletar tabelas e visualizar clientes/consultas

    Este projeto foi preparado para uso em sala de aula: tudo pode ser feito com arquivos PHP simples (sem ferramentas externas). Aqui explico, passo a passo, como criar, alterar, listar e apagar tabelas e como ver clientes e suas consultas pela interface.

    ## Requisitos
    - XAMPP (Apache + MySQL)
    - Visual Studio Code (para editar/rodar os scripts PHP)

    ## Preparação

    1. Abra o XAMPP Control Panel e inicie Apache e MySQL.
    2. Coloque o projeto em `C:\xampp\htdocs\projeto1` (ou use seu caminho atual).  
    3. Abra a pasta `projeto1` no VS Code.

    ## Banco de dados my admin

    1. clique em admin na no my sql e abra o http://localhost/phpmyadmin/
    2. Clique em novo ou banco de dados 
    3. Coloque o nome do banco como projeto1 e ao lado selecione a utf8mb4_unicode_ci e clique em criar

    ## ACESSAR O SITE!
    
    1. dentro do xamp, na parte de Apache clique em admin
    2. Dentro da url que abrir que será http://localhost/dashboard/ você irá retirar o "dashboard" e irá substituir pelo nome da pasta
    3. no caso, https>//localhost/projeto1 e irá ter acesso ao site
    4. O banco de dados precisa ter o mesmo nome da pasta com os arquivos php!


    ## Após isso, seu código irá rodar normalmente !

    

   