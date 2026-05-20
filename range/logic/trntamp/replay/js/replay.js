/**
 * HeaSec天积安全团队 - 重放攻击靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
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
    window.initReplay = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindLoginForm();
        bindLogoutButton();
        bindSigninButton();
        bindVerifyForm();
        bindCustomReset();
    };

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
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }

            fetch('api/level' + currentLevel + '/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        displayUserInfo(data.data);
                    } else {
                        showLoginError(data.message);
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                        }
                    }
                })
                .catch(function (error) {
                    showLoginError('登录失败，请稍后重试');
                    if (submitBtn) {
                        submitBtn.classList.remove('loading');
                    }
                });
        });
    }

    /**
     * 显示用户信息
     * @param {Object} userData
     */
    function displayUserInfo(userData) {
        // 隐藏登录表单
        var loginForm = document.getElementById('loginForm');
        var loginErrorArea = document.getElementById('loginErrorArea');
        if (loginForm) loginForm.style.display = 'none';
        if (loginErrorArea) loginErrorArea.style.display = 'none';

        // 移除登录按钮的loading状态
        var submitBtn = loginForm ? loginForm.querySelector('button[type="submit"]') : null;
        if (submitBtn) submitBtn.classList.remove('loading');

        // 显示用户信息
        var userInfoArea = document.getElementById('userInfoArea');
        if (userInfoArea) userInfoArea.style.display = 'block';

        // 更新卡片标题
        var mainCardTitle = document.getElementById('mainCardTitle');
        if (mainCardTitle) mainCardTitle.textContent = '用户信息';

        // 更新用户信息显示
        var displayUsername = document.getElementById('displayUsername');
        var displayBalance = document.getElementById('displayBalance');
        if (displayUsername) displayUsername.textContent = userData.username;
        if (displayBalance) displayBalance.textContent = parseFloat(userData.balance).toFixed(2);

        // 检查是否显示通关密码
        var passcodeArea = document.getElementById('passcodeArea');
        var passcodeHint = document.getElementById('passcodeHint');
        if (userData.balance >= 500 && userData.passcode) {
            if (passcodeArea) {
                passcodeArea.style.display = 'flex';
                var displayPasscode = document.getElementById('displayPasscode');
                if (displayPasscode) displayPasscode.textContent = userData.passcode;
            }
            if (passcodeHint) passcodeHint.style.display = 'none';
        } else {
            if (passcodeArea) passcodeArea.style.display = 'none';
            if (passcodeHint) passcodeHint.style.display = 'flex';
        }

        // 更新签到状态
        updateSigninStatus(userData.hasSignedIn);
    }

    /**
     * 更新签到状态
     * @param {boolean} hasSignedIn
     */
    function updateSigninStatus(hasSignedIn) {
        var signinBtn = document.getElementById('signinBtn');
        var signinStatus = document.getElementById('signinStatus');

        if (signinBtn) {
            if (hasSignedIn) {
                signinBtn.disabled = true;
                signinBtn.innerHTML = '<i class="fa fa-check"></i> 已领取';
            } else {
                signinBtn.disabled = false;
                signinBtn.innerHTML = '<i class="fa fa-gift"></i> 签到领红包';
            }
        }

        if (signinStatus) {
            if (hasSignedIn) {
                signinStatus.className = 'signin-status signed';
                signinStatus.innerHTML = '<i class="fa fa-check-circle"></i> 今日已签到';
            } else {
                signinStatus.className = 'signin-status';
                signinStatus.innerHTML = '<i class="fa fa-info-circle"></i> 点击按钮签到';
            }
        }
    }

    /**
     * 显示登录错误
     * @param {string} message
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
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            // 重置UI
            var loginForm = document.getElementById('loginForm');
            var loginErrorArea = document.getElementById('loginErrorArea');
            var userInfoArea = document.getElementById('userInfoArea');
            var verifyResultArea = document.getElementById('verifyResultArea');
            var nextLevelBtn = document.getElementById('nextLevelBtn');
            var mainCardTitle = document.getElementById('mainCardTitle');

            if (loginForm) {
                loginForm.style.display = 'block';
                loginForm.reset();
                // 移除登录按钮的loading状态
                var submitBtn = loginForm.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.classList.remove('loading');
            }
            if (loginErrorArea) loginErrorArea.style.display = 'none';
            if (userInfoArea) userInfoArea.style.display = 'none';
            if (verifyResultArea) verifyResultArea.style.display = 'none';
            if (nextLevelBtn) nextLevelBtn.style.display = 'none';
            if (mainCardTitle) mainCardTitle.textContent = '用户登录';
        });
    }

    /**
     * 绑定签到按钮
     */
    function bindSigninButton() {
        var signinBtn = document.getElementById('signinBtn');
        if (!signinBtn) return;

        signinBtn.addEventListener('click', function () {
            if (signinBtn.disabled) return;

            signinBtn.classList.add('loading');

            fetch('api/level' + currentLevel + '/signin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        // 更新余额显示
                        var displayBalance = document.getElementById('displayBalance');
                        if (displayBalance && data.data) {
                            displayBalance.textContent = parseFloat(data.data.balance).toFixed(2);
                        }

                        // 显示签到成功消息
                        showSigninResult(true, data.message);

                        // 更新签到状态
                        updateSigninStatus(true);

                        // 检查是否达到500元
                        if (data.data && data.data.balance >= 500 && data.data.passcode) {
                            var passcodeArea = document.getElementById('passcodeArea');
                            var passcodeHint = document.getElementById('passcodeHint');
                            var displayPasscode = document.getElementById('displayPasscode');

                            if (passcodeArea) passcodeArea.style.display = 'flex';
                            if (passcodeHint) passcodeHint.style.display = 'none';
                            if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                        }
                    } else {
                        showSigninResult(false, data.message);
                    }
                })
                .catch(function (error) {
                    showSigninResult(false, '签到失败，请稍后重试');
                })
                .finally(function () {
                    signinBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 显示签到结果
     * @param {boolean} success
     * @param {string} message
     */
    function showSigninResult(success, message) {
        var signinStatus = document.getElementById('signinStatus');
        if (!signinStatus) return;

        if (success) {
            signinStatus.className = 'signin-status signed';
            signinStatus.innerHTML = '<i class="fa fa-check-circle"></i> ' + escapeHtml(message);
        } else {
            signinStatus.className = 'signin-status';
            signinStatus.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + escapeHtml(message);
        }
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
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }

            fetch('api/level' + currentLevel + '/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    passcode: passcode
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && (data.data ? data.data.passed : data.passed)) {
                        showVerifyResult(true, data.message);
                        // 显示下一关按钮或恭喜弹窗
                        if (currentLevel === 3) {
                            showCongratsModal();
                        } else {
                            var nextBtn = document.getElementById('nextLevelBtn');
                            if (nextBtn) {
                                nextBtn.style.display = 'inline-flex';
                            }
                        }
                    } else {
                        showVerifyResult(false, data.message || '通关密码错误');
                    }
                })
                .catch(function (error) {
                    showVerifyResult(false, '验证失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.classList.remove('loading');
                    }
                });
        });
    }

    /**
     * 显示验证结果
     * @param {boolean} success
     * @param {string} message
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
     * 绑定自定义重置功能
     */
    function bindCustomReset() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        // 移除原有的事件监听器（通过克隆节点）
        var newResetBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newResetBtn, resetBtn);

        // 添加新的事件监听器
        newResetBtn.addEventListener('click', function () {
            if (!confirm('确定要重置靶场吗？这将清除所有数据并重新开始。')) {
                return;
            }

            newResetBtn.classList.add('loading');

            fetch('api/reset.php', {
                method: 'POST'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        alert('重置成功，页面即将刷新');
                        location.reload();
                    } else {
                        alert('重置失败: ' + data.message);
                        newResetBtn.classList.remove('loading');
                    }
                })
                .catch(function (error) {
                    alert('重置失败，请稍后重试');
                    newResetBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了重放攻击的实现方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'replay',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了重放攻击的实现方式！');
        }
    }

    /**
     * HTML转义函数，防止XSS
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
