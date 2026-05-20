<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第二关支付接口
 * 版本: v1.0.0
 * 功能：处理订单支付，支持余额和积分支付
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
$level = 2;
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

// 获取订单ID（支持单个或批量）
$orderIds = isset($data['order_ids']) ? $data['order_ids'] : null;
$orderId = isset($data['order_id']) ? intval($data['order_id']) : 0;

if ($orderIds === null && $orderId > 0) {
    $orderIds = [$orderId];
}

if (empty($orderIds) || !is_array($orderIds)) {
    sendJsonResponse(false, '请选择要支付的订单');
}

// 计算总支付金额
$totalBalance = 0;
$totalPoints = 0;
$ordersToPay = [];

foreach ($orderIds as $oid) {
    $order = getOrderById(intval($oid), $level, $pdo);
    if (!$order) {
        continue;
    }

    // 验证订单归属
    if ($order['user_id'] != $user['id']) {
        continue;
    }

    // 检查订单状态
    if ($order['status'] !== 'pending') {
        continue;
    }

    $ordersToPay[] = $order;

    if ($order['payment_type'] === 'points') {
        $totalPoints += $order['total_amount'] * 100; // 100积分=1元
    } else {
        $totalBalance += $order['total_amount'];
    }
}

if (empty($ordersToPay)) {
    sendJsonResponse(false, '没有可支付的订单');
}

// 检查余额和积分是否足够
if ($user['balance'] < $totalBalance) {
    sendJsonResponse(false, '余额不足，当前余额：¥' . number_format($user['balance'], 2) . '，需要：¥' . number_format($totalBalance, 2));
}

if ($user['points'] < $totalPoints) {
    sendJsonResponse(false, '积分不足，当前积分：' . $user['points'] . '，需要：' . $totalPoints);
}

// 扣除余额和积分
$newBalance = $user['balance'] - $totalBalance;
$newPoints = $user['points'] - $totalPoints;

updateUserBalance($user['id'], $level, $newBalance, $pdo);
updateUserPoints($user['id'], $level, $newPoints, $pdo);

// 更新所有订单状态
foreach ($ordersToPay as $order) {
    $paidAmount = $order['total_amount'];
    $usedPoints = $order['payment_type'] === 'points' ? $order['total_amount'] * 100 : 0;
    updateOrderPaid($order['id'], $paidAmount, $usedPoints, $pdo);
}

// 检查通关条件并获取通关密码
$passcode = null;
if (checkLevel2PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '支付成功', [
    'paid_orders' => count($ordersToPay),
    'paid_balance' => $totalBalance,
    'paid_points' => $totalPoints,
    'balance' => $newBalance,
    'points' => $newPoints,
    'passcode' => $passcode
]);
