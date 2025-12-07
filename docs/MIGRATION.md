# Migration SQL - CalendarBundle

## Table `calendar_events`

Cette migration sera générée automatiquement par Doctrine quand vous exécutez :

```bash
php bin/console make:migration
```

### Structure complète

```sql
CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    all_day TINYINT(1) NOT NULL DEFAULT 0,
    description LONGTEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3788d8',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
```

### Champs

| Nom | Type | Description |
|-----|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `title` | VARCHAR(255) | Titre de l'événement (obligatoire) |
| `start_date` | DATETIME | Date et heure de début (obligatoire) |
| `end_date` | DATETIME | Date et heure de fin (obligatoire) |
| `all_day` | TINYINT(1) | Événement sur journée entière (booléen) |
| `description` | LONGTEXT | Description longue (optionnel) |
| `color` | VARCHAR(7) | Couleur au format hex #RRGGBB |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

### Index

- **PRIMARY KEY** sur `id`
- **INDEX** sur `start_date` (pour les requêtes de plage)
- **INDEX** sur `end_date` (pour les requêtes de plage)

### Validation Doctrine

L'entité `Event` inclut des validations :

- **title** : NotBlank, Length(max=255)
- **startDate** : NotNull
- **endDate** : NotNull, Expression(endDate >= startDate)
- **color** : Regex(#[0-9A-Fa-f]{6})

### PostgreSQL

Pour PostgreSQL, la migration sera automatiquement adaptée :

```sql
CREATE TABLE calendar_events (
    id SERIAL NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    all_day BOOLEAN NOT NULL DEFAULT false,
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3788d8',
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX idx_start_date ON calendar_events (start_date);
CREATE INDEX idx_end_date ON calendar_events (end_date);
```

### SQLite

Pour SQLite :

```sql
CREATE TABLE calendar_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    all_day BOOLEAN NOT NULL DEFAULT 0,
    description CLOB DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3788d8',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE INDEX idx_start_date ON calendar_events (start_date);
CREATE INDEX idx_end_date ON calendar_events (end_date);
```

## Rollback

Pour revenir en arrière :

```bash
php bin/console doctrine:migrations:migrate prev
```

Ou manuellement :

```sql
DROP TABLE calendar_events;
```
