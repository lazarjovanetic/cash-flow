# Dugovanja — praćenje dugovanja prema dobavljačima

Laravel 12 + MySQL aplikacija za market, po dizajnu „Troskovi Market v4“ (tamni stakleni izgled).

- **Pregled:** ukupno zaduženje, grafikon „Rokovi plaćanja“ za 14 dana, kartice dobavljača sa filterima i pretragom i panel „Za plaćanje“ sa fakturama koje kasne ili dospevaju u narednih 7 dana.
- **Strana dobavljača:** fakture grupisane po statusu, uz prekidač „Sakrij plaćene“, delimične uplate, kontakte komercijalista i PIB.
- **Rok plaćanja:** računa se automatski kao datum fakture + valuta (podrazumevano 15 dana).
- **Upozorenja:** uvek se vide u zaglavlju i u bočnom meniju. Dnevni podsetnik stiže na email i/ili Telegram.
- **PWA:** na telefonu se doda na početni ekran i koristi kao aplikacija.
- **Bez Node.js:** CSS i Alpine.js su već u `public/`, pa na serveru nema build koraka.

## Lokalno pokretanje

```bash
composer install
cp .env.example .env            # za lokalni rad: DB_CONNECTION=sqlite i obrišite ostale DB_ linije
php artisan key:generate
php artisan migrate --seed      # --seed ubacuje demo podatke
php artisan app:korisnik vi@example.com --name="Vaše ime"
php artisan serve
```

Testovi se pokreću sa `php artisan test`.

## Postavljanje na cPanel shared hosting

1. **PHP:** u „Select PHP Version“ izaberite PHP **8.2 ili noviji**. Ekstenzije: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`.
2. **Baza:** u „MySQL Databases“ napravite bazu i korisnika, pa korisniku dodelite sva prava (ALL PRIVILEGES) nad tom bazom.
3. **Fajlovi:** ceo projekat stavite **van** `public_html`, na primer u `~/dugovanja`.
   - Ako hosting ima SSH i Composer: `git clone …` pa `composer install --no-dev --optimize-autoloader`.
   - Ako nema: lokalno pokrenite `composer install --no-dev --optimize-autoloader` i uploadujte sve, zajedno sa folderom `vendor`.
4. **Document root:** u „Domains“ podesite document root domena (ili poddomena) na `~/dugovanja/public`.
   Ako hosting to ne dozvoljava (glavni domen), preko SSH-a zamenite `public_html` linkom ka `public` folderu:
   ```bash
   mv ~/public_html ~/public_html_staro && ln -s ~/dugovanja/public ~/public_html
   ```
   Ne stavljajte ceo projekat u `public_html`, jer bi `.env` sa lozinkama bio dostupan sa interneta.
5. **`.env`:** kopirajte `.env.example` u `.env` i popunite:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://vas-domen.rs
   DB_DATABASE=…
   DB_USERNAME=…
   DB_PASSWORD=…
   MAIL_MAILER=smtp
   MAIL_HOST=mail.vas-domen.rs
   MAIL_PORT=465
   MAIL_SCHEME=smtps
   MAIL_USERNAME=…
   MAIL_PASSWORD=…
   MAIL_FROM_ADDRESS=dugovanja@vas-domen.rs
   PODSETNIK_EMAIL=vi@example.com
   ```
6. **Inicijalizacija:** pokrenite preko SSH-a ili privremeno preko cPanel „Terminal“:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan app:korisnik vi@example.com --name="Vaše ime"
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   Folderi `storage/` i `bootstrap/cache/` moraju biti upisivi (755/775).
7. **Cron (podsetnici):** u „Cron Jobs“ dodajte posao koji se pokreće svakog minuta:
   ```
   * * * * * cd ~/dugovanja && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
   (putanju do PHP-a proverite sa `which php`; na nekim hostinzima je `/opt/cpanel/ea-php82/root/usr/bin/php`).
8. **HTTPS:** uključite AutoSSL u „SSL/TLS Status“.
9. **Backup baze:** dodajte dnevni cron, na primer:
   ```
   15 3 * * * mysqldump -u KORISNIK -p'LOZINKA' BAZA | gzip > ~/backup/dugovanja-$(date +\%F).sql.gz
   ```
   i povremeno brišite stare kopije.

Posle svake izmene `.env` pokrenite `php artisan config:cache`.

## Podešavanja (`.env`)

| Promenljiva | Podrazumevano | Značenje |
|---|---|---|
| `DUGOVANJA_UPOZORENJE_DANA` | 7 | Fakture koje dospevaju u narednih N dana su „uskoro“ (žuto) |
| `DUGOVANJA_PODRAZUMEVANI_ROK` | 15 | Podrazumevana valuta za nove dobavljače i fakture |
| `DUGOVANJA_DEMO` | false | Prikazuje „Vrati demo podatke“. **Briše sve podatke**, zato na produkciji ostaje false |
| `PODSETNIK_DANA_PRE` | 3 | Podsetnik obuhvata fakture koje kasne i one koje dospevaju u narednih N dana |
| `PODSETNIK_VREME` | 07:00 | Vreme slanja dnevnog podsetnika |
| `PODSETNIK_EMAIL` | — | Jedna ili više adresa, odvojene zarezom |
| `PODSETNIK_TELEGRAM_TOKEN`, `PODSETNIK_TELEGRAM_CHAT_ID` | — | Telegram bot (napravite ga preko @BotFather) |

Poruku podsetnika možete videti bez slanja: `php artisan dugovanja:podsetnik --suvo`.

## Struktura

- `app/Services/Ledger.php`: računa saldo, rok i status faktura i zbirove po dobavljaču
- `app/Support/Fmt.php`: formati 274.340,38 RSD, datumi i srpska množina. Iznosi se računaju u parama.
- `resources/views/`: Blade šabloni (`home`, `supplier`, `layouts/app`, `partials/modals`)
- `public/css/app.css`, `public/js/app.js`: izgled i Alpine.js logika modala i filtera
- `app/Console/Commands/`: `app:korisnik` (nalog za prijavu) i `dugovanja:podsetnik`
