<?php
/**
 * HeaSec天积安全团队 - 靶场公共头部组件（简化成功模态框版）
 * Common Header for Ranges (Simplified Success Modal Version)
 * 版本: v2.1.2
 * 创建日期: 2025-10-26
 * 更新日期: 2025-11-23
 * 团队: 天积安全 (HeavenlySecret)
 */

// 引入路径处理类
require_once __DIR__ . '/../classes/HeaSecPath.php';

// 获取公共组件URL路径，如果未设置$commonBasePath，则使用自动计算的路径
if (!isset($commonBasePath)) {
    $commonBasePath = HeaSecPath::getCommonUrl();
}

// 确保变量存在
$pageTitle = isset($pageTitle) ? $pageTitle : '天积安全靶场平台';
$rangeName = isset($rangeName) ? $rangeName : '安全靶场';
$showVersion = isset($showVersion) ? $showVersion : false;
$version = isset($version) ? $version : 'v1.0.0';

// 计算返回网站前台首页的相对路径
$rootPath = HeaSecPath::getRootPath();


// 网站前台首页相对路径
$homePageUrl = $rootPath . 'index.php';

// 计算短信模拟器的相对路径
$smsSimulatorUrl = $commonBasePath . 'components/sms-simulator/manage.php';

// 短信模拟器按钮显示控制（默认不显示）
$showSmsSimulator = isset($showSmsSimulator) ? $showSmsSimulator : false;

// 重置功能相关变量（由具体靶场设置）
$resetAction = isset($resetAction) ? $resetAction : 'reset';  // reset 或 init
$initSqlFile = isset($initSqlFile) ? $initSqlFile : null;
$databaseName = isset($databaseName) ? $databaseName : 'heasec_base';
$showResetButton = isset($showResetButton) ? $showResetButton : false;  // 是否显示重置按钮

// 数据库使用状态参数（智能检测）
// 只有明确设置$useDatabase = true且存在数据库配置的靶场才使用数据库
$useDatabase = (isset($useDatabase) && $useDatabase === true) ? true : false;

// 智能检测：如果存在数据库初始化文件，自动启用数据库功能
if (!$useDatabase && isset($initSqlFile) && file_exists($initSqlFile)) {
    $useDatabase = true;
}

// 数据库状态相关变量（由具体靶场设置）
$dbStatus = isset($dbStatus) ? $dbStatus : 'normal';
$dbCheckError = isset($dbCheckError) ? $dbCheckError : '';

// 定义访问常量并引入数据库组件
define('HEASEC_RANGE_ACCESS', true);
require_once __DIR__ . '/HeaSec_Database.php';

// 新增：自动数据库检查功能
if ($useDatabase && $initSqlFile && file_exists($initSqlFile)) {
    $dbStatus = 'normal';
    $dbCheckError = '';

    try {
        // 读取SQL文件内容，分析需要的数据库和表
        $sqlContent = file_get_contents($initSqlFile);

        // 预处理：去除注释
        $sqlContentClean = preg_replace('/^--.*$/m', '', $sqlContent);
        $sqlContentClean = preg_replace('/\/\*.*?\*\//s', '', $sqlContentClean);

        // 提取数据库名（从CREATE DATABASE语句）
        if (preg_match('/CREATE\s+DATABASE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^\s`]+)`?/i', $sqlContentClean, $matches)) {
            $requiredDatabase = $matches[1];
        } else {
            $requiredDatabase = $databaseName;
        }

        // 提取表名（从CREATE TABLE语句）
        preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([^\s`]+)`?/i', $sqlContentClean, $tableMatches);
        $requiredTables = $tableMatches[1];

        // 检查数据库连接
        $pdo = HeaSec_Database::getServerConnection();

        // 检查数据库是否存在
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$requiredDatabase]);
        $dbExists = $stmt->fetchColumn();

        if (!$dbExists) {
            $dbStatus = 'database_missing';
            $dbCheckError = "靶场数据库 '{$requiredDatabase}' 不存在，需要创建并初始化数据库";
        } else {
            // 数据库存在，检查表是否存在
            $pdo->exec("USE `{$requiredDatabase}`");

            $missingTables = [];
            foreach ($requiredTables as $table) {
                $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
                $stmt->execute([$requiredDatabase, $table]);
                if (!$stmt->fetchColumn()) {
                    $missingTables[] = $table;
                }
            }

            if (!empty($missingTables)) {
                $dbStatus = 'table_missing';
                $dbCheckError = "数据表 [" . implode(', ', $missingTables) . "] 不存在，需要初始化靶场数据库";
            }
        }

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, 'Unknown database') !== false) {
            $dbStatus = 'database_missing';
            $dbCheckError = '靶场数据库不存在，需要创建并初始化数据库';
        } else {
            $dbStatus = 'connection_failed';
            $dbCheckError = '数据库连接失败: ' . $errorMessage;
        }
    }
}

// 处理重置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && ($_GET['action'] === 'reset' || $_GET['action'] === 'init')) {
    header('Content-Type: application/json');

    try {
        // 检查是否使用数据库
        if (!$useDatabase) {
            echo json_encode(['success' => false, 'message' => '本靶场未使用数据库，无需重置']);
            exit;
        }

        // 先尝试连接到mysql服务器（不指定数据库）
        $pdo = HeaSec_Database::getServerConnection();

        // 如果指定了初始化SQL文件，则执行
        if ($initSqlFile && file_exists($initSqlFile)) {
            $sqlContent = file_get_contents($initSqlFile);

            // 移除注释
            $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent); // 去除行首注释
            $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent); // 去除块注释

            // 移除DELIMITER语句（PDO不支持DELIMITER命令）
            $sqlContent = preg_replace('/^DELIMITER\s+.*$/im', '', $sqlContent);

            // 分割SQL语句
            $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

            $errorMessages = []; // 用于收集错误信息

            foreach ($sqlStatements as $sql) {
                if (!empty($sql)) {
                    try {
                        $pdo->exec($sql);
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                        error_log('[HeaSec] 执行SQL语句警告: ' . $errorMsg);
                        $errorMessages[] = $errorMsg;
                    }
                }
            }

            // 如果有错误但不全部失败，记录警告但仍视为成功
            if (!empty($errorMessages)) {
                error_log('[HeaSec] SQL执行过程中有 ' . count($errorMessages) . ' 条语句出现警告');
            }
        }

        if (function_exists('HeaSec_DestroyCurrentRangeSession')) {
            HeaSec_DestroyCurrentRangeSession();
        }

        $action = $_GET['action'];
        $message = ($action === 'init') ? '数据库初始化成功' : '重置成功';
        echo json_encode(['success' => true, 'message' => $message]);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => '[HeaSec] ' . $e->getMessage()]);
    }
    exit;
}

// 团队信息
$team_info = [
    'name' => '天积安全',
    'nameEn' => 'HeavenlySecret',
    'abbr' => 'HeaSec',
    'slogan' => '日积寸功，乐享安全',
    'version' => $version
];
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - 天积安全靶场平台</title>

    <!-- 团队Meta信息 -->
    <meta name="author" content="天积安全 HeavenlySecret">
    <meta name="keywords" content="天积安全,HeavenlySecret,HeaSec,<?php echo htmlspecialchars($rangeName); ?>,靶场平台,网络安全">
    <meta name="description" content="<?php echo htmlspecialchars($rangeName); ?> - 天积安全靶场平台，专业的网络安全学习环境">
    <meta name="generator" content="HeavenlySecret/HeaSec <?php echo $version; ?>">
    <link rel="icon" href="<?php echo $rootPath; ?>favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo $rootPath; ?>favicon.ico" type="image/x-icon">

    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/font-awesome.min.css">

    <!-- 公共样式文件 -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range_common.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_modal.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_simple_modal.css?v=<?php echo $version; ?>">

    <!-- 引入公共JavaScript文件（包含模态框管理器和初始化器） -->
    <script src="<?php echo $commonBasePath; ?>js/heasec_range_common.js?v=<?php echo $version; ?>"></script>

    <script>
        /*数据库状态检查*/
        window.HeaSecConfig = {
            useDatabase: <?php echo json_encode($useDatabase); ?>,
            dbStatus: <?php echo json_encode($dbStatus); ?>,
            dbError: <?php echo json_encode($dbCheckError); ?>,
            resetConfig: {
                action: <?php echo json_encode($resetAction); ?>,
                url: <?php echo json_encode(isset($resetUrl) ? $resetUrl : null); ?>,
                autoRefresh: true,
                refreshDelay: 1500
            }
        };
    </script>

    <!-- 自定义样式（如果存在） -->
    <?php if (isset($customCSS)): ?>
        <style>
            <?php echo $customCSS; ?>
        </style>
    <?php endif; ?>
</head>

<body>
    <!-- 顶部导航栏 -->
    <header class="top-header">
        <div class="header-content">
            <!-- 左上角Logo -->
            <div class="logo-section">
                <a href="<?php echo htmlspecialchars($homePageUrl); ?>" class="logo-link" title="返回网站首页">
                    <img src="<?php echo $commonBasePath; ?>assets/logo.jpg" alt="HeavenlySecret" class="main-logo">
                </a>
            </div>

            <!-- 中间标题和口号 -->
            <div class="title-section">
                <div class="title-slogan-container">
                    <h1 class="main-title"><?php echo htmlspecialchars($rangeName); ?></h1>
                    <span class="main-slogan">日积寸功，乐享安全</span>
                </div>
            </div>

            <!-- 右上角短信模拟器、重置按钮和靶场说明按钮 -->
            <div class="version-section">
                <button class="range-info-btn" id="rangeInfoBtn">
                    <i class="fa fa-info-circle"></i>
                    <span class="btn-text">靶场说明</span>
                </button>
                <?php if ($showSmsSimulator): ?>
                    <button class="sms-simulator-btn" id="smsSimulatorBtn"
                        onclick="window.open('<?php echo htmlspecialchars($smsSimulatorUrl); ?>', '_blank', 'width=900,height=720,resizable=yes')">
                        <i class="fa fa-mobile"></i>
                        <span class="btn-text">短信模拟器</span>
                    </button>
                <?php endif; ?>
                <?php if ($showVersion): ?>
                    <span class="version-badge"><?php echo htmlspecialchars($version); ?></span>
                <?php endif; ?>
                <?php if ($showResetButton): ?>
                    <button class="reset-database-btn" id="resetDatabaseBtn">
                        <i class="fa fa-refresh"></i>
                        <span class="btn-text">重置</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 主要内容区域 -->
    <div class="content-wrapper">
        <div class="main-container">
            <!-- 内容区 -->
            <main class="content-area">