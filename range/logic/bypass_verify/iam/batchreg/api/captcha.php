<?php
/**
 * HeaSec天积安全团队 - 批量注册靶场图片验证码接口
 * Batch Registration Range Captcha API
 * 版本: v1.0.0
 * 创建日期: 2026-02-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 批量注册靶场 Captcha API v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('batchreg');

// 引入验证码类
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 生成验证码（使用captcha_batchreg作为session key）
$captcha = new HeaSec_Captcha(120, 40, 4, 20, 'captcha_batchreg', $commonBasePath);
$captcha->generate();
