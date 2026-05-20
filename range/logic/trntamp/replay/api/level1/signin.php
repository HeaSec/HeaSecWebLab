<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场 - 第一关签到接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 漏洞类型：后端接口未对签到次数进行校验，仅依赖前端限制
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

// 获取当前关卡用户ID（从会话中获取）
$level = 1;
$userId = isset($_SESSION['replay_user_id_level' . $level]) ? $_SESSION['replay_user_id_level' . $level] : null;

if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取用户信息
    $user = getUserById($userId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }

    // 漏洞代码：不检查用户是否已签到过，直接处理签到请求并增加余额
    // 每次签到固定获得10元红包
    $amount = 10.00;

    // 更新用户余额
    updateBalance($userId, $amount, $pdo);

    // 记录签到（虽然记录但不用于限制）
    $today = date('Y-m-d');
    recordSignin($userId, $level, $today, $amount, $pdo);

    // 获取更新后的余额
    $user = getUserById($userId, $level, $pdo);
    $newBalance = floatval($user['balance']);

    // 检查是否达到500元
    $passcode = null;
    if ($newBalance >= 500) {
        $passcode = getPasscode($level);
        if (!$passcode) {
            $passcode = generatePasscode($level);
        }
    }

    sendJsonResponse(true, '签到成功，获得' . $amount . '元红包', [
        'amount' => $amount,
        'balance' => $newBalance,
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Signin error: ' . $e->getMessage());
    sendJsonResponse(false, '签到失败，请稍后重试');
}
