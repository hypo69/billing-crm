# Изменения в конфигурации Billing CRM

## ✅ Что было сделано:

### 1. Вынесена конфигурация БД в .env

Все параметры базы данных теперь читаются из `.env` файла:

```env
DB_HOST=localhost
DB_NAME=u177424397_billing_crm
DB_USER=u177424397_david
DB_PASS=&l.QZcUaTf4%!Q_|8r:$
DB_CHARSET=utf8mb4
```

### 2. Расширена поддержка параметров из .env

Класс `Config` теперь читает **ВСЕ** параметры из `.env`:

- ✅ Database (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET)
- ✅ Security (API_KEY_SALT, JWT_SECRET, ENCRYPTION_KEY)
- ✅ Application (APP_ENV, APP_DEBUG, APP_URL)
- ✅ API Providers (GEMINI_API_KEY, OPENAI_API_KEY, ANTHROPIC_API_KEY)
- ✅ Pricing (TOKENS_PER_DOLLAR, MIN_PAYMENT_AMOUNT, MAX_PAYMENT_AMOUNT)
- ✅ Rate Limiting (RATE_LIMIT_PER_MINUTE, RATE_LIMIT_PER_HOUR, RATE_LIMIT_PER_DAY)
- ✅ Session (SESSION_LIFETIME, SESSION_SECURE, SESSION_HTTP_ONLY, SESSION_SAME_SITE)
- ✅ Logging (LOG_LEVEL, LOG_FILE, ERROR_LOG_FILE)
- ✅ Timezone (TIMEZONE)

### 3. Добавлена валидация конфигурации

Метод `Config::validate()` проверяет наличие обязательных параметров:
- DB_HOST
- DB_NAME
- DB_USER
- DB_PASS

Если параметры отсутствуют, будет записана ошибка в лог.

### 4. Улучшена обработка ошибок

- **APP_DEBUG=false** (production): показывается только общее сообщение "Internal server error"
- **APP_DEBUG=true** (development): показываются детали ошибки, файл, строка и trace

### 5. Автоматическая настройка окружения

После загрузки `.env` автоматически настраиваются:
- Отображение ошибок (в зависимости от APP_DEBUG)
- Timezone (из параметра TIMEZONE)
- Путь к логу ошибок (из ERROR_LOG_FILE)

---

## 📋 Что нужно сделать:

### 1. Загрузи обновленные файлы на сервер

```bash
# Загрузи новые версии файлов
/var/www/html/billing-crm/index.php
/var/www/html/billing-crm/.env
```

### 2. Сгенерируй ключи безопасности

```bash
# Запусти генератор ключей
php /var/www/html/billing-crm/generate_keys.php

# Скопируй полученные ключи в .env файл
nano /var/www/html/billing-crm/.env
```

Замени эти строки:
```env
API_KEY_SALT=REPLACE_WITH_RANDOM_32_CHARS_MIN
JWT_SECRET=REPLACE_WITH_RANDOM_64_CHARS_MIN
ENCRYPTION_KEY=REPLACE_WITH_32_RANDOM_CHARS
```

На сгенерированные значения.

### 3. Добавь API ключи провайдеров (опционально)

Если хочешь использовать AI API, добавь ключи в `.env`:

```env
GEMINI_API_KEY=твой_ключ_от_google
OPENAI_API_KEY=твой_ключ_от_openai
ANTHROPIC_API_KEY=твой_ключ_от_anthropic
```

### 4. Настрой логирование (опционально)

Укажи пути к файлам логов:

```env
LOG_FILE=/var/www/html/billing-crm/logs/app.log
ERROR_LOG_FILE=/var/www/html/billing-crm/logs/error.log
```

### 5. Установи права доступа

```bash
# Права на файлы
sudo chown -R www-data:www-data /var/www/html/billing-crm
sudo chmod 644 /var/www/html/billing-crm/index.php
sudo chmod 600 /var/www/html/billing-crm/.env

# Создай директорию для логов
sudo mkdir -p /var/www/html/billing-crm/logs
sudo chown www-data:www-data /var/www/html/billing-crm/logs
sudo chmod 755 /var/www/html/billing-crm/logs
```

---

## ✅ Проверка работы

### 1. Health Check

```bash
curl http://127.0.0.1/billing-crm/api/v1/health
```

Должен вернуть:
```json
{
    "status": "healthy",
    "database": "connected",
    "timestamp": "..."
}
```

### 2. Проверка информации об API

```bash
curl http://127.0.0.1/billing-crm/
```

Должен вернуть информацию об API с версией и endpoints.

### 3. Проверка логов

Если есть ошибки, проверь логи:

```bash
# Лог ошибок PHP
tail -f /var/www/html/billing-crm/logs/php_errors.log

# Лог Apache
sudo tail -f /var/log/apache2/billing-crm-error.log
```

---

## 🔒 Безопасность

### ✅ Проверь что .env защищён

```bash
# Попытка доступа через веб должна вернуть 403
curl http://127.0.0.1/billing-crm/.env
# Ожидается: 403 Forbidden
```

### ✅ Режим production

Убедись что в `.env` установлено:
```env
APP_ENV=production
APP_DEBUG=false
```

### ✅ Удали тестовые файлы

```bash
sudo rm /var/www/html/billing-crm/test.php
sudo rm /var/www/html/billing-crm/generate_keys.php
```

---

## 📝 Структура конфигурации

```
Config::$dbHost           ← DB_HOST
Config::$dbName           ← DB_NAME
Config::$dbUser           ← DB_USER
Config::$dbPass           ← DB_PASS
Config::$apiKeySalt       ← API_KEY_SALT
Config::$jwtSecret        ← JWT_SECRET
Config::$appDebug         ← APP_DEBUG (boolean)
Config::$tokensPerDollar  ← TOKENS_PER_DOLLAR (int)
... и т.д.
```

Все параметры теперь читаются из `.env` файла!

---

## 🆘 Troubleshooting

### Ошибка: "Missing required configuration"

**Проблема**: Отсутствуют обязательные параметры в .env

**Решение**: Проверь что в `.env` заполнены:
```env
DB_HOST=localhost
DB_NAME=u177424397_billing_crm
DB_USER=u177424397_david
DB_PASS=your_password
```

### Ошибка: "Database connection failed"

**Проблема**: Неверные данные подключения к БД

**Решение**: 
1. Проверь логи: `tail -f /var/www/html/billing-crm/logs/php_errors.log`
2. Проверь данные в `.env`
3. Проверь подключение вручную: `mysql -u u177424397_david -p -h localhost u177424397_billing_crm`

### Предупреждение: "API_KEY_SALT is not configured properly"

**Проблема**: Не сгенерированы ключи безопасности

**Решение**: Запусти `php generate_keys.php` и скопируй ключи в `.env`

---

**Готово! Конфигурация вынесена в .env файл.** 🎉
