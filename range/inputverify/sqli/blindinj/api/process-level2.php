<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 功能: 用户登录验证（布尔盲注）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 从数据库读取密码并设置为MySQL会话变量
$varStmt = $pdo->query("SELECT var_value FROM heasec_blindinj_vars WHERE level = 2 AND var_name = 'password' LIMIT 1");
$varRow = $varStmt ? $varStmt->fetch(PDO::FETCH_ASSOC) : null;
if ($varRow && $varRow['var_value'] !== '') {
    $pdo->exec("SET @password = '" . addslashes($varRow['var_value']) . "'");
}

// 接收用户提交的用户名和密码
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === '') {
    sendJsonResponse(false, '请输入用户名');
}

// 字符型SQL查询 — 直接拼接用户名和密码
$sql = "SELECT id, username, status FROM heasec_blindinj_users WHERE username = '"
    . $username . "' AND password = '" . $password . "'";

try {
    $stmt = $pdo->query($sql);
    $user = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($user) {
        sendJsonResponse(true, '登录成功', ['success' => true]);
    } else {
        sendJsonResponse(true, '用户名或密码错误', ['success' => false]);
    }
} catch (PDOException $e) {
    // 不输出SQL错误信息，统一返回登录失败
    sendJsonResponse(true, '用户名或密码错误', ['success' => false]);
}
