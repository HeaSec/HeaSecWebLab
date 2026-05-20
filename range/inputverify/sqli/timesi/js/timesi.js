/**
 * HeaSec天积安全团队 - 时间盲注靶场前端交互脚本
 * 版本: v2.0.0
 * 创建日期: 2026-04-20
 * 更新日期: 2026-04-26 - 双维度成就面板更新
 * 团队: 天积安全 (HeavenlySecret)
 */
var HeaSec = HeaSec || {};
HeaSec.Timesi = (function () {
    'use strict';

    var queryHistory = [];

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 查询按钮点击
        var queryBtn = document.getElementById('queryBtn');
        if (queryBtn) {
            queryBtn.addEventListener('click', function () {
                var input = document.getElementById('queryInput');
                if (input && input.value.trim()) {
                    executeQuery(input.value.trim());
                }
            });
        }

        // 回车键查询
        var queryInput = document.getElementById('queryInput');
        if (queryInput) {
            queryInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var val = this.value.trim();
                    if (val) {
                        executeQuery(val);
                    }
                }
            });
        }

        // 查询历史折叠/展开
        var historyToggle = document.getElementById('historyToggle');
        if (historyToggle) {
            historyToggle.addEventListener('click', function () {
                var body = document.getElementById('queryHistory');
                if (body) {
                    var isVisible = body.style.display !== 'none';
                    body.style.display = isVisible ? 'none' : 'block';
                    this.classList.toggle('expanded', !isVisible);
                }
            });
        }

        // 重置按钮覆盖
        overrideResetButton();
    }

    /**
     * 执行查询
     * @param {string} id 服务ID或注入payload
     */
    function executeQuery(id) {
        if (!id) return;

        var resultArea = document.getElementById('resultArea');
        if (resultArea) {
            resultArea.innerHTML = '<div style="text-align: center; color: #adb5bd; padding: 20px;"><i class="fa fa-spinner fa-spin"></i><p>查询中...</p></div>';
        }

        fetch('api/query.php?id=' + encodeURIComponent(id))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                showResult(data);
                addHistory(id, data);
                refreshAchievementStatus();
            })
            .catch(function (err) {
                showResult({ success: false, message: '请求失败：' + err.message });
            });
    }

    /**
     * 展示查询结果（含响应时间）
     * @param {object} data 响应数据
     */
    function showResult(data) {
        var resultArea = document.getElementById('resultArea');
        if (!resultArea) return;

        var timeStr = '';
        if (data.data && data.data.execution_time !== undefined) {
            timeStr = '（耗时：' + data.data.execution_time + '秒）';
        }

        if (data.success) {
            resultArea.innerHTML = '<div class="alert alert-success">' +
                '<i class="fa fa-check-circle"></i> ' + escapeHtml(data.message) + timeStr + '</div>';
        } else if (data.message === '输入包含非法字符' || data.message === '输入包含非法关键字' || data.message === '非法操作') {
            resultArea.innerHTML = '<div class="alert alert-warning">' +
                '<i class="fa fa-shield"></i> ' + escapeHtml(data.message) + timeStr + '</div>';
        } else {
            resultArea.innerHTML = '<div class="alert alert-danger">' +
                '<i class="fa fa-times-circle"></i> ' + escapeHtml(data.message) + timeStr + '</div>';
        }
    }

    /**
     * 添加查询历史记录（含响应时间）
     * @param {string} id   查询参数
     * @param {object} data 响应数据
     */
    function addHistory(id, data) {
        var status = 'error';
        if (data.success) {
            status = 'success';
        } else if (data.message === '输入包含非法字符' || data.message === '输入包含非法关键字' || data.message === '非法操作') {
            status = 'blocked';
        }

        queryHistory.unshift({
            id: id,
            timestamp: new Date().toLocaleTimeString(),
            status: status,
            message: data.message ? data.message.substring(0, 200) : '',
            time: (data.data && data.data.execution_time) ? data.data.execution_time : 0
        });

        if (queryHistory.length > 10) {
            queryHistory.pop();
        }

        renderHistory();
    }

    /**
     * 渲染查询历史列表
     */
    function renderHistory() {
        var container = document.getElementById('queryHistory');
        if (!container) return;

        if (queryHistory.length === 0) {
            container.innerHTML = '<p class="history-empty">暂无查询记录</p>';
            return;
        }

        var html = '';
        for (var i = 0; i < queryHistory.length; i++) {
            var item = queryHistory[i];
            var timeDisplay = item.time ? ' | ' + item.time + 's' : '';
            html += '<div class="history-item">';
            html += '<span class="history-time">' + escapeHtml(item.timestamp) + '</span>';
            html += '<span class="history-id" title="' + escapeHtml(item.id) + '">' + escapeHtml(item.id) + '</span>';
            html += '<span class="history-status ' + item.status + '">' + item.status + timeDisplay + '</span>';
            html += '</div>';
        }
        container.innerHTML = html;
    }

    /**
     * 刷新成就状态（双维度）
     */
    function refreshAchievementStatus() {
        fetch('api/get-status.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    var card = document.querySelector('[data-config]');
                    if (!card) return;

                    // 比较星星数量，触发解锁事件
                    try {
                        var config = JSON.parse(card.getAttribute('data-config'));
                        var previousCount = config.achievedCount || 0;
                        var newCount = data.data.star_count || 0;

                        if (newCount > previousCount) {
                            config.achievedCount = newCount;
                            card.setAttribute('data-config', JSON.stringify(config));

                            document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                                detail: { starCount: newCount }
                            }));
                        }
                    } catch (e) {
                        // 静默处理
                    }

                    // 更新两个维度的记录面板
                    var recordPanels = card.querySelectorAll('.tech-info-panel');
                    for (var i = 0; i < recordPanels.length; i++) {
                        var h4 = recordPanels[i].querySelector('h4');
                        if (!h4) continue;
                        var text = h4.textContent.trim();

                        if (text.indexOf('已掌握的延迟技术') !== -1) {
                            updateRecordPanel(recordPanels[i], data.data.delay_records || [], data.data.delay_hint || '');
                        } else if (text.indexOf('已使用的字符串函数') !== -1) {
                            updateRecordPanel(recordPanels[i], data.data.string_records || [], data.data.string_hint || '');
                        }
                    }
                }
            })
            .catch(function () {
                // 静默处理
            });
    }

    /**
     * 更新单个记录面板
     * @param {HTMLElement} panel   面板DOM元素
     * @param {Array}       records 记录列表
     * @param {string}      hint    提示文字
     */
    function updateRecordPanel(panel, records, hint) {
        // 更新提示信息
        var hintEl = panel.querySelector('.alert-info span');
        if (hintEl && hint) {
            hintEl.textContent = hint;
        }

        // 更新记录列表
        var grid = panel.querySelector('.info-grid');
        if (!grid) return;

        // 保存表头行（info-grid 的第一个 info-item 子元素）
        var headerRow = grid.querySelector('.info-item:first-child');

        // 只保留表头，清除其余内容
        grid.innerHTML = '';
        if (headerRow) {
            grid.appendChild(headerRow);
        }

        if (!records || records.length === 0) {
            var emptyDiv = document.createElement('div');
            emptyDiv.className = 'info-item';
            emptyDiv.innerHTML = '<span class="info-label">暂无记录</span><span class="info-value"></span>';
            grid.appendChild(emptyDiv);
        } else {
            records.forEach(function (record) {
                var div = document.createElement('div');
                div.className = 'info-item';
                div.innerHTML = '<span class="info-label" style="font-size: 13px;">' +
                    escapeHtml(record.name) + '：</span>' +
                    '<span class="info-value"><span class="badge badge-success" style="font-size: 11px;">' +
                    record.count + '</span></span>';
                grid.appendChild(div);
            });
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
                if (confirm('确定要重置靶场数据吗？所有成就记录将被清除。')) {
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
     * HTML转义
     * @param {string} text 原始文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    return {
        init: function () {
            bindEvents();
            // 默认加载ID=1的查询结果
            executeQuery('1');
        },
        executeQuery: executeQuery
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    HeaSec.Timesi.init();
});
