<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 第二关通关密码验证接口
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
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';
require_once __DIR__ . '/../../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('idref');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

$response = ['success' => false, 'passed' => false, 'message' => ''];

try {
    if ($passcode === '') {
        throw new Exception('请输入通关密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化用户数据
    initLevelUsers(2, $pdo);

    // 查询guanliyuan用户的通关密码
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_idref_users WHERE level = 2 AND account = 'guanliyuan'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['passcode'])) {
        throw new Exception('系统错误，请重置靶场');
    }

    if ($passcode === $user['passcode']) {
        // 通关成功，更新学习状态
        HeaSec_UpdateLearningStatusIfNeeded('idref');

        $response['success'] = true;
        $response['passed'] = true;
        $response['message'] = '验证成功，恭喜通关！';
    } else {
        throw new Exception('通关密码错误');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
