<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第二关发送验证码API
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化会话
HeaSec_InitRangeSession('sessionoverride');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$username = isset($data['username']) ? trim($data['username']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';

$response = ['success' => false, 'message' => ''];

try {
    if (empty($username)) {
        throw new Exception('请输入账号');
    }
    if (empty($phone)) {
        throw new Exception('请输入手机号');
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT * FROM heasec_sessionoverride_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);



    if (!$user || $user['phone'] !== $phone) {
        throw new Exception('账号或手机号不匹配');
    } else{
        // 验证成功后会更新会话中的账号信息，但不重置verified_level2
        $_SESSION['reset_username_level2'] = $username;
    }

    // 生成6位随机验证码
    $captcha = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // 存储验证码到会话
    $_SESSION['captcha_level2'] = $captcha;

    // 发送短信
    $message = '您的验证码是：' . $captcha . '，有效期5分钟，请勿泄露。';
    $result = HeaSec_SmsSender::send($phone, $message, 'sessionoverride_level2');

    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = '验证码已发送到手机号：' . $phone;
    } else {
        throw new Exception($result['message'] ?? '发送失败');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
