<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 本地验证码生成接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 本地验证码生成，避免跨路径Session问题
 */

// 禁用错误输出，防止破坏图片数据
error_reporting(0);
ini_set('display_errors', 0);

// 设置公共组件基础路径
$commonBasePath = '../../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（使用与页面相同的会话）
HeaSec_InitRangeSession('resetstepbp');

// 引入验证码类
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 可以通过 GET 参数自定义配置，但为了安全限制范围
$width = isset($_GET['w']) ? intval($_GET['w']) : 120;
$height = isset($_GET['h']) ? intval($_GET['h']) : 40;
$length = isset($_GET['l']) ? intval($_GET['l']) : 4;

// 限制最大尺寸防止 DoS
if ($width > 300)
    $width = 300;
if ($height > 100)
    $height = 100;
if ($length > 8)
    $length = 8;

// 生成并输出验证码
$captcha = new HeaSec_Captcha($width, $height, $length, 20, 'captcha_resetstepbp', $commonBasePath);
$captcha->generate();
