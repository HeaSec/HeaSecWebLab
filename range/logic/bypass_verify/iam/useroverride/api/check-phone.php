<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 检查手机号接口
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
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$captcha = isset($input['captcha']) ? trim($input['captcha']) : '';

// 验证必填字段
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => '请输入手机号']);
    exit;
}

// 验证手机号格式
if (!preg_match('/^1\d{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '手机号格式不正确']);
    exit;
}

// 检查验证码是否已在check-phone中使用过（防止重复使用）
if (isset($_SESSION['useroverride_captcha_used_value']) &&
    strtolower($captcha) === strtolower($_SESSION['useroverride_captcha_used_value'])) {
    // 验证码重复使用，使其失效并报错
    unset($_SESSION['useroverride_captcha']);
    unset($_SESSION['useroverride_captcha_used_value']);
    echo json_encode(['success' => false, 'message' => '图片验证码已失效，请刷新验证码']);
    exit;
}

// 验证图片验证码（不使其失效，只检查是否正确）
$sessionKey = 'useroverride_captcha';
if (empty($captcha) || !isset($_SESSION[$sessionKey])) {
    echo json_encode(['success' => false, 'message' => '图片验证码错误']);
    exit;
}

// 不区分大小写比较
if (strtolower($captcha) !== strtolower($_SESSION[$sessionKey])) {
    echo json_encode(['success' => false, 'message' => '图片验证码错误']);
    exit;
}

// 记录已使用的验证码值（防止在check-phone中重复使用，但允许在register中再用）
$_SESSION['useroverride_captcha_used_value'] = $captcha;

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询手机号是否存在
    $stmt = $pdo->prepare("SELECT username FROM heasec_useroverride_users WHERE phone = ?");
    $stmt->execute([$phone]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        echo json_encode([
            'success' => true,
            'exists' => 't',
            'username' => $existingUser['username']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => 'f'
        ]);
    }

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Check phone error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}
