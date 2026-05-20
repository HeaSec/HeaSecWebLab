/**
 * HeaSec天积安全团队 - 未授权访问靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initNoauth = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        bindLoginForm();
        bindPasscodeForm();
        bindLogoutForm();
    };

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var loginForm = document.getElementById('loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var account = document.getElementById('account').value.trim();
            var password = document.getElementById('password').value.trim();

            if (!account || !password) {
                showLoginResult('error', '请输入账号和密码');
                return;
            }

            var submitBtn = loginForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            var apiPath = getApiPath('login.php');

            fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    account: account,
                    password: password
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.href = data.data.redirect;
                    } else {
                        showLoginResult('error', data.message);
                        if (submitBtn) submitBtn.disabled = false;
                    }
                })
                .catch(function (error) {
                    showLoginResult('error', '请求失败，请稍后重试');
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    /**
     * 显示登录结果
     * @param {string} type - 类型 (success/error)
     * @param {string} message - 消息
     */
    function showLoginResult(type, message) {
        var resultArea = document.getElementById('loginResultArea');
        if (!resultArea) return;

        resultArea.className = 'result-area visible result-' + type;
        resultArea.innerHTML = '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + escapeHtml(message);
    }

    /**
     * 绑定通关密码验证表单
     */
    function bindPasscodeForm() {
        var passcodeForm = document.getElementById('passcodeForm');
        if (!passcodeForm) return;

        passcodeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var passcode = document.getElementById('passcode').value.trim();

            if (!passcode) {
                showPasscodeResult('error', '请输入通关密码');
                return;
            }

            var submitBtn = passcodeForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            var apiPath = getApiPath('verify-passcode.php');

            fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ passcode: passcode })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.passed) {
                        showPasscodeResult('success', data.message);

                        var nextLevelBtn = document.getElementById('nextLevelBtn');
                        if (nextLevelBtn) {
                            nextLevelBtn.style.display = 'inline-flex';
                        }

                        if (currentLevel === 3) {
                            showCongratsModal({
                                title: '恭喜你掌握了一个新技能',
                                message: '你掌握了未授权访问漏洞的实现方式',
                                buttonText: '继续学习',
                                enableNextRangeButton: true,
                                rangeCode: 'noauth',
                                updateLearningStatus: true,
                                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                                learningStatus: '已掌握',
                                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                                showParticles: true,
                                particleCount: 8,
                                animationDuration: 2000
                            });
                        }
                    } else {
                        showPasscodeResult('error', data.message);
                    }
                    if (submitBtn) submitBtn.disabled = false;
                })
                .catch(function (error) {
                    showPasscodeResult('error', '验证失败，请稍后重试');
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    /**
     * 显示通关密码验证结果
     * @param {string} type - 类型
     * @param {string} message - 消息
     */
    function showPasscodeResult(type, message) {
        var resultArea = document.getElementById('passcodeResultArea');
        if (!resultArea) return;

        resultArea.className = 'result-area visible result-' + type;
        resultArea.innerHTML = '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + escapeHtml(message);
    }

    /**
     * 绑定退出登录表单
     */
    function bindLogoutForm() {
        var logoutForm = document.getElementById('logoutForm');
        if (!logoutForm) return;

        logoutForm.addEventListener('submit', function (e) {
            // 允许表单正常提交
        });
    }

    /**
     * 根据关卡获取API路径
     * @param {string} endpoint - API端点
     * @returns {string} API路径
     */
    function getApiPath(endpoint) {
        return 'api/' + endpoint;
    }

    /**
     * HTML转义函数
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return String(text);
        }
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 显示恭喜弹窗
     * @param {object} config - 弹窗配置对象
     */
    function showCongratsModal(config) {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(config);
        }
        // 移除console.error和alert兜底，避免非必要的输出
    }
})();
