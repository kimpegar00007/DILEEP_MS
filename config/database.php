<?php
// config/database.php
// Database Configuration for DOLE DILP Monitoring System

// Load environment variables from .env if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = preg_split("/(\r\n|\n|\r)/", $envFile);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, strlen('export ')));
        }
        
        // Parse KEY=VALUE format
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Database configuration with environment fallbacks
if (!function_exists('env_any')) {
    function env_any(array $keys, $default = null) {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return $value;
            }
            if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                return $_ENV[$key];
            }
        }
        return $default;
    }
}

$requestHost = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $requestHost = strtolower((string) $_SERVER['HTTP_HOST']);
}

$isLocalHost = $requestHost === 'localhost'
    || $requestHost === '127.0.0.1'
    || strpos($requestHost, 'localhost:') === 0
    || strpos($requestHost, '127.0.0.1:') === 0;

$allowRemoteDbFromLocal = filter_var(env_any(['ALLOW_REMOTE_DB_FROM_LOCAL'], 'false'), FILTER_VALIDATE_BOOLEAN);

$configuredHost = (string) env_any(['DB_HOST'], 'localhost');
$configuredPort = (string) env_any(['DB_PORT'], '3306');
$configuredName = (string) env_any(['DB_NAME', 'DB_DATABASE'], 'dilp_monitoring');
$configuredUser = (string) env_any(['DB_USER', 'DB_USERNAME'], 'root');
$configuredPass = (string) env_any(['DB_PASS', 'DB_PASSWORD'], '');

$looksRemoteHost = $configuredHost !== 'localhost' && $configuredHost !== '127.0.0.1';

if ($isLocalHost && !$allowRemoteDbFromLocal && $looksRemoteHost) {
    $configuredHost = '127.0.0.1';
    $configuredPort = '3306';
    $configuredName = 'dilp_monitoring';
    $configuredUser = 'root';
    $configuredPass = '';
}

define('DB_HOST', $configuredHost);
define('DB_PORT', $configuredPort);
define('DB_NAME', $configuredName);
define('DB_USER', $configuredUser);
define('DB_PASS', $configuredPass);
define('DB_CHARSET', 'utf8mb4');
define('DB_SOCKET', env_any(['DB_SOCKET'], ''));

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = $this->connectWithFallback();
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    private function connectWithFallback() {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $dsn = $this->buildDSN();

        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $shouldRetryTcp = empty(DB_SOCKET)
                && strtolower((string) DB_HOST) === 'localhost'
                && (string) $e->getCode() === '2002';

            if (!$shouldRetryTcp) {
                throw $e;
            }

            $fallbackDsn = "mysql:host=127.0.0.1;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            return new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
        }
    }
    
    private function buildDSN() {
        if (!empty(DB_SOCKET)) {
            return "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        }
        return "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    
    private function handleConnectionError(PDOException $e) {
        $errorCode = $e->getCode();
        $errorMsg = $e->getMessage();
        
        $debugInfo = "Database connection failed.\n";
        $debugInfo .= "Error Code: " . $errorCode . "\n";
        $debugInfo .= "Error Message: " . $errorMsg . "\n";
        $debugInfo .= "Host: " . DB_HOST . "\n";
        $debugInfo .= "Port: " . DB_PORT . "\n";
        $debugInfo .= "Database: " . DB_NAME . "\n";
        $debugInfo .= "User: " . DB_USER . "\n";
        $debugInfo .= "PDO Driver Available: " . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
        
        $appEnv = strtolower((string) getenv('APP_ENV'));
        $appDebug = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
        $isDev = $appDebug || in_array($appEnv, ['development', 'local', 'dev'], true);

        error_log($debugInfo);

        if ($isDev) {
            die($debugInfo);
        }
        die("Database connection failed. Please contact the administrator.");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
