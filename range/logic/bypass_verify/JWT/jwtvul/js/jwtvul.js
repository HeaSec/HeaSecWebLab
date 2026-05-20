/**
 * HeaSec天积安全团队 - JWT基础漏洞靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// ==================== Base64URL编解码函数 ====================

/**
 * Base64URL编码（支持Unicode字符）
 * @param {string} str - 要编码的字符串
 * @returns {string} Base64URL编码结果
 */
function base64UrlEncode(str) {
    return btoa(unescape(encodeURIComponent(str)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

/**
 * Base64URL解码（支持Unicode字符）
 * @param {string} str - Base64URL编码的字符串
 * @returns {string} 解码结果
 */
function base64UrlDecode(str) {
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    while (str.length % 4) {
        str += '=';
    }
    return decodeURIComponent(escape(atob(str)));
}

// ==================== 靶场交互逻辑 ====================

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var currentToken = null;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initJwtVul = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        setMainCardTitle('用户登录');
        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();
        bindCustomReset();

        // 检查是否有保存的Token
        checkExistingToken();
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

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    level: currentLevel
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        // 保存Token到localStorage
                        currentToken = data.token;
                        localStorage.setItem('jwtvul_token_' + currentLevel, data.token);

                        // 获取用户信息
                        fetchProfile(data.token);
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
     * 获取用户信息
     * @param {string} token
     */
    function fetchProfile(token) {
        fetch('api/profile.php?level=' + currentLevel, {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayUserInfo(data.data);
                } else {
                    showLoginError('获取用户信息失败');
                }
            })
            .catch(function (error) {
                showLoginError('获取用户信息失败');
            })
            .finally(function () {
                var submitBtn = document.querySelector('#loginForm button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 设置主卡片标题
     * @param {string} title
     */
    function setMainCardTitle(title) {
        var titleElement = document.getElementById('mainCardTitle');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }

    /**
     * 显示用户信息
     * @param {Object} userData
     */
    function displayUserInfo(userData) {
        // 隐藏登录表单
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('loginErrorArea').style.display = 'none';

        // 显示用户信息
        document.getElementById('userInfoArea').style.display = 'block';
        setMainCardTitle('用户信息');
        document.getElementById('displayUsername').textContent = userData.username;
        document.getElementById('displayRole').textContent = userData.role === 'admin' ? '管理员' : '普通用户';

        if (userData.role === 'admin' && userData.passcode) {
            // 显示通关密码
            document.getElementById('passcodeArea').style.display = 'flex';
            document.getElementById('displayPasscode').textContent = userData.passcode;
            document.getElementById('userHintArea').style.display = 'none';
        } else {
            // 显示提示信息
            document.getElementById('passcodeArea').style.display = 'none';
            document.getElementById('userHintArea').style.display = 'flex';
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
            // 清除Token
            currentToken = null;
            localStorage.removeItem('jwtvul_token_' + currentLevel);

            // 重置UI
            document.getElementById('loginForm').style.display = 'block';
            setMainCardTitle('用户登录');
            document.getElementById('loginForm').reset();
            document.getElementById('loginErrorArea').style.display = 'none';
            document.getElementById('userInfoArea').style.display = 'none';
            document.getElementById('verifyResultArea').style.display = 'none';
            var nextLevelBtn = document.getElementById('nextLevelBtn');
            if (nextLevelBtn) {
                nextLevelBtn.style.display = 'none';
            }
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
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }

            fetch('api/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    passcode: passcode,
                    level: currentLevel
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
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
                        showVerifyResult(false, data.message);
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
     * 检查是否有保存的Token
     */
    function checkExistingToken() {
        var savedToken = localStorage.getItem('jwtvul_token_' + currentLevel);
        if (savedToken) {
            currentToken = savedToken;
            fetchProfile(savedToken);
        }
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
            // 使用模态框确认
            if (window.heasecModalManager) {
                window.heasecModalManager.showResetConfirm({
                    url: 'api/reset.php',
                    onConfirm: function (modal) {
                        var confirmBtn = modal.querySelector('.modal-confirm');
                        var originalText = confirmBtn.innerHTML;
                        confirmBtn.disabled = true;
                        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 重置中...';

                        fetch('api/reset.php', {
                            method: 'POST'
                        })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                window.heasecModalManager.hideModal(modal);
                                if (data.success) {
                                    // 清除所有Token
                                    localStorage.removeItem('jwtvul_token_1');
                                    localStorage.removeItem('jwtvul_token_2');
                                    localStorage.removeItem('jwtvul_token_3');

                                    window.heasecModalManager.showSuccessMessage('重置成功，页面即将刷新', {
                                        onConfirm: function () {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    window.heasecModalManager.showModal('success_message', {
                                        title: '重置失败',
                                        content: '<div class="text-center"><i class="fa fa-times-circle" style="font-size: 48px; color: #dc3545; margin: 20px 0;"></i><p style="margin: 0; font-size: 16px; color: #333;">' + (data.message || '操作失败') + '</p></div>'
                                    });
                                    newResetBtn.classList.remove('loading');
                                }
                            })
                            .catch(function (error) {
                                window.heasecModalManager.hideModal(modal);
                                window.heasecModalManager.showModal('success_message', {
                                    title: '重置失败',
                                    content: '<div class="text-center"><i class="fa fa-times-circle" style="font-size: 48px; color: #dc3545; margin: 20px 0;"></i><p style="margin: 0; font-size: 16px; color: #333;">网络错误，请稍后重试</p></div>'
                                });
                                newResetBtn.classList.remove('loading');
                            })
                            .finally(function () {
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = originalText;
                            });

                        return false; // 不自动关闭模态框
                    }
                });
            } else {
                // 降级处理：使用原生 confirm
                if (!confirm('确定要重置靶场吗？这将清除所有数据并重新生成密钥。')) {
                    return;
                }

                newResetBtn.classList.add('loading');

                fetch('api/reset.php', {
                    method: 'POST'
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            localStorage.removeItem('jwtvul_token_1');
                            localStorage.removeItem('jwtvul_token_2');
                            localStorage.removeItem('jwtvul_token_3');
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
            }
        });
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '🎉 恭喜你掌握了一个新技能',
                message: '你掌握了JWT基础漏洞的实现方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'jwtvul',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('🎉 恭喜你掌握了一个新技能\n\n你掌握了JWT基础漏洞的实现方式！');
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
