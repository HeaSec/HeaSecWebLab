<?php
/**
 * HeaSec天积安全团队 - 第三关：获取验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 使用公共组件HeaSec_Captcha生成正常的图片验证码
 */
// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入验证码组件
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 生成并输出验证码图片
$captcha = new HeaSec_Captcha(120, 40, 4, 20, 'captcha_level3', $commonBasePath);
$captcha->generate();
