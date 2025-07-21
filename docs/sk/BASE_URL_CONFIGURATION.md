# Konfigurácia Base URL - Mezzio Boot

Návod na zmenu base URL pre sitemap.xml, robots.txt a deployment instructions.

## Kde zmeniť Base URL

Build systém podporuje 3 spôsoby nastavenia base URL (v poradí priority):

### 1. Environment Variable (najvyššia priorita)

```bash
# Priamo pri spustení buildu
BASE_URL="https://mydomain.sk" ./bin/build.sh production

# Alebo export a potom spustenie
export BASE_URL="https://mydomain.sk"
./bin/build.sh production
```

**Výhody:**
- Rýchle pre jednorazové buildy
- Nepotrebuje zmenu súborov
- Ideálne pre CI/CD

### 2. Konfiguračný súbor (odporúčané)

```bash
# Skopírujte template
cp config/build.php.dist config/build.php

# Upravte config/build.php
nano config/build.php
```

**Upravte tieto riadky v `config/build.php`:**

```php
<?php
return [
    // ZMEŇTE TÚTO URL
    'base_url' => 'https://mydomain.sk',
    
    // Environment-specific overrides
    'environments' => [
        'production' => [
            'base_url' => 'https://mydomain.sk',
        ],
        'staging' => [
            'base_url' => 'https://staging.mydomain.sk',
        ],
        'development' => [
            'base_url' => 'http://localhost:8080',
        ],
    ],
];
```

**Výhody:**
- Trvalé nastavenie
- Environment-specific konfigurácia
- Verzované v gite

### 3. Environment-specific builds

```bash
# Production build (použije production environment)
BUILD_ENV=production ./bin/build.sh production

# Staging build (použije staging environment)
BUILD_ENV=staging ./bin/build.sh production

# Development build (použije development environment)
BUILD_ENV=development ./bin/build.sh production
```

## Kde sa Base URL používa

### 1. sitemap.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://mydomain.sk</loc>
        <lastmod>2025-07-15</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://mydomain.sk/page/about</loc>
        <lastmod>2025-07-15</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
```

### 2. robots.txt
```txt
# Production robots.txt for Mezzio Boot
User-agent: *
Allow: /

# Disallow sensitive directories
Disallow: /config/
Disallow: /src/
Disallow: /var/

# Sitemap
Sitemap: https://mydomain.sk/sitemap.xml
```

### 3. Deployment Instructions
Build systém automaticky vygeneruje deployment instructions s správnou base URL.

## Overenie konfigurácie

### Test konfigurácie
```bash
# Overenie konfiguračného súboru
php -r "
\$config = require 'config/build.php';
echo 'Base URL: ' . \$config['base_url'] . PHP_EOL;
"
```

### Test buildu
```bash
# Spustenie test buildu
./bin/build.sh shared-hosting-minimal

# Overenie vygenerovaných súborov
cat build/shared-hosting-minimal/public/sitemap.xml
cat build/shared-hosting-minimal/public/robots.txt
```

## Príklady použitia

### Lokálny development
```bash
# config/build.php
'base_url' => 'http://localhost:8080',

# Alebo environment variable
BASE_URL="http://localhost:8080" ./bin/build.sh production
```

### Staging server
```bash
# Environment-specific build
BUILD_ENV=staging ./bin/build.sh production

# Alebo priamo
BASE_URL="https://staging.mydomain.sk" ./bin/build.sh production
```

### Production server
```bash
# Použije production environment z config/build.php
./bin/build.sh production

# Alebo explicitne
BUILD_ENV=production ./bin/build.sh production
```

### CI/CD Pipeline
```yaml
# GitHub Actions príklad
- name: Build production package
  run: |
    BASE_URL="https://mydomain.sk" ./bin/build.sh production
  env:
    BASE_URL: ${{ secrets.PRODUCTION_URL }}
```

## Troubleshooting

### Problém: Stále sa používa yourdomain.com
**Riešenie:** Skontrolujte prioritu nastavení:
1. Environment variable má najvyššiu prioritu
2. Konfiguračný súbor má strednú prioritu
3. Default hodnota má najnižšiu prioritu

### Problém: Environment override nefunguje
**Riešenie:** Skontrolujte `environments` sekciu v `config/build.php`:
```php
'environments' => [
    'production' => [
        'base_url' => 'https://mydomain.sk', // ZMEŇTE TÚTO URL
    ],
],
```

### Problém: Konfiguračný súbor sa nenačítava
**Riešenie:** Skontrolujte, či existuje `config/build.php`:
```bash
ls -la config/build.php
# Ak neexistuje, skopírujte template:
cp config/build.php.dist config/build.php
```

## Bezpečnosť

- **Nepridávajte citlivé údaje** do konfiguračného súboru
- **Používajte environment variables** pre produkčné URL v CI/CD
- **Verzujte konfiguračný súbor** ale nie citlivé údaje
- **Používajte HTTPS** pre produkčné URL

Konfigurácia base URL je teraz kompletne flexibilná a podporuje všetky scenáre použitia!
