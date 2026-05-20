<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场 - 第三关通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 重放攻击 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('replay');

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
$level = 3;

// 验证参数
if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

try {
    // 验证通关密码
    if (verifyPasscode($passcode, $level)) {
        sendJsonResponse(true, '验证通过', ['passed' => true]);
    } else {
        sendJsonResponse(false, '通关密码错误', ['passed' => false]);
    }
    
} catch (Exception $e) {
    error_log('[HeaSec] Verify passcode error: ' . $e->getMessage());
    sendJsonResponse(false, '验证失败，请稍后重试');
}
