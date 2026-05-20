<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第一关第一步API
 * 版本: v1.1.0
 * 创建日期: 2026-02-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 处理第一步：输入账号
 * 返回动态生成的下一步URL，而非硬编码
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件基础路径
$commonBasePath = '../../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入短信发送器
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

// 生成6位随机验证码
function generateCaptcha() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

$username = isset($input['username']) ? trim($input['username']) : '';

if (empty($username)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入账号'
    ]);
    exit;
}

// 验证账号是否存在
$pdo = HeaSec_Database::getConnection('heasec_logic');
$stmt = $pdo->prepare("SELECT phone FROM heasec_resetstepbp_users WHERE level = 1 AND username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => '账号不存在'
    ]);
    exit;
}

// 生成短信验证码
$smsCode = generateCaptcha();

// 存储验证码到会话
$_SESSION['resetstepbp_level1_sms_captcha'] = $smsCode;

// 存储账号到会话
$_SESSION['resetstepbp_level1_reset_username'] = $username;

//将验证结果变量初始化为null（不是false）
unset($_SESSION['resetstepbp_level1_reset_verified']);

// 发送短信验证码
$message = "【天积安全】您的验证码是：{$smsCode}，有效期5分钟，请勿泄露。";
HeaSec_SmsSender::send($user['phone'], $message, 'resetstepbp');

// 动态生成下一步URL（使用时间戳混淆，防止缓存和预测）
$nextUrl = 'step2.php?t=' . time();

echo json_encode([
    'success' => true,
    'message' => '验证码已发送',
    'next_url' => $nextUrl,
    'timestamp' => time()
]);
