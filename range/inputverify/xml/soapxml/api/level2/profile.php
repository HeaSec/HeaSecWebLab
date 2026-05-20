<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场第二关用户信息接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once dirname(__DIR__) . '/../includes/functions.php';

if (!isset($_SESSION['soapxml_level2_user'])) {
    sendJsonResponse(false, '未登录');
}

$userData = $_SESSION['soapxml_level2_user'];
$response = [
    'success' => true,
    'data' => [
        'username' => $userData['username'],
        'role' => $userData['role']
    ]
];

// 管理员可查看通关密码
if ($userData['role'] === 'admin') {
    $secretPath = getSecretFilePath(2);
    generateSecretFile($secretPath);
    $passcode = extractPasscode($secretPath);
    $response['data']['passcode'] = $passcode;
}

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
