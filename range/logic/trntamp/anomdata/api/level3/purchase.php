<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第三关购买接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 漏洞说明：
 * 后端仅校验数量不能为负数，未校验数量必须大于0，
 * 当数量为0时仍能完成订单创建和自动发货，导致零元购买商品。
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

$level = 3;

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

    // 获取参数
    $productId = isset($data['productId']) ? intval($data['productId']) : 0;
    $quantity = isset($data['quantity']) ? $data['quantity'] : 0;

    // 验证商品
    $product = getProductById($productId, $level, $pdo);
    if (!$product) {
        sendJsonResponse(false, '商品不存在');
    }

    // 漏洞点：仅校验数量不能为负数，允许为0
    // 正确的做法应该是：if ($quantity <= 0)
    if ($quantity < 0) {
        sendJsonResponse(false, '购买数量不能为负数');
    }

    // 计算订单金额
    $price = floatval($product['price']);
    $totalAmount = $price * $quantity;

    // 检查余额是否足够
    if ($user['balance'] < $totalAmount) {
        sendJsonResponse(false, '余额不足');
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        // 扣款（当quantity为0时，totalAmount为0，余额不变）
        $newBalance = $user['balance'] - $totalAmount;
        updateBalance($user['id'], $newBalance, $pdo);

        // 生成通关密码
        $passcode = generatePasscode($level);

        // 创建订单（漏洞点：只要有订单记录就生成通关密码）
        $orderId = createOrder($user['id'], $level, $productId, $quantity, $totalAmount, $passcode, $pdo);

        $pdo->commit();

        // 返回成功
        sendJsonResponse(true, '购买成功', [
            'orderId' => $orderId,
            'balance' => $newBalance,
            'totalAmount' => $totalAmount
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('[HeaSec] Purchase error: ' . $e->getMessage());
    sendJsonResponse(false, '购买失败，请稍后重试');
}
