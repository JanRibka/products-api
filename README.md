# Products API

REST API pro evidenci produktů (krytů pod motor). Umožňuje výpis produktů s filtrováním,
stránkováním a řazením, detail produktu, editaci produktu a export do CSV. Součástí jsou
číselníky značek a materiálů.

Postaveno na **Slim 4** + **PHP-DI** + **Doctrine ORM 3** + **PHP 8.5**.

---

## Použité technologie

| Oblast           | Technologie                             |
| ---------------- | --------------------------------------- |
| Jazyk            | PHP 8.5                                 |
| HTTP framework   | Slim 4                                  |
| DI kontejner     | PHP-DI 7                                |
| ORM              | Doctrine ORM 3 / DBAL 4                 |
| Migrace          | doctrine/migrations 3                   |
| Fixtures         | doctrine/data-fixtures 2                |
| Validace         | vlucas/valitron                         |
| CLI              | symfony/console 8                       |
| Testy            | PHPUnit 12                              |
| Statická analýza | PHPStan (level 5)                       |
| Coding standard  | PSR-12 (php_codesniffer + php-cs-fixer) |

### Poznámka k PHP 8.5

Projekt je vyvíjen a testován na **PHP 8.5**. Následující tabulka uvádí **skutečně nainstalované
verze** (ověřeno proti `composer.lock`); verze v `composer.json` jsou uvedené jako rozsahy.

| Balíček                     | Nainstalováno |
| --------------------------- | ------------- |
| `slim/slim`                 | 4.15.3        |
| `slim/psr7`                 | 1.8.0         |
| `php-di/php-di`             | 7.1.1         |
| `doctrine/orm`              | 3.7.4         |
| `doctrine/dbal`             | 4.5.0         |
| `doctrine/migrations`       | 3.9.8         |
| `doctrine/data-fixtures`    | 2.2.1         |
| `symfony/console`           | 8.1.8         |
| `symfony/cache`             | 8.1.8         |
| `vlucas/valitron`           | 1.4.11        |
| `vlucas/phpdotenv`          | 5.7.0         |
| `phpunit/phpunit`           | 12.5.38       |
| `phpstan/phpstan`           | 2.3.0         |
| `squizlabs/php_codesniffer` | 4.0.4         |
| `friendsofphp/php-cs-fixer` | 3.95.27       |

`composer check-platform-reqs` na PHP 8.5.4 prochází bez chyb.

---

## Struktura projektu

```
.
├── database/                 # schema.sql + seed.sql (rychlý snapshot mimo Doctrine)
├── docker/                   # Dockerfile, docker-compose.yml, nginx, certifikáty
└── src/api/                  # samotná aplikace
    ├── app/                  # zdrojový kód (PSR-4 App\)
    │   ├── Command/          # CLI příkazy (fixtures:load)
    │   ├── Config.php
    │   ├── Controller/       # tenké controllery
    │   ├── DataObject/       # DTO (ProductListQuery)
    │   ├── Entity/           # entity rozdělené na Contract/ + Implementation/
    │   ├── Enum/             # HttpStatusCode, AppEnvironmentEnum
    │   ├── Exception/        # ValidationException, NotFoundException
    │   ├── Fixture/          # fixtures
    │   ├── Middleware/       # zpracování výjimek
    │   ├── Repository/       # přístup k datům (Contract/ + Implementation/)
    │   ├── RequestValidator/ # valitron validátory
    │   ├── Service/          # EntityManagerService
    │   └── Shared/           # ResponseFormatter, CsvExporter, RouteEntityBindingStrategy
    ├── configs/              # konfigurace, DI bindingy, routes, middleware
    ├── constants/            # regex konstanty
    ├── migrations/           # Doctrine migrace
    ├── storage/              # cache a logy
    ├── test/Unit/            # unit testy (RequestValidator, Shared, DataObject)
    ├── test/Integration/     # integrační testy (Http) + AppFactory
    ├── bootstrap.php         # sestavení kontejneru
    ├── index.php             # HTTP vstupní bod
    └── tracker               # CLI vstupní bod
```

---

## Požadavky

- Docker + Docker Compose
- (alternativně pro lokální běh) PHP 8.5 s rozšířeními `pdo_mysql` a `mbstring`

---

## Spuštění přes Docker

> Všechny příkazy spouštějte z **kořene repozitáře** (adresáře, kde je `docker/` a `src/`).
> Na Linuxu je potřeba `sudo` u `docker` příkazů.

1. Zkopírujte `.env.example` na `.env`:

   ```bash
   cp src/api/.env.example src/api/.env
   ```

2. Sestavte a spusťte kontejnery (počkejte, než je `db` healthy):

   ```bash
   cd docker
   sudo docker compose up -d --build
   cd ..
   ```

3. Nainstalujte závislosti:

   ```bash
   cd docker
   sudo docker compose exec app composer install
   cd ..
   ```

4. Spusťte migrace a načtěte fixtures:

   ```bash
   cd docker
   sudo docker compose exec app php tracker migrations:migrate
   sudo docker compose exec app php tracker fixtures:load
   cd ..
   ```

5. Spusťte testy. Uvnitř kontejneru se DB jmenuje `db:3306` a testovací databázi
   `products-api-test` (i práva pro uživatele aplikace) zakládá automaticky init skript
   kontejneru `db`, takže není potřeba nic dalšího nastavovat:

   ```bash
   cd docker
   sudo docker compose exec app composer test
   cd ..
   ```

   > Init skript `docker/mysql/initdb/01-test-database.sql` MySQL spouští **jen při prvním
   > startu s prázdným volume**. Po jeho změně je třeba volume resetovat:
   > `docker compose down -v` a znovu `docker compose up -d`.

Adresy:

| Služba      | URL                        |
| ----------- | -------------------------- |
| API (HTTPS) | https://localhost:3444/api |
| API (HTTP)  | http://localhost:3011/api  |
| phpMyAdmin  | http://localhost:3016      |
| MySQL       | localhost:3308             |

Kompletní přehled příkazů: [DOCKER_COMMANDS.md](DOCKER_COMMANDS.md).

---

## Porty a hostitelé (Ports and hosts)

Hostitelské porty jsou konfigurovatelné přes proměnné s výchozími hodnotami, takže stack
funguje bez jakéhokoli dalšího souboru. Přehled:

| Proměnná          | Výchozí | Strana hostitele                   | Strana kontejneru         |
| ----------------- | ------- | ---------------------------------- | ------------------------- |
| `API_HTTP_PORT`   | `3011`  | API nginx HTTP na hostu            | kontejner nginx `80`      |
| `API_HTTPS_PORT`  | `3444`  | API nginx HTTPS na hostu           | kontejner nginx `443`     |
| `MYSQL_PORT`      | `3308`  | MySQL na hostu (nástroje na hostu) | kontejner MySQL `3306`    |
| `PHPMYADMIN_PORT` | `3016`  | phpMyAdmin na hostu                | kontejner phpMyAdmin `80` |
| `UI_PORT`         | `8080`  | UI (projekt `ui/`)                 | kontejner UI nginx `80`   |

Pravidlo: **kontejnery spolu komunikují přes název služby a port kontejneru**
(`db:3306`, `app:9000`, `products-api-nginx` na `443`). Hostitelské porty se uvnitř
kontejnerů nikdy nepoužívají - slouží jen nástrojům na hostu (prohlížeč, `curl`,
MySQL klient, phpMyAdmin).

Proměnné se nastavují volitelným souborem `docker/.env` (kopie z `docker/.env.example`).
Tento soubor je pouze pro lokální přepisy a **necommituje se** (je v `.gitignore`).
`docker/.env` (substituce proměnných pro Docker Compose) je **jiný soubor** než
`src/api/.env` (konfigurace aplikace, kterou načítá PHP za běhu).

### HTTP -> HTTPS redirect

API nginx má dva listenery: HTTP na portu `80` (na hostu `API_HTTP_PORT`, výchozí
`3011`) a HTTPS na portu `443` (na hostu `API_HTTPS_PORT`, výchozí `3444`). Veškerý
HTTP provoz se **přesměruje na HTTPS na stejném hostu, ale na publikovaný HTTPS
port** - proto se v `docker/nginx/nginx.conf.template` používá
`return 301 https://$host:${API_HTTPS_PORT}$request_uri;`. Bez explicitního portu by
prohlížeč skončil na `:443`, kde však API neposlouchá.

Nginx konfigurace je šablona: oficiální nginx image ji přes `envsubst` vyrenderuje z
`/etc/nginx/templates/default.conf.template`. Substituují se **jen definované
proměnné prostředí**, takže nginx proměnné jako `$host`, `$request_uri`, `$uri` či
`$document_root` zůstávají nedotčené.

HTTPS používá **self-signed certifikát** (`docker/certificates/`, `CN=localhost`, bez
SAN), takže prohlížeč zobrazí varování a `curl` je třeba spustit s `-k`. Příklad:

```bash
curl -k https://localhost:3444/api/products
# nebo přes HTTP (nginx přesměruje na HTTPS na portu 3444):
curl -kL http://localhost:3011/api/products
```

### CORS a UI proxy

V sestavení s UI (projekt `ui/`) komunikuje prohlížeč **pouze s origin UI**
(`http://localhost:8080`); UI nginx proxuje `/api/` na API server-to-server. CORS se v
tomto uspořádání tedy vůbec neuplatní. `ALLOWED_ORIGINS` je relevantní jen pro
**přímý přístup z prohlížeče z jiného origin** (např. lokální dev server).

---

## Konfigurace (`.env`)

| Proměnná          | Popis                                                                                       | Výchozí (dev)                                 |
| ----------------- | ------------------------------------------------------------------------------------------- | --------------------------------------------- |
| `APP_NAME`        | Název aplikace                                                                              | `Products API`                                |
| `APP_VERSION`     | Verze                                                                                       | `0.1.0`                                       |
| `APP_ENV`         | `development` / `production` / `test`                                                       | `development`                                 |
| `APP_DEBUG`       | Zobrazovat detaily chyb                                                                     | `true`                                        |
| `APP_URL`         | Veřejná URL                                                                                 | `http://localhost:3011`                       |
| `ALLOWED_ORIGINS` | Povolené CORS origins (čárkou oddělené); jen pro přímý přístup z prohlížeče z jiného origin | `http://localhost:3000,http://localhost:8080` |
| `DB_DRIVER`       | Doctrine driver                                                                             | `pdo_mysql`                                   |
| `DB_HOST`         | Host databáze                                                                               | `db`                                          |
| `DB_PORT`         | Port databáze                                                                               | `3306`                                        |
| `DB_NAME`         | Název databáze                                                                              | `products-api`                                |
| `DB_USER`         | Uživatel                                                                                    | `productsuser`                                |
| `DB_PASS`         | Heslo                                                                                       | `productspassword`                            |
| `TEST_DB_NAME`    | Databáze pro integrační testy                                                               | `products-api-test`                           |

---

## API endpointy

Základ je `/api`. Všechny chybové odpovědi jsou JSON (nikdy HTML stránka s trace).

Chybový formát:

```json
{ "error": "<typ>", "message": "<lidsky čitelná zpráva>" }
```

| Situace                   | Stav | `error`                      | `Allow` hlavička |
| ------------------------- | ---- | ---------------------------- | ---------------- |
| Neexistující cesta        | 404  | `notFound`                   | –                |
| Neexistující produkt      | 404  | `notFound`                   | –                |
| Neplatná HTTP metoda      | 405  | `methodNotAllowed`           | Ano              |
| Neplatné/chybějící vstup  | 422  | `validationError` (viz výše) | –                |
| Neočekávaná chyba serveru | 500  | `serverError`                | –                |

Detaily (trace, cesty k souborům, SQL) se přidají pouze při `APP_DEBUG=true` v poli `debug`;
v produkci odpověď neobsahuje žádné interní informace. Chyby se zároveň zapisují do error logu
(Slim `ErrorHandler`, řízeno `log_errors` / `log_error_details`).

CORS: povolené origins se nastavují přes `ALLOWED_ORIGINS` a vyhodnocuje je aplikace
(`App\Middleware\CorsMiddleware`) - nginx CORS neřeší. Neznámý origin nedostane žádné CORS
hlavičky; preflight `OPTIONS` vrací `204`.

| Metoda | Cesta                  | Popis                               |
| ------ | ---------------------- | ----------------------------------- |
| GET    | `/api/products`        | Stránkovaný výpis produktů s filtry |
| GET    | `/api/products/export` | Export produktů do CSV              |
| GET    | `/api/products/{id}`   | Detail produktu                     |
| PUT    | `/api/products/{id}`   | Editace produktu                    |
| GET    | `/api/brands`          | Číselník značek                     |
| GET    | `/api/materials`       | Číselník materiálů                  |

### GET /api/products

Query parametry:

| Parametr     | Typ    | Popis                                        |
| ------------ | ------ | -------------------------------------------- |
| `page`       | int    | Číslo stránky (od 1). Výchozí `1`            |
| `sort`       | string | `code`, `name`, `price`, `brand`, `material` |
| `order`      | string | `asc` / `desc`                               |
| `brandId`    | int    | Filtr na značku                              |
| `materialId` | int    | Filtr na materiál                            |
| `priceFrom`  | string | Filtr cena od (včetně)                       |
| `priceTo`    | string | Filtr cena do (včetně)                       |
| `search`     | string | Fulltext v `name` a `code`                   |

10 položek na stránku.

**Stránka mimo rozsah:** pokud `page` přesáhne `totalPages`, vrací se **`200`** s prázdným polem
`data` a `meta.page` rovným požadované stránce (např. `page=9999` → `{"data":[],"meta":{"page":9999,...,"totalPages":3}}`).
Nejedná se tedy o `404`.

Odpověď:

```json
{
  "data": [
    {
      "id": 1,
      "code": "PM00431",
      "name": "Kryt pod motor SKODA FABIA I Hatchback (6Y2) - Plast (6Q0825237L)",
      "description": "…",
      "price": "968.00",
      "brandId": 1,
      "brandName": "Škoda",
      "materialId": 1,
      "materialName": "Plast",
      "createdAt": "2026-10-11T09:00:00+00:00",
      "updatedAt": "2026-10-11T09:00:00+00:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 24,
    "totalPages": 3
  }
}
```

### GET /api/products/{id}

Vrací jeden produkt ve stejném tvaru jako v `data`. Neexistující ID → `404`.

### PUT /api/products/{id}

Tělo (JSON):

```json
{
  "code": "PM00431",
  "name": "Kryt pod motor SKODA FABIA I Hatchback (6Y2) - Plast (6Q0825237L)",
  "description": "…",
  "price": "998.00",
  "brandId": 1,
  "materialId": 2
}
```

Validace:

- `code` – povinný, max 20 znaků, unikátní (kromě editovaného produktu)
- `name` – povinný, max 255 znaků
- `description` – volitelný, max 5000 znaků
- `price` – povinný, číslo > 0, max 2 desetinná místa
- `brandId` / `materialId` – povinné, musí existovat

Chyby validace vracejí `422` ve tvaru:

```json
{
  "validationError": [{ "price": ["price.invalid"] }]
}
```

### GET /api/products/export

Respektuje stejné filtry jako výpis. Vrací `text/csv` (UTF-8 s BOM, oddělovač `;`) ke stažení.

---

## Testy a kontrola kvality

```bash
composer test             # PHPUnit – Unit + Integration suity (obě)
composer test:unit        # jen Unit suita (bez DB)
composer test:integration # jen Integration suita (proti MySQL)
composer analyze          # PHPStan (level 5)
composer lint:check       # PSR-12 kontrola
composer lint:fix         # automatické opravy
```

Testy jsou rozdělené na:

- **Unit** (`test/Unit`) – čistá logika bez DB:
  - `RequestValidator/UpdateProductRequestValidatorTest` – všechna pravidla PUT včetně špatných typů,
  - `Shared/CsvExporterTest` – CSV hardening (formula injection, quoting, round-trip),
  - `Shared/NginxConfigTest` – nginx nesmí řešit CORS,
  - `DataObject/ProductListQueryTest` – parsování/sanitizace query.
- **Integration** (`test/Integration`) – bootují reálný kontejner proti MySQL a ověřují chování
  endpointů: výpis/řazení/stránkování, detail, CORS, export, search a JSON chybové stavy
  (404/405/422/500).

### Integrační testy

Integrační testy bootují reálný kontejner proti **oddělené testovací databázi** a před každým
testem znovu vytvoří schéma ze současných Doctrine metadat, takže nikdy nesáhnou na vývojová ani
produkční data. Zdroj databáze se nastavuje přes proměnné prostředí:

| Proměnná       | Popis                    | Výchozí             |
| -------------- | ------------------------ | ------------------- |
| `TEST_DB_NAME` | Název testovací databáze | `products-api-test` |
| `TEST_DB_HOST` | Host testovací MySQL     | `db`                |
| `TEST_DB_PORT` | Port testovací MySQL     | `3306`              |
| `TEST_DB_USER` | Uživatel testovací MySQL | `productsuser`      |
| `TEST_DB_PASS` | Heslo testovací MySQL    | `productspassword`  |

Výchozí hodnoty jsou **container-first** (`db:3306`), protože testy se mají spouštět uvnitř
kontejneru `app`. Existují dvě možnosti:

- **Uvnitř kontejneru (výchozí, nic se nenastavuje):**

  ```bash
  cd docker
  sudo docker compose exec app composer test            # Unit + Integration
  sudo docker compose exec app composer test:integration # jen Integration
  cd ..
  ```

- **Z hostu** (PHP a MySQL klient na hostu) je třeba říct, že DB běží na publikovaném
  hostitelském portu:

  ```bash
  TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3308 composer test
  ```

  (`TEST_DB_PORT` je hostitelský port odpovídající `MYSQL_PORT`, výchozě `3308`.)

Testovací databázi `products-api-test` a práva pro uživatele aplikace zakládá init skript
kontejneru `db` (`docker/mysql/initdb/01-test-database.sql`), takže uvnitř kontejneru
není potřeba nic ručně vytvářet.

---

## Databáze

Strukturu spravují **Doctrine migrace** (`src/api/migrations`). Viz [MIGRATIONS.md](MIGRATIONS.md).

Pro rychlé založení DB bez Doctrine slouží snapshoty v `database/`:

```bash
mysql -u root -p products-api < database/schema.sql
mysql -u root -p products-api < database/seed.sql
```

`database/seed.sql` je **generovaný soubor** - needitujte ho ručně. Vzniká z jediného zdroje
pravdy (`src/api/app/Fixture/data/products.php`) příkazem:

```bash
cd docker
sudo docker compose exec app php tracker fixtures:dump-sql
cd ..
```

(nebo z hostu: `cd src/api && php tracker fixtures:dump-sql`).

Seed odpovídá fixtures: 7 značek, 3 materiály a 24 produktů. Materiál `Hliník` je v číselníku,
ale nemá žádný produkt (na e-shopu se hliníkový kryt nenašel), takže filtr `materialId=3` vrací
prázdný seznam. Ceny obsahují záměrné shody (968 dvakrát, 1331 třikrát), na kterých je postavené
řazení a stránkování.

---

## Xdebug

Xdebug je nastavený tak, že se **klient připojuje z kontejneru na
`host.docker.internal:9003`** (`docker/xdebug.ini`, `xdebug.client_host=host.docker.internal`),
takže **není třeba publikovat žádný port** - spojení iniciuje kontejner směrem k hostu.
`EXPOSE 9003` v `docker/Dockerfile` je jen informativní a nic nepublikuje.
