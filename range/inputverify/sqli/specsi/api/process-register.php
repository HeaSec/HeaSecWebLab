<?php
/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场 - 第一关用户注册接口
 * 版本: v1.0.0
 * 功能: 用户注册处理（参数化查询，安全）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';

if ($username === '' || $password === '') {
    sendJsonResponse(false, '请输入用户名和密码');
}

$pdo = HeaSec_Database::getConnection('heasec_sqli');

try {
    // 检查用户名是否已存在
    $stmt = $pdo->prepare("SELECT id FROM heasec_specsi_customers WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        sendJsonResponse(false, '用户名已存在');
    }

    // 参数化INSERT — 安全！即使username包含SQL代码也不会在此执行
    $stmt = $pdo->prepare("INSERT INTO heasec_specsi_customers (username, password, email, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$username, $password, $email]);
} catch (PDOException $e) {
    sendJsonResponse(false, '系统错误，请稍后重试');
}

sendJsonResponse(true, '注册成功');
