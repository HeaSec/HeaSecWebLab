<?php
/**
 * HeaSec天积安全团队 - 金额篡改靶场 - 第三关订单列表接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-15
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
$level = 3;
$userId = isset($_SESSION['amttamp_user_id_level' . $level]) ? $_SESSION['amttamp_user_id_level' . $level] : null;

if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取用户订单列表
    $orders = getUserOrders($userId, $level, $pdo);

    sendJsonResponse(true, '获取成功', ['orders' => $orders]);

} catch (Exception $e) {
    error_log('[HeaSec] Get orders error: ' . $e->getMessage());
    sendJsonResponse(false, '获取订单失败，请稍后重试');
}
