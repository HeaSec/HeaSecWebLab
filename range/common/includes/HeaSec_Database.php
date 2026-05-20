<?php
/**
 * HeaSec天积安全团队 - 数据库连接组件 (完全简化版)
 * 版本: v3.0.0 - 完全简化数据库分配策略
 * 创建日期: 2025-11-07
 * 更新日期: 2025-11-18
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 简化策略：
 * - 直接使用传入的数据库名和完整表名
 * - 移除所有复杂的数据库选择和表前缀逻辑
 * - 保留连接池、错误处理和核心功能
 * - 提供清晰的API接口
 */


/**
 * HeaSec 数据库连接类
 */
class HeaSec_Database {

    private static $connections = array();

    /**
     * 获取数据库连接 - 简化版本
     * @param string $databaseName 数据库名称，直接使用不做转换
     * @param array $connectionConfig 可选的连接配置，覆盖默认配置
     * @return PDO 数据库连接对象
     */
    public static function getConnection($databaseName = '', $connectionConfig = array()) {
        // 如果没有指定数据库名，使用默认数据库名
        if (empty($databaseName)) {
            $databaseName = 'heasec_base';
        }

        $config = self::getDatabaseConfig($databaseName, $connectionConfig);
        $key = md5(serialize($config));

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = self::createNewConnection($config);
        } else {
            // 检查现有连接是否仍然有效
            if (!self::isConnectionValid(self::$connections[$key])) {
                // 连接已失效，重新创建
                unset(self::$connections[$key]);
                self::$connections[$key] = self::createNewConnection($config);
            }
        }

        return self::$connections[$key];
    }

    
    /**
     * 创建新的数据库连接
     * @param array $config 数据库配置
     * @return PDO 数据库连接对象
     */
    private static function createNewConnection($config) {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
                PDO::ATTR_TIMEOUT => 5, // 设置连接超时
                PDO::ATTR_PERSISTENT => false // 禁用持久连接，避免连接状态问题
            ));
            return $pdo;
        } catch (PDOException $e) {
            error_log('[HeaSec] Database connection failed: ' . $e->getMessage());
            throw new Exception('[HeaSec] Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * 检查数据库连接是否有效
     * @param PDO $connection 数据库连接
     * @return bool 连接是否有效
     */
    private static function isConnectionValid($connection) {
        try {
            // 执行一个简单查询来检查连接状态
            $connection->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 从统一配置文件加载数据库基础配置
     * @return array 数据库基础配置
     */
    private static function loadDatabaseConfig() {
        static $config = null;

        if ($config === null) {
            // 尝试从项目根目录的config.json加载配置
            $configFile = dirname(__DIR__, 3) . '/config/config.json';

            if (file_exists($configFile)) {
                $jsonContent = file_get_contents($configFile);
                $jsonConfig = json_decode($jsonContent, true);

                if (isset($jsonConfig['database'])) {
                    $config = $jsonConfig['database'];
                } else {
                    error_log('[HeaSec] 配置文件格式错误：缺少database配置');
                    $config = self::getDefaultConfig();
                }
            } else {
                // 如果配置文件不存在，使用默认配置
                $config = self::getDefaultConfig();
            }
        }

        return $config;
    }

    /**
     * 获取默认数据库配置
     * @return array 默认数据库配置
     * @throws Exception 当配置文件不存在时抛出异常
     */
    private static function getDefaultConfig() {
        // 不再提供硬编码的默认配置，强制要求使用config.json
        throw new Exception('[HeaSec] 配置文件不存在或无法读取，请确保config/config.json文件存在且包含database配置');
    }

    /**
     * 获取数据库配置 - 简化版本
     * @param string $databaseName 数据库名称
     * @param array $overrideConfig 覆盖配置
     * @return array 数据库配置
     */
    private static function getDatabaseConfig($databaseName = '', $overrideConfig = array()) {
        // 从统一配置文件加载基础配置
        $baseConfig = self::loadDatabaseConfig();

        // 构建完整配置
        $config = array(
            'host' => $baseConfig['host'],
            'port' => $baseConfig['port'],
            'username' => $baseConfig['username'],
            'password' => $baseConfig['password'],
            'database' => empty($databaseName) ? 'heasec_base' : $databaseName,
            'charset' => $baseConfig['charset']
        );

        // 合并覆盖配置
        return array_merge($config, $overrideConfig);
    }

    
    /**
     * 获取前台数据库连接
     * @return PDO 前台数据库连接对象
     */
    public static function getFrontendConnection() {
        return self::getConnection('heasec_cms');
    }

    /**
     * 获取默认数据库连接
     * @return PDO 默认数据库连接对象
     */
    public static function getDefaultConnection() {
        return self::getConnection('heasec_base');
    }

    /**
     * 获取MySQL服务器连接（不指定数据库）
     * @return PDO MySQL服务器连接对象
     */
    public static function getServerConnection() {
        static $serverConnection = null;

        if ($serverConnection === null) {
            $config = self::loadDatabaseConfig();

            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
                $serverConnection = new PDO($dsn, $config['username'], $config['password'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ));
            } catch (PDOException $e) {
                error_log('[HeaSec] Server connection failed: ' . $e->getMessage());
                throw new Exception('[HeaSec] Server connection failed: ' . $e->getMessage());
            }
        } else {
            // 检查连接是否仍然有效
            try {
                $serverConnection->query("SELECT 1");
            } catch (Exception $e) {
                // 连接已失效，重新创建
                $serverConnection = null;
                return self::getServerConnection();
            }
        }

        return $serverConnection;
    }
}

/**
 * HeaSec 数据库操作辅助类
 */
class HeaSec_DatabaseHelper {

    /**
     * 查询单行数据 - 简化版本
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @param string $databaseName 数据库名称（简化策略）
     * @return array|false 查询结果
     */
    public static function fetchOne($sql, $params = array(), $databaseName = '') {
        try {
            $db = HeaSec_Database::getConnection($databaseName);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('[HeaSec] Query failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询多行数据 - 简化版本
     * @param string $sql SQL语句
     * @param array $params 参数数组
     * @param string $databaseName 数据库名称（简化策略）
     * @return array 查询结果数组
     */
    public static function fetchAll($sql, $params = array(), $databaseName = '') {
        try {
            $db = HeaSec_Database::getConnection($databaseName);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('[HeaSec] Query failed: ' . $e->getMessage());
            return array();
        }
    }

    
    
    
    }


?>