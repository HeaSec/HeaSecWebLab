<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第二关下单接口
 * 版本: v1.0.2
 * 功能：处理购物车商品订单创建并直接完成支付
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.2');
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

// 获取商品列表
$items = isset($data['items']) ? $data['items'] : [];
if (empty($items) || !is_array($items)) {
    sendJsonResponse(false, '请选择要购买的商品');
}

// 获取支付方式
$paymentType = isset($data['payment_type']) ? $data['payment_type'] : 'balance';
if (!in_array($paymentType, ['balance', 'points'])) {
    $paymentType = 'balance';
}

// 检查积分支付的商品限制并计算总金额
$totalAmount = 0;
$validItems = [];

foreach ($items as $item) {
    $productId = isset($item['product_id']) ? intval($item['product_id']) : 0;
    $quantity = isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1;

    $product = getProductById($productId, $level, $pdo);
    if (!$product) {
        continue;
    }

    $amount = $product['price'] * $quantity;
    $totalAmount += $amount;
    $validItems[] = [
        'product' => $product,
        'quantity' => $quantity,
        'amount' => $amount
    ];
}

if (empty($validItems)) {
    sendJsonResponse(false, '商品不存在或已下架');
}

// 计算需要支付的余额和积分
$totalBalance = 0;
$totalPoints = 0;
if ($paymentType === 'points') {
    $totalPoints = $totalAmount * 100; // 100积分=1元
} else {
    $totalBalance = $totalAmount;
}

// 检查余额和积分是否足够（在创建订单前检查）
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

// 创建订单并直接标记为已支付
$orderIds = [];
foreach ($validItems as $item) {
    $product = $item['product'];
    $quantity = $item['quantity'];
    $amount = $item['amount'];

    $orderNo = generateOrderNo();
    $orderData = [
        'user_id' => $user['id'],
        'level' => $level,
        'order_no' => $orderNo,
        'product_id' => $product['id'],
        'product_name' => $product['name'],
        'quantity' => $quantity,
        'price' => $product['price'],
        'discount' => 0,
        'payment_type' => $paymentType,
        'total_amount' => $amount,
        'paid_amount' => $paymentType === 'balance' ? $amount : 0,
        'used_points' => $paymentType === 'points' ? $amount * 100 : 0
    ];

    $orderId = createOrder($orderData, $pdo);
    updateOrderPaid($orderId, $orderData['paid_amount'], $orderData['used_points'], $pdo);
    $orderIds[] = $orderId;
}

// 检查通关条件并获取通关密码
$passcode = null;
if (checkLevel2PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '购买成功', [
    'order_ids' => $orderIds,
    'count' => count($orderIds),
    'paid_balance' => $totalBalance,
    'paid_points' => $totalPoints,
    'balance' => $newBalance,
    'points' => $newPoints,
    'passcode' => $passcode
]);
