-- =============================================================
--  Bazar Online — Schema
--  Banco: bazar
--  Executado por: banco.sh / banco.ps1
--
--  Tabelas:
--    usuarios         — contas de usuários comuns (confirmados)
--    usuarios_admin   — contas de administradores (separadas, sem redundância)
--    logs_sistema     — auditoria de eventos
--    roupas           — anúncios de doação
--
--  Execute com um usuário root / admin do MySQL.
--  Os scripts de automação gerenciam o usuário da aplicação (bazar).
--  O usuário `bazar` tem somente EXECUTE nas stored procedures.
-- =============================================================

CREATE DATABASE IF NOT EXISTS `bazar` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `bazar`;

-- ── Tabela de usuários comuns ──────────────────────────────────
-- O e-mail é confirmado ANTES do INSERT (via sessão PHP).
-- Portanto não há coluna de token de confirmação aqui.
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`               INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome`             VARCHAR(100) NOT NULL,
    `email`            VARCHAR(150) NOT NULL UNIQUE,
    `telefone`         VARCHAR(20)  DEFAULT NULL,
    `endereco`         VARCHAR(200) DEFAULT NULL,
    `data_nascimento`  DATE         DEFAULT NULL,
    `senha`            VARCHAR(255) NOT NULL,
    `criado_em`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Recuperação de senha (código de 6 dígitos + tentativas + hash pendente)
    `recuperacao_token` VARCHAR(512) DEFAULT NULL,
    `recuperacao_expira` DATETIME    DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de administradores ──────────────────────────────────
-- Completamente separada de usuarios. Admins não aparecem em usuarios.
CREATE TABLE IF NOT EXISTS `usuarios_admin` (
    `id`                      INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nome`                    VARCHAR(100) NOT NULL,
    `email`                   VARCHAR(150) NOT NULL UNIQUE,
    `senha`                   VARCHAR(255) NOT NULL,
    `telegram_chat_id`        VARCHAR(50)  DEFAULT NULL,
    `pergunta_seguranca`      VARCHAR(255) DEFAULT NULL,
    `resposta_seguranca_hash` VARCHAR(255) DEFAULT NULL,
    `criado_em`               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de logs do sistema ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `logs_sistema` (
    `id`          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`  INT          DEFAULT NULL,
    `admin_id`    INT          DEFAULT NULL,
    `nome`        VARCHAR(100) DEFAULT NULL,
    `email`       VARCHAR(150) DEFAULT NULL,
    `acao`        VARCHAR(80)  NOT NULL DEFAULT '',
    `detalhes`    VARCHAR(255) DEFAULT NULL,
    `criado_em`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_log_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_log_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `usuarios_admin` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Tabela de roupas ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roupas` (
    `id`           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `titulo`       VARCHAR(120) DEFAULT NULL,
    `tipo`         VARCHAR(100) NOT NULL DEFAULT '',
    `tamanho`      VARCHAR(10)  DEFAULT NULL,
    `sexo`         ENUM('Masculino','Feminino','Unissex') DEFAULT NULL,
    `estado`       ENUM('Novo','Semi-Novo','Usado')       DEFAULT NULL,
    `local_doacao` VARCHAR(180) DEFAULT NULL,
    `foto_path`    VARCHAR(255) DEFAULT NULL,
    `status`       ENUM('disponivel','doado','editado','deletado') NOT NULL DEFAULT 'disponivel',
    `id_usuario`   INT          DEFAULT NULL,
    `criado_em`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_roupa_usuario`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migração segura: converte coluna `pausado` (TINYINT) para `status` (ENUM)
-- Só executa se `pausado` ainda existir e `status` ainda não existir
SET @_has_pausado = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roupas' AND COLUMN_NAME = 'pausado');

SET @_has_status = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roupas' AND COLUMN_NAME = 'status');

-- Passo 1: Adicionar coluna status (se não existir)
SET @_q1 = IF(@_has_status = 0,
    'ALTER TABLE roupas ADD COLUMN `status` ENUM(\'disponivel\',\'doado\',\'editado\',\'deletado\') NOT NULL DEFAULT \'disponivel\' AFTER foto_path',
    'SELECT 1');
PREPARE _s1 FROM @_q1; EXECUTE _s1; DEALLOCATE PREPARE _s1;

-- Passo 2: Migrar dados de pausado → status (se pausado existir)
SET @_q2 = IF(@_has_pausado > 0,
    'UPDATE roupas SET `status` = CASE `pausado` WHEN 0 THEN \'disponivel\' WHEN 1 THEN \'doado\' WHEN 2 THEN \'editado\' ELSE \'deletado\' END',
    'SELECT 1');
PREPARE _s2 FROM @_q2; EXECUTE _s2; DEALLOCATE PREPARE _s2;

-- Passo 3: Remover coluna antiga pausado (se existir)
SET @_q3 = IF(@_has_pausado > 0,
    'ALTER TABLE roupas DROP COLUMN `pausado`',
    'SELECT 1');
PREPARE _s3 FROM @_q3; EXECUTE _s3; DEALLOCATE PREPARE _s3;

-- ══════════════════════════════════════════════════════════════
--  STORED PROCEDURES
--  Proteção contra SQL Injection. Usuário `bazar` tem EXECUTE only.
-- ══════════════════════════════════════════════════════════════

DELIMITER //

-- ── Usuários comuns ───────────────────────────────────────────

-- 1. Buscar usuário por e-mail
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_email //
CREATE PROCEDURE sp_buscar_usuario_por_email(IN p_email VARCHAR(150))
BEGIN
    SELECT id, nome, email, telefone, endereco, data_nascimento,
           senha, criado_em, recuperacao_token, recuperacao_expira
    FROM usuarios
    WHERE email = p_email;
END //

-- 2. Inserir novo usuário (chamado APÓS confirmação do código via sessão PHP)
DROP PROCEDURE IF EXISTS sp_inserir_usuario //
CREATE PROCEDURE sp_inserir_usuario(
    IN p_nome            VARCHAR(100),
    IN p_email           VARCHAR(150),
    IN p_telefone        VARCHAR(20),
    IN p_endereco        VARCHAR(200),
    IN p_data_nascimento DATE,
    IN p_senha           VARCHAR(255)
)
BEGIN
    INSERT INTO usuarios (nome, email, telefone, endereco, data_nascimento, senha)
    VALUES (p_nome, p_email, p_telefone, p_endereco, p_data_nascimento, p_senha);
    SELECT LAST_INSERT_ID() AS id;
END //

-- 3. Buscar usuário por ID (profile)
DROP PROCEDURE IF EXISTS sp_buscar_usuario_por_id //
CREATE PROCEDURE sp_buscar_usuario_por_id(IN p_id INT)
BEGIN
    SELECT id, nome, email, telefone, endereco, data_nascimento, criado_em
    FROM usuarios
    WHERE id = p_id;
END //

-- 4. Definir token de recuperação de senha
DROP PROCEDURE IF EXISTS sp_definir_token_recuperacao //
CREATE PROCEDURE sp_definir_token_recuperacao(
    IN p_email  VARCHAR(150),
    IN p_token  VARCHAR(512),
    IN p_expira DATETIME
)
BEGIN
    UPDATE usuarios
    SET recuperacao_token  = p_token,
        recuperacao_expira = p_expira
    WHERE email = p_email;
END //

-- 5. Redefinir senha (limpa token após uso)
DROP PROCEDURE IF EXISTS sp_redefinir_senha //
CREATE PROCEDURE sp_redefinir_senha(
    IN p_email      VARCHAR(150),
    IN p_senha_hash VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET senha              = p_senha_hash,
        recuperacao_token  = NULL,
        recuperacao_expira = NULL
    WHERE email = p_email;
END //

-- 6. Atualizar Telegram do usuário (profile)
DROP PROCEDURE IF EXISTS sp_atualizar_telegram //
CREATE PROCEDURE sp_atualizar_telegram(IN p_id INT, IN p_telegram_chat_id VARCHAR(50))
BEGIN
    -- Usuários comuns não têm telegram; mantemos por compatibilidade caso se mova
    UPDATE usuarios SET criado_em = criado_em WHERE id = p_id;
END //

-- 7. Excluir campo opcional do perfil
DROP PROCEDURE IF EXISTS sp_excluir_campo_perfil //
CREATE PROCEDURE sp_excluir_campo_perfil(IN p_id INT, IN p_campo VARCHAR(50))
BEGIN
    IF p_campo = 'telefone' THEN
        UPDATE usuarios SET telefone = NULL WHERE id = p_id;
    ELSEIF p_campo = 'endereco' THEN
        UPDATE usuarios SET endereco = NULL WHERE id = p_id;
    ELSEIF p_campo = 'data_nascimento' THEN
        UPDATE usuarios SET data_nascimento = NULL WHERE id = p_id;
    END IF;
END //

-- 8. Excluir conta de usuário
DROP PROCEDURE IF EXISTS sp_excluir_conta //
CREATE PROCEDURE sp_excluir_conta(IN p_id INT)
BEGIN
    DELETE FROM usuarios WHERE id = p_id;
END //

-- ── Administradores ───────────────────────────────────────────

-- 9. Buscar admin por e-mail (login admin)
DROP PROCEDURE IF EXISTS sp_buscar_admin_por_email //
CREATE PROCEDURE sp_buscar_admin_por_email(IN p_email VARCHAR(150))
BEGIN
    SELECT id, nome, email, senha, telegram_chat_id,
           pergunta_seguranca, resposta_seguranca_hash
    FROM usuarios_admin
    WHERE email = p_email;
END //

-- 10. Buscar admin por ID
DROP PROCEDURE IF EXISTS sp_buscar_admin_por_id //
CREATE PROCEDURE sp_buscar_admin_por_id(IN p_id INT)
BEGIN
    SELECT id, nome, email, telegram_chat_id, pergunta_seguranca
    FROM usuarios_admin
    WHERE id = p_id;
END //

-- 11. Atualizar pergunta de segurança do admin
DROP PROCEDURE IF EXISTS sp_atualizar_pergunta //
CREATE PROCEDURE sp_atualizar_pergunta(
    IN p_id       INT,
    IN p_pergunta VARCHAR(255),
    IN p_hash     VARCHAR(255)
)
BEGIN
    UPDATE usuarios_admin
    SET pergunta_seguranca      = p_pergunta,
        resposta_seguranca_hash = p_hash
    WHERE id = p_id;
END //

-- 12. Listar todos os usuários (admin panel)
DROP PROCEDURE IF EXISTS sp_listar_usuarios //
CREATE PROCEDURE sp_listar_usuarios()
BEGIN
    SELECT id, nome, email, criado_em FROM usuarios ORDER BY id ASC;
END //

-- 13. Excluir usuário (admin panel)
DROP PROCEDURE IF EXISTS sp_excluir_usuario_admin //
CREATE PROCEDURE sp_excluir_usuario_admin(IN p_id INT)
BEGIN
    DELETE FROM usuarios WHERE id = p_id;
END //

-- ── Roupas ────────────────────────────────────────────────────

-- 14. Cadastrar roupa
DROP PROCEDURE IF EXISTS sp_cadastrar_roupa //
CREATE PROCEDURE sp_cadastrar_roupa(
    IN p_titulo       VARCHAR(120),
    IN p_tipo         VARCHAR(100),
    IN p_tamanho      VARCHAR(10),
    IN p_sexo         VARCHAR(20),
    IN p_estado       VARCHAR(20),
    IN p_local_doacao VARCHAR(180),
    IN p_foto_path    VARCHAR(255),
    IN p_id_usuario   INT
)
BEGIN
    INSERT INTO roupas (titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario)
    VALUES (p_titulo, p_tipo, p_tamanho, p_sexo, p_estado, p_local_doacao, p_foto_path, p_id_usuario);
    SELECT LAST_INSERT_ID() AS id;
END //

-- 15. Buscar foto de roupa (para exclusão física do arquivo)
DROP PROCEDURE IF EXISTS sp_buscar_foto_roupa //
CREATE PROCEDURE sp_buscar_foto_roupa(IN p_id INT)
BEGIN
    SELECT foto_path FROM roupas WHERE id = p_id;
END //

-- 16. Excluir roupa
DROP PROCEDURE IF EXISTS sp_excluir_roupa //
CREATE PROCEDURE sp_excluir_roupa(IN p_id INT)
BEGIN
    DELETE FROM roupas WHERE id = p_id;
END //

-- 17. Listar roupas com filtros opcionais (apenas disponíveis)
DROP PROCEDURE IF EXISTS sp_listar_roupas //
CREATE PROCEDURE sp_listar_roupas(
    IN p_tamanho VARCHAR(10),
    IN p_sexo    VARCHAR(20),
    IN p_tipo    VARCHAR(100),
    IN p_q       VARCHAR(100)
)
BEGIN
    SELECT r.id, r.titulo, r.tipo, r.tamanho, r.sexo, r.estado,
           r.local_doacao, r.foto_path, r.status, r.id_usuario, r.criado_em,
           u.nome AS nome_usuario
    FROM roupas r
    LEFT JOIN usuarios u ON u.id = r.id_usuario
    WHERE r.status = 'disponivel'
      AND (p_tamanho IS NULL OR r.tamanho = p_tamanho)
      AND (p_sexo    IS NULL OR r.sexo    = p_sexo)
      AND (p_tipo    IS NULL OR r.tipo    = p_tipo)
      AND (p_q       IS NULL OR r.titulo  LIKE CONCAT('%', p_q, '%'))
    ORDER BY r.criado_em DESC;
END //

-- 18. Pausar roupa como 'doado' (finalizar doação)
DROP PROCEDURE IF EXISTS sp_pausar_roupa //
CREATE PROCEDURE sp_pausar_roupa(IN p_id INT)
BEGIN
    UPDATE roupas SET `status` = 'doado' WHERE id = p_id;
END //

-- 19. Buscar roupa por ID
DROP PROCEDURE IF EXISTS sp_buscar_roupa_por_id //
CREATE PROCEDURE sp_buscar_roupa_por_id(IN p_id INT)
BEGIN
    SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, id_usuario, `status`
    FROM roupas
    WHERE id = p_id;
END //

-- 19b. Listar roupas por usuário
DROP PROCEDURE IF EXISTS sp_listar_roupas_por_usuario //
CREATE PROCEDURE sp_listar_roupas_por_usuario(IN p_id_usuario INT)
BEGIN
    SELECT id, titulo, tipo, tamanho, sexo, estado, local_doacao, foto_path, `status`, criado_em
    FROM roupas
    WHERE id_usuario = p_id_usuario
    ORDER BY criado_em DESC;
END //

-- 19c. Remover/pausar roupa pelo próprio usuário (status → 'deletado')
DROP PROCEDURE IF EXISTS sp_pausar_roupa_usuario //
CREATE PROCEDURE sp_pausar_roupa_usuario(IN p_id INT, IN p_id_usuario INT)
BEGIN
    UPDATE roupas SET `status` = 'deletado'
    WHERE id = p_id AND id_usuario = p_id_usuario AND `status` = 'disponivel';
    SELECT ROW_COUNT() AS afetadas;
END //

-- 19d. Marcar roupa como 'editado' — feita quando usuário edita a publicação
DROP PROCEDURE IF EXISTS sp_marcar_alterada //
CREATE PROCEDURE sp_marcar_alterada(IN p_id INT, IN p_id_usuario INT)
BEGIN
    UPDATE roupas SET `status` = 'editado'
    WHERE id = p_id AND id_usuario = p_id_usuario AND `status` = 'disponivel';
    SELECT ROW_COUNT() AS afetadas;
END //

-- ── Logs ──────────────────────────────────────────────────────

-- 20. Salvar log de auditoria
DROP PROCEDURE IF EXISTS sp_salvar_log //
CREATE PROCEDURE sp_salvar_log(
    IN p_usuario_id INT,
    IN p_admin_id   INT,
    IN p_nome       VARCHAR(100),
    IN p_email      VARCHAR(150),
    IN p_acao       VARCHAR(80),
    IN p_detalhes   VARCHAR(255)
)
BEGIN
    INSERT INTO logs_sistema (usuario_id, admin_id, nome, email, acao, detalhes)
    VALUES (p_usuario_id, p_admin_id, p_nome, p_email, p_acao, p_detalhes);
END //

-- 21. Contar logs (admin panel)
DROP PROCEDURE IF EXISTS sp_contar_logs //
CREATE PROCEDURE sp_contar_logs()
BEGIN
    SELECT COUNT(*) AS total FROM logs_sistema;
END //

-- 22. Listar logs paginados (admin panel)
DROP PROCEDURE IF EXISTS sp_listar_logs_paginados //
CREATE PROCEDURE sp_listar_logs_paginados(IN p_limite INT, IN p_offset INT)
BEGIN
    SELECT id, usuario_id, admin_id, nome, email, acao, detalhes, criado_em
    FROM logs_sistema
    ORDER BY criado_em DESC
    LIMIT p_limite OFFSET p_offset;
END //

DELIMITER ;