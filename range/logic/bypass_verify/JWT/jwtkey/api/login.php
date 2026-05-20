<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - 登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT密钥注入 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径（从api目录到common目录的相对路径）
$commonBasePath = '../../../../../common/';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场配置和功能文件
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/init_users.php';
require_once dirname(__DIR__) . '/includes/jwt.php';

// 初始化用户
initializeUsers();

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

// 验证参数
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入账号和密码'
    ]);
    exit;
}

try {
    // 获取用户信息
    $user = getUserByUsername($username);

    if (!$user || $user['password'] !== $password) {
        echo json_encode([
            'success' => false,
            'message' => '账号或密码错误'
        ]);
        exit;
    }

    // 生成JWT Token
    $token = JWT_KeyInjection::encode($username, $user['role']);

    if (!$token) {
        echo json_encode([
            'success' => false,
            'message' => 'Token生成失败'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'token' => $token,
        'message' => '登录成功'
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Login error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器错误，请稍后重试'
    ]);
}
