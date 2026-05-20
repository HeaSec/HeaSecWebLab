<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第二关订单列表接口
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

$level = 2;

try {
    // 检查是否已登录
    $sessionUserId = isset($_SESSION['anomdata_user_id_level' . $level]) ? $_SESSION['anomdata_user_id_level' . $level] : null;
    if (!$sessionUserId) {
        sendJsonResponse(false, '请先登录');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单列表
    $orders = getUserOrders($sessionUserId, $level, $pdo);

    // 获取已购买元宝数量
    $yuanbaoCount = getPurchasedYuanbaoCount($sessionUserId, $level, $pdo);

    // 返回结果
    sendJsonResponse(true, '获取成功', [
        'orders' => $orders,
        'yuanbaoCount' => $yuanbaoCount
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Orders error: ' . $e->getMessage());
    sendJsonResponse(false, '获取订单列表失败');
}
