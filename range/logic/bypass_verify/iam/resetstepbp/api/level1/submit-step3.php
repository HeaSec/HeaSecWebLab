<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第一关第三步API
 * 版本: v1.1.0
 * 创建日期: 2026-02-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 处理第三步：设置新密码
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

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 检查是否有账号信息
$username = isset($_SESSION['resetstepbp_level1_reset_username']) ? $_SESSION['resetstepbp_level1_reset_username'] : '';

if (empty($username)) {
    echo json_encode([
        'success' => false,
        'message' => '会话已过期，请重新开始',
        'redirect_url' => 'step1.php'
    ]);
    exit;
}

// 获取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

$password = isset($input['password']) ? trim($input['password']) : '';
$confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';

if (empty($password) || empty($confirmPassword)) {
    echo json_encode([
        'success' => false,
        'message' => '请填写完整信息'
    ]);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => '两次密码不一致'
    ]);
    exit;
}


$resetVerified = isset($_SESSION['resetstepbp_level1_reset_verified']) ? $_SESSION['resetstepbp_level1_reset_verified'] : null;

if ($resetVerified !== false) {  
    // 验证通过（包括 null 的情况）
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 验证账号是否存在
    $stmt = $pdo->prepare("SELECT id FROM heasec_resetstepbp_users WHERE level = 1 AND username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        // 重置密码
        $stmt = $pdo->prepare("UPDATE heasec_resetstepbp_users SET password = ? WHERE level = 1 AND username = ?");
        $stmt->execute([$password, $username]);

        // 清除会话中的验证状态和账号信息
        unset($_SESSION['resetstepbp_level1_reset_username']);
        unset($_SESSION['resetstepbp_level1_reset_verified']);
        unset($_SESSION['resetstepbp_level1_sms_captcha']);

        echo json_encode([
            'success' => true,
            'message' => '密码重置成功',
            'reset_complete' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '账号不存在'
        ]);
    }
} else {
    // 验证失败（reset_verified === false）
    echo json_encode([
        'success' => false,
        'message' => '验证未通过，请重新验证'
    ]);
}
