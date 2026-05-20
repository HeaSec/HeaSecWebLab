<?php
/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 功能: 订单查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的订单号
$orderId = $_POST['order_id'] ?? '';

if ($orderId === '') {
    sendJsonResponse(false, '请输入订单号');
}

// WAF过滤：拦截比较符号
if (!waf_level1($orderId)) {
    sendJsonResponse(false, '请求被WAF拦截：检测到比较符号');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 字符型SQL查询 — 直接拼接用户输入
$sql = "SELECT id, order_no, status FROM heasec_bsiadv_orders WHERE order_no = '" . $orderId . "'";

try {
    $stmt = $pdo->query($sql);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($row) {
        sendJsonResponse(true, '查询成功：订单存在', ['found' => true]);
    } else {
        sendJsonResponse(true, '查询完成：订单不存在', ['found' => false]);
    }
} catch (PDOException $e) {
    sendJsonResponse(false, '查询出错');
}
