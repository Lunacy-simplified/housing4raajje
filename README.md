# Steps to Run

```bash
git clone https://github.com
cd housing4raajje
cp .env.example .env
docker compose up -d --build
docker compose exec app bash
```

### Inside Container

```bash
composer install
php artisan key:generate
php artisan migrate
exit
```
