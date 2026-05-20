<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 第一关个人信息接口
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

// 获取查询参数
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = ['success' => false, 'message' => ''];

try {
    if ($id <= 0) {
        throw new Exception('无效的ID参数');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化第一关用户数据
    initLevelUsers(1, $pdo);

    // 根据数字ID查询用户信息
    $stmt = $pdo->prepare("SELECT name, phone, idcard, num_id, passcode FROM heasec_idref_users WHERE level = 1 AND num_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('用户不存在');
    }

    // 构建返回数据
    $responseData = [
        'name' => $user['name'],
        'phone' => $user['phone'],
        'idcard' => $user['idcard'],
        'num_id' => $user['num_id']
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
