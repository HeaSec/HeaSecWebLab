/**
 * HeaSec天积安全团队 - 命令执行实战靶场前端交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */
var HeaSec = HeaSec || {};
HeaSec.RceAdv = (function () {
    'use strict';

    var commandHistory = [];
    var previousAchievedCount = window.HeaSecRceAdvInitCount || 0;

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 开始诊断按钮
        var execBtn = document.getElementById('execBtn');
        if (execBtn) {
            execBtn.addEventListener('click', execCommand);
        }

        // 回车键执行
        var hostInput = document.getElementById('hostInput');
        if (hostInput) {
            hostInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    execCommand();
                }
            });
        }

        // 成就验证按钮
        var verifyBtns = document.querySelectorAll('.verify-btn');
        for (var i = 0; i < verifyBtns.length; i++) {
            verifyBtns[i].addEventListener('click', function () {
                var type = this.getAttribute('data-type');
                if (type === 'reverse_shell') {
                    verifyReverseShell();
                } else {
                    verifyAchievement(type);
                }
            });
        }

        // 重置按钮覆盖
        overrideResetButton();
    }

    /**
     * 执行诊断命令
     */
    function execCommand() {
        var hostInput = document.getElementById('hostInput');
        var host = hostInput ? hostInput.value.trim() : '';
        if (!host) return;

        var execBtn = document.getElementById('execBtn');
        if (execBtn) {
            execBtn.disabled = true;
            execBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 诊断中...';
        }

        fetch('api/exec-cmd.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'host=' + encodeURIComponent(host)
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var outputArea = document.getElementById('outputArea');
            if (!outputArea) return;

            if (data.success) {
                commandHistory.push({
                    time: new Date().toLocaleTimeString(),
                    host: host,
                    output: data.data.output
                });
                if (commandHistory.length > 10) commandHistory.shift();
                renderHistory(outputArea);
            } else {
                outputArea.innerHTML += '<div class="cmd-error">' + escapeHtml(data.message) + '</div>';
            }
            outputArea.scrollTop = outputArea.scrollHeight;
        })
        .catch(function (err) {
            var outputArea = document.getElementById('outputArea');
            if (outputArea) {
                outputArea.innerHTML += '<div class="cmd-error">请求失败：' + escapeHtml(err.message) + '</div>';
            }
        })
        .finally(function () {
            if (execBtn) {
                execBtn.disabled = false;
                execBtn.innerHTML = '<i class="fa fa-play"></i> 开始诊断';
            }
        });
    }

    /**
     * 渲染命令执行历史
     */
    function renderHistory(container) {
        var html = '';
        commandHistory.forEach(function (entry) {
            html += '<div class="cmd-entry">';
            html += '<div class="cmd-header">[' + escapeHtml(entry.time) + '] $ ping ' + escapeHtml(entry.host) + '</div>';
            html += '<div class="cmd-output">' + escapeHtml(entry.output) + '</div>';
            html += '</div>';
        });
        container.innerHTML = html;
    }

    /**
     * 验证反弹shell成就
     */
    function verifyReverseShell() {
        var ip = document.getElementById('shellIp').value.trim();
        var port = document.getElementById('shellPort').value.trim();

        if (!ip || !port) {
            showVerifyResult('reverse_shell', false, '请输入监听IP和端口');
            return;
        }

        var params = 'type=reverse_shell&ip=' + encodeURIComponent(ip) + '&port=' + encodeURIComponent(port);
        verifyWithParams(params, 'reverse_shell');
    }

    /**
     * 验证成就
     */
    function verifyAchievement(type) {
        verifyWithParams('type=' + encodeURIComponent(type), type);
    }

    /**
     * 通用验证请求
     */
    function verifyWithParams(params, type) {
        showVerifyResult(type, null, '验证中...');

        fetch('api/check-achievement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            showVerifyResult(type, data.success, data.message);
            if (data.success) {
                refreshAchievementStatus();
            }
        })
        .catch(function () {
            showVerifyResult(type, false, '请求失败');
        });
    }

    /**
     * 显示验证结果
     */
    function showVerifyResult(type, success, message) {
        var el = document.getElementById('result-' + type);
        if (!el) return;

        el.className = 'verify-result';
        if (success === null) {
            el.textContent = message;
            el.style.color = '#6c757d';
        } else if (success) {
            el.className = 'verify-result success';
            el.textContent = message;
        } else {
            el.className = 'verify-result error';
            el.textContent = message;
        }
    }

    /**
     * 刷新成就状态
     */
    function refreshAchievementStatus() {
        fetch('api/get-status.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    var d = data.data;

                    // 更新各成就验证区域的完成状态
                    updateVerifyStatus('reverse_shell', d.reverse_shell);
                    updateVerifyStatus('create_user', d.create_user);
                    updateVerifyStatus('open_port', d.open_port);

                    // 更新右侧 achievement-card 组件
                    var card = document.querySelector('[data-config]');
                    if (card) {
                        try {
                            var config = JSON.parse(card.getAttribute('data-config'));
                            var newCount = d.achieved_count;

                            // 更新记录列表
                            updateCardRecords(card, d.records);
                            updateCardProgressHint(card, d.progress_hint);

                            if (newCount > previousAchievedCount) {
                                previousAchievedCount = newCount;
                                config.achievedCount = newCount;
                                card.setAttribute('data-config', JSON.stringify(config));

                                document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                                    detail: { starCount: newCount }
                                }));
                            }
                        } catch (e) {
                            // 静默处理
                        }
                    }
                }
            })
            .catch(function () {
                // 静默处理
            });
    }

    /**
     * 更新验证区域状态图标
     */
    function updateVerifyStatus(type, completed) {
        var el = document.getElementById('status-' + type);
        if (!el) return;

        if (completed) {
            el.innerHTML = '<i class="fa fa-check-circle" style="color: #28a745;"></i>';
        } else {
            el.innerHTML = '<i class="fa fa-lock" style="color: #6c757d;"></i>';
        }
    }

    /**
     * 更新卡片记录列表
     */
    function updateCardRecords(card, records) {
        var listEl = card.querySelector('.tech-record-list');
        if (!listEl || !records) return;

        var html = '';
        for (var i = 0; i < records.length; i++) {
            html += '<div class="tech-record-item">';
            html += '<span class="record-name">' + escapeHtml(records[i].name) + '</span>';
            if (records[i].count > 1) {
                html += '<span class="record-count">x' + records[i].count + '</span>';
            }
            html += '</div>';
        }

        if (records.length === 0) {
            html = '<div class="tech-record-empty">暂无成就记录</div>';
        }

        listEl.innerHTML = html;
    }

    /**
     * 更新卡片进度提示
     */
    function updateCardProgressHint(card, hint) {
        var progressEl = card ? card.querySelector('.tech-info-panel .alert-info.mb-2 span') : null;
        if (progressEl && hint) {
            progressEl.textContent = hint;
        }
    }

    /**
     * 覆盖重置按钮行为
     */
    function overrideResetButton() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (resetBtn) {
            var newBtn = resetBtn.cloneNode(true);
            resetBtn.parentNode.replaceChild(newBtn, resetBtn);

            newBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm('确定要重置靶场数据吗？所有成就记录将被清除，并尝试清理系统变更（删除用户、关闭端口等）。')) {
                    fetch('api/reset.php', { method: 'POST' })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('重置失败：' + data.message);
                            }
                        })
                        .catch(function () {
                            alert('重置请求失败');
                        });
                }
            });
        }
    }

    /**
     * HTML 转义
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    return {
        init: function () {
            bindEvents();
            refreshAchievementStatus();
        },
        execCommand: execCommand,
        verifyAchievement: verifyAchievement
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    HeaSec.RceAdv.init();
});
