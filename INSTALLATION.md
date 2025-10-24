# Billing CRM - Быстрый старт

## 📦 Шаги установки

### 1. Загрузка файлов на сервер

Загрузи следующие файлы в директорию `/var/www/html/billing-crm/`:

```bash
cd /var/www/html/billing-crm/
```

### 2. Настройка файла .env

1. **Скопируй файл `.env` в корень проекта**
2. **Отредактируй данные подключения к БД** (уже заполнены):
   ```env
   DB_NAME=u177424397_billing_crm
   DB_USER=u177424397_david
   DB_PASS=&l.QZcUaTf4%!Q_|8r:$
   ```

3. **Сгенерируй ключи безопасности**:
   
   Вариант A: Через PHP скрипт
   ```bash
   php generate_keys.php
   ```
   
   Вариант B: Через OpenSSL
   ```bash
   # API_KEY_SALT (32 символа)
   openssl rand -hex 16
   
   # JWT_SECRET (64 символа)
   openssl rand -base64 48
   
   # ENCRYPTION_KEY (32 символа)
   openssl rand -hex 16
   ```

4. **Скопируй сгенерированные ключи в .env файл**

5. **Добавь API ключи провайдеров**:
   ```env
   GEMINI_API_KEY=твой_ключ_от_google
   OPENAI_API_KEY=твой_ключ_от_openai
   ANTHROPIC_API_KEY=твой_ключ_от_anthropic
   ```

### 3. Установка прав доступа

```bash
# Права на файлы
sudo chown -R www-data:www-data /var/www/html/billing-crm
sudo find /var/www/html/billing-crm -type d -exec chmod 755 {} \;
sudo find /var/www/html/billing-crm -type f -exec chmod 644 {} \;

# Защита .env файла
sudo chmod 600 /var/www/html/billing-crm/.env

# Создание директории для логов
sudo mkdir -p /var/www/html/billing-crm/logs
sudo chown www-data:www-data /var/www/html/billing-crm/logs
sudo chmod 755 /var/www/html/billing-crm/logs
```

### 4. Настройка Apache

```bash
# Конфиг уже создан в /etc/apache2/sites-available/billing-crm.conf

# Убедись что модули включены
sudo a2enmod rewrite headers expires deflate

# Проверка конфигурации
sudo apache2ctl configtest

# Перезапуск Apache
sudo systemctl restart apache2
```

### 5. Настройка SSL (Let's Encrypt)

```bash
# Установка Certbot
sudo apt install certbot python3-certbot-apache -y

# Получение SSL сертификата
sudo certbot --apache -d srv378106.hstgr.cloud

# Проверка автообновления
sudo certbot renew --dry-run
```

### 6. Импорт базы данных

База данных уже создана. Если нужно пересоздать структуру:

```bash
# Используй файл create_db_fixed.sql через phpMyAdmin
# или через командную строку:
mysql -u u177424397_david -p u177424397_billing_crm < create_db_fixed.sql
```

### 7. Проверка установки

```bash
# Проверка подключения к БД через PHP
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=u177424397_billing_crm;charset=utf8mb4', 'u177424397_david', '&l.QZcUaTf4%!Q_|8r:\$');
echo 'Database connection: OK\n';
"

# Проверка через curl
curl http://localhost/billing-crm/

# Проверка HTTPS
curl -I https://srv378106.hstgr.cloud/billing-crm/
```

## 🔒 Безопасность после установки

### Обязательно выполни:

1. **Удали файл генерации ключей**:
   ```bash
   sudo rm /var/www/html/billing-crm/generate_keys.php
   ```

2. **Ограничь доступ к директории install**:
   ```bash
   sudo chmod 700 /var/www/html/billing-crm/install
   ```

3. **Проверь что .env защищён**:
   ```bash
   ls -la /var/www/html/billing-crm/.env
   # Должно быть: -rw------- (600)
   ```

4. **Включи HTTPS редирект в .htaccess**:
   Раскомментируй строки:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
   ```

## 🧪 Тестирование API

### Тест подключения:

```bash
curl https://srv378106.hstgr.cloud/billing-crm/api/v1/health
```

### Создание тестового пользователя и API ключа:

```sql
-- Подключись к БД через phpMyAdmin и выполни:

-- Создание тестового пользователя
INSERT INTO users (email_hash, password_hash, name, status) 
VALUES (
    MD5('test@example.com'), 
    MD5('test_password_salt'), 
    'Test User', 
    'active'
);

SET @user_id = LAST_INSERT_ID();

-- Создание API ключа (bcm_test_key_12345)
INSERT INTO api_keys (user_id, key_hash, key_prefix, status) 
VALUES (
    @user_id,
    MD5('bcm_test_key_12345'),
    'bcm_test_key',
    'active'
);

-- Добавление токенов
INSERT INTO user_balances (user_id, tokens_purchased) 
VALUES (@user_id, 1000000);
```

### Тест API запроса:

```bash
curl -X POST https://srv378106.hstgr.cloud/billing-crm/api/v1/generate \
  -H "Authorization: Bearer bcm_test_key_12345" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gemini-pro",
    "contents": [
      {
        "parts": [
          {
            "text": "Hello, test!"
          }
        ]
      }
    ]
  }'
```

## 📊 Структура проекта

```
/var/www/html/billing-crm/
├── index.php              # Главный файл API
├── .env                   # Конфигурация (НЕ коммитить!)
├── .htaccess             # Apache правила
├── .gitignore            # Игнорируемые файлы для Git
├── readme.md             # Документация
├── admin/
│   └── dashboard.html     # Админ-панель
├── install/
│   └── create_db_fixed.sql # SQL для создания БД
├── logs/                  # Логи приложения
│   ├── app.log
│   ├── error.log
│   └── php_errors.log
└── config/               # Дополнительные конфиги (создай если нужно)
```

## 🔧 Troubleshooting

### Проблема: 500 Internal Server Error

**Решение**:
```bash
# Проверь логи
sudo tail -f /var/log/apache2/billing-crm-error.log
sudo tail -f /var/www/html/billing-crm/logs/error.log

# Проверь права доступа
ls -la /var/www/html/billing-crm/

# Проверь синтаксис PHP
php -l /var/www/html/billing-crm/index.php
```

### Проблема: Database connection failed

**Решение**:
```bash
# Проверь данные в .env
cat /var/www/html/billing-crm/.env | grep DB_

# Проверь подключение через MySQL
mysql -u u177424397_david -p -h localhost u177424397_billing_crm
```

### Проблема: .htaccess не работает

**Решение**:
```bash
# Проверь что mod_rewrite включен
apache2ctl -M | grep rewrite

# Включи если отключен
sudo a2enmod rewrite
sudo systemctl restart apache2

# Проверь AllowOverride в конфиге виртуального хоста
# Должно быть: AllowOverride All
```

## 📞 Поддержка

При проблемах проверь:
1. Логи Apache: `/var/log/apache2/billing-crm-error.log`
2. Логи PHP: `/var/www/html/billing-crm/logs/php_errors.log`
3. Логи приложения: `/var/www/html/billing-crm/logs/error.log`

---

**Готово! Твой Billing CRM установлен и готов к работе!** 🚀
