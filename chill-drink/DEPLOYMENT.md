# 🚀 Hướng Dẫn Deploy

## 📋 Checklist

- [ ] Test kỹ code
- [ ] Build assets: `npm run build`
- [ ] Optimize: `composer install --optimize-autoloader --no-dev`
- [ ] Cache: `php artisan config:cache`
- [ ] Backup database

## 🌐 Shared Hosting

### 1. Upload Files
- Build: `npm run build`
- Upload qua FTP (trừ node_modules)

### 2. Cấu Hình
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass
```

### 3. Permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 4. Database
```bash
php artisan migrate --force
php artisan db:seed --force
```

## ☁️ VPS (Ubuntu)

### Install
```bash
# PHP 8.2
sudo apt install php8.2 php8.2-fpm php8.2-mysql -y

# MySQL
sudo apt install mysql-server -y

# Nginx
sudo apt install nginx -y

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Deploy
```bash
cd /var/www
git clone <repo> chill-drink
cd chill-drink
composer install --no-dev
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

### Nginx Config
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/chill-drink/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### SSL
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com
```

## 🔒 Security

- Set `APP_DEBUG=false`
- Use strong passwords
- Enable HTTPS
- Regular backups
- Update dependencies

## 📝 Post-Deploy

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 🔄 Update

```bash
git pull origin main
composer install --no-dev
npm install && npm run build
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
```
