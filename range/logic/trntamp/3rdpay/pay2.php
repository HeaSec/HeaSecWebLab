<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第二关天积宝支付页面
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 天积宝 Payment v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay_pay');

// 引入公共函数
require_once 'includes/functions.php';

// 获取订单参数
$orderNo = isset($_GET['order_no']) ? trim($_GET['order_no']) : '';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
$level = 2;

// 验证参数
if (empty($orderNo) || $amount <= 0) {
    die('参数错误');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 获取天积宝用户余额
$payUser = getPayUser($level, 'heasecpay', $pdo);
$balance = $payUser ? $payUser['balance'] : 20.00;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>天积宝 - 安全支付</title>
    <meta name="author" content="天积安全 HeavenlySecret">
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range_common.css">
    <link rel="stylesheet" href="css/pay.css">
</head>
<body>
    <div class="pay-container">
        <div class="pay-header">
            <div class="pay-logo"><i class="fa fa-shield"></i></div>
            <div class="pay-brand">天积宝</div>
            <div class="pay-slogan">安全支付 · 快捷便利</div>
        </div>

        <div class="pay-body">
            <div class="order-info">
                <div class="order-info-title">
                    <i class="fa fa-receipt"></i>
                    <span>订单信息</span>
                </div>
                <div class="order-row">
                    <span class="order-label">订单号</span>
                    <span class="order-value"><?php echo htmlspecialchars($orderNo); ?></span>
                </div>
                <div class="order-row">
                    <span class="order-label">支付金额</span>
                    <span class="order-amount">¥<?php echo number_format($amount, 2); ?></span>
                </div>
            </div>

            <div id="errorArea" class="error-msg">
                <i class="fa fa-exclamation-circle"></i>
                <span id="errorText"></span>
            </div>

            <form id="payForm" class="pay-form">
                <div class="form-group">
                    <label class="form-label">天积宝账号</label>
                    <input type="text" id="account" class="form-input" placeholder="请输入天积宝账号" value="">
                </div>
                <div class="form-group">
                    <label class="form-label">支付密码</label>
                    <input type="password" id="password" class="form-input" placeholder="请输入支付密码">
                </div>
                <input type="hidden" id="orderNo" value="<?php echo htmlspecialchars($orderNo); ?>">
                <input type="hidden" id="amount" value="<?php echo $amount; ?>">
                <button type="submit" class="pay-btn" id="submitBtn">
                    <i class="fa fa-lock"></i> 确认支付
                </button>
            </form>

            <div id="successMsg" class="success-msg">
                <i class="fa fa-check-circle"></i>
                <div>支付成功</div>
                <div class="countdown">窗口将在 <span id="countdown">3</span> 秒后自动关闭</div>
            </div>

            <div class="balance-hint">
                <i class="fa fa-info-circle"></i> 天积宝余额：¥<?php echo number_format($balance, 2); ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('payForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var account = document.getElementById('account').value.trim();
            var password = document.getElementById('password').value.trim();
            var orderNo = document.getElementById('orderNo').value;
            var amount = document.getElementById('amount').value;
            var submitBtn = document.getElementById('submitBtn');
            var errorArea = document.getElementById('errorArea');
            var errorText = document.getElementById('errorText');

            if (!account || !password) {
                errorText.textContent = '请输入账号和支付密码';
                errorArea.style.display = 'flex';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 支付中...';
            errorArea.style.display = 'none';

            fetch('api/pay/level2.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderNo,
                    amount: parseFloat(amount),
                    account: account,
                    password: password
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('payForm').style.display = 'none';
                    document.getElementById('successMsg').style.display = 'block';
                    startCountdown();
                } else {
                    errorText.textContent = data.message;
                    errorArea.style.display = 'flex';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-lock"></i> 确认支付';
                }
            })
            .catch(function(err) {
                errorText.textContent = '支付失败，请稍后重试';
                errorArea.style.display = 'flex';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-lock"></i> 确认支付';
            });
        });

        function startCountdown() {
            var count = 3;
            var countdownEl = document.getElementById('countdown');
            var timer = setInterval(function() {
                count--;
                countdownEl.textContent = count;
                if (count <= 0) {
                    clearInterval(timer);
                    window.close();
                    setTimeout(function() {
                        countdownEl.textContent = '请手动关闭此窗口';
                    }, 500);
                }
            }, 1000);
        }
    </script>
</body>
</html>
