<?php
/**
 * HeaSec天积安全团队 - CRLF注入靶场重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-28
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CRLF API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('crlf');

$response = ['success' => false, 'message' => ''];

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_base');

    // 重置所有用户的通关状态
    $stmt = $pdo->prepare('UPDATE heasec_crlf_users SET passcode = NULL, completed_at = NULL');
    $stmt->execute();

    // 清除会话
    unset($_SESSION['crlf_user_id']);
    unset($_SESSION['crlf_username']);
    unset($_SESSION['heasec_secret']);

    $response['success'] = true;
    $response['message'] = '重置成功';

} catch (Exception $e) {
    $response['message'] = '重置失败：' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
