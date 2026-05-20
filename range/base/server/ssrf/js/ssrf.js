/**
 * HeaSec天积安全团队 - SSRF漏洞靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var config = {
        currentStep: 1
    };

    var requestHistory = [];
    var MAX_HISTORY = 5;

    /**
     * 初始化靶场主页
     */
    window.initSsrfRange = function (options) {
        config = Object.assign(config, options || {});

        bindFetchForm();
        bindHistoryToggle();
        loadStepHints();
        bindSecretCardSuccess();
    };

    /**
     * 监听秘密验证成功事件，更新进度为4/4
     */
    function bindSecretCardSuccess() {
        document.addEventListener('heasec:secretcard:success', function () {
            config.currentStep = 5;
            updateStepDisplay(['进度：4/4 - 已完成全部挑战', '恭喜你完成了所有挑战！']);
        });
    }

    /**
     * 初始化端口扫描页面
     */
    window.initPortScan = function (options) {
        config = Object.assign(config, options || {});

        loadPortResults();
        // 定时轮询端口结果
        setInterval(function () {
            loadPortResults();
        }, 3000);
    };

    /**
     * 绑定URL提交表单
     */
    function bindFetchForm() {
        var form = document.getElementById('ssrfFetchForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var urlInput = document.getElementById('ssrfUrl');
            var url = urlInput.value.trim();

            if (!url) {
                showResult('error', '请输入URL');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 请求中...';
            }

            showLoading();

            fetch('api/fetch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: url })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        // 图片类型：正常展示识图结果
                        displayResult(data);
                        addToHistory(url, data.type);
                    } else if (data.raw_response) {
                        // 非图片类型：展示"AI识别失败" + 原始响应
                        displayRecognitionFailure(data);
                        addToHistory(url, data.type);
                    } else {
                        // 其他错误（如空URL、请求异常等）
                        showResult('error', data.message || '请求失败');
                    }

                    // 更新步骤状态（无论成功与否都要更新）
                    if (data.current_step && data.current_step !== config.currentStep) {
                        config.currentStep = data.current_step;
                        updateStepDisplay(data.step_hint);
                    }
                })
                .catch(function (err) {
                    showResult('error', '请求失败，请检查URL格式');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-search"></i> 识别图片';
                    }
                });
        });
    }

    /**
     * 显示加载状态
     */
    function showLoading() {
        var area = document.getElementById('ssrfResultArea');
        if (!area) return;

        area.innerHTML = '<div class="loading-spinner">' +
            '<i class="fa fa-spinner"></i>' +
            '<p>正在获取...</p>' +
            '</div>';
    }

    /**
     * 显示结果
     */
    function showResult(type, message) {
        var area = document.getElementById('ssrfResultArea');
        if (!area) return;

        if (type === 'error') {
            area.innerHTML = '<div class="alert alert-error">' +
                '<div><i class="fa fa-exclamation-circle"></i><strong>请求失败</strong></div>' +
                '<p class="alert-hint"><small>' + escapeHtml(message) + '</small></p>' +
                '</div>';
        }
    }

    /**
     * 显示AI识别失败结果（非图片响应）
     * 模拟真实识图功能：返回错误提示 + 可展开的原始响应内容
     */
    function displayRecognitionFailure(data) {
        var area = document.getElementById('ssrfResultArea');
        if (!area) return;

        var html = '<div class="result-content">';

        // 识别失败错误卡片
        html += '<div class="recognition-failure">';
        html += '<div class="failure-header">';
        html += '<i class="fa fa-times-circle"></i>';
        html += '<span><strong>AI识别失败</strong></span>';
        html += '</div>';
        html += '<p class="failure-message">' + escapeHtml(data.message) + '</p>';

        // 可展开的原始响应区域
        html += '<div class="raw-response-toggle" onclick="toggleRawResponse(this)">';
        html += '<i class="fa fa-chevron-right"></i>';
        html += '<span>查看原始响应</span>';
        html += '</div>';
        html += '<div class="raw-response-body" style="display:none;">';
        html += '<pre>' + escapeHtml(data.raw_response) + '</pre>';
        html += '</div>';
        html += '</div>';

        // 结果头部（显示协议类型标签）
        html += '<div class="result-header">';
        html += '<span class="result-type type-' + escapeHtml(data.type) + '">' + escapeHtml(data.type) + '</span>';
        html += '</div>';

        html += '</div>';
        area.innerHTML = html;
    }

    /**
     * 切换原始响应区域的展开/折叠
     */
    window.toggleRawResponse = function (toggleEl) {
        var body = toggleEl.nextElementSibling;
        var icon = toggleEl.querySelector('.fa');

        if (body.style.display === 'none') {
            body.style.display = 'block';
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
            toggleEl.querySelector('span').textContent = '收起原始响应';
        } else {
            body.style.display = 'none';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
            toggleEl.querySelector('span').textContent = '查看原始响应';
        }
    };

    /**
     * 显示请求结果
     */
    function displayResult(data) {
        var area = document.getElementById('ssrfResultArea');
        if (!area) return;

        var html = '<div class="result-content">';

        // 结果头部
        html += '<div class="result-header">';
        html += '<span class="result-type type-' + escapeHtml(data.type) + '">' + escapeHtml(data.type) + '</span>';

        if (data.type === 'gopher') {
            html += '<div class="view-toggle">';
            html += '<button class="view-toggle-btn active" onclick="switchView(this, \'text\')">文本</button>';
            html += '<button class="view-toggle-btn" onclick="switchView(this, \'hex\')">十六进制</button>';
            html += '</div>';
        }

        html += '</div>';

        // 结果内容
        html += '<div class="result-body">';

        switch (data.type) {
            case 'image':
                html += '<div class="result-image-preview">';
                html += '<img src="data:image/png;base64,' + data.content + '" alt="图片预览">';
                html += '</div>';
                html += '<p style="text-align: center; color: #28a745; font-weight: 600;"><i class="fa fa-check-circle"></i> AI识别结果：这是一张图片</p>';
                break;

            case 'text':
                html += '<pre>' + escapeHtml(data.content) + '</pre>';
                break;

            case 'file':
                html += '<pre>' + escapeHtml(data.content) + '</pre>';
                break;

            case 'dict':
                html += '<pre>' + escapeHtml(data.content) + '</pre>';
                break;

            case 'gopher':
                html += '<div class="gopher-text-view">' + escapeHtml(data.content) + '</div>';
                html += '<div class="gopher-hex-view" style="display:none;"><pre>' + escapeHtml(textToHex(data.content)) + '</pre></div>';
                break;

            default:
                html += '<pre>' + escapeHtml(data.content) + '</pre>';
                break;
        }

        html += '</div></div>';
        area.innerHTML = html;
    }

    /**
     * 切换gopher视图（文本/十六进制）
     */
    window.switchView = function (btn, viewType) {
        var container = btn.closest('.result-content');
        if (!container) return;

        // 更新按钮状态
        var buttons = container.querySelectorAll('.view-toggle-btn');
        buttons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        // 切换视图
        var textView = container.querySelector('.gopher-text-view');
        var hexView = container.querySelector('.gopher-hex-view');

        if (viewType === 'hex') {
            if (textView) textView.style.display = 'none';
            if (hexView) hexView.style.display = 'block';
        } else {
            if (textView) textView.style.display = 'block';
            if (hexView) hexView.style.display = 'none';
        }
    };

    /**
     * 文本转十六进制显示
     */
    function textToHex(text) {
        var hex = '';
        for (var i = 0; i < text.length; i++) {
            var charCode = text.charCodeAt(i);
            hex += charCode.toString(16).padStart(2, '0') + ' ';
            if ((i + 1) % 16 === 0) {
                hex += '\n';
            }
        }
        return hex;
    }

    /**
     * 更新步骤显示
     */
    function updateStepDisplay(stepHint) {
        if (!stepHint) return;

        var taskArea = document.getElementById('stepTask');
        var hintArea = document.getElementById('stepHint');
        var hintCard = hintArea ? hintArea.closest('.alert') : null;

        if (taskArea && stepHint[0]) {
            taskArea.innerHTML = '<small>' + stepHint[0] + '</small>';
        }
        if (hintArea && stepHint[1]) {
            hintArea.innerHTML = '<small>' + stepHint[1] + '</small>';
            if (hintCard) hintCard.style.display = '';
        } else if (hintCard) {
            hintCard.style.display = 'none';
        }

        // 动画效果
        var taskCard = document.querySelector('.step-guidance');
        if (taskCard) {
            taskCard.style.animation = 'none';
            taskCard.offsetHeight; // 触发重排
            taskCard.style.animation = 'fadeIn 0.5s ease';
        }
    }

    /**
     * 加载步骤提示信息
     */
    function loadStepHints() {
        fetch('api/get-hint.php', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    config.currentStep = data.current_step;
                    updateStepDisplay([data.task_text, data.hint_text]);
                }
            })
            .catch(function () {
                // 静默失败
            });
    }

    /**
     * 添加请求到历史记录
     */
    function addToHistory(url, type) {
        var now = new Date();
        var timeStr = now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0') + ':' +
            now.getSeconds().toString().padStart(2, '0');

        requestHistory.unshift({
            url: url,
            type: type,
            time: timeStr
        });

        // 最多保留5条
        if (requestHistory.length > MAX_HISTORY) {
            requestHistory.pop();
        }

        renderHistory();
    }

    /**
     * 渲染历史记录
     */
    function renderHistory() {
        var list = document.getElementById('historyList');
        if (!list) return;

        if (requestHistory.length === 0) {
            list.innerHTML = '<div style="text-align:center; color:#adb5bd; padding:10px; font-size:12px;">暂无请求记录</div>';
            return;
        }

        var html = '';
        for (var i = 0; i < requestHistory.length; i++) {
            var item = requestHistory[i];
            html += '<div class="history-item">';
            html += '<span class="history-index">#' + (i + 1) + '</span>';
            html += '<span class="history-url" title="' + escapeHtml(item.url) + '">' + escapeHtml(truncateUrl(item.url)) + '</span>';
            html += '<span class="history-type type-' + escapeHtml(item.type) + '">' + escapeHtml(item.type) + '</span>';
            html += '<span class="history-time">' + escapeHtml(item.time) + '</span>';
            html += '</div>';
        }

        list.innerHTML = html;
    }

    /**
     * 绑定历史记录折叠/展开
     */
    function bindHistoryToggle() {
        var toggle = document.getElementById('historyToggle');
        var list = document.getElementById('historyList');
        if (!toggle || !list) return;

        toggle.addEventListener('click', function () {
            toggle.classList.toggle('expanded');
            list.classList.toggle('show');
        });
    }

    /**
     * 加载端口扫描结果
     */
    function loadPortResults() {
        fetch('api/get-hint.php', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderPortTable(data.ports || []);

                    // 如果步骤3完成，显示第四步提示
                    if (data.current_step >= 4) {
                        showStep4Hint(data.db_info);
                    }
                }
            })
            .catch(function () {
                // 静默失败
            });
    }

    /**
     * 渲染端口表格
     */
    function renderPortTable(ports) {
        var container = document.getElementById('portResults');
        if (!container) return;

        if (ports.length === 0) {
            container.innerHTML = '<div class="no-ports">' +
                '<i class="fa fa-search"></i>' +
                '<p>尚未探测到任何开放端口...</p>' +
                '</div>';
            return;
        }

        var html = '<table class="port-table">';
        html += '<thead><tr><th>端口号</th><th>状态</th><th>探测时间</th></tr></thead>';
        html += '<tbody>';

        for (var i = 0; i < ports.length; i++) {
            var port = ports[i];
            var statusClass = port.is_open == 1 ? 'open' : 'closed';
            var statusText = port.is_open == 1 ? '开放' : '关闭';
            var statusIcon = port.is_open == 1 ? 'fa-check-circle' : 'fa-times-circle';

            html += '<tr>';
            html += '<td><strong>' + escapeHtml(String(port.port)) + '</strong></td>';
            html += '<td><span class="port-status ' + statusClass + '"><i class="fa ' + statusIcon + '"></i> ' + statusText + '</span></td>';
            html += '<td>' + escapeHtml(port.probed_at || '-') + '</td>';
            html += '</tr>';
        }

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    /**
     * 显示第四步提示
     */
    function showStep4Hint(dbInfo) {
        var hintArea = document.getElementById('step3Hint');
        if (!hintArea) return;

        hintArea.style.display = 'block';
        hintArea.innerHTML = '<div class="alert alert-success">' +
            '<div><i class="fa fa-check-circle"></i><strong>探测成功！你找到了数据库端口！</strong></div>' +
            '<div style="margin-top: 12px;">' +
            '<p>此外，内网扫描还发现了一台可疑的内网服务器：</p>' +
            '<p>地址：<strong>10.66.66.66</strong>，端口：<strong>56379</strong>（疑似 Redis 服务，存在未授权访问）</p>' +
            '</div>' +
            '<div style="margin-top: 12px;">' +
            '<p>请利用SSRF漏洞攻击该 Redis 服务，提取秘密字符串</p>' +
            '</div>' +
            '</div>';
    }

    /**
     * 截断过长的URL
     */
    function truncateUrl(url) {
        if (url.length > 60) {
            return url.substring(0, 57) + '...';
        }
        return url;
    }

    /**
     * HTML转义函数，防止XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
