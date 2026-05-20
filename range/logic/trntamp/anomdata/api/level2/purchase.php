<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第二关购买接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 异常数据 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('anomdata');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$level = 2;

// 获取参数
$productId = isset($data['productId']) ? intval($data['productId']) : 0;
$quantity = isset($data['quantity']) ? $data['quantity'] : 0;

try {
    // 检查是否已登录
    $sessionUserId = isset($_SESSION['anomdata_user_id_level' . $level]) ? $_SESSION['anomdata_user_id_level' . $level] : null;
    if (!$sessionUserId) {
        sendJsonResponse(false, '请先登录');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取用户信息
    $user = getUserById($sessionUserId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }

    // 验证商品
    $product = getProductById($productId, $level, $pdo);
    if (!$product) {
        sendJsonResponse(false, '商品不存在');
    }

    // 验证数量必须为正整数
    if (!is_int($quantity) && !ctype_digit(strval($quantity))) {
        sendJsonResponse(false, '购买数量必须为正整数');
    }
    $quantity = intval($quantity);
    if ($quantity <= 0) {
        sendJsonResponse(false, '购买数量必须大于0');
    }

    // 计算总金额（漏洞点：使用32位整数溢出）
    $rawTotal = $product['price'] * $quantity;
    $totalAmount = int32_overflow($rawTotal);

    // 验证总金额（漏洞点：溢出后可能为负数，负数<余额则通过）
    if ($user['balance'] < $totalAmount) {
        sendJsonResponse(false, '余额不足');
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        // 扣款（漏洞点：当totalAmount为负数时，实际余额会增加）
        $newBalance = $user['balance'] - $totalAmount;
        updateBalance($sessionUserId, $newBalance, $pdo);

        // 生成通关密码（如果余额达到1000元）
        $passcode = null;
        if ($newBalance >= 1000) {
            $passcode = getPasscode($level);
            if (!$passcode) {
                $passcode = generatePasscode($level);
            }
        }

        // 创建订单
        $orderId = createOrder($sessionUserId, $level, $productId, $quantity, $totalAmount, $passcode, $pdo);

        // 添加交易记录
        addTransaction($sessionUserId, $level, 'purchase', -$totalAmount, null, '购买' . $product['name'] . ' x' . $quantity, $pdo);

        $pdo->commit();

        // 获取已购买元宝数量
        $yuanbaoCount = getPurchasedYuanbaoCount($sessionUserId, $level, $pdo);

        // 返回成功
        sendJsonResponse(true, '购买成功', [
            'orderId' => $orderId,
            'balance' => $newBalance,
            'totalAmount' => $totalAmount,
            'quantity' => $quantity,
            'yuanbaoCount' => $yuanbaoCount,
            'passcode' => $passcode
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('[HeaSec] Purchase error: ' . $e->getMessage());
    sendJsonResponse(false, '购买失败，请稍后重试');
}
