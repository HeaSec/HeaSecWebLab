<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第一关通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
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

$level = 1;

// 获取通关密码
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

// 验证通关密码
if (verifyPasscode($passcode, $level)) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
