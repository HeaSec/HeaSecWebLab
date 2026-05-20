<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第二关登录接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../../includes/functions.php';

$level = 2;
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

// 轮换会话ID，防止会话固定
session_regenerate_id(true);

// 写入会话
$_SESSION['session_user_id_level2'] = $user['id'];
$_SESSION['session_username_level2'] = $user['username'];
$_SESSION['session_realname_level2'] = $user['realname'];
$_SESSION['session_role_level2'] = $user['role'];
$_SESSION['session_logged_in_level2'] = true;

// 记录活跃会话（使用轮换后的新会话ID）
recordActiveSession($level, session_id(), $user['username'], $user['role'], $pdo);

sendJsonResponse(true, '登录成功', [
    'username' => $user['username'],
    'realname' => $user['realname'],
    'role' => $user['role']
]);
