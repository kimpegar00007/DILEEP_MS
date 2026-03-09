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

// Use 127.0.0.1 instead of localhost for better network compatibility
define('DB_HOST', (string) env_any(['DB_HOST'], '127.0.0.1'));
define('DB_PORT', (string) env_any(['DB_PORT'], '3306'));
define('DB_NAME', (string) env_any(['DB_NAME', 'DB_DATABASE'], 'dilp_monitoring'));
define('DB_USER', (string) env_any(['DB_USER', 'DB_USERNAME'], 'root'));
define('DB_PASS', (string) env_any(['DB_PASS', 'DB_PASSWORD'], ''));
define('DB_CHARSET', 'utf8mb4');
define('DB_SOCKET', env_any(['DB_SOCKET'], ''));

class Database {
    private static $instance = null;
    private $connection;
    private $lastError = null;
    
    private function __construct() {
        try {
            $this->connection = $this->connectWithFallback();
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    private function connectWithFallback() {
        $options = $this->getPDOOptions();
        $hostSequence = $this->getHostSequence();
        $lastException = null;

        foreach ($hostSequence as $host) {
            try {
                $dsn = $this->buildDSN($host);
                $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                return $connection;
            } catch (PDOException $e) {
                $lastException = $e;
                error_log("Failed to connect with host '$host': " . $e->getMessage());
                continue;
            }
        }

        if ($lastException) {
            throw $lastException;
        }
        throw new PDOException("No valid connection method available");
    }

    private function getPDOOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
            PDO::ATTR_TIMEOUT => 10,
        ];
    }

    private function getHostSequence() {
        $hosts = [];
        
        if (!empty(DB_SOCKET)) {
            $hosts[] = ['type' => 'socket', 'value' => DB_SOCKET];
        }

        $currentHost = DB_HOST;
        $hosts[] = ['type' => 'host', 'value' => $currentHost];

        if ($currentHost === '127.0.0.1') {
            $hosts[] = ['type' => 'host', 'value' => 'localhost'];
        } elseif (strtolower($currentHost) === 'localhost') {
            $hosts[] = ['type' => 'host', 'value' => '127.0.0.1'];
        }

        $hosts[] = ['type' => 'host', 'value' => 'localhost'];
        $hosts[] = ['type' => 'host', 'value' => '127.0.0.1'];

        $hostname = gethostname();
        if ($hostname && $hostname !== false) {
            $hosts[] = ['type' => 'host', 'value' => $hostname];
            $hosts[] = ['type' => 'host', 'value' => 'localhost.localdomain'];
        }

        return array_unique($hosts, SORT_REGULAR);
    }
    
    private function buildDSN($hostConfig) {
        if ($hostConfig['type'] === 'socket') {
            return "mysql:unix_socket=" . $hostConfig['value'] . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        }
        return "mysql:host=" . $hostConfig['value'] . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    
    private function handleConnectionError(PDOException $e) {
        $errorCode = $e->getCode();
        $errorMsg = $e->getMessage();
        
        $debugInfo = "Database connection failed.\n";
        $debugInfo .= "Error Code: " . $errorCode . "\n";
        $debugInfo .= "Error Message: " . $errorMsg . "\n";
        $debugInfo .= "Configured Host: " . DB_HOST . "\n";
        $debugInfo .= "Port: " . DB_PORT . "\n";
        $debugInfo .= "Database: " . DB_NAME . "\n";
        $debugInfo .= "User: " . DB_USER . "\n";
        $debugInfo .= "PDO Driver Available: " . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
        $debugInfo .= "Socket: " . (empty(DB_SOCKET) ? 'not configured' : DB_SOCKET) . "\n";
        $debugInfo .= "\nTroubleshooting Steps:\n";
        $debugInfo .= "1. Verify MariaDB/MySQL is running\n";
        $debugInfo .= "2. Check user permissions: GRANT ALL ON dilp_monitoring.* TO 'root'@'localhost';\n";
        $debugInfo .= "3. Verify user exists for both 'localhost' and '127.0.0.1'\n";
        $debugInfo .= "4. Check MariaDB bind-address in my.cnf (should be 0.0.0.0 or 127.0.0.1)\n";
        
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