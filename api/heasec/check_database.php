<?php
/**
 * ========================================
 * HeaSec天积安全团队 - 数据库初始化检测API接口
 * Database Initialization Check API
 * 版本: v1.0.0
 * 创建日期: 2025-10-25
 * 团队: 天积安全 (HeavenlySecret)
 * ========================================
 */

require_once __DIR__ . '/../../config/config.php';

class HeaSec_DatabaseChecker
{
    private $db;
    private $connectionError;
    private $databaseExists;
    private $serverConnected;

    public function __construct()
    {
        $this->serverConnected = false;
        $this->databaseExists = false;
        $this->connectionError = null;

        try {
            // 第一步：尝试连接MySQL服务器（不指定数据库）
            $this->db = $this->connectToServer();

            if ($this->db) {
                $this->serverConnected = true;

                // 第二步：检查数据库是否存在
                $this->databaseExists = $this->checkDatabaseExists();

                if (!$this->databaseExists) {
                    // 数据库不存在，但不抛出异常，让checkDatabase()方法处理
                    return;
                }

                // 第三步：连接到具体数据库
                $this->db = $this->connectToDatabase();
            }
        } catch (Exception $e) {
            $this->connectionError = $e->getMessage();
            $this->db = null;
        }
    }

    /**
     * 连接到MySQL服务器（不指定数据库）
     */
    private function connectToServer()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $conn;
        } catch (PDOException $e) {
            throw new Exception('MySQL服务器连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 检查数据库是否存在
     */
    private function checkDatabaseExists()
    {
        try {
            $stmt = $this->db->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([DB_NAME]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 连接到具体数据库
     */
    private function connectToDatabase()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $conn;
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 检查数据库初始化状态
     */
    public function checkDatabase()
    {
        HeaSec_validateRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            HeaSec_handleError('请求方法不支持', 405);
        }

        try {
            // ========== 优先检查连接状态 ==========
            if (!$this->serverConnected || $this->connectionError) {
                // MySQL服务器无法连接
                HeaSec_log('database_server_unreachable', [
                    'error' => $this->connectionError,
                    'host' => DB_HOST,
                    'port' => DB_PORT
                ]);

                $response_data = [
                    'initialized' => false,
                    'error_type' => 'server_unreachable',
                    'error_message' => '无法连接到MySQL服务器',
                    'error_detail' => $this->connectionError,
                    'suggestion' => '请检查MySQL服务是否已启动，以及数据库配置是否正确',
                    'tables_check' => [
                        'required' => [],
                        'existing' => [],
                        'missing' => []
                    ],
                    'data_check' => [
                        'has_data' => false
                    ],
                    'actions' => [
                        'can_reset' => false,
                        'can_initialize' => false,
                        'can_selective_reset' => false
                    ]
                ];

                header('Content-Type: application/json');
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'success' => false,
                    'message' => '[HeaSec] MySQL服务器不可达: ' . $this->connectionError,
                    'data' => $response_data,
                    'team' => [
                        'name' => HEASEC_TEAM_NAME,
                        'abbr' => HEASEC_TEAM_ABBR,
                        'version' => HEASEC_VERSION
                    ]
                ]);
                exit;
            }

            if (!$this->databaseExists) {
                // 数据库不存在
                HeaSec_log('database_not_exists', [
                    'database' => DB_NAME,
                    'host' => DB_HOST
                ]);

                $response_data = [
                    'initialized' => false,
                    'error_type' => 'database_not_exists',
                    'error_message' => '数据库不存在',
                    'error_detail' => "数据库 '" . DB_NAME . "' 不存在，需要进行初始化",
                    'suggestion' => '请点击"确认初始化"按钮创建数据库并导入初始数据',
                    'tables_check' => [
                        'required' => ['all_categories', 'links', 'admin_users', 'heasec_team_info'],
                        'existing' => [],
                        'missing' => ['all_categories', 'links', 'admin_users', 'heasec_team_info']
                    ],
                    'data_check' => [
                        'has_data' => false
                    ],
                    'actions' => [
                        'can_reset' => true,
                        'can_initialize' => true,
                        'can_selective_reset' => false
                    ]
                ];

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => '[HeaSec] 检测完成：数据库不存在，需要初始化',
                    'data' => $response_data,
                    'team' => [
                        'name' => HEASEC_TEAM_NAME,
                        'abbr' => HEASEC_TEAM_ABBR,
                        'version' => HEASEC_VERSION
                    ]
                ]);
                exit;
            }

            // ========== 正常的数据库检查流程 ==========
            // 读取初始化配置
            $initConfig = HeaSec_loadInitializationConfig();
            $checkRangeDatabases = $initConfig['check_range_databases'];
            $showUninitializedRanges = $initConfig['show_uninitialized_ranges'];

            // 检查必要的表是否存在
            $required_tables = ['all_categories', 'links', 'admin_users', 'heasec_team_info'];
            $existing_tables = [];

            foreach ($required_tables as $table) {
                $table = addslashes($table);
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existing_tables[] = $table;
                }
            }

            // 检查表是否为空
            $has_data = false;

            if (count($existing_tables) === count($required_tables)) {
                // 检查关键表是否有数据
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM `all_categories`");
                $stmt->execute();
                $result = $stmt->fetch();
                $categories_count = $result['count'];

                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM `admin_users`");
                $stmt->execute();
                $result = $stmt->fetch();
                $admin_users_count = $result['count'];

                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM `heasec_team_info`");
                $stmt->execute();
                $result = $stmt->fetch();
                $team_info_count = $result['count'];

                $has_data = ($categories_count > 0) && ($admin_users_count > 0) && ($team_info_count > 0);
            }

            // 检查靶场数据库状态（如果配置开启）
            $range_database_status = [];
            $uninitialized_ranges = [];

            if ($checkRangeDatabases) {
                $range_database_status = $this->checkRangeDatabases();

                // 提取未初始化的靶场列表
                if ($showUninitializedRanges && isset($range_database_status['details'])) {
                    foreach ($range_database_status['details'] as $range) {
                        if (!$range['initialized'] && $range['sql_file_exists']) {
                            $uninitialized_ranges[] = [
                                'directory' => $range['directory'],
                                'database_name' => $range['database_name'],
                                'missing_tables_count' => count($range['missing_tables']),
                                'missing_tables' => $range['missing_tables'],
                                'database_not_specified' => !empty($range['database_not_specified'])
                            ];
                        }
                    }
                }
            }

            // 生成CSRF令牌
            if (!isset($_SESSION)) {
                session_start();
            }
            if (!isset($_SESSION['csrf_token'])) {
                // 兼容性更好的随机字符串生成方法
                if (function_exists('openssl_random_pseudo_bytes')) {
                    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
                } else {
                    // 备用方法：使用uniqid和rand的组合
                    $_SESSION['csrf_token'] = bin2hex(uniqid('', true) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT));
                }
            }

            // 返回检测结果
            $is_initialized = (count($existing_tables) === count($required_tables)) && $has_data;
            $has_uninitialized_ranges = !empty($uninitialized_ranges);
            $missing_tables = array_diff($required_tables, $existing_tables);

            // 确定错误类型和提示信息
            $error_type = null;
            $error_message = null;
            $error_detail = null;
            $suggestion = null;

            if (!$is_initialized) {
                if (!empty($missing_tables)) {
                    $error_type = 'tables_missing';
                    $error_message = '数据库表不完整';
                    $error_detail = '缺少必要的数据库表: ' . implode(', ', $missing_tables);
                    $suggestion = '请点击"确认初始化"按钮创建缺失的表并导入初始数据';
                } elseif (!$has_data) {
                    $error_type = 'data_empty';
                    $error_message = '数据库为空';
                    $error_detail = '数据库表存在但没有数据，需要进行初始化';
                    $suggestion = '请点击"确认初始化"按钮导入初始数据';
                }
            }

            // 记录检测日志
            HeaSec_log('database_check', [
                'initialized' => $is_initialized,
                'existing_tables' => count($existing_tables),
                'required_tables' => count($required_tables),
                'has_data' => $has_data,
                'error_type' => $error_type,
                'check_range_databases' => $checkRangeDatabases,
                'uninitialized_ranges_count' => count($uninitialized_ranges),
                'range_status' => $range_database_status
            ]);

            // 构建响应数据（包含详细的错误信息）
            $response_data = [
                'initialized' => $is_initialized,
                'error_type' => $error_type,
                'error_message' => $error_message,
                'error_detail' => $error_detail,
                'suggestion' => $suggestion,
                'tables_check' => [
                    'required' => $required_tables,
                    'existing' => $existing_tables,
                    'missing' => $missing_tables
                ],
                'data_check' => [
                    'has_data' => $has_data
                ],
                'csrf_token' => $_SESSION['csrf_token'],
                'actions' => [
                    'can_reset' => true,
                    'can_initialize' => !$is_initialized,
                    'can_selective_reset' => $has_uninitialized_ranges
                ],
                'config' => [
                    'check_range_databases' => $checkRangeDatabases,
                    'show_uninitialized_ranges' => $showUninitializedRanges
                ]
            ];

            // 如果开启了靶场检查且有未初始化的靶场，添加到响应中
            if ($checkRangeDatabases && $has_uninitialized_ranges) {
                $response_data['uninitialized_ranges'] = $uninitialized_ranges;
                $response_data['range_check'] = $range_database_status;
            } elseif ($checkRangeDatabases) {
                $response_data['range_check'] = $range_database_status;
            }

            HeaSec_returnResponse(true, '检测完成', $response_data);

        } catch (Exception $e) {
            HeaSec_handleError('[HeaSec] 数据库检测失败: ' . $e->getMessage());
        }
    }

    /**
     * 检查靶场数据库状态
     */
    private function checkRangeDatabases()
    {
        $range_status = [];
        $range_dir = __DIR__ . '/../../range';

        // 递归查找所有靶场目录
        $range_dirs = $this->findRangeDirectories($range_dir);

        foreach ($range_dirs as $dir) {
            $init_sql_file = $dir . '/database/init_database.sql';
            if (file_exists($init_sql_file)) {
                $dir_name = str_replace($range_dir, '', $dir);
                $dir_name = trim($dir_name, '/');

                try {
                    // 通过检查SQL文件中的表名和数据库名来判断靶场数据库状态
                    $sql_content = file_get_contents($init_sql_file);
                    $database_name = $this->extractDatabaseFromSQL($sql_content);
                    $tables = $this->extractTablesFromSQL($sql_content);

                    $missing_tables = [];
                    $db_accessible = true;
                    $db_error = null;

                    // 如果SQL文件没有指定数据库名，标记警告
                    $database_not_specified = empty($database_name);

                    try {
                        // 如果指定了数据库，检查该数据库是否存在和可访问
                        if ($database_name) {
                            $stmt = $this->db->query("SHOW DATABASES LIKE '$database_name'");
                            if ($stmt->rowCount() === 0) {
                                // 数据库不存在，标记为未初始化
                                $range_status[] = [
                                    'directory' => $dir_name,
                                    'path' => $dir,
                                    'database_name' => $database_name,
                                    'tables_count' => count($tables),
                                    'missing_tables' => $tables, // 所有表都缺失
                                    'initialized' => false,
                                    'sql_file_exists' => true,
                                    'database_exists' => false,
                                    'database_not_specified' => $database_not_specified
                                ];
                                continue;
                            }

                            // 切换到目标数据库检查表
                            $this->db->exec("USE `$database_name`");
                        }

                        // 检查表是否存在
                        foreach ($tables as $table) {
                            $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() === 0) {
                                $missing_tables[] = $table;
                            }
                        }
                    } catch (Exception $db_e) {
                        $db_accessible = false;
                        $db_error = $db_e->getMessage();
                        $missing_tables = $tables; // 如果数据库不可访问，认为所有表都缺失
                    }

                    // 切回默认数据库
                    try {
                        $this->db->exec("USE `heasec_cms`");
                    } catch (Exception $e) {
                        // 忽略切换错误
                    }

                    $range_status[] = [
                        'directory' => $dir_name,
                        'path' => $dir,
                        'database_name' => $database_name ?: 'heasec_cms',
                        'tables_count' => count($tables),
                        'missing_tables' => $missing_tables,
                        'initialized' => empty($missing_tables) && $db_accessible,
                        'sql_file_exists' => true,
                        'database_exists' => $db_accessible,
                        'database_error' => $db_error,
                        'database_not_specified' => $database_not_specified
                    ];
                } catch (Exception $e) {
                    $range_status[] = [
                        'directory' => $dir_name,
                        'path' => $dir,
                        'database_name' => 'unknown',
                        'tables_count' => 0,
                        'missing_tables' => [],
                        'initialized' => false,
                        'sql_file_exists' => true,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'total_ranges' => count($range_status),
            'initialized_count' => count(array_filter($range_status, function ($r) {
                return $r['initialized'];
            })),
            'details' => $range_status,
            'needs_initialization' => count($range_status) > count(array_filter($range_status, function ($r) {
                return $r['initialized'];
            }))
        ];
    }

    /**
     * 从SQL文件中提取数据库名
     */
    private function extractDatabaseFromSQL($sql_content)
    {
        // 移除注释
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

        // 查找CREATE DATABASE语句
        if (preg_match('/CREATE\s+DATABASE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
            return $matches[1];
        }

        // 查找USE语句
        if (preg_match('/USE\s+`?(\w+)`?/i', $sql_content, $matches)) {
            return $matches[1];
        }

        return null; // 如果没有找到数据库名，返回null表示使用默认数据库
    }

    /**
     * 从SQL文件中提取表名
     */
    private function extractTablesFromSQL($sql_content)
    {
        $tables = [];

        // 移除注释
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

        // 查找CREATE TABLE语句
        if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
            foreach ($matches[1] as $table) {
                $tables[] = $table;
            }
        }

        return array_unique($tables);
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
$checker = new HeaSec_DatabaseChecker();
$checker->checkDatabase();
?>