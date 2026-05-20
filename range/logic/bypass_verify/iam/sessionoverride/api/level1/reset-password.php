<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第一关重置密码API
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
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
$password = isset($data['password']) ? trim($data['password']) : '';
$confirmPassword = isset($data['confirm_password']) ? trim($data['confirm_password']) : '';
$captcha = isset($data['captcha']) ? trim($data['captcha']) : '';

$response = ['success' => false, 'message' => ''];

try {
    // 基本验证
    if (empty($username)) {
        throw new Exception('请输入账号');
    }
    if (empty($password)) {
        throw new Exception('请输入新密码');
    }
    if ($password !== $confirmPassword) {
        throw new Exception('两次密码不一致');
    }
    if (empty($captcha)) {
        throw new Exception('请输入验证码');
    }

    // 检查目标账号是否在当前会话中下发过验证码
    if (!isset($_SESSION['code_sent_accounts_level1']) || !in_array($username, $_SESSION['code_sent_accounts_level1'])) {
        throw new Exception('该账号未下发验证码');
    }

    // 校验验证码（不校验账号与手机号的关联）
    if (!isset($_SESSION['captcha_level1']) || $captcha !== $_SESSION['captcha_level1']) {
        throw new Exception('验证码错误');
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证账号是否存在
    $stmt = $pdo->prepare("SELECT id FROM heasec_sessionoverride_users WHERE level = 1 AND username = ?");
    $stmt->execute([$username]);
    if (!$stmt->fetch()) {
        throw new Exception('账号不存在');
    }

    // 重置密码
    $stmt = $pdo->prepare("UPDATE heasec_sessionoverride_users SET password = ? WHERE level = 1 AND username = ?");
    $stmt->execute([$password, $username]);

    // 销毁会话
    session_destroy();

    $response['success'] = true;
    $response['message'] = '密码重置成功';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
