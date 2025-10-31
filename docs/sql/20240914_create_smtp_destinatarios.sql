-- Tabela para armazenar destinatários de relatórios de backup
CREATE TABLE IF NOT EXISTS smtp_destinatarios (
    destinatario_id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_smtp_destinatarios_smtp
        FOREIGN KEY (smtp_id)
        REFERENCES smtp (smtp_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX idx_smtp_destinatarios_email
    ON smtp_destinatarios (smtp_id, email);
