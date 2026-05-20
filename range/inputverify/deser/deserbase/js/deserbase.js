/**
 * HeaSec天积安全团队 - 反序列化练习靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var sourceVisible = false;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initDeserBase = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindSubmitButton();
        bindSourceCodeButton();
        bindVerifyForm();
        overrideResetButton();
    };

    /**
     * 绑定序列化数据提交按钮事件
     */
    function bindSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;

        submitBtn.addEventListener('click', function () {
            var dataInput = document.getElementById('serializedData');
            if (!dataInput) return;

            var data = dataInput.value.trim();
            if (!data) {
                showResult(false, '请输入序列化数据', null);
                return;
            }

            submitBtn.classList.add('loading');
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中';

            triggerSubmit(data, function () {
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            });
        });
    }

    /**
     * 触发序列化数据提交
     * @param {string} data - 序列化字符串
     * @param {Function} onDone - 完成回调
     */
    function triggerSubmit(data, onDone) {
        var apiUrl = 'api/process-level' + currentLevel + '.php';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: data })
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (res.success) {
                showResult(true, res.message, res.data);
            } else {
                showResult(false, res.message, null);
            }
        })
        .catch(function () {
            showResult(false, '请求失败，请稍后重试', null);
        })
        .finally(function () {
            if (onDone) onDone();
        });
    }

    /**
     * 显示反序列化结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {object} data - 响应数据
     */
    function showResult(success, message, data) {
        var resultArea = document.getElementById('resultArea');
        if (!resultArea) return;

        var html = '';
        if (success) {
            html = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
            if (data) {
                html += buildResultTable(currentLevel, data);
            }
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 根据关卡构建结果表格
     * @param {number} level - 关卡编号
     * @param {object} data - 结果数据
     * @returns {string} HTML字符串
     */
    function buildResultTable(level, data) {
        var html = '<div class="result-section">';
        html += '<h4><i class="fa fa-table"></i> 反序列化结果</h4>';

        if (level === 1) {
            html += '<table class="result-table">';
            html += '<tr><th>姓名</th><td>' + escapeHtml(data.name || '') + '</td></tr>';
            html += '<tr><th>角色</th><td>' + escapeHtml(data.role || '') + '</td></tr>';
            html += '<tr><th>信息</th><td>' + escapeHtml(data.info || '') + '</td></tr>';
            if (data.secret) {
                html += '<tr><th>通关密码</th><td style="color: #e74c3c; font-weight: bold;">' + escapeHtml(data.secret) + '</td></tr>';
            }
            html += '</table>';
        } else if (level === 2) {
            html += '<table class="result-table">';
            html += '<tr><th>对象类名</th><td>' + escapeHtml(data.class || '') + '</td></tr>';
            html += '<tr><th>文件路径</th><td>' + escapeHtml(data.filename || '') + '</td></tr>';
            html += '</table>';
            html += '<div class="result-content">' + escapeHtml(data.content || '') + '</div>';
        } else if (level === 3) {
            html += '<table class="result-table">';
            html += '<tr><th>昵称</th><td>' + escapeHtml(data.nickname || '') + '</td></tr>';
            html += '<tr><th>头像</th><td>' + escapeHtml(data.avatar || '') + '</td></tr>';
            html += '<tr><th>签名</th><td>' + escapeHtml(data.signature || '') + '</td></tr>';
            html += '<tr><th>是否VIP</th><td>' + escapeHtml(data.isVIP || '否') + '</td></tr>';
            if (data.secret) {
                html += '<tr><th>通关密码</th><td style="color: #e74c3c; font-weight: bold;">' + escapeHtml(data.secret) + '</td></tr>';
            }
            html += '</table>';
        }

        html += '</div>';
        return html;
    }

    /**
     * 绑定查看源代码按钮事件
     */
    function bindSourceCodeButton() {
        var sourceBtn = document.getElementById('sourceCodeBtn');
        if (!sourceBtn) return;

        sourceBtn.addEventListener('click', function () {
            var sourceArea = document.getElementById('sourceArea');
            if (!sourceArea) return;

            if (sourceVisible) {
                sourceArea.style.display = 'none';
                sourceVisible = false;
                sourceBtn.innerHTML = '<i class="fa fa-code"></i> 查看源代码';
                return;
            }

            // 首次展开时加载源码
            if (!sourceArea.getAttribute('data-loaded')) {
                fetch('api/get-source-code.php?level=' + currentLevel)
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) {
                        if (res.sections && res.sections.length > 0) {
                            // 多段源码模式（如 level 3 展示类定义 + 过滤逻辑）
                            renderMultiSectionSource(sourceArea, res.sections);
                        } else if (res.source) {
                            // 单段源码模式（level 1、level 2 兼容）
                            var codeBlock = sourceArea.querySelector('.source-code-block');
                            if (codeBlock) {
                                codeBlock.textContent = res.source;
                            }
                        }
                        sourceArea.setAttribute('data-loaded', 'true');
                    }
                })
                .catch(function () {
                    if (!sourceArea.querySelector('.source-section')) {
                        var codeBlock = sourceArea.querySelector('.source-code-block');
                        if (codeBlock) {
                            codeBlock.textContent = '加载源码失败，请稍后重试';
                        }
                    }
                });
            }

            sourceArea.style.display = 'block';
            sourceVisible = true;
            sourceBtn.innerHTML = '<i class="fa fa-code"></i> 隐藏源代码';
        });
    }

    /**
     * 渲染多段源码展示（用于需要展示多个代码区间的关卡）
     * @param {HTMLElement} container - 源码展示容器 (#sourceArea)
     * @param {Array} sections - 代码段数组，每项含 title 和 code
     */
    function renderMultiSectionSource(container, sections) {
        for (var idx = 0; idx < sections.length; idx++) {
            var section = document.createElement('div');
            section.className = 'source-section';

            var title = document.createElement('h4');
            title.className = 'source-section-title';
            title.innerHTML = '<i class="fa fa-file-code-o"></i> ' + escapeHtml(sections[idx].title);

            var codeBlock = document.createElement('div');
            codeBlock.className = 'source-code-block';
            codeBlock.textContent = sections[idx].code;

            section.appendChild(title);
            section.appendChild(codeBlock);
            container.appendChild(section);
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
                message: '你掌握了PHP反序列化漏洞的核心利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'deserbase',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了PHP反序列化漏洞的核心利用技巧！');
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
                code: 'deserbase',
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
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码，恢复初始状态</p>' +
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
