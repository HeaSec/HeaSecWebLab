<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第二关支付回调接口
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

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';
$status = isset($data['status']) ? trim($data['status']) : '';
$amount = isset($data['amount']) ? trim($data['amount']) : '';
$timestamp = isset($data['timestamp']) ? trim($data['timestamp']) : '';
$sign = isset($data['sign']) ? trim($data['sign']) : '';

$level = 2;

// 验证参数
if (empty($orderId) || empty($status) || empty($amount) || empty($timestamp) || empty($sign)) {
    sendJsonResponse(false, '参数错误');
}

// 验证签名
if (!verifyCallbackSignV2($orderId, $status, $amount, $timestamp, $sign)) {
    sendJsonResponse(false, '签名验证失败');
}

// 验证支付状态
if ($status !== 'success') {
    sendJsonResponse(false, '支付失败');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单信息
    $order = getOrderByNo($orderId, $level, $pdo);
    if (!$order) {
        sendJsonResponse(false, '订单不存在');
    }

    // 检查订单状态
    if ($order['status'] !== 'pending') {
        sendJsonResponse(false, '订单状态异常');
    }

    // 验证金额是否与订单一致（修复了第一关的漏洞）
    if (floatval($amount) != floatval($order['amount'])) {
        sendJsonResponse(false, '金额不匹配');
    }

    // 更新订单状态
    updateOrderPaid($orderId, floatval($amount), $pdo);

    sendJsonResponse(true, '支付成功');

} catch (Exception $e) {
    error_log('[HeaSec] Callback error: ' . $e->getMessage());
    sendJsonResponse(false, '回调处理失败');
}
