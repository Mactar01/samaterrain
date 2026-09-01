-- ============================================================
-- myTerrain — Schéma Base de Données MySQL 8.0
-- Phase 1 : Conception & Architecture
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS myterrain;
CREATE DATABASE myterrain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myterrain;

-- ============================================================
-- TABLE : users
-- Tous les comptes (joueur, loueur, admin)
-- ============================================================
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    phone           VARCHAR(20)  NULL,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('player','owner','admin') NOT NULL DEFAULT 'player',
    avatar          VARCHAR(500) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : owners
-- Profil étendu pour les loueurs de terrains
-- ============================================================
CREATE TABLE owners (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL UNIQUE,
    business_name   VARCHAR(200)  NULL,
    address         VARCHAR(300)  NULL,
    siret           VARCHAR(50)   NULL,  -- ou numéro NINEA/RCCM
    verified_at     TIMESTAMP     NULL,
    commission_rate DECIMAL(5,2)  NOT NULL DEFAULT 10.00, -- % commission plateforme
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_owners_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : fields
-- Terrains de football
-- ============================================================
CREATE TABLE fields (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    description     TEXT         NULL,
    address         VARCHAR(300) NOT NULL,
    city            VARCHAR(100) NOT NULL,
    latitude        DECIMAL(10, 8) NULL,
    longitude       DECIMAL(11, 8) NULL,
    type            ENUM('natural_grass','artificial_grass','concrete','futsal','beach') NOT NULL DEFAULT 'artificial_grass',
    capacity        TINYINT UNSIGNED NOT NULL DEFAULT 10, -- nb de joueurs
    size            VARCHAR(50)  NULL,  -- ex: "40x20m"
    price_per_hour  DECIMAL(10, 2) NOT NULL,
    currency        VARCHAR(5)   NOT NULL DEFAULT 'XOF',  -- FCFA
    amenities       JSON         NULL,  -- ["douche","vestiaire","éclairage","parking"]
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    is_featured     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fields_owner FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : field_images
-- Photos associées aux terrains
-- ============================================================
CREATE TABLE field_images (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id    BIGINT UNSIGNED NOT NULL,
    url         VARCHAR(500)  NOT NULL,
    is_primary  TINYINT(1)    NOT NULL DEFAULT 0,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_field_images_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : time_slots
-- Créneaux horaires par terrain et par date
-- ============================================================
CREATE TABLE time_slots (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id    BIGINT UNSIGNED NOT NULL,
    date        DATE NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    status      ENUM('available','reserved','blocked') NOT NULL DEFAULT 'available',
    price       DECIMAL(10, 2) NULL,  -- prix spécifique (surcharge le prix par défaut)
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_time_slots_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE,
    -- Un créneau ne peut exister qu'une seule fois par terrain/date/heure
    CONSTRAINT uq_time_slots UNIQUE (field_id, date, start_time)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : reservations
-- Réservations de créneaux
-- CONTRAINTE CRITIQUE : pas de double réservation confirmée
-- ============================================================
CREATE TABLE reservations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    field_id        BIGINT UNSIGNED NOT NULL,
    time_slot_id    BIGINT UNSIGNED NOT NULL,
    status          ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    total_price     DECIMAL(10, 2) NOT NULL,
    commission      DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    notes           TEXT NULL,
    cancelled_at    TIMESTAMP NULL,
    cancel_reason   VARCHAR(300) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_reservations_field     FOREIGN KEY (field_id)     REFERENCES fields(id)     ON DELETE CASCADE,
    CONSTRAINT fk_reservations_slot      FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ⚠️  INDEX UNIQUE PARTIEL : Une seule réservation CONFIRMÉE par créneau
-- (les réservations 'pending' ou 'cancelled' peuvent coexister)
-- Implémenté via une contrainte applicative Laravel + SELECT FOR UPDATE en transaction
-- MySQL ne supporte pas les index partiels filtrés, on utilise une colonne calculée :
ALTER TABLE reservations
    ADD COLUMN confirmed_slot_key VARCHAR(100) AS (
        IF(status = 'confirmed', CONCAT(field_id, '_', time_slot_id), NULL)
    ) STORED,
    ADD UNIQUE INDEX uq_confirmed_reservation (confirmed_slot_key);

-- ============================================================
-- TABLE : payments
-- Paiements liés aux réservations
-- ============================================================
CREATE TABLE payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id  BIGINT UNSIGNED NOT NULL,
    amount          DECIMAL(10, 2)  NOT NULL,
    currency        VARCHAR(5)      NOT NULL DEFAULT 'XOF',
    method          ENUM('wave','orange_money','free_money','card','cash') NOT NULL,
    status          ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(200)    NULL,  -- référence du prestataire
    paid_at         TIMESTAMP       NULL,
    metadata        JSON            NULL,  -- données brutes prestataire
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : matches
-- Matchs communautaires organisés sur des terrains
-- ============================================================
CREATE TABLE matches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id        BIGINT UNSIGNED NOT NULL,
    reservation_id  BIGINT UNSIGNED NULL,  -- terrain déjà réservé
    organizer_id    BIGINT UNSIGNED NOT NULL,  -- user_id de l'organisateur
    title           VARCHAR(200)    NOT NULL,
    description     TEXT            NULL,
    date            DATE            NOT NULL,
    start_time      TIME            NOT NULL,
    max_players     TINYINT UNSIGNED NOT NULL DEFAULT 10,
    level           ENUM('beginner','intermediate','advanced','mixed') NOT NULL DEFAULT 'mixed',
    status          ENUM('open','full','ongoing','finished','cancelled') NOT NULL DEFAULT 'open',
    is_public       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_matches_field        FOREIGN KEY (field_id)       REFERENCES fields(id)       ON DELETE CASCADE,
    CONSTRAINT fk_matches_reservation  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    CONSTRAINT fk_matches_organizer    FOREIGN KEY (organizer_id)   REFERENCES users(id)        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : match_players
-- Joueurs inscrits à un match
-- ============================================================
CREATE TABLE match_players (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id    BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    status      ENUM('confirmed','waitlisted','cancelled') NOT NULL DEFAULT 'confirmed',
    joined_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_players_match  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_players_user   FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT uq_match_player         UNIQUE (match_id, user_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : reviews
-- Avis des joueurs sur les terrains
-- ============================================================
CREATE TABLE reviews (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    field_id    BIGINT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_user   FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_reviews_field  FOREIGN KEY (field_id) REFERENCES fields(id)  ON DELETE CASCADE,
    CONSTRAINT uq_user_review    UNIQUE (user_id, field_id)  -- un seul avis par utilisateur par terrain
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : favorites
-- Terrains favoris des joueurs
-- ============================================================
CREATE TABLE favorites (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    field_id    BIGINT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_favorites_user   FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_favorites_field  FOREIGN KEY (field_id) REFERENCES fields(id)  ON DELETE CASCADE,
    CONSTRAINT uq_favorite         UNIQUE (user_id, field_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : notifications
-- Notifications in-app et push
-- ============================================================
CREATE TABLE notifications (
    id          CHAR(36)        PRIMARY KEY DEFAULT (UUID()),  -- UUID pour compatibilité Laravel
    user_id     BIGINT UNSIGNED NOT NULL,
    type        VARCHAR(100)    NOT NULL,  -- ex: 'reservation.confirmed'
    title       VARCHAR(200)    NOT NULL,
    body        TEXT            NULL,
    data        JSON            NULL,  -- payload (reservation_id, field_id, etc.)
    read_at     TIMESTAMP       NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : personal_access_tokens (Laravel Sanctum)
-- ============================================================
CREATE TABLE personal_access_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type  VARCHAR(255) NOT NULL,
    tokenable_id    BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    token           VARCHAR(64)  NOT NULL UNIQUE,
    abilities       TEXT NULL,
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB;

-- ============================================================
-- INDEX de performance
-- ============================================================
CREATE INDEX idx_fields_location      ON fields      (latitude, longitude);
CREATE INDEX idx_fields_city          ON fields      (city);
CREATE INDEX idx_fields_owner         ON fields      (owner_id);
CREATE INDEX idx_time_slots_field_date ON time_slots  (field_id, date);
CREATE INDEX idx_reservations_user    ON reservations (user_id);
CREATE INDEX idx_reservations_field   ON reservations (field_id);
CREATE INDEX idx_reservations_status  ON reservations (status);
CREATE INDEX idx_matches_date         ON matches      (date);
CREATE INDEX idx_notifications_user   ON notifications (user_id, read_at);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DU SCHÉMA
-- ============================================================
