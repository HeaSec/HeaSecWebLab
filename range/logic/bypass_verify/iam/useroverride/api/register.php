<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 注册接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec 用户覆盖 Range v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件路径
$commonBasePath = '../../../../../common/';

// 引入必要组件
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'classes/HeaSec_Captcha.php';

// 初始化会话
HeaSec_InitRangeSession('useroverride');

// 仅接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$username = isset($input['username']) ? trim($input['username']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';
$captcha = isset($input['captcha']) ? trim($input['captcha']) : '';

// 验证必填字段
if (empty($username) || empty($phone) || empty($password) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => '请填写完整信息']);
    exit;
}

// 验证手机号格式
if (!preg_match('/^1\d{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '手机号格式不正确']);
    exit;
}

// 验证用户名格式
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => '用户名格式不正确（3-20位字母数字下划线）']);
    exit;
}

// 验证密码
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => '密码长度不能少于6位']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => '两次输入的密码不一致']);
    exit;
}

// 验证图片验证码（必须是通过check-phone验证过的）
$captchaVerifier = new HeaSec_Captcha(120, 40, 4, 20, 'useroverride_captcha', $commonBasePath);
if (empty($captcha) || !$captchaVerifier->verify($captcha)) {
    echo json_encode(['success' => false, 'message' => '图片验证码错误']);
    exit;
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户名是否存在及其对应的手机号
    $stmt = $pdo->prepare("SELECT phone FROM heasec_useroverride_users WHERE username = ?");
    $stmt->execute([$username]);
    $userByUsername = $stmt->fetch(PDO::FETCH_ASSOC);

    // 查询手机号是否存在及其对应的用户名
    $stmt = $pdo->prepare("SELECT username FROM heasec_useroverride_users WHERE phone = ?");
    $stmt->execute([$phone]);
    $userByPhone = $stmt->fetch(PDO::FETCH_ASSOC);

    // 检查一致性：用户名存在时，校验手机号是否匹配
    if ($userByUsername && $userByUsername['phone'] !== $phone) {
        echo json_encode(['success' => false, 'message' => '手机号与账号不匹配']);
        exit;
    }

    // 检查一致性：手机号存在时，校验用户名是否匹配
    if ($userByPhone && $userByPhone['username'] !== $username) {
        echo json_encode(['success' => false, 'message' => '手机号与账号不匹配']);
        exit;
    }

    // 执行注册
    $stmt = $pdo->prepare("
        INSERT INTO heasec_useroverride_users (username, phone, password, is_admin)
        VALUES (?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE password = VALUES(password)
    ");
    $stmt->execute([$username, $phone, $password]);

    // 检查是插入还是更新
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => '注册成功，请使用新账号登录'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '注册失败，请稍后重试']);
    }

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Register error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}
