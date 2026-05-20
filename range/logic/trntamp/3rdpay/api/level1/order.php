<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第一关创建订单接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 3rdPay Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取当前关卡用户ID
$level = 1;
$userId = isset($_SESSION['3rdpay_user_id_level' . $level]) ? $_SESSION['3rdpay_user_id_level' . $level] : null;

if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$productId = isset($data['productId']) ? intval($data['productId']) : 0;
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

if ($productId <= 0 || $quantity <= 0) {
    sendJsonResponse(false, '参数错误');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取用户信息
    $user = getUserById($userId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }

    // 获取商品信息
    $product = getProductById($productId, $level, $pdo);
    if (!$product) {
        sendJsonResponse(false, '商品不存在');
    }

    // 计算订单金额
    $amount = $product['price'] * $quantity;

    // 生成订单号
    $orderNo = generateOrderNo();

    // 创建订单
    $orderId = createOrder(
        $userId,
        $level,
        $orderNo,
        $product['name'],
        $quantity,
        $product['price'],
        $amount,
        0, // 第一关没有优惠
        $pdo
    );

    sendJsonResponse(true, '订单创建成功', [
        'orderId' => $orderId,
        'orderNo' => $orderNo,
        'productName' => $product['name'],
        'quantity' => $quantity,
        'price' => $product['price'],
        'amount' => $amount
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Order error: ' . $e->getMessage());
    sendJsonResponse(false, '订单创建失败，请稍后重试');
}
