<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第二关通关密码验证接口
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
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';
$level = 2;

// 验证参数
if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

// 检查用户是否登录
$userId = isset($_SESSION['3rdpay_user_id_level' . $level]) ? $_SESSION['3rdpay_user_id_level' . $level] : null;
if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 直接从用户表验证通关密码
    if (verifyUserPasscode($userId, $passcode, $level, $pdo)) {
        sendJsonResponse(true, '验证通过', ['passed' => true]);
    } else {
        sendJsonResponse(false, '通关密码错误');
    }
} catch (Exception $e) {
    error_log('[HeaSec] Verify passcode error: ' . $e->getMessage());
    sendJsonResponse(false, '验证失败，请稍后重试');
}
