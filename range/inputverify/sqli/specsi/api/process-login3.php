<?php
/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场 - 第三关登录接口
 * 版本: v1.0.0
 * 功能: 用户登录验证（参数化查询，安全）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';
require_once __DIR__ . '/../includes/functions.php';

HeaSec_InitRangeSession('specsi');

// 退出登录处理
$action = $_POST['action'] ?? '';
if ($action === 'logout') {
    unset($_SESSION['specsi_user_id'], $_SESSION['specsi_username'], $_SESSION['specsi_role'], $_SESSION['specsi_level']);
    sendJsonResponse(true, '已退出登录');
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    sendJsonResponse(false, '请输入用户名和密码');
}

$pdo = HeaSec_Database::getConnection('heasec_sqli');

try {
    // 登录查询使用参数化查询（安全，非注入点）
    $stmt = $pdo->prepare("SELECT id, username, role FROM heasec_specsi_employees WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJsonResponse(false, '系统错误，请稍后重试');
}

if ($user) {
    $_SESSION['specsi_user_id'] = $user['id'];
    $_SESSION['specsi_username'] = $user['username'];
    $_SESSION['specsi_role'] = $user['role'];
    $_SESSION['specsi_level'] = 3;
    sendJsonResponse(true, '登录成功', ['username' => $user['username'], 'role' => $user['role']]);
} else {
    sendJsonResponse(false, '用户名或密码错误');
}
