<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第二关验证手机API
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：验证验证码和账号手机号匹配性
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化会话
HeaSec_InitRangeSession('sessionoverride');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$username = isset($data['username']) ? trim($data['username']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$captcha = isset($data['captcha']) ? trim($data['captcha']) : '';

$response = ['success' => false, 'message' => ''];

try {
    if (empty($username)) {
        throw new Exception('请输入账号');
    }
    if (empty($phone)) {
        throw new Exception('请输入手机号');
    }
    if (empty($captcha)) {
        throw new Exception('请输入验证码');
    }

    // 校验验证码
    if (!isset($_SESSION['captcha_level2']) || $captcha !== $_SESSION['captcha_level2']) {
        throw new Exception('验证码错误');
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证账号和手机号是否匹配
    $stmt = $pdo->prepare("SELECT phone FROM heasec_sessionoverride_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['phone'] !== $phone) {
        throw new Exception('账号或手机号不匹配');
    }

    // 设置验证通过状态
    $_SESSION['verified_level2'] = true;
    $_SESSION['reset_username_level2'] = $username;

    // 清除验证码
    unset($_SESSION['captcha_level2']);

    $response['success'] = true;
    $response['message'] = '验证通过';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
