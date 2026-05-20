<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 第二关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('vertbp');

// 获取JSON输入
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$account = isset($data['account']) ? trim($data['account']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

$response = ['success' => false, 'message' => ''];

try {
    if (empty($account) || empty($password)) {
        throw new Exception('请输入账号和密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化用户数据
    initVertbpLevelUsers(2, $pdo);

    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM heasec_vertbp_users WHERE level = 2 AND account = ? AND password = ?");
    $stmt->execute([$account, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('账号或密码错误');
    }

    // 设置会话
    $_SESSION['vertbp_level2_logged_in'] = true;
    $_SESSION['vertbp_level2_user'] = $user;

    // 将当前账号对应的状态写入Cookie，供后续页面读取
    $isAdmin = ($user['role'] === 'admin') ? '1' : '0';
    setcookie('vertbp_level2_is_admin', $isAdmin, time() + 3600, '/', '', false, false);  // 供后续页面读取

    $response['success'] = true;
    $response['message'] = '登录成功';
    $response['data'] = [
        'account' => $user['account'],
        'role' => $user['role'],
        'redirect' => 'admin.php'
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
