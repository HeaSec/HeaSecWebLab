<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第二关重置密码API
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：从会话中读取账号信息进行密码重置
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
$password = isset($data['password']) ? trim($data['password']) : '';
$confirmPassword = isset($data['confirm_password']) ? trim($data['confirm_password']) : '';

$response = ['success' => false, 'message' => ''];

try {
    // 基本验证
    if (empty($password)) {
        throw new Exception('请输入新密码');
    }
    if ($password !== $confirmPassword) {
        throw new Exception('两次密码不一致');
    }

    // 检查验证状态
    if (!isset($_SESSION['verified_level2']) || $_SESSION['verified_level2'] !== true) {
        throw new Exception('请先完成手机验证');
    }

    // 从会话中获取账号信息
    $username = isset($_SESSION['reset_username_level2']) ? $_SESSION['reset_username_level2'] : '';
    if (empty($username)) {
        throw new Exception('会话信息无效');
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证账号是否存在
    $stmt = $pdo->prepare("SELECT id FROM heasec_sessionoverride_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);
    if (!$stmt->fetch()) {
        throw new Exception('账号不存在');
    }

    // 重置密码
    $stmt = $pdo->prepare("UPDATE heasec_sessionoverride_users SET password = ? WHERE level = 2 AND username = ?");
    $stmt->execute([$password, $username]);

    // 销毁会话
    session_destroy();

    $response['success'] = true;
    $response['message'] = '密码重置成功';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
