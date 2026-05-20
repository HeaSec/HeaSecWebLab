<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第二关用户信息接口
 * 版本: v1.0.0
 *
 * 管理员用户登录时显示通关密码
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../../includes/functions.php';

$level = 2;
initRangeSession($level);

$pdo = HeaSec_Database::getConnection('heasec_logic');

// 检查登录状态
$userId = isset($_SESSION['session_user_id_level2']) ? $_SESSION['session_user_id_level2'] : null;
$loggedIn = isset($_SESSION['session_logged_in_level2']) && $_SESSION['session_logged_in_level2'] === true;

if (!$userId || !$loggedIn) {
    sendJsonResponse(true, '未登录', [
        'logged_in' => false,
        'username' => null,
        'realname' => null,
        'role' => null,
        'passcode' => null
    ]);
}

// 获取用户信息
$user = getUserById($userId, $level, $pdo);
if (!$user) {
    sendJsonResponse(true, '未登录', [
        'logged_in' => false,
        'username' => null,
        'realname' => null,
        'role' => null,
        'passcode' => null
    ]);
}

// 管理员用户显示通关密码
$passcode = null;
if ($user['role'] === 'admin') {
    $passcode = getOrCreatePasscode($level, $pdo);
}

sendJsonResponse(true, '已登录', [
    'logged_in' => true,
    'username' => $user['username'],
    'realname' => $user['realname'],
    'role' => $user['role'],
    'passcode' => $passcode
]);
