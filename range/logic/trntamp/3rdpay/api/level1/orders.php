<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第一关订单列表接口
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

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单列表
    $orders = getUserOrders($userId, $level, $pdo);

    // 检查是否有已支付订单
    $hasPaidOrder = false;
    foreach ($orders as $order) {
        if ($order['status'] === 'paid') {
            $hasPaidOrder = true;
            break;
        }
    }

    // 如果有已支付订单，获取通关密码
    $passcode = null;
    if ($hasPaidOrder) {
        $passcode = getUserPasscode($userId, $level, $pdo);
    }

    sendJsonResponse(true, '获取成功', [
        'orders' => $orders,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Orders error: ' . $e->getMessage());
    sendJsonResponse(false, '获取订单失败，请稍后重试');
}
