/**
 * HeaSec天积安全团队 - 无回显命令注入靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initBlindRce = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindPingTest();
        bindCheckFileButton();
        bindVerifyForm();
        overrideResetButton();
    };

    /**
     * 绑定ping测试按钮事件
     */
    function bindPingTest() {
        var pingBtn = document.getElementById('pingBtn');
        if (!pingBtn) return;

        pingBtn.addEventListener('click', function () {
            executePing();
        });

        // 支持回车键提交
        var ipInput = document.getElementById('ipInput');
        if (ipInput) {
            ipInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    executePing();
                }
            });
        }
    }

    /**
     * 执行ping测试
     */
    function executePing() {
        var ipInput = document.getElementById('ipInput');
        if (!ipInput || ipInput.value.trim() === '') {
            showPingResult('error', '请输入IP地址或域名');
            return;
        }

        var ip = ipInput.value.trim();
        var resultArea = document.getElementById('pingResultArea');
        if (resultArea) {
            resultArea.innerHTML = '<div class="doc-loading"><i class="fa fa-spinner fa-spin"></i> 正在检测...</div>';
            resultArea.style.display = 'block';
        }

        fetch('api/process-level' + currentLevel + '.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ip=' + encodeURIComponent(ip),
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data.success) {
                showPingResult('success', data.message, data.ip);
            } else {
                showPingResult('error', data.message);
            }
        }).catch(function (err) {
            showPingResult('error', '请求失败，请稍后重试');
        });
    }

    /**
     * 显示ping测试结果
     * @param {string} type - 结果类型：success/error
     * @param {string} message - 消息文本
     * @param {string} [ip] - IP地址（可选）
     */
    function showPingResult(type, message, ip) {
        var resultArea = document.getElementById('pingResultArea');
        if (!resultArea) return;

        var html = '';
        if (type === 'success') {
            var icon = message === '目标可达' ? 'fa-check-circle' : 'fa-times-circle';
            var colorClass = message === '目标可达' ? 'text-success' : 'text-danger';
            html = '<div class="ping-result ' + colorClass + '">';
            html += '<i class="fa ' + icon + '"></i> ';
            if (ip) html += '<strong>' + escapeHtml(ip) + '</strong> — ';
            html += message;
            html += '</div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i> ' + escapeHtml(message) + '</div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 绑定文件检测按钮事件（第二关/第三关）
     */
    function bindCheckFileButton() {
        var checkBtn = document.getElementById('checkFileBtn');
        if (!checkBtn) return;

        checkBtn.addEventListener('click', function () {
            checkBtn.classList.add('loading');
            var originalText = checkBtn.innerHTML;
            checkBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 检测中';

            fetch('api/check-file.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'level=' + currentLevel,
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (data.success && data.passcode) {
                    showCheckResult(true, data.message, data.passcode);
                } else {
                    showCheckResult(false, data.message, null);
                }
            }).catch(function () {
                showCheckResult(false, '检测失败，请稍后重试', null);
            }).finally(function () {
                checkBtn.classList.remove('loading');
                checkBtn.innerHTML = originalText;
            });
        });
    }

    /**
     * 显示文件检测结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {string|null} passcode - 通关密码（可选）
     */
    function showCheckResult(success, message, passcode) {
        var resultArea = document.getElementById('checkResultArea');
        var passcodeArea = document.getElementById('passcodeArea');

        if (!resultArea) return;

        if (success) {
            resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
            resultArea.style.display = 'block';

            if (passcodeArea && passcode) {
                passcodeArea.innerHTML =
                    '<div class="passcode-display">' +
                    '<div class="passcode-label"><i class="fa fa-key"></i> 通关密码</div>' +
                    '<div class="passcode-value">' + escapeHtml(passcode) + '</div>' +
                    '<div class="passcode-hint">请将此密码复制到下方的通关验证区域提交</div>' +
                    '</div>';
                passcodeArea.style.display = 'block';
            }
        } else {
            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
            resultArea.style.display = 'block';
        }
    }

    /**
     * 绑定通关验证表单
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

            fetch('api/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    level: currentLevel,
                    passcode: passcode
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showVerifyResult(true, data.message);
                    if (currentLevel === 3) {
                        showCongratsModal();
                    } else {
                        updateLearningStatus('学习中');
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
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
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
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了无回显命令注入漏洞的利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'blind_rce',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了无回显命令注入漏洞的利用技巧！');
        }
    }

    /**
     * 更新学习进度状态
     * @param {string} status - 学习状态值
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'blind_rce',
                status: status
            })
        })
        .then(function (res) { return res.json(); })
        .then(function () {})
        .catch(function () {});
    }

    /**
     * 覆盖公共头部重置按钮的行为
     */
    function overrideResetButton() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        var newBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newBtn, resetBtn);

        newBtn.addEventListener('click', function () {
            if (window.heasecModalManager) {
                window.heasecModalManager.showModal('reset_confirm', {
                    content: '<div class="text-center">' +
                        '<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin: 20px 0;"></i>' +
                        '<p style="margin: 0; font-size: 16px; color: #333;">确定要重置靶场数据吗？</p>' +
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码和配置文件，恢复初始状态</p>' +
                        '</div>',
                    onConfirm: function () {
                        fetch('api/reset.php', {
                            method: 'POST'
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.success) {
                                showNotification('重置成功', 'success');
                                setTimeout(function () {
                                    location.reload();
                                }, 1500);
                            } else {
                                showNotification(data.message || '重置失败', 'error');
                            }
                        })
                        .catch(function () {
                            showNotification('重置失败，请稍后重试', 'error');
                        });
                    }
                });
            } else {
                if (confirm('确定要重置靶场数据吗？')) {
                    fetch('api/reset.php', { method: 'POST' })
                    .then(function () { location.reload(); });
                }
            }
        });
    }

    /**
     * 显示通知
     * @param {string} message - 通知消息
     * @param {string} type - 通知类型
     */
    function showNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * HTML转义函数
     * @param {string} text - 原始文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
