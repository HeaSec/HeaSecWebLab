<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第三关通关密码验证接口
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';
require_once __DIR__ . '/../../includes/config-init.php';

HeaSec_InitRangeSession('noauth');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

$response = ['success' => false, 'passed' => false, 'message' => ''];

try {
    if ($passcode === '') {
        throw new Exception('请输入通关密码');
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $config = initNoauthLevelConfig(3, $pdo);

    if ($passcode === $config['passcode']) {
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
