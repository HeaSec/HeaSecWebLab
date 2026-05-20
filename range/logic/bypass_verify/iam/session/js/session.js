/**
 * HeaSec天积安全团队 - 会话安全靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
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
    window.initSessionRange = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();
    };

    /**
     * 从服务器数据初始化页面
     * @param {object} userData - 用户数据
     */
    window.displayUserInfoFromServer = function (userData) {
        var loginForm = document.getElementById('loginForm');
        var loginErrorArea = document.getElementById('loginErrorArea');
        var logoutMsgArea = document.getElementById('logoutMsgArea');
        if (loginForm) loginForm.style.display = 'none';
        if (loginErrorArea) loginErrorArea.style.display = 'none';
        if (logoutMsgArea) logoutMsgArea.style.display = 'none';

        var userInfoArea = document.getElementById('userInfoArea');
        if (userInfoArea) userInfoArea.style.display = 'block';

        var mainCardTitle = document.getElementById('mainCardTitle');
        if (mainCardTitle) mainCardTitle.textContent = '用户信息';

        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        // 显示用户名
        var displayUsername = document.getElementById('displayUsername');
        if (displayUsername && userData.username) {
            displayUsername.textContent = userData.username;
        }

        // 显示姓名
        var displayRealname = document.getElementById('displayRealname');
        if (displayRealname && userData.realname) {
            displayRealname.textContent = userData.realname;
        }

        // 显示角色（第二关）
        if (currentLevel === 2 && userData.role) {
            var displayRole = document.getElementById('displayRole');
            if (displayRole) {
                displayRole.textContent = userData.role === 'admin' ? '管理员' : '普通用户';
            }
        }

        // 显示通关密码
        if (userData.passcode) {
            var passcodeDisplay = document.getElementById('passcodeDisplay');
            var displayPasscode = document.getElementById('displayPasscode');
            if (passcodeDisplay) passcodeDisplay.style.display = 'flex';
            if (displayPasscode) displayPasscode.textContent = userData.passcode;
        }
    };

    /**
     * 显示退出登录消息
     * @param {string} message 消息内容
     */
    function showLogoutMessage(message) {
        var loginForm = document.getElementById('loginForm');
        var userInfoArea = document.getElementById('userInfoArea');
        var logoutMsgArea = document.getElementById('logoutMsgArea');
        var mainCardTitle = document.getElementById('mainCardTitle');
        var logoutBtn = document.getElementById('logoutBtn');

        if (loginForm) loginForm.style.display = 'none';
        if (userInfoArea) userInfoArea.style.display = 'none';
        if (logoutBtn) logoutBtn.style.display = 'none';

        if (logoutMsgArea) {
            logoutMsgArea.innerHTML = '<i class="fa fa-info-circle"></i> <span>' + escapeHtml(message) + '</span>';
            logoutMsgArea.style.display = 'flex';
        }

        if (mainCardTitle) mainCardTitle.textContent = '用户登录';

        // 显示登录表单
        setTimeout(function () {
            if (loginForm) {
                loginForm.style.display = 'block';
                loginForm.reset();
            }
            if (logoutMsgArea) logoutMsgArea.style.display = 'none';
        }, 1500);
    }

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value.trim();

            if (!username || !password) {
                showLoginError('请输入账号和密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/level' + currentLevel + '/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: username, password: password })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        // 登录成功，获取用户信息
                        fetchProfile();
                    } else {
                        showLoginError(data.message);
                        if (submitBtn) submitBtn.classList.remove('loading');
                    }
                })
                .catch(function () {
                    showLoginError('登录失败，请稍后重试');
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 获取用户信息
     */
    function fetchProfile() {
        fetch('api/level' + currentLevel + '/profile.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    window.displayUserInfoFromServer(data.data);
                }
            })
            .catch(function () {
                // 静默失败
            });
    }

    /**
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            fetch('api/level' + currentLevel + '/logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    // 重置UI
                    var userInfoArea = document.getElementById('userInfoArea');
                    var passcodeDisplay = document.getElementById('passcodeDisplay');
                    var verifyResultArea = document.getElementById('verifyResultArea');
                    var nextLevelBtn = document.getElementById('nextLevelBtn');
                    var logoutBtnEl = document.getElementById('logoutBtn');

                    if (userInfoArea) userInfoArea.style.display = 'none';
                    if (passcodeDisplay) passcodeDisplay.style.display = 'none';
                    if (verifyResultArea) verifyResultArea.style.display = 'none';
                    if (nextLevelBtn) nextLevelBtn.style.display = 'none';
                    if (logoutBtnEl) logoutBtnEl.style.display = 'none';

                    showLogoutMessage(data.message || '您已安全退出登录');
                })
                .catch(function () {
                    showLogoutMessage('您已安全退出登录');
                });
        });
    }

    /**
     * 绑定通关密码验证表单
     */
    function bindVerifyForm() {
        var form = document.getElementById('verifyForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var passcode = document.getElementById('passcode').value.trim();
            if (!passcode) {
                showVerifyResult(false, '请输入通关密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/level' + currentLevel + '/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ passcode: passcode })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showVerifyResult(true, data.message);
                        if (currentLevel === 3) {
                            showCongratsModal();
                        } else {
                            var nextBtn = document.getElementById('nextLevelBtn');
                            if (nextBtn) nextBtn.style.display = 'inline-flex';
                        }
                    } else {
                        showVerifyResult(false, data.message || '通关密码错误');
                    }
                })
                .catch(function () {
                    showVerifyResult(false, '验证失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 显示验证结果
     */
    function showVerifyResult(success, message) {
        var resultArea = document.getElementById('verifyResultArea');
        if (!resultArea) return;

        if (success) {
            resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        resultArea.style.display = 'block';
    }

    /**
     * 显示登录错误
     */
    function showLoginError(message) {
        var errorArea = document.getElementById('loginErrorArea');
        var errorMsg = document.getElementById('loginErrorMsg');
        if (errorArea && errorMsg) {
            errorMsg.textContent = message;
            errorArea.style.display = 'flex';
        }
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了会话安全漏洞的利用方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'session',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了会话安全漏洞的利用方式！');
        }
    }

    /**
     * HTML转义函数
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
