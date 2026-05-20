<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 图片验证码接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件路径
$commonBasePath = '../../../../../common/';

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('useroverride');

// 引入验证码类
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 生成验证码
$captcha = new HeaSec_Captcha(120, 40, 4, 20, 'useroverride_captcha', $commonBasePath);
$captcha->generate();
