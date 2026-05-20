<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第一关下单接口
 * 版本: v1.0.1
 * 功能：处理商品订单创建并直接完成支付，支持使用优惠券
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

// 获取商品列表
$items = isset($data['items']) ? $data['items'] : [];
if (empty($items) || !is_array($items)) {
    if (isset($data['product_id'])) {
        $items = [['product_id' => $data['product_id'], 'quantity' => isset($data['quantity']) ? $data['quantity'] : 1]];
    } else {
        sendJsonResponse(false, '请选择要购买的商品');
    }
}

// 计算订单总金额和验证商品
$totalAmount = 0;
$validItems = [];
foreach ($items as $item) {
    $productId = isset($item['product_id']) ? intval($item['product_id']) : 0;
    $quantity = isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1;

    $product = getProductById($productId, $level, $pdo);
    if ($product) {
        $totalAmount += $product['price'] * $quantity;
        $validItems[] = [
            'product' => $product,
            'quantity' => $quantity
        ];
    }
}

if (empty($validItems)) {
    sendJsonResponse(false, '商品不存在或已下架');
}

$totalDiscount = 0;

// 优惠券处理
$couponIds = isset($data['coupon_id']) ? $data['coupon_id'] : null;
$usedCouponIds = [];

if ($couponIds !== null) {
    if (is_array($couponIds)) {
        foreach ($couponIds as $cid) {
            $coupon = getCouponById(intval($cid), $level, $pdo);
            if ($coupon) {
                if ($totalAmount >= $coupon['min_amount']) {
                    $totalDiscount += $coupon['discount'];
                    $usedCouponIds[] = intval($cid);
                }
            }
        }
    } else {
        $coupon = getCouponById(intval($couponIds), $level, $pdo);
        if ($coupon && $totalAmount >= $coupon['min_amount']) {
            $totalDiscount = $coupon['discount'];
            $usedCouponIds[] = intval($couponIds);
        }
    }
}

// 计算最终金额
$finalAmount = $totalAmount - $totalDiscount;
if ($finalAmount < 0) {
    $finalAmount = 0;
}

// 检查余额
if ($user['balance'] < $finalAmount) {
    sendJsonResponse(false, '余额不足，当前余额：¥' . number_format($user['balance'], 2) . '，需要：¥' . number_format($finalAmount, 2));
}

// 扣除余额
$newBalance = $user['balance'] - $finalAmount;
updateUserBalance($user['id'], $level, $newBalance, $pdo);

// 遍历商品创建订单并直接标记为已支付
$orderIds = [];
$firstOrderNo = '';
foreach ($validItems as $index => $item) {
    $product = $item['product'];
    $quantity = $item['quantity'];
    $amount = $product['price'] * $quantity;

    // 将所有优惠券的discount折算到第一个订单中
    $orderDiscount = ($index === 0) ? $totalDiscount : 0;
    $orderFinalAmount = $amount - $orderDiscount;
    if ($orderFinalAmount < 0) {
        $orderFinalAmount = 0;
    }

    $orderNo = generateOrderNo();
    if ($index === 0) $firstOrderNo = $orderNo;

    $orderData = [
        'user_id' => $user['id'],
        'level' => $level,
        'order_no' => $orderNo,
        'product_id' => $product['id'],
        'product_name' => $product['name'],
        'quantity' => $quantity,
        'price' => $product['price'],
        'coupon_ids' => ($index === 0 && !empty($usedCouponIds)) ? json_encode($usedCouponIds) : null,
        'discount' => $orderDiscount,
        'payment_type' => 'balance',
        'total_amount' => $amount,
        'paid_amount' => $orderFinalAmount,
        'used_points' => 0
    ];

    $orderId = createOrder($orderData, $pdo);

    // 直接更新订单状态为已支付
    updateOrderPaid($orderId, $orderFinalAmount, 0, $pdo);
    $orderIds[] = $orderId;
}

// 检查通关条件并获取通关密码
$passcode = null;
if (checkLevel1PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '购买成功', [
    'order_ids' => $orderIds,
    'order_no' => $firstOrderNo,
    'total_amount' => $totalAmount,
    'discount' => $totalDiscount,
    'final_amount' => $finalAmount,
    'balance' => $newBalance,
    'passcode' => $passcode
]);
