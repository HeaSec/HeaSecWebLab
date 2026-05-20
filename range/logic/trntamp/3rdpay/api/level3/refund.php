<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第三关退款接口
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
$level = 3;
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
$orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;
$refundQuantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

if ($orderId <= 0 || $refundQuantity <= 0) {
    sendJsonResponse(false, '参数错误');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单信息
    $order = getOrderById($orderId, $level, $pdo);
    if (!$order) {
        sendJsonResponse(false, '订单不存在');
    }

    // 验证订单所属
    if ($order['user_id'] != $userId) {
        sendJsonResponse(false, '无权操作此订单');
    }

    // 验证订单状态
    if ($order['status'] !== 'paid' && $order['status'] !== 'partial_refund') {
        sendJsonResponse(false, '订单状态不支持退款');
    }

    // 验证退款数量
    if ($refundQuantity > $order['quantity']) {
        sendJsonResponse(false, '退款数量超过购买数量');
    }

    // 计算退款金额
    $refundAmount = $order['price'] * $refundQuantity;

    // 查询已退款金额
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_refunded FROM heasec_3rdpay_transactions WHERE order_no = ? AND level = ? AND type = 'refund'");
    $stmt->execute([$order['order_no'], $level]);
    $refundInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $alreadyRefunded = floatval($refundInfo['total_refunded']);

    // 检查累计退款是否超过实际支付金额，如超过则调整为剩余可退金额
    $paidAmount = floatval($order['paid_amount']);
    $remainingRefundable = $paidAmount - $alreadyRefunded;
    if ($remainingRefundable <= 0) {
        sendJsonResponse(false, '该订单已无剩余可退金额');
    }
    if ($refundAmount > $remainingRefundable) {
        $refundAmount = $remainingRefundable;
    }

    // 获取天积宝用户
    $payUser = getPayUser($level, 'heasecpay', $pdo);
    if (!$payUser) {
        sendJsonResponse(false, '天积宝用户不存在');
    }

    // 开始事务
    $pdo->beginTransaction();

    // 更新天积宝余额
    $newBalance = $payUser['balance'] + $refundAmount;
    updatePayUserBalance($payUser['id'], $newBalance, $level, $pdo);

    // 创建退款交易记录
    createTransaction($payUser['id'], $level, $order['order_no'], $refundAmount, 'refund', $pdo);

    // 更新订单状态
    $newQuantity = $order['quantity'] - $refundQuantity;
    if ($newQuantity <= 0) {
        updateOrderStatus($orderId, 'refunded', $pdo);
    } else {
        updateOrderStatus($orderId, 'partial_refund', $pdo);
        // 更新订单数量
        $stmt = $pdo->prepare("UPDATE heasec_3rdpay_orders SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $orderId]);
    }

    // 提交事务
    $pdo->commit();

    // 获取用户拥有的天积元宝数量
    $yuanbaoCount = getUserYuanbaoCount($userId, $level, $pdo);

    // 检查是否达到通关条件
    $passcode = null;
    if ($yuanbaoCount >= 6) {
        $passcode = getPasscode($level);
        if (!$passcode) {
            $passcode = generatePasscode($level);
        }
    }

    sendJsonResponse(true, '退款成功', [
        'refundAmount' => $refundAmount,
        'yuanbaoCount' => $yuanbaoCount,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[HeaSec] Refund error: ' . $e->getMessage());
    sendJsonResponse(false, '退款失败，请稍后重试');
}
