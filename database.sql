

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS controle_gastos;
USE controle_gastos;


CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    renda_mensal DECIMAL(10, 2) DEFAULT 0.00,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS logs_acesso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    data_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    cor_hex VARCHAR(7) DEFAULT '#FF0000',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_categoria (usuario_id, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS gastos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    descricao VARCHAR(255),
    valor DECIMAL(10, 2) NOT NULL,
    data_gasto DATE NOT NULL,
    hora_gasto TIME,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_usuario_data (usuario_id, data_gasto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS configuracoes_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL UNIQUE,
    cor_fundo VARCHAR(7) DEFAULT '#FFFFFF',
    cor_gastos VARCHAR(7) DEFAULT '#FF6B6B',
    cor_grafico_1 VARCHAR(7) DEFAULT '#4ECDC4',
    cor_grafico_2 VARCHAR(7) DEFAULT '#45B7D1',
    cor_grafico_3 VARCHAR(7) DEFAULT '#FFA07A',
    tema VARCHAR(20) DEFAULT 'claro',
    mostrar_tutorial BOOLEAN DEFAULT TRUE,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS mensagens_ajuda (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resposta TIMESTAMP NULL,
    resposta TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS auditoria_exclusao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    nome_usuario VARCHAR(100) NOT NULL,
    email_usuario VARCHAR(100) NOT NULL,
    motivo_exclusao TEXT,
    data_exclusao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    excluido_por INT,
    FOREIGN KEY (excluido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar os históricos de gastos
CREATE TABLE IF NOT EXISTS gasto_historico (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gasto_id INT NOT NULL,
  usuario_id INT NOT NULL,
  descricao TEXT,
  valor DECIMAL(10,2) NOT NULL,
  data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (gasto_id),
  CONSTRAINT fk_gh_gasto FOREIGN KEY (gasto_id) REFERENCES gastos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela para armazenar gastos recorrentes (fixos)
CREATE TABLE IF NOT EXISTS gastos_fixos (
        id INT PRIMARY KEY AUTO_INCREMENT,
        usuario_id INT NOT NULL,
        categoria_id INT NOT NULL,
        descricao VARCHAR(255),
        valor DECIMAL(10,2) NOT NULL,
        periodicidade ENUM('semana','mes','ano') NOT NULL DEFAULT 'mes',
        start_date DATE NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
        INDEX idx_usuario_fixos (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_usuario_gastos ON gastos(usuario_id);
CREATE INDEX idx_usuario_categorias ON categorias(usuario_id);
CREATE INDEX idx_usuario_acesso ON logs_acesso(usuario_id);
CREATE INDEX idx_gasto_data ON gastos(data_gasto);


