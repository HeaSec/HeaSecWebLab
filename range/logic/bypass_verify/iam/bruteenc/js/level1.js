/**
 * HeaSec天积安全团队 - 暴力破解前端加密靶场（第一关）
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 对密码进行SHA256加密
 * @param {string} password - 原始密码
 * @returns {string} SHA256哈希值（十六进制字符串）
 */
function encryptPassword(password) {
    // 使用CryptoJS计算SHA256哈希值
    var sha256Hash = CryptoJS.SHA256(password).toString();
    return sha256Hash;
}

/**
 * 处理登录表单提交
 * @param {Event} e - 表单提交事件
 */
function handleLogin(e) {
    e.preventDefault();

    // 获取表单数据
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value.trim();

    // 验证输入
    if (!username || !password) {
        showResult(false, '请输入用户名和密码');
        return;
    }

    // 对密码进行SHA256哈希加密
    var encryptedPassword = encryptPassword(password);

    // 显示加载状态
    var submitBtn = document.getElementById('submitBtn');
    var originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';

    // 发送AJAX请求
    fetch(window.HeaSecPageConfig.loginApiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            username: username,
            password: encryptedPassword,
            level: window.HeaSecPageConfig.level
        })
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            showResult(true, data.message);
            // 显示下一关按钮
            document.getElementById('nextBtn').style.display = 'inline-flex';
        } else {
            showResult(false, data.message);
        }
    })
    .catch(function(error) {
        showResult(false, '网络错误，请稍后重试');
    })
    .finally(function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

/**
 * 显示登录结果
 * @param {boolean} success - 是否成功
 * @param {string} message - 结果消息
 */
function showResult(success, message) {
    var resultDiv = document.getElementById('loginResult');
    var resultAlert = document.getElementById('resultAlert');
    var resultIcon = document.getElementById('resultIcon');
    var resultMessage = document.getElementById('resultMessage');
    var resultHint = document.getElementById('resultHint');

    // 设置样式
    resultAlert.className = 'alert alert-' + (success ? 'success' : 'error');
    resultIcon.className = 'fa fa-' + (success ? 'check-circle' : 'exclamation-triangle');
    resultMessage.textContent = message;

    // 显示/隐藏提示
    resultHint.style.display = success ? 'none' : 'block';

    // 显示结果区域
    resultDiv.style.display = 'block';
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 绑定表单提交事件
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // 自动聚焦到密码输入框
    var passwordInput = document.getElementById('password');
    if (passwordInput) {
        setTimeout(function() {
            passwordInput.focus();
        }, 300);
    }
});
