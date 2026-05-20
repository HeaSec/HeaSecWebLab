<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第一关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/config-init.php';

HeaSec_InitRangeSession('noauth');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$account = isset($data['account']) ? trim($data['account']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if (empty($account) || empty($password)) {
        throw new Exception('请输入账号和密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化配置
    $config = initNoauthLevelConfig(1, $pdo);

    // 验证账号密码（只有admin账号）
    if ($account !== 'admin') {
        throw new Exception('账号或密码错误');
    }

    if ($password !== $config['admin_password']) {
        throw new Exception('账号或密码错误');
    }

    // 登录成功
    $_SESSION['noauth_level1_logged_in'] = true;
    $_SESSION['noauth_level1_user'] = [
        'account' => 'admin',
        'role' => 'admin'
    ];

    $response['success'] = true;
    $response['message'] = '登录成功';
    $response['data'] = [
        'redirect' => $config['random_path']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
