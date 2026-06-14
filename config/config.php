<?php
/**
 * 天积安全团队配置文件
 *
 * @package HeavenlySecret
 * @version HeaSec v1.0.1
 * @since 2025-10-15
 */

// 团队信息配置
define('HEASEC_TEAM_NAME', '天积安全');
define('HEASEC_TEAM_EN_NAME', 'HeavenlySecret');
define('HEASEC_TEAM_ABBR', 'HeaSec');
define('HEASEC_TEAM_SLOGAN', '日积寸功，乐享安全');
define('HEASEC_SITE_NAME', '天积安全靶场平台');

// 系统版本配置
define('HEASEC_VERSION', 'v1.01');
define('HEASEC_BUILD', '20261015');

// 安全配置
define('HEASEC_SECURITY_LEVEL', 1);
define('HEASEC_TOKEN_EXPIRE', 7200);
define('HEASEC_MAX_LOGIN_ATTEMPTS', 5);

// API配置
define('HEASEC_API_PREFIX', '/api/heasec/');
define('HEASEC_API_VERSION', 'v1');

// 从统一配置文件读取数据库配置
function HeaSec_loadDatabaseConfig() {
    static $config = null;

    if ($config === null) {
        $configFile = __DIR__ . '/config.json';

        if (file_exists($configFile)) {
            $jsonContent = file_get_contents($configFile);
            $jsonConfig = json_decode($jsonContent, true);

            if (isset($jsonConfig['database'])) {
                $config = $jsonConfig['database'];
            } else {
                HeaSec_handleError("配置文件格式错误：缺少database配置", 500);
            }
        } else {
            // 如果JSON配置文件不存在，抛出错误而不是使用硬编码配置
            HeaSec_handleError("配置文件不存在: " . $configFile, 500);
        }
    }

    return $config;
}

// 读取初始化配置
function HeaSec_loadInitializationConfig() {
    static $config = null;

    if ($config === null) {
        $configFile = __DIR__ . '/config.json';

        if (file_exists($configFile)) {
            $jsonContent = file_get_contents($configFile);
            $jsonConfig = json_decode($jsonContent, true);

            if (isset($jsonConfig['initialization'])) {
                $config = $jsonConfig['initialization'];
            } else {
                // 默认配置
                $config = [
                    'check_range_databases' => false,
                    'show_uninitialized_ranges' => true
                ];
            }
        } else {
            // 如果JSON配置文件不存在，使用默认配置
            $config = [
                'check_range_databases' => false,
                'show_uninitialized_ranges' => true
            ];
        }
    }

    return $config;
}

// 数据库配置（保持与原配置的兼容性，从JSON文件读取）
$dbConfig = HeaSec_loadDatabaseConfig();
define('DB_HOST', $dbConfig['host']);
define('DB_PORT', $dbConfig['port']);
define('DB_USER', $dbConfig['username']);
define('DB_PASS', $dbConfig['password']);
define('DB_CHARSET', $dbConfig['charset']);

// 前台和后台专用数据库名
define('DB_NAME', 'heasec_cms');

// 创建MySQL服务器连接（不指定数据库）
function HeaSec_getServerConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $conn;
    } catch (PDOException $e) {
        HeaSec_handleError("MySQL服务器连接失败: " . $e->getMessage(), 500);
    }
}

// 检查并创建数据库
function HeaSec_ensureDatabaseExists() {
    try {
        $serverConn = HeaSec_getServerConnection();

        // 检查数据库是否存在
        $stmt = $serverConn->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([DB_NAME]);

        if ($stmt->rowCount() === 0) {
            // 数据库不存在，创建数据库
            HeaSec_log('database_create', ['database' => DB_NAME]);
            $serverConn->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci");
        }

        $serverConn = null; // 关闭服务器连接

        return true;
    } catch (PDOException $e) {
        HeaSec_handleError("数据库检查或创建失败: " . $e->getMessage(), 500);
    }
}

// 创建HeaSec数据库连接
function HeaSec_getConnection() {
    static $conn = null;

    if ($conn === null) {
        // 先确保数据库存在
        HeaSec_ensureDatabaseExists();

        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            HeaSec_handleError("数据库连接失败: " . $e->getMessage(), 500);
        }
    }

    return $conn;
}

// HeaSec统一返回格式
function HeaSec_returnResponse($success, $message, $data = null, $code = 200) {
    // 设置团队标识响应头
    header('X-Powered-By: HeavenlySecret/HeaSec ' . HEASEC_VERSION);
    header('X-Team-Name: ' . HEASEC_TEAM_ABBR);
    header('Content-Type: application/json');

    http_response_code($code);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'team' => [
            'name' => HEASEC_TEAM_NAME,
            'abbr' => HEASEC_TEAM_ABBR,
            'version' => HEASEC_VERSION
        ]
    ]);
    exit;
}

// HeaSec错误处理
function HeaSec_handleError($message, $code = 500) {
    $teamMessage = "[HeaSec] " . $message;
    error_log($teamMessage);
    HeaSec_returnResponse(false, $teamMessage, null, $code);
}

// HeaSec日志记录
function HeaSec_log($operation, $data = []) {
    $logEntry = [
        'team' => 'HeaSec',
        'operation' => $operation,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown',
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
    ];
    error_log(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    // file_put_contents(__DIR__ . '/../debug_heasec.log', json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
}

// HeaSec安全验证
function HeaSec_validateRequest() {
    // 基础安全验证
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 验证CSRF token等安全措施
        // 这里可以扩展更多安全验证逻辑
    }

    return true;
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 记录初始化日志
HeaSec_log('system_init', [
    'version' => HEASEC_VERSION,
    'build' => HEASEC_BUILD,
    'team_name' => HEASEC_TEAM_NAME
]);
?>