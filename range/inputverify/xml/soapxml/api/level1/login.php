<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场第一关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once dirname(__DIR__) . '/../includes/functions.php';

$rawXml = file_get_contents('php://input');
$params = parseSoapRequest($rawXml, 'Login');

$username = isset($params['username']) ? $params['username'] : '';
$password = isset($params['password']) ? $params['password'] : '';

if (empty($username) || empty($password)) {
    sendJsonResponse(false, '用户名和密码不能为空');
}

$usersFile = dirname(__DIR__) . '/../data/level1_users.xml';

if (!file_exists($usersFile)) {
    sendJsonResponse(false, '用户名或密码错误');
}

$usersXml = simplexml_load_file($usersFile);
if ($usersXml === false) {
    sendJsonResponse(false, '系统错误');
}

// 使用遍历查找用户并比对密码，避免XPath注入
$loggedIn = false;
foreach ($usersXml->user as $user) {
    if ((string)$user->username === $username && (string)$user->password === $password) {
        $_SESSION['soapxml_level1_user'] = [
            'username' => (string)$user->username,
            'role'     => (string)$user->role
        ];

        sendJsonResponse(true, '登录成功', [
            'data' => [
                'username' => (string)$user->username,
                'role' => (string)$user->role
            ]
        ]);
    }
}

sendJsonResponse(false, '用户名或密码错误');
