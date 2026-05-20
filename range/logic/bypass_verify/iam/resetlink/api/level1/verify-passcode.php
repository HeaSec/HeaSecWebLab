<?php
/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 第一关通关密码验证API
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 初始化靶场会话（使用相同的会话名称）
HeaSec_InitRangeSession('resetlink');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

$response = ['success' => false, 'passed' => false, 'message' => ''];

try {
    if ($passcode === '') {
        throw new Exception('请输入通关密码');
    }

    $storedPasscode = isset($_SESSION['passcode_level1']) ? $_SESSION['passcode_level1'] : '';

    if ($passcode === $storedPasscode) {
        // 通关成功，更新学习状态：从"待学习"更新为"学习中"
        HeaSec_UpdateLearningStatusIfNeeded('resetlink');

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
