<?php
/**
 * ========================================
 * HeaSec天积安全团队 - 数据库重置API接口
 * Database Reset API
 * 版本: v1.0.0
 * 创建日期: 2025-10-25
 * 团队: 天积安全 (HeavenlySecret)
 * ========================================
 */

// 开启输出缓冲，防止header警告
ob_start();

require_once __DIR__ . '/../../config/config.php';

class HeaSec_DatabaseReset
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 创建独立的数据库连接（用于靶场重置，不影响主连接事务状态）
     * 注意：不预设数据库，让SQL文件中的USE语句来切换数据库
     */
    private function createIndependentConnection()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * 重置数据库
     */
    public function resetDatabase()
    {
        HeaSec_validateRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            HeaSec_handleError('请求方法不支持', 405);
        }

        // 检查确认参数
        if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'YES_RESET_DATABASE') {
            HeaSec_handleError('重置确认参数无效或缺失', 400);
        }

        // 获取重置选项
        $resetLearningStatus = isset($_POST['reset_learning_status']) && $_POST['reset_learning_status'] === '1';
        $resetRangeDatabases = isset($_POST['reset_range_databases']) && $_POST['reset_range_databases'] === '1';
        $resetSmsSimulator = isset($_POST['reset_sms_simulator']) && $_POST['reset_sms_simulator'] === '1';
        $selectiveRangeReset = isset($_POST['selective_range_reset']) && $_POST['selective_range_reset'] === '1';
        $targetRanges = isset($_POST['target_ranges']) ? $_POST['target_ranges'] : [];

        try {
            // 开始事务
            $this->db->beginTransaction();

            $rangeResetResults = [];

            // 判断重置模式
            if ($selectiveRangeReset && !empty($targetRanges)) {
                // 选择性重置模式：只重置指定的靶场数据库
                $rangeResetResults = $this->resetSpecificRangeDatabases($targetRanges);

                // 记录操作日志
                HeaSec_log('selective_range_database_reset', [
                    'success' => true,
                    'target_ranges' => $targetRanges,
                    'range_results' => $rangeResetResults,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                HeaSec_returnResponse(true, '指定靶场数据库重置成功！', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'team' => 'HeaSec天积安全团队',
                    'auto_refresh' => true,
                    'reset_type' => 'selective',
                    'target_ranges' => $targetRanges,
                    'range_results' => $rangeResetResults
                ]);

            } else {
                // 完整重置模式：重置主数据库和（可选的）所有靶场数据库

                // 判断是否为初始化模式（由前端显式标记决定）
                $isInitializationMode = isset($_POST['is_initialization']) && $_POST['is_initialization'] === '1';

                // 1. 先保存学习状态（如果不重置学习情况）
                $savedLearningStatus = [];
                if (!$resetLearningStatus) {
                    $savedLearningStatus = $this->saveCurrentLearningStatus();
                }

                // 2. 重置主数据库
                $this->resetMainDatabase();

                // 3. 恢复学习状态（如果不重置学习情况）
                if (!$resetLearningStatus && !empty($savedLearningStatus)) {
                    $this->restoreLearningStatus($savedLearningStatus);
                }

                // 4. 提交主数据库事务（先完成主数据库操作，确保靶场重置不影响主事务）
                $this->db->commit();

                // 5. 初始化/重置子靶场数据库（在主事务提交之后，使用独立连接）
                if ($isInitializationMode) {
                    // 初始化模式：自动初始化所有子靶场数据库
                    $rangeResetResults = $this->initRangeDatabases();
                } elseif ($resetRangeDatabases) {
                    // 重置模式：用户手动勾选才重置
                    $rangeResetResults = $this->resetRangeDatabases();
                }

                // 6. 重置短信模拟器数据库（仅在用户勾选时执行）
                $smsResetResult = null;
                if ($resetSmsSimulator) {
                    $smsResetResult = $this->resetSmsSimulatorDatabase();
                }

                // 记录操作日志
                HeaSec_log('database_reset', [
                    'success' => true,
                    'is_initialization' => $isInitializationMode,
                    'reset_learning_status' => $resetLearningStatus,
                    'reset_range_databases' => $resetRangeDatabases,
                    'reset_sms_simulator' => $resetSmsSimulator,
                    'range_results' => $rangeResetResults,
                    'sms_reset_result' => $smsResetResult,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                $message = $isInitializationMode ? '数据库初始化成功！' : '数据库重置成功！';
                if ($resetRangeDatabases) {
                    $message .= ' 靶场数据库也已重置。';
                }
                if ($resetSmsSimulator) {
                    if ($smsResetResult && $smsResetResult['success']) {
                        $message .= ' 短信模拟器数据库也已重置。';
                    } else {
                        // 短信模拟器重置失败，返回错误响应
                        $errorMessage = $smsResetResult && isset($smsResetResult['message'])
                            ? $smsResetResult['message']
                            : '短信模拟器数据库重置失败';
                        HeaSec_returnResponse(false, $message . ' ' . $errorMessage, [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'team' => 'HeaSec天积安全团队',
                            'auto_refresh' => false,
                            'reset_type' => $isInitializationMode ? 'initialization' : 'full',
                            'reset_learning_status' => $resetLearningStatus,
                            'reset_range_databases' => $resetRangeDatabases,
                            'reset_sms_simulator' => $resetSmsSimulator,
                            'range_results' => $rangeResetResults,
                            'sms_reset_result' => $smsResetResult
                        ]);
                    }
                }

                // 检查靶场重置结果，如果有失败的情况，返回警告响应
                if ($resetRangeDatabases && !empty($rangeResetResults)) {
                    $failedRanges = array_filter($rangeResetResults, function ($result) {
                        return isset($result['status']) && $result['status'] === 'error';
                    });
                    if (!empty($failedRanges)) {
                        $failedCount = count($failedRanges);
                        $message .= " 注意：{$failedCount}个靶场数据库重置失败。";
                        // 设置自动刷新为false，让用户看到错误信息
                        HeaSec_returnResponse(true, $message, [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'team' => 'HeaSec天积安全团队',
                            'auto_refresh' => false,
                            'reset_type' => $isInitializationMode ? 'initialization' : 'full',
                            'reset_learning_status' => $resetLearningStatus,
                            'reset_range_databases' => $resetRangeDatabases,
                            'reset_sms_simulator' => $resetSmsSimulator,
                            'range_results' => $rangeResetResults,
                            'sms_reset_result' => $smsResetResult,
                            'has_warnings' => true
                        ]);
                    }
                }

                $message .= '系统将自动刷新...';

                HeaSec_returnResponse(true, $message, [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'team' => 'HeaSec天积安全团队',
                    'auto_refresh' => true,
                    'reset_type' => $isInitializationMode ? 'initialization' : 'full',
                    'reset_learning_status' => $resetLearningStatus,
                    'reset_range_databases' => $resetRangeDatabases,
                    'reset_sms_simulator' => $resetSmsSimulator,
                    'range_results' => $rangeResetResults,
                    'sms_reset_result' => $smsResetResult
                ]);
            }

        } catch (Exception $e) {
            // 回滚事务
            $this->db->rollBack();
            HeaSec_handleError('[HeaSec] 数据库重置失败: ' . $e->getMessage());
        }
    }

    /**
     * 保存当前学习状态
     */
    private function saveCurrentLearningStatus()
    {
        try {
            $stmt = $this->db->query("SELECT id, learning_status FROM links WHERE learning_status != '待学习'");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[HeaSec] 保存学习状态失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 恢复学习状态
     */
    private function restoreLearningStatus($savedStatus)
    {
        try {
            $stmt = $this->db->prepare("UPDATE links SET learning_status = :status WHERE id = :id");
            foreach ($savedStatus as $status) {
                $stmt->execute([
                    ':status' => $status['learning_status'],
                    ':id' => $status['id']
                ]);
            }
        } catch (PDOException $e) {
            error_log('[HeaSec] 恢复学习状态失败: ' . $e->getMessage());
        }
    }

    /**
     * 重置主数据库
     */
    private function resetMainDatabase()
    {
        // 前台系统需要的表（其他表都应该被删除）
        $allowedTables = ['all_categories', 'links', 'admin_users', 'heasec_team_info'];

        // 临时禁用外键检查
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // 1. 获取当前数据库中的所有表
        $result = $this->db->query("SHOW TABLES");
        $allTables = $result->fetchAll(PDO::FETCH_COLUMN);

        // 2. 删除不属于前台系统的表（清理之前bug遗留的靶场表）
        foreach ($allTables as $table) {
            if (!in_array($table, $allowedTables)) {
                $this->db->exec("DROP TABLE IF EXISTS `" . $table . "`");
                error_log('[HeaSec] 清理非前台表: ' . $table);
            }
        }

        // 3. 清空前台系统表的数据
        foreach ($allowedTables as $table) {
            $result = $this->db->query("SHOW TABLES LIKE '" . $table . "'");
            if ($result->rowCount() > 0) {
                $this->db->exec("TRUNCATE TABLE `" . $table . "`");
            }
        }

        // 重新启用外键检查
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

        // 4. 读取并执行初始化SQL文件
        $init_sql_file = __DIR__ . '/../../database/init_database.sql';

        if (!file_exists($init_sql_file)) {
            throw new Exception('初始化数据库文件不存在: ' . $init_sql_file);
        }

        $this->executeSqlFile($init_sql_file);
    }

    /**
     * 执行SQL文件（使用主连接，仅用于主数据库操作）
     * 主数据库连接已预设到heasec_cms，所以移除USE语句
     */
    private function executeSqlFile($sql_file)
    {
        $this->executeSqlFileOnConnection($this->db, $sql_file, false);
    }

    /**
     * 使用独立连接执行SQL文件（用于靶场重置，失败时抛出异常）
     */
    private function executeSqlFileIndependent($sql_file)
    {
        $connection = $this->createIndependentConnection();
        $this->executeSqlFileOnConnection($connection, $sql_file, true, true);
        $connection = null; // 关闭独立连接
    }

    /**
     * 在指定数据库连接上执行SQL文件
     * @param PDO $connection 数据库连接
     * @param string $sql_file SQL文件路径
     * @param bool $preserveUseStatement 是否保留USE语句（靶场重置需要保留）
     * @param bool $throwOnError 是否在SQL执行错误时抛出异常（靶场重置应为true）
     */
    private function executeSqlFileOnConnection($connection, $sql_file, $preserveUseStatement = true, $throwOnError = false)
    {
        $sql_content = file_get_contents($sql_file);

        // 根据参数决定是否移除USE语句
        // 靶场重置需要保留USE语句，让SQL文件自己切换到正确的数据库
        // 主数据库重置可以移除USE语句（因为连接已预设到heasec_cms）
        if (!$preserveUseStatement) {
            $sql_content = preg_replace('/^USE\s+`[^`]+`;?\s*/im', '', $sql_content);
        }

        // 移除DELIMITER语句（PDO不支持DELIMITER命令）
        $sql_content = preg_replace('/^DELIMITER\s+.*$/im', '', $sql_content);

        // 移除注释和多余空行
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

        // 分割SQL语句（仅在;后跟换行或字符串末尾时分割，避免字符串值内的;被误拆分）
        $statements = array_filter(array_map('trim', preg_split('/;(?:\s*\n|\s*$)/', $sql_content)));

        $errors = [];
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $connection->exec($statement);
                } catch (PDOException $e) {
                    error_log('[HeaSec] SQL执行警告: ' . $e->getMessage() . ' | SQL: ' . mb_substr($statement, 0, 200));
                    if ($throwOnError) {
                        $errors[] = $e->getMessage() . ' (SQL: ' . mb_substr($statement, 0, 100) . ')';
                    }
                }
            }
        }

        // 如果要求严格模式且有错误，抛出异常
        if ($throwOnError && !empty($errors)) {
            throw new Exception('SQL执行失败 (' . count($errors) . '个错误): ' . implode('; ', array_slice($errors, 0, 3)));
        }
    }

    /**
     * 重置指定的靶场数据库
     */
    private function resetSpecificRangeDatabases($targetRanges)
    {
        $results = [];
        $range_dir = __DIR__ . '/../../range';

        foreach ($targetRanges as $rangeDirectory) {
            // 安全检查：防止目录遍历攻击
            if (strpos($rangeDirectory, '..') !== false || strpos($rangeDirectory, '\\') !== false) {
                $results[] = [
                    'directory' => $rangeDirectory,
                    'status' => 'error',
                    'message' => '非法的靶场目录参数'
                ];
                continue;
            }

            $targetPath = $range_dir . '/' . ltrim($rangeDirectory, '/');

            if (!is_dir($targetPath)) {
                $results[] = [
                    'directory' => $rangeDirectory,
                    'status' => 'error',
                    'message' => '靶场目录不存在'
                ];
                continue;
            }

            // 查找初始化SQL文件
            $init_sql_file = $this->findRangeInitSqlFile($targetPath);
            if (!$init_sql_file) {
                $results[] = [
                    'directory' => $rangeDirectory,
                    'status' => 'error',
                    'message' => '初始化SQL文件不存在'
                ];
                continue;
            }

            try {
                // 使用独立的数据库连接来重置靶场数据库
                $this->executeSqlFileIndependent($init_sql_file);
                $results[] = [
                    'directory' => $rangeDirectory,
                    'status' => 'success',
                    'message' => '重置成功'
                ];
                error_log('[HeaSec] 指定靶场数据库重置成功: ' . $rangeDirectory);
            } catch (Exception $e) {
                $results[] = [
                    'directory' => $rangeDirectory,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                error_log('[HeaSec] 指定靶场数据库重置失败: ' . $rangeDirectory . ' - ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 查找靶场初始化SQL文件
     */
    private function findRangeInitSqlFile($rangePath)
    {
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
     * 初始化所有子靶场数据库（初始化模式使用）
     */
    private function initRangeDatabases()
    {
        $results = [];
        $range_dir = __DIR__ . '/../../range';

        // 递归查找所有靶场目录
        $range_dirs = $this->findRangeDirectories($range_dir);

        foreach ($range_dirs as $dir) {
            // 跳过 common 目录（公共组件单独处理）
            if (strpos($dir, '/common') !== false || strpos($dir, '\\common') !== false) {
                continue;
            }

            $init_sql_file = $dir . '/database/init_database.sql';
            if (file_exists($init_sql_file)) {
                try {
                    // 使用独立的数据库连接来初始化靶场数据库
                    $this->executeSqlFileIndependent($init_sql_file);
                    $results[] = [
                        'directory' => str_replace($range_dir, '', $dir),
                        'status' => 'success',
                        'message' => '初始化成功'
                    ];
                    error_log('[HeaSec] 靶场数据库初始化成功: ' . $dir);
                } catch (Exception $e) {
                    $results[] = [
                        'directory' => str_replace($range_dir, '', $dir),
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    error_log('[HeaSec] 靶场数据库初始化失败: ' . $dir . ' - ' . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * 重置短信模拟器数据库
     */
    private function resetSmsSimulatorDatabase()
    {
        try {
            // 获取数据库配置
            require_once __DIR__ . '/../../config/config.php';

            // 连接到MySQL系统数据库
            $smsDb = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 创建heasec_common数据库（如果不存在）
            $smsDb->exec("CREATE DATABASE IF NOT EXISTS `heasec_common` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 切换到heasec_common数据库
            $smsDb->exec("USE `heasec_common`");

            // 执行短信模拟器初始化脚本
            $initSqlFile = __DIR__ . '/../../range/common/components/sms-simulator/database/init_database.sql';
            if (!file_exists($initSqlFile)) {
                throw new Exception('短信模拟器初始化脚本不存在');
            }

            // 执行SQL文件
            $this->executeSqlFileOnConnection($smsDb, $initSqlFile);

            error_log('[HeaSec] 短信模拟器数据库重置成功');

            return [
                'success' => true,
                'message' => '短信模拟器数据库重置成功'
            ];

        } catch (Exception $e) {
            error_log('[HeaSec] 短信模拟器数据库重置失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 重置所有靶场数据库
     */
    private function resetRangeDatabases()
    {
        $results = [];
        $range_dir = __DIR__ . '/../../range';

        // 递归查找所有靶场目录
        $range_dirs = $this->findRangeDirectories($range_dir);

        foreach ($range_dirs as $dir) {
            $init_sql_file = $dir . '/database/init_database.sql';
            if (file_exists($init_sql_file)) {
                try {
                    // 使用独立的数据库连接来重置靶场数据库
                    $this->executeSqlFileIndependent($init_sql_file);
                    $results[] = [
                        'directory' => str_replace($range_dir, '', $dir),
                        'status' => 'success',
                        'message' => '重置成功'
                    ];
                    error_log('[HeaSec] 靶场数据库重置成功: ' . $dir);
                } catch (Exception $e) {
                    $results[] = [
                        'directory' => str_replace($range_dir, '', $dir),
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    error_log('[HeaSec] 靶场数据库重置失败: ' . $dir . ' - ' . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * 递归查找所有靶场目录
     */
    private function findRangeDirectories($base_dir)
    {
        $directories = [];
        $items = scandir($base_dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full_path = $base_dir . '/' . $item;
            if (is_dir($full_path)) {
                // 如果是公共目录或包含数据库目录的靶场目录
                if ($item === 'common' || is_dir($full_path . '/database')) {
                    $directories[] = $full_path;
                }
                // 递归查找子目录
                $directories = array_merge($directories, $this->findRangeDirectories($full_path));
            }
        }

        return $directories;
    }
}

// 处理请求
$reset = new HeaSec_DatabaseReset();
$reset->resetDatabase();
?>