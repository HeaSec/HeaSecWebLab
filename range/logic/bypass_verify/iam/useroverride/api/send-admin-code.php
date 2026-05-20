<?php
/**
 * HeaSec天积安全团队 - 用户覆盖靶场 - 发送管理员验证码接口
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
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

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

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取当前用户信息
    $userId = $_SESSION['useroverride_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM heasec_useroverride_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }

    // 生成4位验证码
    $adminCode = sprintf('%04d', mt_rand(0, 9999));

    // 保存验证码到会话
    $_SESSION['useroverride_admin_code'] = $adminCode;
    $_SESSION['useroverride_admin_code_expire'] = time() + 300; // 5分钟有效期

    // 发送短信
    $message = '【天积安全】管理员验证码为：' . $adminCode . '，有效期5分钟，请勿泄露。';
    $sendResult = HeaSec_SmsSender::send($user['phone'], $message, 'useroverride');

    if ($sendResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => '验证码已发送到手机 ' . substr($user['phone'], 0, 3) . '****' . substr($user['phone'], -4)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '验证码发送失败，请稍后重试']);
    }

} catch (Exception $e) {
    error_log('[HeaSec UserOverride] Send admin code error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系统错误，请稍后重试']);
}
