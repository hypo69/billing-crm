<?php
/**
 * @file debug.php
 * @description Billing CRM - Инструмент диагностики и отладки системы
 * 
 * Утилита для диагностики проблем с конфигурацией, подключением к БД и правами доступа
 * 
 * Функционал:
 * - Проверка версии PHP и необходимых расширений
 * - Анализ файла .env и параметров конфигурации
 * - Тестирование подключения к базе данных
 * - Проверка прав доступа к файлам и директориям
 * - Отображение списка таблиц и количества записей
 * 
 * ВАЖНО: Файл содержит конфиденциальную информацию!
 * Используйте только для отладки на dev окружении
 * Удалите файл после завершения диагностики
 * 
 * @version 1.0
 * @author Billing CRM Team
 * @date 2025-10-24
 * 
 * @example
 * // Открытие в браузере
 * http://yourdomain.com/billing-crm/debug.php
 * 
 * // Удаление после использования
 * sudo rm /var/www/html/billing-crm/debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing CRM - Debug</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .box { background: #f5f5f5; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .error-box { background: #ffebee; border-left-color: #f44336; }
        .success-box { background: #e8f5e9; border-left-color: #4CAF50; }
        pre { background: #263238; color: #aed581; padding: 15px; overflow-x: auto; border-radius: 4px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        ul { line-height: 1.8; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background-color: #f5f5f5; font-weight: 600; }
    </style>
</head>
<body>
    <h1>🔍 Billing CRM - Debug Information</h1>

<?php
// ============================================================================
// 1. PHP Information
// ============================================================================
echo "<h2>1. PHP Environment</h2>";
echo "<table>";
echo "<tr><th>Parameter</th><th>Value</th></tr>";
echo "<tr><td>PHP Version</td><td><strong>" . PHP_VERSION . "</strong></td></tr>";
echo "<tr><td>PHP SAPI</td><td>" . php_sapi_name() . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Script Path</td><td>" . __FILE__ . "</td></tr>";
echo "</table>";

// ============================================================================
// 2. Required PHP Extensions
// ============================================================================
echo "<h2>2. PHP Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'curl'];
echo "<table>";
echo "<tr><th>Extension</th><th>Status</th></tr>";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>';
    echo "<tr><td>$ext</td><td>$status</td></tr>";
}
echo "</table>";

// ============================================================================
// 3. .env File Check
// ============================================================================
echo "<h2>3. .env File</h2>";
$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    echo "<div class='box success-box'>";
    echo "<p class='success'>✓ .env file exists</p>";
    echo "<p><strong>Path:</strong> $envPath</p>";
    echo "<p><strong>Size:</strong> " . filesize($envPath) . " bytes</p>";
    echo "<p><strong>Readable:</strong> " . (is_readable($envPath) ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Permissions:</strong> " . substr(sprintf('%o', fileperms($envPath)), -4) . "</p>";
    echo "</div>";
    
    // Parse .env
    echo "<h3>Configuration (sanitized):</h3>";
    $config = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    echo "<table>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $config[$key] = $value;
        
        // Sanitize sensitive data
        if (strpos($key, 'PASS') !== false || 
            strpos($key, 'SECRET') !== false || 
            strpos($key, 'KEY') !== false) {
            $displayValue = empty($value) ? '<span class="error">EMPTY!</span>' : '<span class="success">***SET*** (length: ' . strlen($value) . ')</span>';
        } else {
            $displayValue = htmlspecialchars($value);
        }
        
        // Highlight DB config
        if (strpos($key, 'DB_') === 0) {
            echo "<tr style='background: #fff3e0;'><td><strong>$key</strong></td><td>$displayValue</td></tr>";
        } else {
            echo "<tr><td>$key</td><td>$displayValue</td></tr>";
        }
    }
    echo "</table>";
    
} else {
    echo "<div class='box error-box'>";
    echo "<p class='error'>✗ .env file NOT found</p>";
    echo "<p><strong>Expected path:</strong> $envPath</p>";
    echo "</div>";
    $config = [];
}

// ============================================================================
// 4. Database Connection Test
// ============================================================================
echo "<h2>4. Database Connection Test</h2>";

$dbHost = $config['DB_HOST'] ?? '';
$dbName = $config['DB_NAME'] ?? '';
$dbUser = $config['DB_USER'] ?? '';
$dbPass = $config['DB_PASS'] ?? '';
$dbCharset = $config['DB_CHARSET'] ?? 'utf8mb4';

if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
    echo "<div class='box error-box'>";
    echo "<p class='error'>✗ Missing required database configuration</p>";
    echo "<ul>";
    if (empty($dbHost)) echo "<li>DB_HOST is not set</li>";
    if (empty($dbName)) echo "<li>DB_NAME is not set</li>";
    if (empty($dbUser)) echo "<li>DB_USER is not set</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='box'>";
    echo "<p><strong>DB_HOST:</strong> " . htmlspecialchars($dbHost) . "</p>";
    echo "<p><strong>DB_NAME:</strong> " . htmlspecialchars($dbName) . "</p>";
    echo "<p><strong>DB_USER:</strong> " . htmlspecialchars($dbUser) . "</p>";
    echo "<p><strong>DB_PASS:</strong> " . (empty($dbPass) ? '<span class="error">EMPTY!</span>' : '<span class="success">***SET*** (length: ' . strlen($dbPass) . ')</span>') . "</p>";
    echo "<p><strong>DB_CHARSET:</strong> " . htmlspecialchars($dbCharset) . "</p>";
    echo "</div>";
    
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
        echo "<p><strong>DSN:</strong> <code>" . htmlspecialchars($dsn) . "</code></p>";
        
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "<div class='box success-box'>";
        echo "<p class='success' style='font-size: 18px;'>✓ Database Connection: SUCCESS</p>";
        echo "</div>";
        
        // Check tables
        echo "<h3>Database Tables:</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>Found <strong>" . count($tables) . "</strong> tables:</p>";
            echo "<table>";
            echo "<tr><th>#</th><th>Table Name</th><th>Rows</th></tr>";
            foreach ($tables as $i => $table) {
                try {
                    $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
                    $count = $countStmt->fetch()['cnt'];
                    echo "<tr><td>" . ($i + 1) . "</td><td>$table</td><td>$count</td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>" . ($i + 1) . "</td><td>$table</td><td>-</td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<div class='box warning'>";
            echo "<p class='warning'>⚠ Database is empty (no tables found)</p>";
            echo "<p>Run the SQL script to create tables: <code>create_db_fixed.sql</code></p>";
            echo "</div>";
        }
        
        // Test query
        echo "<h3>Test Query:</h3>";
        try {
            $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db, USER() as user");
            $info = $stmt->fetch();
            echo "<table>";
            echo "<tr><th>MySQL Version</th><td>" . $info['version'] . "</td></tr>";
            echo "<tr><th>Current Database</th><td>" . $info['db'] . "</td></tr>";
            echo "<tr><th>Current User</th><td>" . $info['user'] . "</td></tr>";
            echo "</table>";
        } catch (Exception $e) {
            echo "<p class='error'>Failed to run test query: " . $e->getMessage() . "</p>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='box error-box'>";
        echo "<p class='error' style='font-size: 18px;'>✗ Database Connection: FAILED</p>";
        echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
        echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        echo "</div>";
        
        echo "<h3>Troubleshooting Steps:</h3>";
        echo "<ul>";
        echo "<li>Check if MySQL/MariaDB service is running: <code>sudo systemctl status mysql</code></li>";
        echo "<li>Verify database credentials in .env file</li>";
        echo "<li>Test connection manually: <code>mysql -u " . htmlspecialchars($dbUser) . " -p -h " . htmlspecialchars($dbHost) . " " . htmlspecialchars($dbName) . "</code></li>";
        echo "<li>Check user permissions: <code>SHOW GRANTS FOR '" . htmlspecialchars($dbUser) . "'@'" . htmlspecialchars($dbHost) . "';</code></li>";
        echo "<li>Verify DB_HOST (try 'localhost', '127.0.0.1', or socket path)</li>";
        echo "</ul>";
        
        // Common error codes
        echo "<h3>Common Error Codes:</h3>";
        echo "<ul>";
        echo "<li><strong>2002</strong>: Can't connect to MySQL server (check if service is running)</li>";
        echo "<li><strong>1045</strong>: Access denied (wrong username or password)</li>";
        echo "<li><strong>1049</strong>: Unknown database (database doesn't exist)</li>";
        echo "<li><strong>2054</strong>: Server sent charset unknown (charset issue)</li>";
        echo "</ul>";
    }
}

// ============================================================================
// 5. File Permissions
// ============================================================================
echo "<h2>5. File Permissions</h2>";
$files = [
    'index.php',
    '.env',
    '.htaccess',
    'logs/'
];

echo "<table>";
echo "<tr><th>File/Directory</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Permissions</th></tr>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    echo "<tr>";
    echo "<td>$file</td>";
    echo "<td>" . ($exists ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>" . ($readable ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>" . ($writable ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "<td>$perms</td>";
    echo "</tr>";
}
echo "</table>";

?>

    <hr style="margin: 40px 0;">
    <div class="box error-box">
        <p class="error" style="font-size: 16px;">⚠️ IMPORTANT: Delete this file after debugging!</p>
        <pre>sudo rm <?php echo __FILE__; ?></pre>
    </div>

</body>
</html>