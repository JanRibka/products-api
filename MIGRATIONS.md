# Migrace

Migrace spravuje balíček `doctrine/migrations`. Konfigurace je v `src/api/configs/migrations.php`,
příkazy se registrují v `src/api/configs/commands/migration_commands.php` a spouští se přes CLI `tracker`.

## Spuštění všech neaplikovaných migrací

```bash
php tracker migrations:migrate
```

(nebo přes docker: `docker compose exec app php tracker migrations:migrate`)

## Stav migrací

```bash
php tracker migrations:status
```

## Vytvoření nové migrace z rozdílu entit a databáze

Nejprve musí být databáze ve stavu, který odpovídá předchozím migracím (tj. `migrations:migrate`
proběhlo). Poté:

```bash
php tracker migrations:diff
```

Do `src/api/migrations/` se vygeneruje nový soubor `VersionYYYYMMDDHHMMSS.php`.

## Vytvoření prázdné migrace

```bash
php tracker migrations:generate
```

## Návrat o jednu / konkrétní migraci zpět

```bash
php tracker migrations:execute Migrations\Version20261006214427 --down
```

## Aplikace konkrétní migrace

```bash
php tracker migrations:execute Migrations\Version20261006214427 --up
```

## Zásady

- Migrace se **needitují** po nasazení; vždy se vytváří nová.
- `isTransactional()` vrací `false` (kvůli DDL v MySQL, které neumí být v transakci).
- Migrace jsou zdroj pravdy pro strukturu DB. Soubor `database/schema.sql` je jen
  pohodlný snapshot pro rychlé založení DB bez Doctrine.
