<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第三关用户信息接口
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

$pdo = HeaSec_Database::getConnection('heasec_logic');

// 检查登录状态
$userId = isset($_SESSION['session_user_id_level3']) ? $_SESSION['session_user_id_level3'] : null;
$loggedIn = isset($_SESSION['session_logged_in_level3']) && $_SESSION['session_logged_in_level3'] === true;

if (!$userId || !$loggedIn) {
    sendJsonResponse(true, '未登录', [
        'logged_in' => false,
        'username' => null,
        'realname' => null,
        'passcode' => null,
        'attack_success' => false
    ]);
}

// 获取用户信息
$user = getUserById($userId, $level, $pdo);
if (!$user) {
    sendJsonResponse(true, '未登录', [
        'logged_in' => false,
        'username' => null,
        'realname' => null,
        'passcode' => null,
        'attack_success' => false
    ]);
}

// 检查当前会话ID是否在参数记录中
$currentSessionId = session_id();
$attackSuccess = isSessionIdInParamLogs($currentSessionId, $level, $pdo);

$passcode = null;
if ($attackSuccess) {
    $passcode = getOrCreatePasscode($level, $pdo);
}

sendJsonResponse(true, '已登录', [
    'logged_in' => true,
    'username' => $user['username'],
    'realname' => $user['realname'],
    'passcode' => $passcode,
    'attack_success' => $attackSuccess
]);
