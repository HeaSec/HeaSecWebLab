/**
 * HeaSec天积安全团队 - 回显型命令注入靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var apiUrl = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initEchoRCE = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        apiUrl = 'api/ping' + (level === 1 ? '.php' : '-level' + level + '.php');

        bindPingButton();
        bindResetButton();
    };

    /**
     * 绑定诊断按钮事件
     */
    function bindPingButton() {
        var btn = document.getElementById('pingBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var input = document.getElementById('ipInput');
            if (!input) return;

            var ip = input.value.trim();
            if (!ip) {
                showOutput(false, '请输入IP地址');
                return;
            }

            btn.classList.add('loading');
            var originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 诊断中';

            fetch(apiUrl + '?ip=' + encodeURIComponent(ip))
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) {
                        showOutput(true, res.message, res.output, res.detected);
                    } else {
                        showOutput(false, res.message);
                    }
                })
                .catch(function () {
                    showOutput(false, '请求失败，请稍后重试');
                })
                .finally(function () {
                    btn.classList.remove('loading');
                    btn.innerHTML = originalText;
                });
        });

        // 支持回车键提交
        var input = document.getElementById('ipInput');
        if (input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btn.click();
                }
            });
        }
    }

    /**
     * 显示命令执行结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {string} output - 命令输出内容
     * @param {boolean} detected - 是否检测到目标命令执行
     */
    function showOutput(success, message, output, detected) {
        var outputArea = document.getElementById('outputArea');
        if (!outputArea) return;

        var html = '';

        if (success) {
            html += '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
            if (output) {
                html += '<div class="output-area">';
                html += '<h4><i class="fa fa-terminal"></i> 命令执行结果</h4>';
                html += '<div class="terminal-output">' + escapeHtml(output) + '</div>';
                html += '</div>';
            }
        } else {
            html += '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        outputArea.innerHTML = html;
        outputArea.style.display = 'block';

        // 检测到目标命令执行成功时，显示通关状态
        if (detected) {
            showLevelStatus();
        }
    }

    /**
     * 显示通关状态区域
     */
    function showLevelStatus() {
        var statusArea = document.getElementById('levelStatusArea');
        if (!statusArea) return;

        var isLastLevel = (currentLevel === 3);

        var html = '<div class="level-status-area">';
        html += '<div class="status-card">';

        if (isLastLevel) {
            html += '<div class="status-icon"><i class="fa fa-trophy"></i></div>';
            html += '<div class="status-title">恭喜通关！</div>';
            html += '<div class="status-desc">你已掌握回显型命令注入的所有技巧</div>';
            html += '<button type="button" id="congratsBtn" class="tech-btn tech-btn-success" onclick="showCongratsPopup()">';
            html += '<i class="fa fa-gift"></i> 查看成就</button>';
        } else {
            html += '<div class="status-icon"><i class="fa fa-check-circle-o"></i></div>';
            html += '<div class="status-title">检测到命令执行成功！</div>';
            html += '<div class="status-desc">已检测到目标命令的输出特征</div>';
            html += '<a href="' + getNextPage() + '" class="tech-btn tech-btn-success" style="margin-top: 10px;">';
            html += '<i class="fa fa-arrow-right"></i> 下一关</a>';
        }

        html += '</div></div>';

        statusArea.innerHTML = html;
        statusArea.style.display = 'block';

        if (isLastLevel) {
            setTimeout(showCongratsPopup, 500);
        }
    }

    /**
     * 获取下一关页面路径
     * @returns {string}
     */
    function getNextPage() {
        switch (currentLevel) {
            case 1: return 'level2.php';
            case 2: return 'level3.php';
            default: return '#';
        }
    }

    /**
     * 显示恭喜弹窗
     */
    window.showCongratsPopup = function () {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了PHP回显型命令注入漏洞的核心利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'echo_rce',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了回显型命令注入的核心利用技巧！');
        }
    };

    /**
     * 覆盖公共头部重置按钮的行为
     */
    function bindResetButton() {
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
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清除所有关卡通关状态</p>' +
                        '</div>',
                    onConfirm: function () {
                        fetch('api/reset.php', { method: 'POST' })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                if (data.success) {
                                    showNotification('重置成功', 'success');
                                    setTimeout(function () { location.reload(); }, 1500);
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
     * @returns {string}
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
