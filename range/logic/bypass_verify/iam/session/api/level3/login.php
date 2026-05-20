<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第三关登录接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../../includes/functions.php';

$level = 3;
initRangeSession($level);

$data = getRequestData();
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

if (empty($username) || empty($password)) {
    sendJsonResponse(false, '请输入账号和密码');
}

$pdo = HeaSec_Database::getConnection('heasec_logic');
$user = getUser($level, $username, $pdo);

if (!$user || $user['password'] !== $password) {
    sendJsonResponse(false, '账号或密码错误');
}

// 确保通关密码已生成
getOrCreatePasscode($level, $pdo);

// 记录登录前的会话ID
$currentSessionId = session_id();

// 写入会话
$_SESSION['session_user_id_level3'] = $user['id'];
$_SESSION['session_username_level3'] = $user['username'];
$_SESSION['session_realname_level3'] = $user['realname'];
$_SESSION['session_role_level3'] = $user['role'];
$_SESSION['session_logged_in_level3'] = true;

// 检查当前会话ID是否在参数记录中
$attackSuccess = isSessionIdInParamLogs($currentSessionId, $level, $pdo);
$passcode = null;
if ($attackSuccess) {
    $passcode = getOrCreatePasscode($level, $pdo);
}

$responseData = [
    'username' => $user['username'],
    'realname' => $user['realname'],
    'attack_success' => $attackSuccess,
    'passcode' => $passcode
];

sendJsonResponse(true, '登录成功', $responseData);
