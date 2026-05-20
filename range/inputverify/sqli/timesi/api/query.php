<?php
/**
 * HeaSec天积安全团队 - 时间盲注靶场 - 核心查询接口
 * 版本: v2.0.0
 * 创建日期: 2026-04-20
 * 更新日期: 2026-04-26 - 双维度成就检测（延迟技术 + 字符串函数）
 * 功能: 服务资产查询（漏洞点 + 时间检测 + 成就记录）
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

// 过滤步骤1：检测空白字符
if (preg_match('/\s/', $id)) {
    sendJsonResponse(false, '输入包含非法字符', ['execution_time' => 0]);
}

// 过滤步骤2：检查AND关键字
if (preg_match('/\bAND\b/i', $id)) {
    sendJsonResponse(false, '输入包含非法关键字', ['execution_time' => 0]);
}

// 过滤步骤3：禁止 substr/substring 函数（强制使用替代方案如 mid/left/right）
if (preg_match('/\bsubstr(?:ing)?\s*\(/i', $id)) {
    sendJsonResponse(false, '输入包含非法关键字', ['execution_time' => 0]);
}

// 只读保护：检查写操作关键字
$writeKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE'];
foreach ($writeKeywords as $keyword) {
    if (preg_match('/\b' . $keyword . '\b/i', $id)) {
        sendJsonResponse(false, '非法操作', ['execution_time' => 0]);
    }
}

// 获取数据库连接（单连接设计：时间盲注不需要双连接）
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 拼接SQL并执行（漏洞点：直接拼接过滤后的输入）
$sql = "SELECT * FROM heasec_timesi_services WHERE id = " . $id;

// 时间盲注检测阈值（秒）：正常查询通常 < 0.1秒，1秒阈值留有足够余量
$delayThreshold = 1.0;

$startTime = microtime(true);
try {
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 3);

    // 时间盲注检测
    if ($executionTime >= $delayThreshold && containsDatabaseCheck($id) && containsFirstCharCheck($id)) {
        $detectedFunction = detectDelayFunction($id);
        if ($detectedFunction === null) {
            $detectedFunction = 'other';
        }
        recordAchievement($pdo, $detectedFunction);

        // 记录字符串函数成就（双维度：第二个维度）
        $detectedStringFunction = detectStringFunction($id);
        if ($detectedStringFunction === null) {
            // 如果使用了 database() 进行首字符判断但无法识别具体函数，记为 other
            $detectedStringFunction = 'other';
        }
        recordStringFunction($pdo, $detectedStringFunction);
    }

    // 返回查询结果 + 响应时间
    if ($result) {
        sendJsonResponse(true, '该服务编号已登记', ['execution_time' => $executionTime]);
    } else {
        sendJsonResponse(false, '未找到该服务编号', ['execution_time' => $executionTime]);
    }
} catch (PDOException $e) {
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 3);

    // 即使SQL错误也检测时间盲注（某些延迟可能在错误前触发）
    if ($executionTime >= $delayThreshold && containsDatabaseCheck($id) && containsFirstCharCheck($id)) {
        $detectedFunction = detectDelayFunction($id);
        if ($detectedFunction === null) {
            $detectedFunction = 'other';
        }
        recordAchievement($pdo, $detectedFunction);

        // 记录字符串函数成就（双维度）
        $detectedStringFunction = detectStringFunction($id);
        if ($detectedStringFunction === null) {
            $detectedStringFunction = 'other';
        }
        recordStringFunction($pdo, $detectedStringFunction);
    }

    // 生产模式：不返回具体错误信息
    sendJsonResponse(false, '查询异常', ['execution_time' => $executionTime]);
}
