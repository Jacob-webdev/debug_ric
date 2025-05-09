-- ========================================
-- 1. TABELA USERS
-- ========================================
CREATE TABLE users (
    id             SERIAL PRIMARY KEY,
    email          VARCHAR(255)    NOT NULL UNIQUE,
    username       VARCHAR(100)    NOT NULL UNIQUE,
    password_hash  TEXT            NOT NULL,
    is_premium     BOOLEAN         NOT NULL DEFAULT FALSE,
    role           VARCHAR(20)     NOT NULL DEFAULT 'user'
                              CHECK (role IN ('user','admin')),
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  CHARSET=utf8mb4;

-- ========================================
-- 2. TABELA PRIORITY_LEVELS
-- ========================================
CREATE TABLE priority_levels (
    id            SMALLINT    PRIMARY KEY,
    label         VARCHAR(50) NOT NULL,
    premium_only  BOOLEAN     NOT NULL DEFAULT FALSE
) ENGINE=InnoDB
  CHARSET=utf8mb4;

INSERT INTO priority_levels (id, label, premium_only) VALUES
  (1, 'Bassa',     FALSE),
  (2, 'Normale',   FALSE),
  (3, 'Alta',      FALSE),
  (4, 'Immediata', TRUE);

-- ========================================
-- 3. TABELLA NOTES
-- ========================================
CREATE TABLE notes (
    id            SERIAL      PRIMARY KEY,
    user_id       INTEGER     NOT NULL
                             REFERENCES users(id) ON DELETE CASCADE,
    title         VARCHAR(255) NOT NULL,
    content       TEXT        NOT NULL
                             CHECK (CHAR_LENGTH(content) <= 1500),
    priority      SMALLINT    NOT NULL DEFAULT 1
                             REFERENCES priority_levels(id),
    is_shared     BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  CHARSET=utf8mb4;

-- indice per accelerare le query sulle note di ciascun utente
CREATE INDEX idx_notes_user_id
    ON notes(user_id);

-- ========================================
-- 4. TRIGGER per aggiornare updated_at
-- ========================================
DELIMITER $$
CREATE TRIGGER trg_notes_set_updated_at
BEFORE UPDATE ON notes
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

-- ========================================
-- 5. TABELLA NOTE_SHARES (condivisioni)
-- ========================================
CREATE TABLE note_shares (
    note_id      INTEGER     NOT NULL
                             REFERENCES notes(id) ON DELETE CASCADE,
    user_id      INTEGER     NOT NULL
                             REFERENCES users(id) ON DELETE CASCADE,
    permission   ENUM('view','edit') NOT NULL DEFAULT 'view',
    shared_at    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (note_id, user_id)
) ENGINE=InnoDB
  CHARSET=utf8mb4;

-- ========================================
-- 6. TABELLE TAGGING (opzionale)
-- ========================================
CREATE TABLE tags (
    id            SERIAL      PRIMARY KEY,
    name          VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB
  CHARSET=utf8mb4;

CREATE TABLE note_tags (
    note_id       INTEGER     NOT NULL
                             REFERENCES notes(id) ON DELETE CASCADE,
    tag_id        INTEGER     NOT NULL
                             REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (note_id, tag_id)
) ENGINE=InnoDB
  CHARSET=utf8mb4;
