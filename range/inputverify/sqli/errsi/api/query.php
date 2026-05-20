<?php
/**
 * HeaSec天积安全团队 - 报错注入靶场 - 核心查询接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-18
 * 功能: 服务资产查询（漏洞点 + 函数检测 + 成就记录）
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 仅支持GET方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, '仅支持GET请求');
}

// 接收用户提交的服务ID
$id = $_GET['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入服务ID');
}

// 只读保护：检查写操作关键字
$writeKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE'];
$upperInput = strtoupper($id);
foreach ($writeKeywords as $keyword) {
    if (preg_match('/\b' . $keyword . '\b/i', $id)) {
        sendJsonResponse(false, '非法操作');
    }
}

// 获取数据库连接（用于成就记录等正常操作）
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 获取当前数据库名（用于检测报错注入是否成功）
$currentDbName = $pdo->query("SELECT database()")->fetchColumn();

// 漏洞查询使用独立的PDO连接，采用默认的模拟预处理模式（EMULATE_PREPARES=true）
// 模拟真实场景：生产环境中开发者使用PDO默认配置直接拼接SQL，MySQL函数报错能正确抛出异常
// HeaSec_Database连接池设置了EMULATE_PREPARES=false，会导致GTID_SUBSET等函数
// 在原生预处理模式下不抛异常（MySQL 5.7已知行为差异），与真实漏洞表现不一致
$dbConfig = json_decode(file_get_contents(dirname(__DIR__, 5) . '/config/config.json'), true)['database'];
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname=heasec_sqli;charset={$dbConfig['charset']}";
$queryPdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 拼接SQL并执行（漏洞点：直接拼接用户输入）
$sql = "SELECT * FROM heasec_errsi_services WHERE id = " . $id;

try {
    $stmt = $queryPdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        sendJsonResponse(true, '该服务编号已登记');
    } else {
        sendJsonResponse(false, '未找到该服务编号');
    }
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();

    // 检测报错注入：错误信息中是否包含当前数据库名
    $isErrorInjection = (strpos($errorMessage, $currentDbName) !== false);

    if ($isErrorInjection) {
        // 识别使用的函数
        $detectedFunction = detectErrorFunction($errorMessage, $id);
        // 兜底：未匹配到已知函数时记录为 'other'
        if ($detectedFunction === null) {
            $detectedFunction = 'other';
        }
        recordAchievement($pdo, $detectedFunction);
    }

    // 返回错误信息（模拟调试模式暴露SQL错误）
    sendJsonResponse(false, $errorMessage, ['error_detail' => $errorMessage]);
}
