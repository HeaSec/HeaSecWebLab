<?php
/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场 - 第三关订单查询接口
 * 版本: v1.0.0
 * 功能: 订单状态查询处理（时间盲注注入点）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 设置MySQL变量
$pdo = HeaSec_Database::getConnection('heasec_sqli');
$passcode = getPasscode(3);
if ($passcode !== false) {
    $pdo->exec("SET @mixedsi_pass = '" . addslashes($passcode) . "'");
}

$orderNo = $_POST['order_no'] ?? '';

if ($orderNo === '') {
    sendJsonResponse(false, '请输入订单号');
}

// 安全过滤器（7层过滤）
if (!filterLevel3_comma($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel3_comparison($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel3_conditional($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel3_logicops($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel3_comments($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel3_spaces($orderNo)) {
    sendJsonResponse(false, '输入包含非法字符');
}

// SQL查询构造
$sql = "SELECT id, order_no, status, created_at FROM heasec_mixedsi_orders WHERE order_no = '" . $orderNo . "'";

try {
    $stmt = $pdo->query($sql);
    $order = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    // 无论成功失败返回相同的JSON结构
    if ($order) {
        $order['order_no'] = htmlspecialchars($order['order_no'], ENT_QUOTES, 'UTF-8');
        $order['status'] = htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8');
        sendJsonResponse(true, '查询成功', ['order' => $order]);
    } else {
        sendJsonResponse(true, '查询成功', ['order' => null]);
    }
} catch (PDOException $e) {
    // 错误信息完全隐藏，返回与正常查询相同的结构
    sendJsonResponse(true, '查询成功', ['order' => null]);
}
