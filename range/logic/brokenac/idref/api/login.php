<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 登录接口
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
require_once __DIR__ . '/../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('idref');

// 获取JSON输入
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$account = isset($data['account']) ? trim($data['account']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';
$level = isset($data['level']) ? intval($data['level']) : 1;

$response = ['success' => false, 'message' => ''];

try {
    if (empty($account) || empty($password)) {
        throw new Exception('请输入账号和密码');
    }

    if (!in_array($level, [1, 2, 3])) {
        throw new Exception('无效的关卡');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化用户数据
    initLevelUsers($level, $pdo);

    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM heasec_idref_users WHERE level = ? AND account = ? AND password = ?");
    $stmt->execute([$level, $account, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('账号或密码错误');
    }

    // 设置会话
    $_SESSION['idref_level' . $level . '_logged_in'] = true;
    $_SESSION['idref_level' . $level . '_user'] = $user;

    // 构建返回数据
    $responseData = [
        'account' => $user['account'],
        'name' => $user['name'],
        'phone' => $user['phone'],
        'idcard' => $user['idcard']
    ];

    // 根据关卡添加额外字段
    if ($level === 1) {
        $responseData['num_id'] = $user['num_id'];
    } elseif ($level === 3) {
        $responseData['user_id'] = $user['user_id'];
    }

    $response['success'] = true;
    $response['message'] = '登录成功';
    $response['data'] = $responseData;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
