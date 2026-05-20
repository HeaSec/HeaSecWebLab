<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场获取登录状态接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/../includes/functions.php';

$level = isset($_GET['level']) ? (int)$_GET['level'] : 0;

if ($level < 1 || $level > 2) {
    sendJsonResponse(false, '无效的关卡编号');
}

$sessionKey = 'soapxml_level' . $level . '_user';

if (isset($_SESSION[$sessionKey])) {
    $userData = $_SESSION[$sessionKey];
    $response = [
        'success' => true,
        'isLoggedIn' => true,
        'data' => [
            'username' => $userData['username'],
            'role' => $userData['role']
        ]
    ];

    // 管理员可查看通关密码
    if ($userData['role'] === 'admin') {
        $secretPath = getSecretFilePath($level);
        generateSecretFile($secretPath);
        $passcode = extractPasscode($secretPath);
        $response['data']['passcode'] = $passcode;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

sendJsonResponse(true, '', ['isLoggedIn' => false]);
