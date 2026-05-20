<?php
/**
 * HeaSec天积安全团队 - 第二关：获取验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第二关不返回验证码明文
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');
header('Cache-Control: no-cache, no-store, must-revalidate');


// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入假验证码生成器
require_once '../../includes/FakeCaptchaGenerator.php';

// 生成验证码（只返回图片）
$generator = new FakeCaptchaGenerator(120, 40, 4);
$image = $generator->generateImageOnly('captcha_level2');

// 正常返回（不包含验证码明文）
echo json_encode(array(
    'success' => true,
    'image' => $image
), JSON_UNESCAPED_UNICODE);
