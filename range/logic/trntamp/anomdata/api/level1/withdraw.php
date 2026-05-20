<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第一关提现接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 异常数据 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('anomdata');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$level = 1;

try {
    // 检查是否已登录
    $sessionUserId = isset($_SESSION['anomdata_user_id_level' . $level]) ? $_SESSION['anomdata_user_id_level' . $level] : null;
    if (!$sessionUserId) {
        sendJsonResponse(false, '请先登录');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取提现金额
    $amount = isset($data['amount']) ? floatval($data['amount']) : 0;

    // 校验提现金额范围
    if ($amount <= 0) {
        sendJsonResponse(false, '提现金额必须大于0');
    }

    if ($amount > 100) {
        sendJsonResponse(false, '单次提现金额不能超过100元');
    }

    // 计算扣款金额（精确到小数点后3位）
    $deductAmount = round($amount, 3);

    // 计算到账金额（四舍五入到小数点后2位）- 漏洞点
    $creditAmount = round($amount, 2);

    // 使用原子操作更新余额（防止条件竞争）
    // 同时在SQL中进行余额检查，确保不会透支
    $stmt = $pdo->prepare("
        UPDATE heasec_anomdata_users
        SET alipay_balance = alipay_balance - ?,
            bank_balance = bank_balance + ?
        WHERE id = ? AND level = ? AND alipay_balance >= ?
    ");
    $stmt->execute([$deductAmount, $creditAmount, $sessionUserId, $level, $deductAmount]);

    // 检查更新是否成功（rowCount为0表示余额不足或用户不存在）
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, '支付宝余额不足');
    }

    // 获取更新后的用户信息
    $user = getUserById($sessionUserId, $level, $pdo);
    if (!$user) {
        sendJsonResponse(false, '用户不存在');
    }

    // 添加交易记录
    addTransaction(
        $user['id'],
        $level,
        'withdraw',
        $creditAmount,
        null,
        '提现到银行卡（扣款：' . number_format($deductAmount, 3) . '元，到账：' . number_format($creditAmount, 2) . '元）',
        $pdo
    );

    // 检查银行卡余额是否达到15元
    $passcode = null;
    if ($user['bank_balance'] >= 15) {
        $passcode = getPasscode($level);
        if (!$passcode) {
            $passcode = generatePasscode($level);
        }
    }

    // 返回结果
    sendJsonResponse(true, '提现成功', [
        'alipayBalance' => floatval($user['alipay_balance']),
        'bankBalance' => floatval($user['bank_balance']),
        'passcode' => $passcode
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Withdraw error: ' . $e->getMessage());
    sendJsonResponse(false, '提现失败，请稍后重试');
}
