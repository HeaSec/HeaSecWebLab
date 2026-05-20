<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第一关通关验证接口
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

$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

$response = ['success' => false, 'passed' => false, 'message' => ''];

try {
    if (empty($passcode)) {
        throw new Exception('请输入通关密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证通关密码
    if (verifyPasscode(1, $passcode, $pdo)) {
        $_SESSION['filebac_level1_passed'] = true;
        $response['success'] = true;
        $response['passed'] = true;
        $response['message'] = '验证通过！恭喜你完成了第一关的挑战。';
    } else {
        $response['message'] = '通关密码错误，请重试。';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
