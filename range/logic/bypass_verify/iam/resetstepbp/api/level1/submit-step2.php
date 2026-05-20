<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第一关第二步API
 * 版本: v1.1.0
 * 创建日期: 2026-02-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 处理第二步：验证短信验证码
 * 返回动态生成的下一步URL
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件基础路径
$commonBasePath = '../../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入验证码类
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 检查是否从第一步正常进入
$username = isset($_SESSION['resetstepbp_level1_reset_username']) ? $_SESSION['resetstepbp_level1_reset_username'] : '';

if (empty($username)) {
    echo json_encode([
        'success' => false,
        'message' => '会话已过期，请重新开始',
        'redirect_url' => 'step1.php'
    ]);
    exit;
}

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

$smsCaptcha = isset($input['sms_captcha']) ? trim($input['sms_captcha']) : '';
$imgCaptcha = isset($input['captcha']) ? trim($input['captcha']) : '';

if (empty($smsCaptcha) || empty($imgCaptcha)) {
    echo json_encode([
        'success' => false,
        'message' => '请填写完整信息'
    ]);
    exit;
}

// 验证图片验证码
$captchaObj = new HeaSec_Captcha(120, 40, 4, 20, 'captcha_resetstepbp');
if (!$captchaObj->verify($imgCaptcha)) {
    echo json_encode([
        'success' => false,
        'message' => '图片验证码错误'
    ]);
    exit;
}

// 验证短信验证码
$correctSmsCaptcha = isset($_SESSION['resetstepbp_level1_sms_captcha']) ? $_SESSION['resetstepbp_level1_sms_captcha'] : '';

if ($smsCaptcha !== $correctSmsCaptcha) {
    $_SESSION['resetstepbp_level1_reset_verified'] = false; // 验证失败设为false

    echo json_encode([
        'success' => false,
        'message' => '短信验证码错误'
    ]);
    exit;
}

// 验证成功
$_SESSION['resetstepbp_level1_reset_verified'] = true;

// 动态生成下一步URL（使用时间戳混淆）
$nextUrl = 'step3.php?t=' . time();

echo json_encode([
    'success' => true,
    'message' => '验证通过',
    'next_url' => $nextUrl,
    'timestamp' => time()
]);
