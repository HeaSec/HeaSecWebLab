<?php
/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场 - 第三关日志查询接口
 * 版本: v1.0.0
 * 功能: 安全日志查询处理（WAF+双URL解码+SQL注入漏洞点）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';
require_once __DIR__ . '/../includes/functions.php';

HeaSec_InitRangeSession('specsi');

// 检查登录状态
$user_id = $_SESSION['specsi_user_id'] ?? 0;
if (!$user_id || ($_SESSION['specsi_level'] ?? 0) !== 3) {
    sendJsonResponse(false, '请先登录');
}

// 接收用户通过GET方式提交的日志ID（PHP已自动进行一次URL解码）
$id = $_GET['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入日志ID');
}

// WAF：严格校验（检查PHP一次解码后的值）
// 检测常见的SQL注入关键字和字符
if (preg_match('/union|select|from|where|information_schema|\'|"|--|#|\/\*|\*\//i', $id)) {
    sendJsonResponse(false, 'WAF拦截：检测到SQL注入攻击特征');
}

// 应用程序对输入进行二次URL解码（WAF检查的是解码前的值）
$id = urldecode($id);

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 数字型注入：二次解码后的值直接拼接到SQL语句中
$sql = "SELECT id, log_type, message FROM heasec_specsi_logs WHERE id = " . $id;

try {
    $stmt = $pdo->query($sql);
    $results = [];
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
    }
    sendJsonResponse(true, '查询完成', ['results' => $results]);
} catch (PDOException $e) {
    sendJsonResponse(false, '查询失败，请检查输入参数');
}
