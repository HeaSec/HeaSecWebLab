<?php
/**
 * ========================================
 * HeaSec天积安全团队 - 单个靶场数据库初始化API接口
 * Single Range Database Initialization API
 * 版本: v1.0.0
 * 创建日期: 2025-11-23
 * 团队: 天积安全 (HeavenlySecret)
 * ========================================
 */

// 开启输出缓冲，防止header警告
ob_start();

require_once __DIR__ . '/../../config/config.php';

class HeaSec_RangeDatabaseInitializer {
    private $db;

    public function __construct() {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 初始化单个靶场数据库
     */
    public function initializeRange() {
        HeaSec_validateRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            HeaSec_handleError('请求方法不支持', 405);
        }

        // 验证必需参数
        if (!isset($_POST['range_directory']) || empty($_POST['range_directory'])) {
            HeaSec_handleError('靶场目录参数缺失', 400);
        }

        $rangeDirectory = $_POST['range_directory'];

        // 安全检查：防止目录遍历攻击
        if (strpos($rangeDirectory, '..') !== false || strpos($rangeDirectory, '\\') !== false) {
            HeaSec_handleError('非法的靶场目录参数', 400);
        }

        try {
            $rangePath = __DIR__ . '/../../range/' . ltrim($rangeDirectory, '/');

            // 验证靶场目录是否存在
            if (!is_dir($rangePath)) {
                HeaSec_handleError('靶场目录不存在: ' . $rangeDirectory, 404);
            }

            // 查找初始化SQL文件
            $initSqlFile = $this->findInitSqlFile($rangePath);
            if (!$initSqlFile) {
                HeaSec_handleError('靶场初始化SQL文件不存在', 404);
            }

            // 执行数据库初始化
            $this->executeRangeDatabaseInit($initSqlFile, $rangeDirectory);

            // 记录操作日志
            HeaSec_log('range_database_init', [
                'success' => true,
                'range_directory' => $rangeDirectory,
                'sql_file' => str_replace(__DIR__, '', $initSqlFile),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            HeaSec_returnResponse(true, '靶场数据库初始化成功！', [
                'range_directory' => $rangeDirectory,
                'timestamp' => date('Y-m-d H:i:s'),
                'team' => 'HeaSec天积安全团队'
            ]);

        } catch (Exception $e) {
            // 记录错误日志
            HeaSec_log('range_database_init', [
                'success' => false,
                'range_directory' => $rangeDirectory,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            HeaSec_handleError('[HeaSec] 靶场数据库初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 查找初始化SQL文件
     */
    private function findInitSqlFile($rangePath) {
        // 常见的SQL文件路径
        $possiblePaths = [
            $rangePath . '/database/init_database.sql',
            $rangePath . '/init_database.sql',
            $rangePath . '/database/init.sql',
            $rangePath . '/init.sql'
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 执行靶场数据库初始化
     */
    private function executeRangeDatabaseInit($sqlFile, $rangeDirectory) {
        // 读取SQL文件内容
        $sqlContent = file_get_contents($sqlFile);
        if ($sqlContent === false) {
            throw new Exception('无法读取SQL文件: ' . $sqlFile);
        }

        // 提取数据库名（如果有的话）
        $databaseName = $this->extractDatabaseFromSQL($sqlContent);

        // 如果SQL文件指定了数据库，需要切换到该数据库
        if ($databaseName) {
            // 检查数据库是否存在，不存在则创建
            $this->ensureDatabaseExists($databaseName);

            // 切换到目标数据库
            $this->db->exec("USE `$databaseName`");
        }

        try {
            // 执行SQL文件
            $this->executeSqlFile($sqlFile);

            // 切回默认数据库（如果切换过）
            if ($databaseName) {
                $this->db->exec("USE `heasec_cms`");
            }

        } catch (Exception $e) {
            // 确保切换回默认数据库
            try {
                $this->db->exec("USE `heasec_cms`");
            } catch (Exception $e2) {
                // 忽略切换错误
            }
            throw $e;
        }
    }

    /**
     * 确保数据库存在
     */
    private function ensureDatabaseExists($databaseName) {
        try {
            $stmt = $this->db->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$databaseName]);

            if ($stmt->rowCount() === 0) {
                // 数据库不存在，创建数据库
                $this->db->exec("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                HeaSec_log('database_create', ['database' => $databaseName, 'context' => 'range_init']);
            }
        } catch (PDOException $e) {
            throw new Exception('检查或创建数据库失败: ' . $e->getMessage());
        }
    }

    /**
     * 从SQL文件中提取数据库名
     */
    private function extractDatabaseFromSQL($sqlContent) {
        // 移除注释
        $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

        // 查找CREATE DATABASE语句
        if (preg_match('/CREATE\s+DATABASE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sqlContent, $matches)) {
            return $matches[1];
        }

        // 查找USE语句
        if (preg_match('/USE\s+`?(\w+)`?/i', $sqlContent, $matches)) {
            return $matches[1];
        }

        return null; // 如果没有找到数据库名，返回null表示使用默认数据库
    }

    /**
     * 执行SQL文件
     */
    private function executeSqlFile($sqlFile) {
        $sqlContent = file_get_contents($sqlFile);

        if ($sqlContent === false) {
            throw new Exception('无法读取SQL文件: ' . $sqlFile);
        }

        // 移除注释和多余空行
        $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(SET|DROP|CREATE)/i', $statement)) {
                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    // 忽略某些语句的错误，继续执行
                    error_log('[HeaSec] SQL执行警告: ' . $e->getMessage());
                }
            }
        }

        // 重新读取并执行INSERT语句
        $insertStatements = [];
        $lines = file($sqlFile);
        $currentStatement = '';
        $inInsert = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过注释和空行
            if (empty($line) || preg_match('/^(--|\/\*|\*)/', $line)) {
                continue;
            }

            if (preg_match('/^INSERT INTO/i', $line)) {
                $inInsert = true;
            }

            if ($inInsert) {
                $currentStatement .= $line . ' ';

                if (substr($line, -1) === ';') {
                    $insertStatements[] = $currentStatement;
                    $currentStatement = '';
                    $inInsert = false;
                }
            }
        }

        // 执行INSERT语句
        foreach ($insertStatements as $insertStatement) {
            if (!empty($insertStatement)) {
                try {
                    $this->db->exec($insertStatement);
                } catch (PDOException $e) {
                    error_log('[HeaSec] INSERT执行警告: ' . $e->getMessage());
                }
            }
        }
    }
}

// 处理请求
$initializer = new HeaSec_RangeDatabaseInitializer();
$initializer->initializeRange();
?>