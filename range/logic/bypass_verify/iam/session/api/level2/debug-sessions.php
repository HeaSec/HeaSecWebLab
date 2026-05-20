<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第二关调试会话列表接口
 * 版本: v1.0.0
 *
 * 查询活跃会话列表，若未发现管理员会话则自动创建（兜底机制）
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
$cookiePath = getRangeCookiePath();

// 确保管理员活跃会话存在（自动兜底：不存在则创建）
ensureAdminSession($pdo, $cookiePath);

// 查询所有第二关的活跃会话
$stmt = $pdo->prepare("SELECT session_id, username, role, login_time FROM heasec_session_active_sessions WHERE level = ? ORDER BY login_time DESC");
$stmt->execute([$level]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendJsonResponse(true, '获取成功', [
    'sessions' => $sessions
]);
