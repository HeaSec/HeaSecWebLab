<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第三关下单接口
 * 版本: v1.0.1
 * 功能：处理商品订单创建并直接完成支付，支持首购优惠
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.1');
header('Content-Type: application/json; charset=utf-8');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('discount');

require_once '../../includes/functions.php';

$data = getRequestData();
$level = 3;
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 检查登录状态
$sessionUserId = isset($_SESSION['discount_user_id_level' . $level]) ? $_SESSION['discount_user_id_level' . $level] : null;
// 提前关闭会话，释放文件锁，允许并发请求同时执行（竞态条件漏洞关键）
session_write_close();
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
    if (isset($data['product_id'])) {
        $items = [['product_id' => $data['product_id'], 'quantity' => isset($data['quantity']) ? $data['quantity'] : 1]];
    } else {
        sendJsonResponse(false, '请选择要购买的商品');
    }
}

// 统计数量并校验
$totalQuantity = 0;
$productId = 0;
foreach ($items as $item) {
    $totalQuantity += isset($item['quantity']) ? intval($item['quantity']) : 1;
    if ($productId == 0 && isset($item['product_id'])) {
        $productId = intval($item['product_id']);
    }
}

if ($totalQuantity != 1) {
    sendJsonResponse(false, '本关卡每次只能购买1个商品');
}

$quantity = 1;

// 获取商品信息
$product = getProductById($productId, $level, $pdo);
if (!$product) {
    sendJsonResponse(false, '商品不存在');
}

// 查询用户是否有已支付的订单（检查首购状态）
$stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_discount_orders
                       WHERE user_id = ? AND level = ? AND status = 'paid'");
$stmt->execute([$user['id'], $level]);
$orderCount = $stmt->fetchColumn();

// 根据首购状态计算价格
if ($orderCount == 0 && $user['first_purchase'] == 0) {
    // 首购价格
    $price = 10.00;

    // 标记用户已首购
    $stmt = $pdo->prepare("UPDATE heasec_discount_users SET first_purchase = 1 WHERE id = ? AND level = ?");
    $stmt->execute([$user['id'], $level]);
} else {
    // 原价
    $price = 50.00;
}

// 计算订单金额
$totalAmount = $price * $quantity;

// 检查余额是否足够
if ($user['balance'] < $totalAmount) {
    sendJsonResponse(false, '余额不足，当前余额：¥' . number_format($user['balance'], 2) . '，需要：¥' . number_format($totalAmount, 2));
}

// 扣除余额
$newBalance = $user['balance'] - $totalAmount;
updateUserBalance($user['id'], $level, $newBalance, $pdo);

// 创建订单并直接标记为已支付
$orderNo = generateOrderNo();
$orderData = [
    'user_id' => $user['id'],
    'level' => $level,
    'order_no' => $orderNo,
    'product_id' => $product['id'],
    'product_name' => $product['name'],
    'quantity' => $quantity,
    'price' => $price,
    'discount' => $product['price'] - $price,
    'payment_type' => 'balance',
    'total_amount' => $totalAmount,
    'paid_amount' => $totalAmount,
    'used_points' => 0
];

$orderId = createOrder($orderData, $pdo);

// 直接更新订单状态为已支付
updateOrderPaid($orderId, $totalAmount, 0, $pdo);

// 检查通关条件并获取通关密码
$passcode = null;
if (checkLevel3PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '购买成功', [
    'order_id' => $orderId,
    'order_no' => $orderNo,
    'product_name' => $product['name'],
    'quantity' => $quantity,
    'price' => $price,
    'original_price' => $product['price'],
    'is_first_purchase' => $orderCount == 0 && $user['first_purchase'] == 0,
    'total_amount' => $totalAmount,
    'balance' => $newBalance,
    'passcode' => $passcode
]);
