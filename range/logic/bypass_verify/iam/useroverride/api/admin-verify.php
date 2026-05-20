<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 管理员二次验证接口
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

// 初始化会话
HeaSec_InitRangeSession('useroverride');

// 仅接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    exit;
}

// 检查是否已登录管理员账号
if (!isset($_SESSION['useroverride_logged_in']) || !$_SESSION['useroverride_logged_in']) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

if (!isset($_SESSION['useroverride_is_admin']) || $_SESSION['useroverride_is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => '非管理员账号']);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim($input['code']) : '';

// 验证必填字段
if (empty($code)) {
    echo json_encode(['success' => false, 'message' => '请输入验证码']);
    exit;
}

// 验证验证码格式
if (!preg_match('/^\d{4}$/', $code)) {
    echo json_encode(['success' => false, 'message' => '验证码格式不正确']);
    exit;
}

// 获取会话中的验证码
$sessionCode = isset($_SESSION['useroverride_admin_code']) ? $_SESSION['useroverride_admin_code'] : '';
$sessionExpire = isset($_SESSION['useroverride_admin_code_expire']) ? $_SESSION['useroverride_admin_code_expire'] : 0;

// 检查验证码是否过期
if (time() > $sessionExpire) {
    echo json_encode(['success' => false, 'message' => '验证码已过期，请重新获取']);
    exit;
}

// 验证验证码
if ($code !== $sessionCode) {
    echo json_encode(['success' => false, 'message' => '验证码错误']);
    exit;
}

// 验证成功，清除验证码
unset($_SESSION['useroverride_admin_code']);
unset($_SESSION['useroverride_admin_code_expire']);

// 标记管理员已通过二次验证
$_SESSION['useroverride_admin_verified'] = true;

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取当前用户信息和秘密
    $userId = $_SESSION['useroverride_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM heasec_useroverride_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 生成管理员秘密字符串（如果不存在）
    if (empty($user['secret'])) {
        $secret = generateRandomString(20);
        $stmt = $pdo->prepare("UPDATE heasec_useroverride_users SET secret = ? WHERE id = ?");
        $stmt->execute([$secret, $user['id']]);
        $user['secret'] = $secret;
    }

    echo json_encode([
        'success' => true,
        'message' => '验证成功',
        'user' => [
            'username' => $user['username'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at'],
            'is_admin' => true,
            'secret' => $user['secret']
        ]
    ]);

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Admin verify error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 20) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}
