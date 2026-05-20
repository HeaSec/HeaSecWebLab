<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 第一关配置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 * 第一关配置接口
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('vertbp');

$response = ['success' => false, 'message' => ''];

try {
    // 检查登录状态（仅检查是否登录，不检查角色）
    if (!isset($_SESSION['vertbp_level1_logged_in']) || $_SESSION['vertbp_level1_logged_in'] !== true) {
        throw new Exception('请先登录');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化用户数据
    initVertbpLevelUsers(1, $pdo);

    // 获取admin用户的通关密码
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_vertbp_users WHERE level = 1 AND role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || empty($admin['passcode'])) {
        throw new Exception('系统错误，请重置靶场');
    }

    // 生成模拟运行时间
    $uptime_days = rand(1, 30);
    $uptime_hours = rand(0, 23);
    $uptime_minutes = rand(0, 59);

    // 返回当前关卡配置数据
    $response['success'] = true;
    $response['data'] = [
        'device_name' => 'HeaSec-TJRouter-X1000',
        'firmware_version' => 'v2.3.1',
        'mac_address' => '00:1A:2B:3C:4D:5E',
        'uptime' => "{$uptime_days}天 {$uptime_hours}小时 {$uptime_minutes}分钟",
        'online_devices' => rand(3, 8),
        'wan_status' => '已连接',
        'lan_status' => '已连接',
        'passcode' => $admin['passcode']  // 通关密码
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
