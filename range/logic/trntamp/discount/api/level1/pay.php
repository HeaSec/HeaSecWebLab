<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第一关支付接口
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

$data = getRequestData();
$level = 1;
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

// 获取订单ID
$orderId = isset($data['order_id']) ? intval($data['order_id']) : 0;

// 获取订单信息
$order = getOrderById($orderId, $level, $pdo);
if (!$order) {
    sendJsonResponse(false, '订单不存在');
}

// 验证订单归属
if ($order['user_id'] != $user['id']) {
    sendJsonResponse(false, '无权操作此订单');
}

// 检查订单状态
if ($order['status'] !== 'pending') {
    sendJsonResponse(false, '订单状态不正确');
}

// 计算实际支付金额
$finalAmount = $order['total_amount'] - $order['discount'];
if ($finalAmount < 0) {
    $finalAmount = 0;
}

// 检查余额是否足够
if ($user['balance'] < $finalAmount) {
    sendJsonResponse(false, '余额不足，当前余额：¥' . number_format($user['balance'], 2));
}

// 扣除余额
$newBalance = $user['balance'] - $finalAmount;
updateUserBalance($user['id'], $level, $newBalance, $pdo);

// 更新订单状态
updateOrderPaid($orderId, $finalAmount, 0, $pdo);

// 检查是否满足通关条件，获取通关密码
$passcode = null;
if (checkLevel1PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '支付成功', [
    'order_id' => $orderId,
    'paid_amount' => $finalAmount,
    'balance' => $newBalance,
    'passcode' => $passcode
]);
