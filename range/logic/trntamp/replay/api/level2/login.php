<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场 - 第二关登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 重放攻击 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('replay');

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
    $stmt = $pdo->prepare("SELECT * FROM heasec_replay_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['password'] !== $password) {
        sendJsonResponse(false, '账号或密码错误');
    }

    // 保存用户ID到会话
    $_SESSION['replay_user_id_level' . $level] = $user['id'];
    $_SESSION['replay_username_level' . $level] = $user['username'];

    // 检查今天是否已签到
    $today = date('Y-m-d');
    $hasSignedIn = hasSignedIn($user['id'], $level, $today, $pdo);

    // 检查余额是否达到500元
    $passcode = null;
    if ($user['balance'] >= 500) {
        $passcode = getPasscode($level);
        if (!$passcode) {
            $passcode = generatePasscode($level);
        }
    }

    // 返回用户信息
    sendJsonResponse(true, '登录成功', [
        'id' => $user['id'],
        'username' => $user['username'],
        'balance' => floatval($user['balance']),
        'hasSignedIn' => $hasSignedIn,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Login error: ' . $e->getMessage());
    sendJsonResponse(false, '登录失败，请稍后重试');
}
