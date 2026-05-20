<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第二关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 3rdPay Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';
$level = 2;

// 验证参数
if (empty($username) || empty($password)) {
    sendJsonResponse(false, '请输入账号和密码');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $user = getUser($level, $username, $pdo);

    if (!$user || $user['password'] !== $password) {
        sendJsonResponse(false, '账号或密码错误');
    }

    // 保存用户ID到会话
    $_SESSION['3rdpay_user_id_level' . $level] = $user['id'];
    $_SESSION['3rdpay_username_level' . $level] = $user['username'];

    // 获取商品列表
    $products = getProducts($level, $pdo);
    $product = !empty($products) ? $products[0] : null;

    // 初始化用户通关密码（首次登录时生成）
    initUserPasscode($user['id'], $level, $pdo);

    // 检查是否满足通关条件（有已支付订单）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_3rdpay_orders WHERE user_id = ? AND level = ? AND status = 'paid'");
    $stmt->execute([$user['id'], $level]);
    $hasPaidOrder = $stmt->fetchColumn() > 0;

    // 如果满足条件，获取通关密码
    $passcode = null;
    if ($hasPaidOrder) {
        $passcode = getUserPasscode($user['id'], $level, $pdo);
    }

    // 返回用户信息
    sendJsonResponse(true, '登录成功', [
        'username' => $user['username'],
        'product' => $product,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Login error: ' . $e->getMessage());
    sendJsonResponse(false, '登录失败，请稍后重试');
}
