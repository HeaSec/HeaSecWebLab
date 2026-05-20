<?php
/**
 * HeaSec天积安全团队 - 获取验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec ImgCodeBP2 GetCaptcha API v1.0.0');
header('Cache-Control: no-cache, no-store, must-revalidate');

// 设置公共组件路径
$commonBasePath = '../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp2');

// 引入假验证码生成器（本靶场本地副本）
require_once '../includes/FakeCaptchaGenerator.php';

try {
    // 生成验证码
    $generator = new FakeCaptchaGenerator(120, 40, 4);
    $result = $generator->generateImageOnly('imgcodebp2_captcha');

    // 返回base64图片（不返回验证码明文）
    echo json_encode([
        'success' => true,
        'image' => $result
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('[HeaSec] GetCaptcha error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '验证码生成失败'
    ], JSON_UNESCAPED_UNICODE);
}
?>