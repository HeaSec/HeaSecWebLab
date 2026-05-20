<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第三关订单列表接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('discount');

require_once '../../includes/functions.php';

$level = 3;
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 检查登录状态
$sessionUserId = isset($_SESSION['discount_user_id_level' . $level]) ? $_SESSION['discount_user_id_level' . $level] : null;
if (!$sessionUserId) {
    sendJsonResponse(false, '请先登录');
}

$user = getUserById($sessionUserId, $level, $pdo);
if (!$user) {
    sendJsonResponse(false, '用户不存在');
}

// 获取订单列表
$orders = getUserOrders($user['id'], $level, $pdo);

// 格式化订单数据
$formattedOrders = array_map(function($order) {
    return [
        'id' => intval($order['id']),
        'order_no' => $order['order_no'],
        'product_name' => $order['product_name'],
        'quantity' => intval($order['quantity']),
        'price' => floatval($order['price']),
        'total_amount' => floatval($order['total_amount']),
        'discount' => floatval($order['discount']),
        'paid_amount' => floatval($order['paid_amount']),
        'status' => $order['status'],
        'created_at' => $order['created_at']
    ];
}, $orders);

// 获取已购买数量
$paidCount = getPaidYuanbaoCount($user['id'], $level, $pdo);

// 检查通关条件并返回通关密码
$passcode = null;
if (checkLevel3PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '获取成功', [
    'orders' => $formattedOrders,
    'paidCount' => $paidCount,
    'passcode' => $passcode
]);
