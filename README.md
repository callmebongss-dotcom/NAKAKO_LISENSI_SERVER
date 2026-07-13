# NAKAKO LICENSE SERVER

Backend lisensi untuk aplikasi NAKAKO GAMES.

## Deploy ke Railway (Gratis 24/7)

1. **Buat akun** di https://railway.app
2. **Buat project** → **Deploy from GitHub repo**
3. **Add MySQL plugin** di Railway Dashboard → `+ New` → `Database` → `MySQL`
4. **Set environment variables** (otomatis dari MySQL plugin):
   - `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`
5. Deploy otomatis. URL: `https://namaproject.up.railway.app`

## Akses

| URL | Keterangan |
|-----|------------|
| `/admin/` | Admin panel |
| `/api/...` | REST API |

### Login Admin
- **Username:** `admin`
- **Password:** `nakako123`

## Cara Jalankan Lokal

```bash
# Persyaratan: PHP 8.0+ dengan pdo_mysql
php -S 0.0.0.0:8080 -t public public/index.php
```

## Struktur Database

MySQL - auto migrate saat pertama kali diakses.
