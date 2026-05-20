<?php
/**
 * HeaSec天积安全团队 - 第一关：验证提交接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第一关只验证验证码是否正确
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'passed' => false,
        'message' => '只允许POST请求'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}


// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入假验证码生成器
require_once '../../includes/FakeCaptchaGenerator.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$captcha = isset($data['captcha']) ? trim($data['captcha']) : '';

// 初始化响应
$response = array(
    'success' => false,
    'passed' => false,
    'message' => ''
);

try {
    if (empty($captcha)) {
        throw new Exception('请输入验证码');
    }

    // 验证验证码
    $generator = new FakeCaptchaGenerator();
    if (!$generator->verify('captcha_level1', $captcha, true)) {
        throw new Exception('验证码错误');
    }

    // 验证成功，更新学习状态：从"待学习"更新为"学习中"
    HeaSec_UpdateLearningStatusIfNeeded('imgcodebp1');

    $response['success'] = true;
    $response['passed'] = true;
    $response['message'] = '验证码正确，恭喜通关！';

} catch (Exception $e) {
    $response['success'] = false;
    $response['passed'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
