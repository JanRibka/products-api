# Docker - spouštěcí příkazy

Všechny příkazy se spouští z adresáře `docker/`. Na Linuxu je potřeba `sudo`.

## Sestavení a spuštění kontejnerů

```bash
cd docker
sudo docker compose up -d --build
```

## Zastavení kontejnerů

```bash
cd docker
sudo docker compose down
```

## Zastavení a smazání databázového volume (vymaže data)

```bash
cd docker
sudo docker compose down -v
```

## Zobrazení logů

```bash
cd docker
sudo docker compose logs -f app
sudo docker compose logs -f nginx
sudo docker compose logs -f db
```

## Spuštění shellu v kontejneru aplikace

```bash
cd docker
sudo docker compose exec app bash
```

## Instalace závislostí (composer)

```bash
cd docker
sudo docker compose exec app composer install
```

## Spuštění migrací

```bash
cd docker
sudo docker compose exec app php tracker migrations:migrate
```

## Načtení fixtures

```bash
cd docker
sudo docker compose exec app php tracker fixtures:load
```

## Spuštění testů

Integrační testy běží proti testovací databázi `products-api-test`, kterou automaticky
zakládá init skript kontejneru `db` (spustí se jen při prvním startu s prázdným volume).
Uvnitř kontejneru není potřeba nic nastavovat.

```bash
cd docker
sudo docker compose exec app composer test              # Unit + Integration
sudo docker compose exec app composer test:unit         # jen Unit (bez DB)
sudo docker compose exec app composer test:integration  # jen Integration
```

Pro spuštění z hostu nastavte hostitelský port MySQL:

```bash
TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3308 composer test
```

## Statická analýza (PHPStan)

```bash
cd docker
sudo docker compose exec app composer analyze
```

## Kontrola / oprava coding standardu

```bash
cd docker
sudo docker compose exec app composer lint:check
sudo docker compose exec app composer lint:fix
```

## Adresy služeb

| Služba      | URL                        | Proměnná (host)   |
| ----------- | -------------------------- | ----------------- |
| API (HTTPS) | https://localhost:3444/api | `API_HTTPS_PORT`  |
| API (HTTP)  | http://localhost:3011/api  | `API_HTTP_PORT`   |
| phpMyAdmin  | http://localhost:3016      | `PHPMYADMIN_PORT` |
| MySQL       | localhost:3308             | `MYSQL_PORT`      |

Hostitelské porty lze přepsat volitelným `docker/.env` (kopie z `docker/.env.example`).
Kontejnery mezi sebou komunikují přes název služby a port kontejneru (`db:3306`,
`app:9000`, `products-api-nginx:443`). Podrobnosti v [README.md](README.md).
