<?php
/**
 * HeaSec天积安全团队 - 金额篡改靶场 - 第一关提交订单接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 金额篡改 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('amttamp');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取当前关卡用户ID（从会话中获取）
$level = 1;
$userId = isset($_SESSION['amttamp_user_id_level' . $level]) ? $_SESSION['amttamp_user_id_level' . $level] : null;

if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取购物车数据
$items = isset($data['items']) ? $data['items'] : [];

if (empty($items) || !is_array($items)) {
    sendJsonResponse(false, '购物车为空');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取用户信息
    $user = getUserById($userId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }

    // 计算订单金额
    $totalAmount = 0;
    $orderItems = [];

    foreach ($items as $item) {
        $productId = isset($item['id']) ? intval($item['id']) : 0;
        $price = isset($item['price']) ? floatval($item['price']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;

        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        // 验证商品是否存在
        $product = getProductById($productId, $level, $pdo);
        if (!$product) {
            continue;
        }

        $subtotal = $price * $quantity;
        $totalAmount += $subtotal;

        $orderItems[] = [
            'productId' => $productId,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }

    // 仅校验总金额必须大于0
    if ($totalAmount <= 0) {
        sendJsonResponse(false, '订单总金额必须大于0');
    }

    // 检查余额是否足够
    if ($user['balance'] < $totalAmount) {
        sendJsonResponse(false, '余额不足');
    }

    // 开始事务
    $pdo->beginTransaction();

    // 扣减余额
    $newBalance = $user['balance'] - $totalAmount;
    updateBalance($userId, $newBalance, $pdo);

    // 创建订单
    $orderId = createOrder($userId, $level, $totalAmount, 0, $pdo);

    // 添加订单详情
    foreach ($orderItems as $orderItem) {
        addOrderItem($orderId, $orderItem['productId'], $orderItem['quantity'], $orderItem['price'], $pdo);
    }

    // 提交事务
    $pdo->commit();

    // 检查购买的天积元宝数量是否达到5个
    $yuanbaoCount = getPurchasedYuanbaoCount($userId, $level, $pdo);
    $passcode = null;
    if ($yuanbaoCount >= 5) {
        $passcode = getPasscode($level);
        if (!$passcode) {
            $passcode = generatePasscode($level);
        }
        // 将通关密码存入当前订单
        updateOrderPasscode($orderId, $passcode, $pdo);
    }

    sendJsonResponse(true, '购买成功', [
        'orderId' => $orderId,
        'totalAmount' => $totalAmount,
        'balance' => $newBalance,
        'yuanbaoCount' => $yuanbaoCount
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[HeaSec] Order error: ' . $e->getMessage());
    sendJsonResponse(false, '购买失败，请稍后重试');
}
