<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 用户名密码登录接口
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
$password = isset($input['password']) ? trim($input['password']) : '';
$captcha = isset($input['captcha']) ? trim($input['captcha']) : '';

// 验证必填字段
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '请填写用户名和密码']);
    exit;
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM heasec_useroverride_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 漏洞点4：账号枚举 - 用户名不存在和密码错误返回不同提示
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }

    // 检查是否需要图片验证码（连续错误5次）
    if ($user['login_attempts'] >= 5) {
        $captchaVerifier = new HeaSec_Captcha(120, 40, 4, 20, 'useroverride_captcha', $commonBasePath);
        if (empty($captcha) || !$captchaVerifier->verify($captcha)) {
            echo json_encode(['success' => false, 'message' => '图片验证码错误', 'need_captcha' => true]);
            exit;
        }
    }

    // 验证密码
    if ($user['password'] !== $password) {
        // 增加错误计数
        $newAttempts = $user['login_attempts'] + 1;
        $stmt = $pdo->prepare("UPDATE heasec_useroverride_users SET login_attempts = ? WHERE id = ?");
        $stmt->execute([$newAttempts, $user['id']]);

        $needCaptcha = $newAttempts >= 5;
        echo json_encode([
            'success' => false,
            'message' => '密码错误',
            'need_captcha' => $needCaptcha
        ]);
        exit;
    }

    // 登录成功，重置错误计数
    $stmt = $pdo->prepare("UPDATE heasec_useroverride_users SET login_attempts = 0 WHERE id = ?");
    $stmt->execute([$user['id']]);

    // 保存登录状态到会话
    $_SESSION['useroverride_logged_in'] = true;
    $_SESSION['useroverride_user_id'] = $user['id'];
    $_SESSION['useroverride_username'] = $user['username'];
    $_SESSION['useroverride_phone'] = $user['phone'];
    $_SESSION['useroverride_is_admin'] = $user['is_admin'];

    // 如果是管理员，需要二次验证
    if ($user['is_admin'] == 1) {
        // 生成管理员秘密字符串（如果不存在）
        if (empty($user['secret'])) {
            $secret = generateRandomString(20);
            $stmt = $pdo->prepare("UPDATE heasec_useroverride_users SET secret = ? WHERE id = ?");
            $stmt->execute([$secret, $user['id']]);
        }

        echo json_encode([
            'success' => true,
            'message' => '登录成功，请完成二次验证',
            'need_admin_verify' => true,
            'is_admin' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'need_admin_verify' => false,
            'is_admin' => false,
            'user' => [
                'username' => $user['username'],
                'phone' => $user['phone'],
                'created_at' => $user['created_at'],
                'is_admin' => false
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Login error: ' . $e->getMessage());
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
