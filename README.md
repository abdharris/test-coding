# Magento 2.4.4 Custom Installation

Proyek ini merupakan implementasi solusi untuk test programming Magento 2.4.4 dengan spesifikasi khusus.

## Fitur Utama

- Instalasi Magento 2.4.4 via Composer dengan sample data
- Modul custom `Lime/Sample` dengan:
  - Custom URL `/lime` yang menampilkan "Hello World"
  - Link "Home" di navigation bar
- Konfigurasi URL `http://local.magento`
- Instalasi modul Mageplaza Product Slider
- Elasticsearch dinonaktifkan

## Prasyarat

- PHP 7.4/8.1
- MySQL 5.7/8.0
- Composer 2.x
- XAMPP/WAMP/LAMP
- Git

## Instalasi

1. Clone repository:
```bash
git clone https://github.com/username/reponame.git
cd reponame

    Install dependencies:

bash

composer install

    Buat database MySQL:

sql

CREATE DATABASE magento244;

    Install Magento (sesuaikan dengan credential database Anda):

bash

php bin/magento setup:install \
--base-url=http://local.magento \
--db-host=localhost \
--db-name=magento244 \
--db-user=root \
--db-password= \
--admin-firstname=admin \
--admin-lastname=admin \
--admin-email=admin@admin.com \
--admin-user=admin \
--admin-password=admin123 \
--language=en_US \
--currency=USD \
--timezone=America/Chicago \
--use-rewrites=1 \
--search-engine=mysql

    Enable modul custom:

bash

php bin/magento module:enable Lime_Sample
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f

Konfigurasi Khusus

    Tambahkan ke hosts file:

127.0.0.1 local.magento

    Nonaktifkan Elasticsearch:

bash

php bin/magento module:disable Magento_Elasticsearch
php bin/magento config:set catalog/search/engine mysql

Screenshot

Instalasi via Composer
Hello World Page
Navigation dengan Home Link
Product Slider
Struktur Modul Custom

app/code/Lime/Sample/
├── registration.php
├── etc/
│   ├── module.xml
│   ├── frontend/
│   │   ├── routes.xml
│   │   └── menu.xml
├── Controller/
│   └── Index/
│       └── Index.php
└── view/
    └── frontend/
        ├── layout/
        │   └── lime_index_index.xml
        └── templates/
            └── hello.phtml

Troubleshooting

Jika terjadi error:
bash

# Bersihkan cache
php bin/magento cache:clean

# Fix permission
chmod -R 777 var generated pub/static

# Jika error kompilasi DI
rm -rf generated/*
php bin/magento setup:di:compile

Kontribusi

Pull request dipersilakan. Untuk perubahan besar, buka issue terlebih dahulu.
Lisensi

MIT
