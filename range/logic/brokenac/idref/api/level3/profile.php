<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 第三关个人信息接口
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
HeaSec_InitRangeSession('idref');

// 获取查询参数（用户ID）
$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';

$response = ['success' => false, 'message' => ''];

try {
    if (empty($uid)) {
        throw new Exception('无效的uid参数');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化第三关用户数据
    initLevelUsers(3, $pdo);

    // 根据用户ID查询用户信息
    $stmt = $pdo->prepare("SELECT name, phone, idcard, user_id, passcode FROM heasec_idref_users WHERE level = 3 AND user_id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('用户不存在');
    }

    // 构建返回数据
    $responseData = [
        'name' => $user['name'],
        'phone' => $user['phone'],
        'idcard' => $user['idcard'],
        'uid' => $user['user_id']
    ];

    // 如果是guanliyuan用户，返回通关密码
    if (!empty($user['passcode'])) {
        $responseData['passcode'] = $user['passcode'];
    }

    $response['success'] = true;
    $response['data'] = $responseData;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
