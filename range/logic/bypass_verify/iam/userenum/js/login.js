/**
 * HeaSec天积安全团队 - 用户枚举靶场
 * 登录表单提交处理（三关共用）
 * 版本: v1.0.0
 * 创建日期: 2026-02-27
 * 团队: 天积安全 (HeavenlySecret)
 */
document.addEventListener('DOMContentLoaded', function() {
    var loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value.trim();
        var level = window.HeaSecPageConfig.level;

        if (!username || !password) {
            showResult(false, '请输入用户名和密码');
            return;
        }

        // 显示加载状态
        var submitBtn = document.getElementById('submitBtn');
        var originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';

        // 发送AJAX请求（密码明文提交，不做加密处理）
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.HeaSecPageConfig.loginApiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;

                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // 登录成功
                        showResult(true, response.message);
                        // 只有非测试账号才显示下一关按钮和恭喜弹窗
                        if (!response.isTestAccount) {
                            document.getElementById('nextBtn').style.display = '';
                            // 第三关显示恭喜弹窗
                            if (response.showCongrats && window.HeaSecPageConfig.congratsConfig && typeof HeaSecCongratsModal !== 'undefined') {
                                HeaSecCongratsModal.show(window.HeaSecPageConfig.congratsConfig);
                            }
                        }
                    } else {
                        // 登录失败，直接显示后端返回的message
                        showResult(false, response.message);
                    }
                } else {
                    showResult(false, '网络错误，请稍后重试');
                }
            }
        };

        xhr.send(JSON.stringify({
            username: username,
            password: password,
            level: level
        }));
    });

    /**
     * 显示登录结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 结果消息
     */
    function showResult(success, message) {
        var resultDiv = document.getElementById('loginResult');
        var alertDiv = document.getElementById('resultAlert');
        var iconEl = document.getElementById('resultIcon');
        var messageEl = document.getElementById('resultMessage');
        var hintEl = document.getElementById('resultHint');

        resultDiv.style.display = 'block';

        if (success) {
            alertDiv.className = 'alert alert-success';
            iconEl.className = 'fa fa-check-circle';
            hintEl.style.display = 'none';
        } else {
            alertDiv.className = 'alert alert-danger';
            iconEl.className = 'fa fa-times-circle';
            hintEl.style.display = 'block';
        }

        messageEl.textContent = message;
    }
});
