<?php
/**
 * HeaSec天积安全团队 - 第一关：获取验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');
header('Cache-Control: no-cache, no-store, must-revalidate');

// 设置公共组件的基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入假验证码生成器
require_once '../../includes/FakeCaptchaGenerator.php';

// 生成验证码
$generator = new FakeCaptchaGenerator(120, 40, 4);
$result = $generator->generate('captcha_level1');

// 返回验证码明文
echo json_encode(array(
    'success' => true,
    'image' => $result['image'],
    'code' => $result['code']  
), JSON_UNESCAPED_UNICODE);
