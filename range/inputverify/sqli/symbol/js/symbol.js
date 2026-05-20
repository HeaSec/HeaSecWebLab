/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场交互脚本
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    var columnMap = {
        1: { keys: ['id', 'hostname', 'status'], labels: ['编号', '主机名', '状态'] },
        2: { keys: ['id', 'name', 'department'], labels: ['编号', '姓名', '部门'] },
        3: { keys: ['id', 'alert_name', 'severity'], labels: ['编号', '告警名称', '严重程度'] }
    };

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initSymbol = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindQueryForm();
        bindVerifyForm();
    };

    /**
     * 绑定查询表单事件
     */
    function bindQueryForm() {
        var form = document.getElementById('queryForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('.tech-input');
            if (!input) return;

            var value = input.value.trim();
            var label = currentLevel === 2 ? '员工姓名' : 'ID';
            if (!value) {
                showQueryResult(false, '请输入' + label);
                return;
            }

            var btn = document.getElementById('queryBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            var paramName = currentLevel === 2 ? 'name' : 'id';
            formData.append(paramName, value);

            var apiUrl = 'api/process-level' + currentLevel + '.php';

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.results && res.data.results.length > 0) {
                        renderResultTable(res.data.results);
                    } else {
                        showQueryResult(true, res.message);
                    }
                } else {
                    showQueryResult(false, res.message);
                }
            })
            .catch(function () {
                showQueryResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 渲染查询结果表格
     * @param {Array} results - 查询结果数组
     */
    function renderResultTable(results) {
        var resultArea = document.getElementById('queryResultArea');
        if (!resultArea) return;

        var map = columnMap[currentLevel];
        if (!map) return;

        var html = '<table class="heasec-result-table"><thead><tr>';
        for (var h = 0; h < map.labels.length; h++) {
            html += '<th>' + escapeHtml(map.labels[h]) + '</th>';
        }
        html += '</tr></thead><tbody>';

        for (var i = 0; i < results.length; i++) {
            html += '<tr>';
            for (var j = 0; j < map.keys.length; j++) {
                var val = results[i][map.keys[j]];
                html += '<td>' + escapeHtml(val !== null && val !== undefined ? val : '') + '</td>';
            }
            html += '</tr>';
        }

        html += '</tbody></table>';
        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 显示查询结果消息（无表格时）
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     */
    function showQueryResult(success, message) {
        var resultArea = document.getElementById('queryResultArea');
        if (!resultArea) return;

        var html = '';
        if (success) {
            html = '<div class="alert-info"><i class="fa fa-info-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
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
                message: '你掌握了SQL注入中特殊字符过滤的绕过技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'symbol',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了SQL注入中特殊字符过滤的绕过技巧！');
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
                code: 'symbol',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    }

    /**
     * 设置按钮加载状态
     * @param {HTMLElement} btn - 按钮元素
     * @param {boolean} loading - 是否加载中
     */
    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.classList.add('loading');
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中';
        } else {
            btn.classList.remove('loading');
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
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
