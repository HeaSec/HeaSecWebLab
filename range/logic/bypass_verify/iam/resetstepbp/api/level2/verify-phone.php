<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第二关验证手机接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 设置公共组件基础路径
$commonBasePath = '../../../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入验证码类
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

try {
    // 接收参数
    $input = json_decode(file_get_contents('php://input'), true);
    $username = isset($input['username']) ? trim($input['username']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $smsCaptcha = isset($input['sms_captcha']) ? trim($input['sms_captcha']) : '';
    $imgCaptcha = isset($input['captcha']) ? trim($input['captcha']) : '';

    // 验证图片验证码
    $captchaObj = new HeaSec_Captcha();
    if (!$captchaObj->verify($imgCaptcha)) {
        echo json_encode([
            'success' => false,
            'message' => '图片验证码错误'
        ]);
        exit;
    }

    // 校验短信验证码
    $correctSmsCaptcha = isset($_SESSION['resetstepbp_level2_sms_captcha']) ? $_SESSION['resetstepbp_level2_sms_captcha'] : '';

    if ($smsCaptcha !== $correctSmsCaptcha) {
        echo json_encode([
            'success' => false,
            'message' => '短信验证码错误'
        ]);
        exit;
    }

    // 验证账号和手机号是否匹配
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT phone FROM heasec_resetstepbp_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['phone'] !== $phone) {
        echo json_encode([
            'success' => false,
            'message' => '账号或手机号不匹配'
        ]);
        exit;
    }

    // 清除短信验证码
    unset($_SESSION['resetstepbp_level2_sms_captcha']);

    // 验证成功，返回账号信息
    echo json_encode([
        'success' => true,
        'message' => '验证通过',
        'username' => $username
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '验证失败，请稍后重试'
    ]);
}
