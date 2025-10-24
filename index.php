<?php
/**
 * @file index.php
 * @description Billing CRM - Главный API файл для управления токенами и биллингом
 * 
 * Этот модуль реализует REST API для системы управления токенами и платежами.
 * Основной функционал:
 * - Валидация API ключей и проверка баланса токенов
 * - Маршрутизация запросов к различным endpoints
 * - Подключение к базе данных и управление конфигурацией
 * - Обработка ошибок и логирование
 * 
 * Endpoints:
 * - GET  /api/v1/health   - Проверка состояния системы
 * - GET  /api/v1/balance  - Получение баланса токенов
 * - POST /api/v1/generate - Генерация контента через AI (в разработке)
 * 
 * Конфигурация загружается из файла .env
 * 
 * @version 1.0
 * @author Billing CRM Team
 * @date 2025-10-24
 * 
 * @example
 * // Health check
 * curl http://domain.com/api/v1/health
 * 
 * // Проверка баланса
 * curl -H "Authorization: Bearer YOUR_API_KEY" http://domain.com/api/v1/balance
 */

// Базовая настройка ошибок (обновляется после загрузки .env)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Заголовки для JSON API
header('Content-Type: application/json; charset=utf-8');
header('X-Powered-By: Billing-CRM/1.0');

/**
 * Класс конфигурации приложения
 * 
 * Управляет загрузкой и хранением параметров конфигурации из файла .env
 * Содержит статические свойства для доступа к настройкам из любой части приложения
 * 
 * @property string $dbHost Хост базы данных
 * @property string $dbName Имя базы данных
 * @property string $dbUser Пользователь базы данных
 * @property string $dbPass Пароль базы данных
 * @property string $apiKeySalt Соль для хеширования API ключей
 * @property string $jwtSecret Секретный ключ для JWT токенов
 * @property bool $appDebug Режим отладки приложения
 */
class Config {
    // Database
    public static string $dbHost = '';
    public static string $dbName = '';
    public static string $dbUser = '';
    public static string $dbPass = '';
    public static string $dbCharset = 'utf8mb4';
    
    // Security
    public static string $apiKeySalt = '';
    public static string $jwtSecret = '';
    public static string $encryptionKey = '';
    
    // Application
    public static string $appEnv = 'production';
    public static bool $appDebug = false;
    public static string $appUrl = '';
    
    // API Providers
    public static string $geminiApiKey = '';
    public static string $geminiApiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    public static string $openaiApiKey = '';
    public static string $openaiApiBaseUrl = 'https://api.openai.com/v1';
    public static string $anthropicApiKey = '';
    public static string $anthropicApiBaseUrl = 'https://api.anthropic.com/v1';
    
    // API Settings
    public static int $tokensPerDollar = 1000000;
    public static float $minPaymentAmount = 1.00;
    public static float $maxPaymentAmount = 10000.00;
    
    // Rate Limiting
    public static int $rateLimitPerMinute = 60;
    public static int $rateLimitPerHour = 1000;
    public static int $rateLimitPerDay = 10000;
    
    // Session
    public static int $sessionLifetime = 7200;
    public static bool $sessionSecure = true;
    public static bool $sessionHttpOnly = true;
    public static string $sessionSameSite = 'strict';
    
    // Logging
    public static string $logLevel = 'info';
    public static string $logFile = '';
    public static string $errorLogFile = '';
    
    // Timezone
    public static string $timezone = 'UTC';
    
    /**
     * Загрузка конфигурации из файла .env
     * 
     * Метод читает файл .env построчно и заполняет статические свойства класса
     * Пропускает пустые строки и комментарии, начинающиеся с #
     * Автоматически преобразует типы данных (boolean, int, float)
     * 
     * @return void
     * @throws void Метод не выбрасывает исключения, только логирует ошибки
     */
    public static function loadEnv(): void {
        $envFile = __DIR__ . '/.env';
        if (!file_exists($envFile)) {
            error_log('WARNING: .env file not found at ' . $envFile);
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            error_log('ERROR: Failed to read .env file');
            return;
        }
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропуск комментариев и пустых строк
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Разбор строки KEY=VALUE
            if (strpos($line, '=') === false) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Применение значений к статическим свойствам
            switch ($key) {
                // Database
                case 'DB_HOST':
                    self::$dbHost = $value;
                    break;
                case 'DB_NAME':
                    self::$dbName = $value;
                    break;
                case 'DB_USER':
                    self::$dbUser = $value;
                    break;
                case 'DB_PASS':
                    self::$dbPass = $value;
                    break;
                case 'DB_CHARSET':
                    self::$dbCharset = $value;
                    break;
                
                // Security
                case 'API_KEY_SALT':
                    self::$apiKeySalt = $value;
                    break;
                case 'JWT_SECRET':
                    self::$jwtSecret = $value;
                    break;
                case 'ENCRYPTION_KEY':
                    self::$encryptionKey = $value;
                    break;
                
                // Application
                case 'APP_ENV':
                    self::$appEnv = $value;
                    break;
                case 'APP_DEBUG':
                    self::$appDebug = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'APP_URL':
                    self::$appUrl = $value;
                    break;
                
                // API Providers
                case 'GEMINI_API_KEY':
                    self::$geminiApiKey = $value;
                    break;
                case 'GEMINI_API_BASE_URL':
                    self::$geminiApiBaseUrl = $value;
                    break;
                case 'OPENAI_API_KEY':
                    self::$openaiApiKey = $value;
                    break;
                case 'OPENAI_API_BASE_URL':
                    self::$openaiApiBaseUrl = $value;
                    break;
                case 'ANTHROPIC_API_KEY':
                    self::$anthropicApiKey = $value;
                    break;
                case 'ANTHROPIC_API_BASE_URL':
                    self::$anthropicApiBaseUrl = $value;
                    break;
                
                // Pricing
                case 'TOKENS_PER_DOLLAR':
                    self::$tokensPerDollar = (int)$value;
                    break;
                case 'MIN_PAYMENT_AMOUNT':
                    self::$minPaymentAmount = (float)$value;
                    break;
                case 'MAX_PAYMENT_AMOUNT':
                    self::$maxPaymentAmount = (float)$value;
                    break;
                
                // Rate Limiting
                case 'RATE_LIMIT_PER_MINUTE':
                    self::$rateLimitPerMinute = (int)$value;
                    break;
                case 'RATE_LIMIT_PER_HOUR':
                    self::$rateLimitPerHour = (int)$value;
                    break;
                case 'RATE_LIMIT_PER_DAY':
                    self::$rateLimitPerDay = (int)$value;
                    break;
                
                // Session
                case 'SESSION_LIFETIME':
                    self::$sessionLifetime = (int)$value;
                    break;
                case 'SESSION_SECURE':
                    self::$sessionSecure = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'SESSION_HTTP_ONLY':
                    self::$sessionHttpOnly = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'SESSION_SAME_SITE':
                    self::$sessionSameSite = $value;
                    break;
                
                // Logging
                case 'LOG_LEVEL':
                    self::$logLevel = $value;
                    break;
                case 'LOG_FILE':
                    self::$logFile = $value;
                    break;
                case 'ERROR_LOG_FILE':
                    self::$errorLogFile = $value;
                    break;
                
                // Timezone
                case 'TIMEZONE':
                    self::$timezone = $value;
                    break;
            }
        }
        
        // Валидация критичных параметров
        self::validate();
    }
    
    /**
     * Валидация критичных параметров конфигурации
     * 
     * Проверка наличия обязательных параметров для работы приложения
     * Логирование предупреждений для дефолтных значений безопасности
     * 
     * @return void
     */
    private static function validate(): void {
        $required = [
            'dbHost' => 'DB_HOST',
            'dbName' => 'DB_NAME',
            'dbUser' => 'DB_USER',
            'dbPass' => 'DB_PASS',
        ];
        
        $missing = [];
        foreach ($required as $property => $envKey) {
            if (empty(self::$$property)) {
                $missing[] = $envKey;
            }
        }
        
        if (!empty($missing)) {
            $error = 'Missing required configuration: ' . implode(', ', $missing);
            error_log('CONFIG ERROR: ' . $error);
            
            if (self::$appDebug) {
                http_response_code(500);
                echo json_encode([
                    'error' => true,
                    'message' => $error,
                    'hint' => 'Check your .env file'
                ]);
                exit;
            }
        }
        
        // Предупреждение о дефолтных ключах безопасности
        if (strpos(self::$apiKeySalt, 'CHANGE_THIS') !== false || 
            strpos(self::$apiKeySalt, 'REPLACE_WITH') !== false ||
            empty(self::$apiKeySalt)) {
            error_log('WARNING: API_KEY_SALT is not configured properly');
        }
        
        if (strpos(self::$jwtSecret, 'CHANGE_THIS') !== false || 
            strpos(self::$jwtSecret, 'REPLACE_WITH') !== false ||
            empty(self::$jwtSecret)) {
            error_log('WARNING: JWT_SECRET is not configured properly');
        }
    }
}

/**
 * Класс для управления подключением к базе данных
 * 
 * Реализует паттерн Singleton для единственного подключения к БД
 * Использует PDO для работы с MySQL/MariaDB
 * Автоматически обрабатывает ошибки подключения и логирует их
 * 
 * @property PDO|null $instance Экземпляр подключения к БД
 */
class Database {
    private static ?PDO $instance = null;
    
    /**
     * Получение экземпляра подключения к базе данных
     * 
     * Метод создает новое подключение при первом вызове
     * При последующих вызовах возвращает существующее подключение
     * В случае ошибки возвращает JSON с описанием проблемы и завершает выполнение
     * 
     * @return PDO Экземпляр подключения к базе данных
     * @throws void Метод не выбрасывает исключения, обрабатывает их внутри
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    Config::$dbHost,
                    Config::$dbName,
                    Config::$dbCharset
                );
                
                self::$instance = new PDO($dsn, Config::$dbUser, Config::$dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                
                $response = [
                    'error' => true,
                    'message' => 'Database connection failed'
                ];
                
                // Показываем детали только при APP_DEBUG=true
                if (Config::$appDebug) {
                    $response['debug'] = [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'dsn' => $dsn,
                        'db_host' => Config::$dbHost,
                        'db_name' => Config::$dbName,
                        'db_user' => Config::$dbUser,
                        'db_charset' => Config::$dbCharset,
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ];
                }
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit;
            }
        }
        
        return self::$instance;
    }
}

/**
 * Класс для обработки API запросов
 * 
 * Основной контроллер приложения, обрабатывающий все входящие запросы
 * Реализует маршрутизацию, валидацию API ключей и формирование ответов
 * 
 * @property PDO $db Подключение к базе данных
 * @property string $method HTTP метод запроса
 * @property string $endpoint Путь endpoint из URL
 * @property array $params Параметры запроса
 */
class API {
    private PDO $db;
    private string $method;
    private string $endpoint;
    private array $params;
    
    /**
     * Конструктор класса API
     * 
     * Инициализация подключения к БД и извлечение параметров запроса
     */
    public function __construct() {
        $this->db = Database::getConnection();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->endpoint = $this->getEndpoint();
        $this->params = $this->getParams();
    }
    
    /**
     * Извлечение endpoint из URL запроса
     * 
     * Метод парсит REQUEST_URI и удаляет префиксы /billing-crm/ и /api/v1/
     * 
     * @return string Путь endpoint без префиксов
     */
    private function getEndpoint(): string {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // Убираем /billing-crm/ если есть
        $uri = preg_replace('#^billing-crm/#', '', $uri);
        
        // Убираем /api/v1/ если есть
        $uri = preg_replace('#^api/v1/#', '', $uri);
        
        return $uri;
    }
    
    /**
     * Извлечение параметров из запроса
     * 
     * Метод читает параметры из $_GET для GET запросов
     * Для остальных методов парсит JSON из тела запроса
     * 
     * @return array Массив параметров запроса
     */
    private function getParams(): array {
        if ($this->method === 'GET') {
            return $_GET;
        }
        
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Извлечение API ключа из заголовка Authorization
     * 
     * Метод ищет заголовок Authorization в формате "Bearer TOKEN"
     * 
     * @return string|null API ключ или null если заголовок отсутствует
     */
    private function getApiKey(): ?string {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Валидация API ключа и получение данных пользователя
     * 
     * Метод проверяет существование ключа в БД и статус пользователя
     * Возвращает информацию о пользователе и его балансе
     * 
     * @param string $apiKey API ключ для валидации
     * @return array|null Данные пользователя или null если ключ невалиден
     */
    private function validateApiKey(string $apiKey): ?array {
        $keyHash = md5($apiKey);
        
        $stmt = $this->db->prepare("
            SELECT 
                ak.id as api_key_id,
                ak.user_id,
                u.status as user_status,
                COALESCE(ub.tokens_available, 0) as tokens_available
            FROM api_keys ak
            JOIN users u ON ak.user_id = u.id
            LEFT JOIN user_balances ub ON u.id = ub.user_id
            WHERE ak.key_hash = ? AND ak.status = 'active'
        ");
        
        $stmt->execute([$keyHash]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Отправка JSON ответа клиенту
     * 
     * Метод устанавливает HTTP код статуса и отправляет JSON
     * Завершает выполнение скрипта после отправки
     * 
     * @param array $data Данные для отправки в формате массива
     * @param int $statusCode HTTP код статуса ответа (по умолчанию 200)
     * @return void
     */
    private function respond(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Обработка входящего запроса и маршрутизация
     * 
     * Метод определяет endpoint и направляет запрос к соответствующему обработчику
     * 
     * @return void
     */
    public function handle(): void {
        // Роутинг
        switch ($this->endpoint) {
            case '':
            case 'index.php':
                $this->handleRoot();
                break;
                
            case 'health':
                $this->handleHealth();
                break;
                
            case 'balance':
                $this->handleBalance();
                break;
                
            case 'generate':
                $this->handleGenerate();
                break;
                
            default:
                $this->respond([
                    'error' => true,
                    'message' => 'Endpoint not found',
                    'endpoint' => $this->endpoint
                ], 404);
        }
    }
    
    /**
     * Обработка корневого endpoint - информация об API
     * 
     * Возвращает название API, версию и список доступных endpoints
     * 
     * @return void
     */
    private function handleRoot(): void {
        $this->respond([
            'name' => 'Billing CRM API',
            'version' => '1.0',
            'status' => 'running',
            'endpoints' => [
                '/api/v1/health' => 'Health check',
                '/api/v1/balance' => 'Check token balance',
                '/api/v1/generate' => 'Generate AI content'
            ],
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Обработка health check endpoint
     * 
     * Проверка работоспособности системы и подключения к БД
     * 
     * @return void
     */
    private function handleHealth(): void {
        try {
            // Проверка подключения к БД
            $this->db->query('SELECT 1');
            
            $this->respond([
                'status' => 'healthy',
                'database' => 'connected',
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->respond([
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage()
            ], 503);
        }
    }
    
    /**
     * Обработка endpoint проверки баланса токенов
     * 
     * Валидация API ключа и возврат информации о балансе пользователя
     * 
     * @return void
     */
    private function handleBalance(): void {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            $this->respond([
                'error' => true,
                'message' => 'API key is required'
            ], 401);
        }
        
        $user = $this->validateApiKey($apiKey);
        
        if (!$user) {
            $this->respond([
                'error' => true,
                'message' => 'Invalid API key'
            ], 401);
        }
        
        if ($user['user_status'] !== 'active') {
            $this->respond([
                'error' => true,
                'message' => 'User account is not active'
            ], 403);
        }
        
        // Получение детальной информации о балансе
        $stmt = $this->db->prepare("
            SELECT 
                tokens_purchased,
                tokens_used,
                tokens_available,
                total_paid,
                last_payment_at
            FROM user_balances
            WHERE user_id = ?
        ");
        
        $stmt->execute([$user['user_id']]);
        $balance = $stmt->fetch();
        
        if (!$balance) {
            $balance = [
                'tokens_purchased' => 0,
                'tokens_used' => 0,
                'tokens_available' => 0,
                'total_paid' => 0.00,
                'last_payment_at' => null
            ];
        }
        
        $this->respond([
            'error' => false,
            'user_id' => $user['user_id'],
            'balance' => $balance
        ]);
    }
    
    /**
     * Обработка endpoint генерации контента через AI
     * 
     * Валидация API ключа, проверка баланса и проксирование запроса к AI провайдеру
     * В текущей версии возвращает заглушку (функционал в разработке)
     * 
     * @return void
     */
    private function handleGenerate(): void {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            $this->respond([
                'error' => true,
                'message' => 'API key is required'
            ], 401);
        }
        
        $user = $this->validateApiKey($apiKey);
        
        if (!$user) {
            $this->respond([
                'error' => true,
                'message' => 'Invalid API key'
            ], 401);
        }
        
        if ($user['tokens_available'] <= 0) {
            $this->respond([
                'error' => true,
                'message' => 'Insufficient tokens',
                'tokens_available' => $user['tokens_available']
            ], 402);
        }
        
        // TODO: Реализация проксирования запросов к AI провайдерам
        // Пока возвращаем заглушку
        
        $this->respond([
            'error' => false,
            'message' => 'AI generation endpoint - coming soon',
            'data' => [
                'status' => 'not_implemented',
                'user_id' => $user['user_id'],
                'tokens_available' => $user['tokens_available']
            ]
        ]);
    }
}

// =============================================================================
// Запуск приложения
// =============================================================================

try {
    // Загрузка конфигурации
    Config::loadEnv();
    
    // Настройка отображения ошибок в зависимости от APP_DEBUG
    ini_set('display_errors', Config::$appDebug ? '1' : '0');
    
    // Установка timezone
    if (!empty(Config::$timezone)) {
        date_default_timezone_set(Config::$timezone);
    }
    
    // Настройка путей к логам если указаны в .env
    if (!empty(Config::$errorLogFile)) {
        ini_set('error_log', Config::$errorLogFile);
    }
    
    // Создание и обработка API запроса
    $api = new API();
    $api->handle();
    
} catch (Exception $e) {
    error_log('Fatal error: ' . $e->getMessage());
    http_response_code(500);
    
    $response = ['error' => true];
    
    if (Config::$appDebug) {
        $response['message'] = $e->getMessage();
        $response['file'] = $e->getFile();
        $response['line'] = $e->getLine();
        $response['trace'] = $e->getTraceAsString();
    } else {
        $response['message'] = 'Internal server error';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}