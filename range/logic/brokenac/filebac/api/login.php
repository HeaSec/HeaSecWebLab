<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第一关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

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
    initLevel1Data($pdo);

    // 获取关卡数据
    $levelData = getLevelData(1, $pdo);
    if (!$levelData) {
        throw new Exception('系统错误，请刷新页面重试');
    }

    // 验证账号密码（测试账号：test / 123456）
    if ($account !== 'test' || $password !== '123456') {
        throw new Exception('账号或密码错误');
    }

    // 解析用户数据
    $userData = json_decode($levelData['user_data'], true);

    // 设置会话
    $_SESSION['filebac_level1_logged_in'] = true;
    $_SESSION['filebac_level1_user'] = $userData;

    $response['success'] = true;
    $response['message'] = '登录成功';
    $response['data'] = $userData;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
