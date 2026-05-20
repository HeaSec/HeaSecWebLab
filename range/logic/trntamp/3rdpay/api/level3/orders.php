<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第三关订单列表接口
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

$requiredYuanbao = 6;

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单列表
    $orders = getUserOrders($userId, $level, $pdo);

    // 为每个订单添加已退款信息
    foreach ($orders as &$order) {
        $refundInfo = getOrderRefundInfo($order['order_no'], $level, $pdo);
        $order['refunded_amount'] = $refundInfo['refunded_amount'];
        $order['refunded_count'] = $refundInfo['refunded_count'];
        // 计算已退款数量（基于单价计算）
        $order['refunded_quantity'] = $order['price'] > 0 ? intval($refundInfo['refunded_amount'] / $order['price']) : 0;
        // 计算原始购买数量 = 当前剩余数量 + 已退款数量
        $order['original_quantity'] = $order['quantity'] + $order['refunded_quantity'];
        // 计算剩余可退金额（基于实际支付金额）
        $paidAmount = floatval($order['paid_amount']);
        $remainingRefundable = $paidAmount - $refundInfo['refunded_amount'];
        $order['remaining_refundable'] = max(0, $remainingRefundable);
        // 剩余可退数量直接使用订单剩余数量（后端退款接口已有金额上限校验）
        $order['max_refund_quantity'] = $order['quantity'];
    }
    unset($order);

    // 获取用户拥有的天积元宝数量
    $yuanbaoCount = getUserYuanbaoCount($userId, $level, $pdo);

    // 检查是否达到通关条件
    $passcode = null;
    if ($yuanbaoCount >= $requiredYuanbao) {
        $passcode = getUserPasscode($userId, $level, $pdo);
    }

    sendJsonResponse(true, '获取成功', [
        'orders' => $orders,
        'yuanbaoCount' => $yuanbaoCount,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Orders error: ' . $e->getMessage());
    sendJsonResponse(false, '获取订单失败，请稍后重试');
}
