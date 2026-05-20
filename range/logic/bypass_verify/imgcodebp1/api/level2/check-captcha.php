<?php
/**
 * HeaSec天积安全团队 - 第二关：校验验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 返回验证码校验结果（1/0）
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'verified' => false,
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

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$captcha = isset($data['captcha']) ? trim($data['captcha']) : '';

if (empty($captcha)) {
    echo json_encode(array(
        'verified' => false,
        'message' => '请输入验证码'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证验证码（不销毁，让用户可以多次校验）
$generator = new FakeCaptchaGenerator();
$verified = $generator->verify('captcha_level2', $captcha, false);

echo json_encode(array(
    'verified' => $verified,
    'message' => $verified ? '验证码正确' : '验证码错误'
), JSON_UNESCAPED_UNICODE);
