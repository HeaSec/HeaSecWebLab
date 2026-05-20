<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场 - 第三关签到接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 * 
 * 漏洞类型：签到功能存在TOCTOU（Time-of-Check to Time-of-Use）竞态条件漏洞
 * 先查询后操作的设计在并发请求下可被绕过
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

// 引入公共函数
require_once '../../includes/functions.php';

// 初始化靶场会话
HeaSec_InitRangeSession('replay');

// 获取当前关卡用户ID（从会话中获取）
$level = 3;
$userId = isset($_SESSION['replay_user_id_level' . $level]) ? $_SESSION['replay_user_id_level' . $level] : null;

if (!$userId) {
    sendJsonResponse(false, '请先登录');
}

// 漏洞点：在获取用户ID后立即释放会话锁
// 这允许并发请求同时执行，从而触发TOCTOU竞态条件
// 正常情况下应该在整个签到过程中保持会话锁
session_write_close();

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    
    // 获取用户信息
    $user = getUserById($userId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }
    
    // 获取当前日期
    $today = date('Y-m-d');
    
    // 漏洞代码：先查询后操作，这三个步骤不是原子操作
    // 在并发请求下可能出现多个请求同时通过步骤1的检查
    
    // 步骤1：查询数据库，检查用户当天是否已有签到记录
    $hasSigned = hasSignedIn($userId, $level, $today, $pdo);

    // 漏洞点：在检查和使用之间没有保护，存在时间窗口
    // 添加延迟模拟真实场景中的处理时间，扩大竞态条件窗口
    if (!$hasSigned) {
        // 模拟业务处理延迟（50ms），使竞态条件更容易被触发
        usleep(50000);
        // 每次签到固定获得100元红包
        $amount = 100.00;
        
        // 步骤2：插入签到记录
        recordSignin($userId, $level, $today, $amount, $pdo);
        
        // 步骤3：增加用户余额
        updateBalance($userId, $amount, $pdo);
        
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
    } else {
        sendJsonResponse(false, '今日已签到');
    }
    
} catch (Exception $e) {
    error_log('[HeaSec] Signin error: ' . $e->getMessage());
    sendJsonResponse(false, '签到失败，请稍后重试');
}
