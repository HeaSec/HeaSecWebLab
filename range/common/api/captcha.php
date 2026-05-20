<?php
/**
 * HeaSec天积安全团队 - 验证码API接口
 * 
 * 用于前端 <img> 标签调用生成验证码图片
 */

// 禁用错误输出，防止破坏图片数据
error_reporting(0);
ini_set('display_errors', 0);

// 定义基础路径
$commonDir = dirname(dirname(__DIR__)) . '/common';
// 计算相对路径（从common/api目录到common目录）
$commonBasePath = '../';

// 引入必要文件 (不引入 header.php 以避免 HTML 输出)
require_once $commonDir . '/includes/session_manager.php';
require_once $commonDir . '/classes/HeaSec_Captcha.php';

// 初始化会话
// 如果传递了 session_name，则使用指定的会话名（用于 HeaSec_SessionManager 兼容）
if (isset($_GET['sname']) && !empty($_GET['sname'])) {
    // 安全过滤
    $sname = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['sname']);
    session_name($sname);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 实例化验证码类
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

$captcha = new HeaSec_Captcha($width, $height, $length, 20, 'heasec_captcha', $commonBasePath);
$captcha->generate();
