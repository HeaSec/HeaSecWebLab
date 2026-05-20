<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第一关第二步
 * 版本: v1.1.0
 * 创建日期: 2026-02-04
 * 更新日期: 2026-02-05
 * 团队: 天积安全 (HeavenlySecret)
 *

 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.1.0');
header('Content-Type: text/html; charset=utf-8');

// 设置公共组件基础路径
$commonBasePath = '../../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 检查是否从第一步正常进入
$username = isset($_SESSION['resetstepbp_level1_reset_username']) ? $_SESSION['resetstepbp_level1_reset_username'] : '';

if (empty($username)) {
    // 如果没有账号信息，通过JavaScript重定向到第一步
    echo '<script>window.location.href="step1.php";</script>';
    exit;
}

// 获取用户手机号后四位
$pdo = HeaSec_Database::getConnection('heasec_logic');
$stmt = $pdo->prepare("SELECT phone FROM heasec_resetstepbp_users WHERE level = 1 AND username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$phoneSuffix = $user ? substr($user['phone'], -4) : '****';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - 第二步</title>
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
        }

        .reset-container {
            max-width: 450px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .reset-header h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .phone-hint {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .step {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            background: #f8f9fa;
            color: #6c757d;
        }

        .step.active {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-input {
            width: 100%;
            height: 45px;
            padding: 0 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .captcha-row {
            display: flex;
            gap: 10px;
        }

        .captcha-row .form-input {
            flex: 1;
        }

        .captcha-img {
            height: 45px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .form-actions .btn {
            flex: 1;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6c757d;
            text-decoration: none;
        }

        .back-link:hover {
            color: #007bff;
        }
        .btn.loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .btn.loading:after {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-left: 8px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="reset-header">
            <h3><i class="fa fa-key"></i> 重置密码</h3>
            <div class="step-indicator">
                <span class="step completed">1. 输入账号</span>
                <span class="step active">2. 验证手机</span>
                <span class="step">3. 设置密码</span>
            </div>
        </div>

        <p class="phone-hint">
            <i class="fa fa-mobile"></i> 短信验证码已下发到尾号为 <?php echo htmlspecialchars($phoneSuffix); ?> 的手机号
        </p>

        <form id="step2Form">
            <div id="resultArea" style="display: none;"></div>

            <div class="form-group">
                <label for="sms_captcha" class="form-label">
                    <i class="fa fa-shield"></i> 短信验证码
                </label>
                <input type="text" id="sms_captcha" name="sms_captcha" class="form-input" placeholder="请输入短信验证码"
                    required autofocus maxlength="6">
            </div>

            <div class="form-group">
                <label for="captcha" class="form-label">
                    <i class="fa fa-image"></i> 图片验证码
                </label>
                <div class="captcha-row">
                    <input type="text" id="captcha" name="captcha" class="form-input" placeholder="请输入图片验证码" required
                        maxlength="4">
                    <img src="../api/captcha.php" class="captcha-img"
                        onclick="this.src='../api/captcha.php?t='+Date.now()" title="点击刷新">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fa fa-arrow-right"></i> 下一步
                </button>
            </div>
        </form>

        <a href="step1.php" class="back-link">
            <i class="fa fa-arrow-left"></i> 返回上一步
        </a>
    </div>
    <script>
    (function() {
        'use strict';

        var form = document.getElementById('step2Form');
        var resultArea = document.getElementById('resultArea');
        var submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var smsCaptcha = document.getElementById('sms_captcha').value.trim();
            var imgCaptcha = document.getElementById('captcha').value.trim();

            if (!smsCaptcha || !imgCaptcha) {
                showResult(false, '请填写完整信息');
                return;
            }

            submitBtn.classList.add('loading');

            fetch('../api/level1/submit-step2.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sms_captcha: smsCaptcha,
                    captcha: imgCaptcha
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                showResult(data.success, data.message);

                if (data.success && data.next_url) {
                    // 使用API返回的动态URL进行跳转
                    setTimeout(function() {
                        window.location.href = data.next_url;
                    }, 1000);
                } else if (data.redirect_url) {
                    // 处理重定向（如会话过期）
                    setTimeout(function() {
                        window.location.href = data.redirect_url;
                    }, 1500);
                } else if (!data.success) {
                    // 验证失败时自动刷新图片验证码
                    var captchaImg = document.querySelector('.captcha-img');
                    if (captchaImg) {
                        captchaImg.src = '../api/captcha.php?t=' + Date.now();
                    }
                }
            })
            .catch(function(error) {
                showResult(false, '请求失败，请稍后重试');
            })
            .finally(function() {
                submitBtn.classList.remove('loading');
            });
        });

        function showResult(success, message) {
            if (success) {
                resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
            } else {
                resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
            }
            resultArea.style.display = 'block';
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    })();
    </script>
</body>

</html>