/**
 * HeaSec天积安全团队 - 垂直越权基础靶场交互脚本
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
    window.initVertbp = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        bindLoginForm();
        bindPasscodeForm();

        // 如果是配置页面，加载配置数据
        if (document.getElementById('configContainer')) {
            loadConfigData();
        }
    };

    /**
     * 重新绑定重置按钮事件，修复URL构建问题
     */
    window.bindVertbpResetButton = function () {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        var newResetBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newResetBtn, resetBtn);
        newResetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.heasecModalManager) {
                window.heasecModalManager.showResetConfirm({
                    action: 'reset',
                    url: window.location.pathname + '?action=reset',
                    onSuccess: function () {
                        setTimeout(function () { location.reload(); }, 1500);
                    },
                    onError: function () {
                    }
                });
            }
        });
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

            // 根据关卡获取API路径
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
                        // 登录成功，跳转到管理页面
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

                        // 显示下一关按钮
                        var nextLevelBtn = document.getElementById('nextLevelBtn');
                        if (nextLevelBtn) {
                            nextLevelBtn.style.display = 'inline-flex';
                        }

                        // 如果是第三关，显示恭喜弹窗
                        if (currentLevel === 3) {
                            showCongratsModal({
                                title: '恭喜你掌握了一个新技能',
                                message: '你掌握了垂直越权攻击的实现方式',
                                buttonText: '继续学习',
                                enableNextRangeButton: true,
                                rangeCode: 'vertbp',
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
     * 加载配置数据（edit.php页面使用）
     */
    function loadConfigData() {
        var loadingEl = document.getElementById('configLoading');
        var displayEl = document.getElementById('configDisplay');

        var apiPath = getApiPath('config.php');

        fetch(apiPath, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (loadingEl) loadingEl.style.display = 'none';

                if (data.success && data.data) {
                    displayConfigData(data.data);
                    if (displayEl) displayEl.style.display = 'block';
                } else {
                    if (displayEl) {
                        displayEl.innerHTML = '<div class="result-area visible result-error"><i class="fa fa-exclamation-triangle"></i> ' + escapeHtml(data.message || '加载配置失败') + '</div>';
                        displayEl.style.display = 'block';
                    }
                }
            })
            .catch(function (error) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (displayEl) {
                    displayEl.innerHTML = '<div class="result-area visible result-error"><i class="fa fa-exclamation-triangle"></i> 请求失败，请稍后重试</div>';
                    displayEl.style.display = 'block';
                }
            });
    }

    /**
     * 显示配置数据
     * @param {object} config - 配置数据
     */
    function displayConfigData(config) {
        var displayEl = document.getElementById('configDisplay');
        if (!displayEl) return;

        var html = '';

        if (config.device_name) {
            html += '<div class="config-item"><span class="config-label">设备名称</span><span class="config-value">' + escapeHtml(config.device_name) + '</span></div>';
        }
        if (config.firmware_version) {
            html += '<div class="config-item"><span class="config-label">固件版本</span><span class="config-value">' + escapeHtml(config.firmware_version) + '</span></div>';
        }
        if (config.mac_address) {
            html += '<div class="config-item"><span class="config-label">MAC地址</span><span class="config-value">' + escapeHtml(config.mac_address) + '</span></div>';
        }
        if (config.uptime) {
            html += '<div class="config-item"><span class="config-label">运行时间</span><span class="config-value">' + escapeHtml(config.uptime) + '</span></div>';
        }
        if (config.online_devices !== undefined) {
            html += '<div class="config-item"><span class="config-label">在线设备数</span><span class="config-value">' + escapeHtml(config.online_devices) + '</span></div>';
        }
        if (config.wan_status) {
            html += '<div class="config-item"><span class="config-label">WAN状态</span><span class="config-value">' + escapeHtml(config.wan_status) + '</span></div>';
        }
        if (config.lan_status) {
            html += '<div class="config-item"><span class="config-label">LAN状态</span><span class="config-value">' + escapeHtml(config.lan_status) + '</span></div>';
        }

        // 通关密码
        if (config.passcode) {
            html += '<div class="config-item highlight"><span class="config-label"><i class="fa fa-key"></i> 通关密码</span><span class="config-value">' + escapeHtml(config.passcode) + '</span></div>';
        }

        displayEl.innerHTML = html;
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
        } else {
            alert(config.message || '恭喜通关！');
        }
    }
})();
