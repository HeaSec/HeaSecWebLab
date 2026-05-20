<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第一关天积宝支付接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 3rdPay Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay_pay');

// 引入公共函数
require_once '../../includes/functions.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$orderId = isset($data['order_id']) ? trim($data['order_id']) : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0;
$account = isset($data['account']) ? trim($data['account']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

$level = 1;

// 验证参数
if (empty($orderId) || $amount <= 0 || empty($account) || empty($password)) {
    sendJsonResponse(false, '参数错误');
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单信息
    $order = getOrderByNo($orderId, $level, $pdo);
    if (!$order) {
        sendJsonResponse(false, '订单不存在');
    }

    // 检查订单状态
    if ($order['status'] !== 'pending') {
        sendJsonResponse(false, '订单状态异常');
    }

    // 获取天积宝用户
    $payUser = getPayUser($level, $account, $pdo);
    if (!$payUser || $payUser['pay_password'] !== $password) {
        sendJsonResponse(false, '账号或支付密码错误');
    }

    // 验证余额是否足够
    if ($payUser['balance'] < $amount) {
        sendJsonResponse(false, '余额不足，当前余额：' . number_format($payUser['balance'], 2) . '元');
    }

    // 开始事务
    $pdo->beginTransaction();

    // 扣款
    $newBalance = $payUser['balance'] - $amount;
    updatePayUserBalance($payUser['id'], $newBalance, $level, $pdo);

    // 创建交易记录
    createTransaction($payUser['id'], $level, $orderId, $amount, 'pay', $pdo);

    // 提交事务
    $pdo->commit();

    // 生成签名并回调通知电商
    $sign = generateCallbackSign($orderId, 'success');
    $callbackData = [
        'order_id' => $orderId,
        'status' => 'success',
        'amount' => $amount,
        'sign' => $sign
    ];

    // 发送回调请求
    $callbackUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST']
        . dirname(dirname($_SERVER['PHP_SELF'])) . '/level1/callback.php';

    sendCallbackRequest($callbackUrl, $callbackData);

    sendJsonResponse(true, '支付成功');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[HeaSec] Pay error: ' . $e->getMessage());
    sendJsonResponse(false, '支付失败，请稍后重试');
}
