# =============================================================================
# Billing CRM - Environment Configuration
# =============================================================================
# ВАЖНО: Этот файл содержит конфиденциальные данные!
# НЕ коммитить в Git! Убедись что .env добавлен в .gitignore
# =============================================================================

# -----------------------------------------------------------------------------
# Database Configuration
# -----------------------------------------------------------------------------
DB_HOST=localhost
DB_NAME=u177424397_billing_crm
DB_USER=u177424397_david
DB_PASS=&l.QZcUaTf4%!Q_|8r:$
DB_CHARSET=utf8mb4

# -----------------------------------------------------------------------------
# Security Keys (ОБЯЗАТЕЛЬНО замени на случайные значения!)
# -----------------------------------------------------------------------------
# Генерация случайных ключей:
# openssl rand -base64 32
# openssl rand -base64 64

# Salt для хеширования API ключей (минимум 32 символа)
API_KEY_SALT=REPLACE_WITH_RANDOM_32_CHARS_MIN

# JWT секрет для токенов аутентификации (минимум 64 символа)
JWT_SECRET=REPLACE_WITH_RANDOM_64_CHARS_MIN

# Ключ шифрования AES-256 (ровно 32 символа)
ENCRYPTION_KEY=REPLACE_WITH_32_RANDOM_CHARS

# -----------------------------------------------------------------------------
# Application Settings
# -----------------------------------------------------------------------------
APP_ENV=production
APP_DEBUG=false
APP_URL=https://srv378106.hstgr.cloud

# -----------------------------------------------------------------------------
# API Providers Configuration
# -----------------------------------------------------------------------------
# Google Gemini API
GEMINI_API_KEY=YOUR_GEMINI_API_KEY_HERE
GEMINI_API_BASE_URL=https://generativelanguage.googleapis.com/v1beta

# OpenAI API (опционально)
OPENAI_API_KEY=YOUR_OPENAI_API_KEY_HERE
OPENAI_API_BASE_URL=https://api.openai.com/v1

# Anthropic Claude API (опционально)
ANTHROPIC_API_KEY=YOUR_ANTHROPIC_API_KEY_HERE
ANTHROPIC_API_BASE_URL=https://api.anthropic.com/v1

# -----------------------------------------------------------------------------
# Pricing Configuration
# -----------------------------------------------------------------------------
# Количество токенов за 1 доллар
TOKENS_PER_DOLLAR=1000000

# Минимальная сумма пополнения (USD)
MIN_PAYMENT_AMOUNT=1.00

# Максимальная сумма пополнения (USD)
MAX_PAYMENT_AMOUNT=10000.00

# -----------------------------------------------------------------------------
# Rate Limiting
# -----------------------------------------------------------------------------
# Максимум запросов в минуту на один API ключ
RATE_LIMIT_PER_MINUTE=60

# Максимум запросов в час на один API ключ
RATE_LIMIT_PER_HOUR=1000

# Максимум запросов в день на один API ключ
RATE_LIMIT_PER_DAY=10000

# -----------------------------------------------------------------------------
# Session Configuration
# -----------------------------------------------------------------------------
SESSION_LIFETIME=7200
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# -----------------------------------------------------------------------------
# Email Configuration (для уведомлений)
# -----------------------------------------------------------------------------
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@srv378106.hstgr.cloud
SMTP_FROM_NAME="Billing CRM"

# -----------------------------------------------------------------------------
# Payment Gateway Configuration (опционально)
# -----------------------------------------------------------------------------
# Stripe
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_SECRET=your_paypal_secret
PAYPAL_MODE=sandbox

# -----------------------------------------------------------------------------
# Logging
# -----------------------------------------------------------------------------
LOG_LEVEL=info
LOG_FILE=/var/www/html/billing-crm/logs/app.log
ERROR_LOG_FILE=/var/www/html/billing-crm/logs/error.log

# -----------------------------------------------------------------------------
# Timezone
# -----------------------------------------------------------------------------
TIMEZONE=UTC

# -----------------------------------------------------------------------------
# CORS Configuration
# -----------------------------------------------------------------------------
CORS_ALLOWED_ORIGINS=https://srv378106.hstgr.cloud,https://www.srv378106.hstgr.cloud
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Authorization,Content-Type,X-Requested-With

# =============================================================================
# END OF CONFIGURATION
# =============================================================================