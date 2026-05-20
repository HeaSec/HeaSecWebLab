<?php
/**
 * HeaSec天积安全团队 - 批量注册靶场发送短信验证码接口
 * Batch Registration Range Send SMS API
 * 版本: v1.0.0
 * 创建日期: 2026-02-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 批量注册靶场 Send SMS API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('batchreg');

// 引入短信发送器
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

$phone = isset($input['phone']) ? trim($input['phone']) : '';
$imgCaptcha = isset($input['captcha']) ? trim($input['captcha']) : '';

// 验证图片验证码（漏洞：验证后不删除）
if (!isset($_SESSION['captcha_batchreg']) || strtolower($imgCaptcha) !== strtolower($_SESSION['captcha_batchreg'])) {
    echo json_encode([
        'success' => false,
        'message' => '图片验证码错误'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证手机号格式
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => '手机号格式不正确'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 生成6位验证码
$smsCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

// 存储到session
$_SESSION['sms_code_batchreg'] = $smsCode;
$_SESSION['sms_phone_batchreg'] = $phone;

// 发送短信（使用短信模拟器）
$message = "【天积安全】您的验证码为：{$smsCode}，有效期5分钟，请勿泄露。";
HeaSec_SmsSender::send($phone, $message, 'batchreg');

echo json_encode([
    'success' => true,
    'message' => '验证码已发送',
    'sms_code' => $smsCode
], JSON_UNESCAPED_UNICODE);
