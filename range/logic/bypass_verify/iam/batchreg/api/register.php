<?php
/**
 * HeaSec天积安全团队 - 批量注册靶场注册接口
 * Batch Registration Range Register API
 * 版本: v1.0.0
 * 创建日期: 2026-02-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 批量注册靶场 Register API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('batchreg');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

$username = isset($input['username']) ? trim($input['username']) : '';
$nickname = isset($input['nickname']) ? trim($input['nickname']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$imgCaptcha = isset($input['captcha']) ? trim($input['captcha']) : '';
$smsCode = isset($input['sms_code']) ? trim($input['sms_code']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';

// 验证必填字段
if (empty($username) || empty($nickname) || empty($phone) || empty($imgCaptcha) || empty($smsCode) || empty($password) || empty($confirmPassword)) {
    echo json_encode([
        'success' => false,
        'message' => '请填写完整信息'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证图片验证码（漏洞：验证后不删除）
if (!isset($_SESSION['captcha_batchreg']) || strtolower($imgCaptcha) !== strtolower($_SESSION['captcha_batchreg'])) {
    echo json_encode([
        'success' => false,
        'message' => '图片验证码错误'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证短信验证码
if (!isset($_SESSION['sms_code_batchreg']) || $smsCode !== $_SESSION['sms_code_batchreg']) {
    echo json_encode([
        'success' => false,
        'message' => '短信验证码错误'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证手机号匹配
if ($phone !== $_SESSION['sms_phone_batchreg']) {
    echo json_encode([
        'success' => false,
        'message' => '手机号与验证码不匹配'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证手机号格式
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => '手机号格式不正确'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证密码一致性
if ($password !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => '两次密码不一致'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证密码长度
if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => '密码长度不能少于6位'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证用户名唯一性
    $stmt = $pdo->prepare("SELECT id FROM heasec_batchreg_users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => '用户名已存在'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 验证手机号唯一性
    $stmt = $pdo->prepare("SELECT id FROM heasec_batchreg_users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => '手机号已被注册'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 插入用户数据
    $stmt = $pdo->prepare("INSERT INTO heasec_batchreg_users (username, nickname, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $nickname, $password, $phone]);

    // 清除短信验证码（一次性使用）
    unset($_SESSION['sms_code_batchreg']);
    unset($_SESSION['sms_phone_batchreg']);

    echo json_encode([
        'success' => true,
        'message' => '注册成功'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库错误，请稍后重试'
    ], JSON_UNESCAPED_UNICODE);
}
