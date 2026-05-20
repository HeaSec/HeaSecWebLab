<?php
/**
 * HeaSec天积安全团队 - SQL关键字过滤靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 功能: 第二关订单查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的订单号
$orderNo = $_POST['order_no'] ?? '';

if ($orderNo === '') {
    sendJsonResponse(false, '请输入订单号');
}

// ===== 关键字拦截器（大小写不敏感，单词边界匹配）=====
$blocked_keywords = '/\b(union|select|from|where|insert|update|delete|drop|alter|create|exec|declare|sleep|into|load_file)\b/i';
if (preg_match($blocked_keywords, $orderNo)) {
    sendJsonResponse(false, '输入包含被过滤的SQL关键字');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, order_no, customer, amount FROM heasec_kwbpsi_orders WHERE order_no = '" . $orderNo . "'";

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
