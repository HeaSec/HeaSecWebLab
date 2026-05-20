<?php
/**
 * HeaSec天积安全团队 - URL任意跳转靶场登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-03
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec URL任意跳转 API v1.0.0');

if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('urlredirect');

// 引入公共函数
require_once '../includes/functions.php';

// 常规表单POST提交
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$url = isset($_POST['url']) ? $_POST['url'] : '';

try {
    if (empty($username) || empty($password)) {
        throw new Exception('请输入用户名和密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_base');

    // 查询用户
    $stmt = $pdo->prepare('SELECT * FROM heasec_urlredirect_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('用户名或密码错误');
    }

    // 验证密码
    if (!password_verify($password, $user['password'])) {
        throw new Exception('用户名或密码错误');
    }

    // 登录成功，设置会话
    $_SESSION['urlredirect_user_id'] = $user['id'];
    $_SESSION['urlredirect_username'] = $user['username'];
    $_SESSION['urlredirect_logged_in'] = true;

    // 处理URL跳转
    handleUrlRedirect($user['id'], $url, $pdo, '../dashboard.php');

} catch (Exception $e) {
    // 登录失败，重定向回index.php并保留url参数
    $redirectUrl = '../index.php?error=1';
    if (!empty($url)) {
        $redirectUrl .= '&url=' . urlencode($url);
    }
    header('Location: ' . $redirectUrl);
    exit;
}
