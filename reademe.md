# Billing CRM - Installation & Usage Guide

## 📋 Оглавление

1. [Требования](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D1%82%D1%80%D0%B5%D0%B1%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F)
2. [Установка](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
3. [Конфигурация](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D0%BA%D0%BE%D0%BD%D1%84%D0%B8%D0%B3%D1%83%D1%80%D0%B0%D1%86%D0%B8%D1%8F)
4. [Использование API](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D0%B8%D1%81%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-api)
5. [Примеры кода для клиентов](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B-%D0%BA%D0%BE%D0%B4%D0%B0)
6. [Безопасность](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D0%B1%D0%B5%D0%B7%D0%BE%D0%BF%D0%B0%D1%81%D0%BD%D0%BE%D1%81%D1%82%D1%8C)
7. [Развертывание](https://claude.ai/chat/f4540bfc-f1ea-4398-ba5a-9451fa44a629#%D1%80%D0%B0%D0%B7%D0%B2%D0%B5%D1%80%D1%82%D1%8B%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5)

---

## 🔧 Требования

### Сервер:

* **PHP** 8.2 или выше
* **MariaDB** 10.6 или выше
* **Apache** с mod_rewrite или **Nginx**
* **Composer** (опционально, для зависимостей)
* **SSL-сертификат** (обязательно для production)

### PHP Extensions:

```bash
php -m | grep -E '(pdo|mysqli|openssl|curl|json|mbstring)'
```

Должны быть установлены:

* `pdo_mysql`
* `openssl`
* `curl`
* `json`
* `mbstring`

---

## 📦 Установка

### Шаг 1: Создание базы данных

```sql
CREATE DATABASE gemini_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gemini_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON gemini_billing.* TO 'gemini_user'@'localhost';
FLUSH PRIVILEGES;
```

### Шаг 2: Загрузка файлов

```bash
# Структура проекта
gemini-billing-crm/
├── index.php              # Главный файл (starter code)
├── admin/
│   └── dashboard.html     # Админ-панель
├── .htaccess             # Apache rewrite rules
├── .env                  # Конфигурация (НЕ коммитить!)
└── README.md
```

### Шаг 3: Настройка конфигурации

Создайте файл `.env` (или отредактируйте класс `Config` в `index.php`):

```env
# Database
DB_HOST=localhost
DB_NAME=gemini_billing
DB_USER=gemini_user
DB_PASS=your_password_here

# Security (генерируйте случайные строки!)
API_KEY_SALT=your_random_salt_32_chars_min
JWT_SECRET=your_jwt_secret_64_chars_min
ENCRYPTION_KEY=your_32_char_encryption_key

# Gemini API
GEMINI_API_BASE_URL=https://generativelanguage.googleapis.com/v1beta

# Pricing
TOKENS_PER_DOLLAR=1000000
```

**Генерация случайных ключей:**

```bash
# Linux/Mac
openssl rand -base64 32

# PHP
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### Шаг 4: Запуск установки

```bash
cd gemini-billing-crm
php -r "require 'index.php'; (new Installer())->install();"
```

Или через браузер:

```php
// Добавьте в конец index.php временно:
if (isset($_GET['install'])) {
    $installer = new Installer();
    $installer->install();
    die('Installation complete!');
}
```

Затем откройте: `https://yourdomain.com/index.php?install`

⚠️ **ВАЖНО:** Удалите код установки после завершения!

---

## ⚙️ Конфигурация

### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
  
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
  
    # API routing
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^api/(.*)$ index.php [QSA,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Content-Security-Policy "default-src 'self'"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
  
    root /var/www/gemini-billing-crm;
    index index.php;
  
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
  
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
  
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
  
    location ~ ^/api/ {
        try_files $uri /index.php?$query_string;
    }
  
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
  
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

---

## 🚀 Использование API

### Базовая структура запроса

```http
POST https://yourdomain.com/api/v1/generate
Authorization: Bearer gbk_your_api_key_here
Content-Type: application/json

{
  "model": "gemini-pro",
  "contents": [
    {
      "parts": [
        {
          "text": "Explain quantum computing in simple terms"
        }
      ]
    }
  ]
}
```

### Ответ

```json
{
  "error": false,
  "data": {
    "candidates": [
      {
        "content": {
          "parts": [
            {
              "text": "Quantum computing is..."
            }
          ]
        }
      }
    ],
    "usageMetadata": {
      "promptTokenCount": 8,
      "candidatesTokenCount": 150,
      "totalTokenCount": 158
    }
  },
  "usage": {
    "tokens_used": 158,
    "balance_remaining": 999842
  }
}
```

### Проверка баланса

```http
GET https://yourdomain.com/api/v1/balance
Authorization: Bearer gbk_your_api_key_here
```

Ответ:

```json
{
  "user_id": 1,
  "balance": {
    "tokens_purchased": 1000000,
    "tokens_used": 158,
    "tokens_available": 999842
  }
}
```

---

## 💻 Примеры кода для клиентов

### Python

```python
import requests
import json

class BillingCRMClient:
    """Клиент для работы с Billing CRM API"""
  
    def __init__(self, api_base: str, api_key: str):
        self.api_base = api_base
        self.api_key = api_key
        self.headers = {
            'Authorization': f'Bearer {self.api_key}',
            'Content-Type': 'application/json'
        }
  
    def generate_gemini(self, prompt: str, model: str = 'gemini-pro') -> dict:
        """Генерация с Gemini"""
        url = f'{self.api_base}/api/v1/generate'
        data = {
            'model': model,
            'contents': [
                {
                    'parts': [{'text': prompt}]
                }
            ]
        }
      
        response = requests.post(url, json=data, headers=self.headers)
        return response.json()
  
    def generate_openai(self, prompt: str, model: str = 'gpt-3.5-turbo') -> dict:
        """Генерация с OpenAI"""
        url = f'{self.api_base}/api/v1/generate'
        data = {
            'model': model,
            'messages': [
                {'role': 'user', 'content': prompt}
            ]
        }
      
        response = requests.post(url, json=data, headers=self.headers)
        return response.json()
  
    def generate_claude(self, prompt: str, model: str = 'claude-3-sonnet-20240229') -> dict:
        """Генерация с Claude"""
        url = f'{self.api_base}/api/v1/generate'
        data = {
            'model': model,
            'messages': [
                {'role': 'user', 'content': prompt}
            ],
            'max_tokens': 1024
        }
      
        response = requests.post(url, json=data, headers=self.headers)
        return response.json()
  
    def get_balance(self) -> dict:
        """Получить баланс токенов"""
        url = f'{self.api_base}/api/v1/balance'
        response = requests.get(url, headers=self.headers)
        return response.json()


# Использование
if __name__ == '__main__':
    client = BillingCRMClient(
        api_base='https://yourdomain.com',
        api_key='bck_your_api_key_here'
    )
  
    # Gemini
    result = client.generate_gemini('Explain quantum computing')
    print(f"Response: {result['data']}")
    print(f"Tokens used: {result['usage']['tokens_used']}")
  
    # Проверка баланса
    balance = client.get_balance()
    print(f"Balance: {balance['balance']['tokens_available']:,} tokens")
```

---

### JavaScript/Node.js

```javascript
class BillingCRMClient {
    constructor(apiBase, apiKey) {
        this.apiBase = apiBase;
        this.apiKey = apiKey;
    }

    async generateGemini(prompt, model = 'gemini-pro') {
        const response = await fetch(`${this.apiBase}/api/v1/generate`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: model,
                contents: [
                    {
                        parts: [{ text: prompt }]
                    }
                ]
            })
        });

        return await response.json();
    }

    async generateOpenAI(prompt, model = 'gpt-3.5-turbo') {
        const response = await fetch(`${this.apiBase}/api/v1/generate`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: model,
                messages: [
                    { role: 'user', content: prompt }
                ]
            })
        });

        return await response.json();
    }

    async getBalance() {
        const response = await fetch(`${this.apiBase}/api/v1/balance`, {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`
            }
        });

        return await response.json();
    }
}

// Использование
const client = new BillingCRMClient(
    'https://yourdomain.com',
    'bck_your_api_key_here'
);

// OpenAI
const result = await client.generateOpenAI('Write a haiku about coding');
console.log('Response:', result.data);
console.log('Tokens used:', result.usage.tokens_used);

// Баланс
const balance = await client.getBalance();
console.log('Balance:', balance.balance.tokens_available);
```

---

### PHP

```php
<?php

class BillingCRMClient {
    private $apiBase;
    private $apiKey;
  
    public function __construct(string $apiBase, string $apiKey) {
        $this->apiBase = $apiBase;
        $this->apiKey = $apiKey;
    }
  
    public function generateClaude(string $prompt, string $model = 'claude-3-sonnet-20240229'): array {
        $url = $this->apiBase . '/api/v1/generate';
      
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1024
        ];
      
        return $this->makeRequest($url, $data);
    }
  
    public function getBalance(): array {
        $url = $this->apiBase . '/api/v1/balance';
      
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey
            ]
        ]);
      
        $response = curl_exec($ch);
        curl_close($ch);
      
        return json_decode($response, true);
    }
  
    private function makeRequest(string $url, array $data): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);
      
        $response = curl_exec($ch);
        curl_close($ch);
      
        return json_decode($response, true);
    }
}

// Использование
$client = new BillingCRMClient(
    'https://yourdomain.com',
    'bck_your_api_key_here'
);

$result = $client->generateClaude('Write a poem about AI');
echo "Response: " . $result['data']['content'][0]['text'] . "\n";
echo "Tokens used: " . $result['usage']['tokens_used'] . "\n";
?>
```

---

### cURL примеры

```bash
# Gemini
curl -X POST https://yourdomain.com/api/v1/generate \
  -H "Authorization: Bearer bck_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gemini-pro",
    "contents": [
      {
        "parts": [
          {
            "text": "Explain AI in simple terms"
          }
        ]
      }
    ]
  }'

# OpenAI
curl -X POST https://yourdomain.com/api/v1/generate \
  -H "Authorization: Bearer bck_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-3.5-turbo",
    "messages": [
      {
        "role": "user",
        "content": "Hello, how are you?"
      }
    ]
  }'

# Claude
curl -X POST https://yourdomain.com/api/v1/generate \
  -H "Authorization: Bearer bck_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "claude-3-sonnet-20240229",
    "messages": [
      {
        "role": "user",
        "content": "Write a short story"
      }
    ],
    "max_tokens": 1024
  }'

# Проверка баланса
curl -X GET https://yourdomain.com/api/v1/balance \
  -H "Authorization: Bearer bck_your_api_key_here"
```

---

## 🔒 Безопасность

### Обязательные меры:

1. **SSL/TLS сертификат**
   * Используй Let's Encrypt (бесплатно)
   * Форсируй HTTPS для всех запросов
2. **Генерация ключей**
   ```bash
   # API_KEY_SALT (минимум 32 символа)
   openssl rand -base64 32

   # JWT_SECRET (минимум 64 символа)
   openssl rand -base64 64

   # ENCRYPTION_KEY (ровно 32 символа для AES-256)
   openssl rand -base64 32 | cut -c1-32
   ```
3. **Rate Limiting**
   ```php
   // Добавь в Config
   const RATE_LIMIT_PER_MINUTE = 60;
   const RATE_LIMIT_PER_HOUR = 1000;
   ```
4. **IP Whitelist (опционально)**
   ```php
   // Для корпоративных клиентов
   const ALLOWED_IPS = [
       'user_id_1' => ['192.168.1.100', '10.0.0.50'],
       'user_id_2' => ['203.0.113.0/24']
   ];
   ```
5. **Логирование**
   * Логируй все API запросы
   * Храни логи минимум 30 дней
   * Мониторь подозрительную активность
6. **Защита БД**
   ```sql
   -- Отдельный пользователь для приложения
   GRANT SELECT, INSERT, UPDATE ON billing_crm.* TO 'app_user'@'localhost';
   REVOKE DELETE ON billing_crm.users FROM 'app_user'@'localhost';
   ```

---

## 🚀 Развертывание

### Production Checklist:

* [ ] SSL сертификат установлен
* [ ] Все ключи безопасности сгенерированы
* [ ] `.env` файл защищен (chmod 600)
* [ ] Бэкапы БД настроены
* [ ] Мониторинг настроен
* [ ] Rate limiting включен
* [ ] Логирование работает
* [ ] CORS настроен правильно
* [ ] Тестовый пользователь создан
* [ ] Документация обновлена

### Рекомендуемый стек:

* **Веб-сервер** : Nginx + PHP-FPM
* **БД** : MariaDB 10.6+
* **Кэш** : Redis (опционально)
* **Мониторинг** : Grafana + Prometheus
* **Логи** : ELK Stack или Graylog

---

## 📊 Следующие шаги

1. **Добавить JWT аутентификацию** для веб-панели
2. **Интеграция платежных систем** (Stripe, PayPal)
3. **Webhook notifications** для событий
4. **Rate limiting** на уровне пользователя
5. **Analytics dashboard** с графиками
6. **Email уведомления** о балансе
7. **API documentation** (Swagger/OpenAPI)
8. **Multi-tenant support** для реселлеров

---

## 💡 Дополнительные функции

### Webhook для уведомлений:

```php
// В Config
const WEBHOOK_URL = 'https://your-webhook-endpoint.com';

// После каждой транзакции
function sendWebhook(array $data): void {
    $ch = curl_init(Config::WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    curl_exec($ch);
    curl_close($ch);
}
```

### Система рефералов:

```sql
CREATE TABLE referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_user_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    bonus_tokens BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_user_id) REFERENCES users(id),
    FOREIGN KEY (referred_user_id) REFERENCES users(id)
);
```

---

## 🆘 Troubleshooting

### Проблема: "Invalid API key"

 **Решение** : Проверь `API_KEY_SALT` в конфигурации

### Проблема: "Insufficient balance"

 **Решение** : Пополни баланс через `/api/v1/payment`

### Проблема: "AI API error"

 **Решение** : Проверь корректность API ключа провайдера

---

## 📞 Поддержка

Для вопросов и предложений:

* GitHub Issues
* Email: support@yourdomain.com
* Документация: https://docs.yourdomain.com

---

**Готов к запуску Billing CRM!** 🚀
headers = {
'Authorization': f'Bearer {API_KEY}',
