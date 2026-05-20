<?php
/**
 * HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 第二关通关密码验证API
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化会话 - 使用与第一关相同的方式
HeaSec_InitRangeSession('sessionoverride');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

$response = ['passed' => false, 'message' => ''];

try {
    if (empty($passcode)) {
        throw new Exception('请输入通关密码');
    }

    $storedPasscode = isset($_SESSION['passcode_level2']) ? $_SESSION['passcode_level2'] : '';

    if (empty($storedPasscode)) {
        throw new Exception('请先登录admin账号获取通关密码');
    }

    if ($passcode === $storedPasscode) {
        $response['passed'] = true;
        $response['message'] = '恭喜通关！';
    } else {
        throw new Exception('通关密码错误');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
