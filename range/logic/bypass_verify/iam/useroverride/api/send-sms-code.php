<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 发送短信验证码接口
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
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

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

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM heasec_useroverride_users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 漏洞点4：账号枚举 - 手机号未注册返回不同提示
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '手机号未注册']);
        exit;
    }

    // 检查是否需要图片验证码
    if ($user['login_attempts'] >= 5) {
        $captchaVerifier = new HeaSec_Captcha(120, 40, 4, 20, 'useroverride_captcha', $commonBasePath);
        if (empty($captcha) || !$captchaVerifier->verify($captcha)) {
            echo json_encode(['success' => false, 'message' => '图片验证码错误', 'need_captcha' => true]);
            exit;
        }
    }

    // 生成6位短信验证码
    $smsCode = sprintf('%06d', mt_rand(0, 999999));

    // 保存验证码到会话
    $_SESSION['useroverride_sms_code'] = $smsCode;
    $_SESSION['useroverride_sms_phone'] = $phone;
    $_SESSION['useroverride_sms_expire'] = time() + 300; // 5分钟有效期

    // 发送短信
    $message = '【天积安全】您的验证码为：' . $smsCode . '，有效期5分钟，请勿泄露。';
    $sendResult = HeaSec_SmsSender::send($phone, $message, 'useroverride');

    if ($sendResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => '验证码已发送',
            'need_captcha' => $user['login_attempts'] >= 5
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '验证码发送失败，请稍后重试']);
    }

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Send SMS error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}
