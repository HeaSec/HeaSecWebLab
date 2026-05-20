/**
 * HeaSec天积安全团队 - 文件包含基础靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
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
    window.initLfiBase = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindDocNavLinks();
        bindViewButton();
        bindVerifyForm();
        bindUploadButton();
        overrideResetButton();

        // 第三关白名单不允许 .php 后缀，不默认加载首页
        if (currentLevel !== 3) {
            loadPage('pages/home.php');
        }
    };

    /**
     * 加载并显示指定页面的内容
     * @param {string} page - 要包含的文件路径
     */
    function loadPage(page) {
        if (!page || page.trim() === '') {
            showContent('error', '请输入要查看的页面路径');
            return;
        }

        var contentArea = document.getElementById('contentArea');
        if (!contentArea) return;

        // 显示加载状态
        contentArea.innerHTML = '<div class="doc-loading"><i class="fa fa-spinner fa-spin"></i> 正在加载...</div>';
        contentArea.style.display = 'block';

        // 发送 AJAX 请求到对应的处理接口
        var viewApiUrl = 'api/process-level' + currentLevel + '.php';
        fetch(viewApiUrl + '?page=' + encodeURIComponent(page), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data.success) {
                showContent('success', data.content, data.page);
            } else {
                showContent('error', data.message);
            }
        }).catch(function (err) {
            showContent('error', '请求失败，请稍后重试');
        });
    }

    /**
     * 显示内容到展示区域
     * @param {string} type - 内容类型: success / error
     * @param {string} content - HTML内容
     * @param {string} [page] - 当前加载的页面路径（用于显示在标题栏）
     */
    function showContent(type, content, page) {
        var contentArea = document.getElementById('contentArea');
        if (!contentArea) return;

        var html = '';
        if (type === 'success') {
            html += '<div class="doc-content-wrapper">';
            html += '<div class="doc-content-header">';
            if (page) {
                html += '<span class="doc-path"><i class="fa fa-file"></i> ' + escapeHtml(page) + '</span>';
            }
            html += '</div>';
            // 使用占位容器，后续通过DOM操作安全注入内容
            html += '<div class="doc-content-body" id="docContentBody"></div>';
            html += '</div>';
        } else {
            html = '<div class="doc-content-wrapper"><div class="alert-error"><i class="fa fa-exclamation-triangle"></i> ' + escapeHtml(content) + '</div></div>';
        }

        contentArea.innerHTML = html;
        contentArea.style.display = 'block';

        // 通过沙箱 iframe 安全渲染文档查看器内容
        // 使用DOM编程设置srcdoc属性，避免HTML属性注入风险
        // sandbox限制：禁止脚本执行、禁止表单提交、禁止弹窗、禁止顶层导航
        if (type === 'success') {
            var bodyEl = document.getElementById('docContentBody');
            if (bodyEl) {
                var iframe = document.createElement('iframe');
                iframe.sandbox = 'allow-same-origin';
                iframe.style.cssText = 'width:100%;min-height:80px;border:none;display:block;';
                iframe.srcdoc = content;
                bodyEl.appendChild(iframe);
            }
        }
    }

    /**
     * 绑定文档导航链接点击事件
     */
    function bindDocNavLinks() {
        var navLinks = document.querySelectorAll('.doc-nav-link');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var page = this.getAttribute('data-page');
                loadPage(page);
            });
        });
    }

    /**
     * 绑定"查看"按钮点击事件
     */
    function bindViewButton() {
        var viewBtn = document.getElementById('viewBtn');
        if (viewBtn) {
            viewBtn.addEventListener('click', function () {
                var pageInput = document.getElementById('pageInput');
                if (pageInput) {
                    loadPage(pageInput.value.trim());
                }
            });
        }

        // 绑定回车键提交
        var pageInput = document.getElementById('pageInput');
        if (pageInput) {
            pageInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    loadPage(this.value.trim());
                }
            });
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
     * 显示恭喜弹窗（第三关通关）
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了PHP文件包含漏洞的基础利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'lfibase',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了PHP文件包含漏洞的基础利用技巧！');
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
                code: 'lfibase',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    }

    /**
     * 绑定头像上传按钮事件（第三关专用）
     */
    function bindUploadButton() {
        var uploadBtn = document.getElementById('uploadBtn');
        if (!uploadBtn) return;

        uploadBtn.addEventListener('click', function () {
            uploadAvatar();
        });
    }

    /**
     * 上传头像文件
     */
    function uploadAvatar() {
        var fileInput = document.getElementById('avatarFile');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showUploadResult('error', '请选择要上传的文件');
            return;
        }

        var file = fileInput.files[0];
        var formData = new FormData();
        formData.append('avatar', file);

        var resultArea = document.getElementById('uploadResultArea');
        if (resultArea) {
            resultArea.innerHTML = '<div class="doc-loading"><i class="fa fa-spinner fa-spin"></i> 正在上传...</div>';
        }

        fetch('api/upload-avatar.php', {
            method: 'POST',
            body: formData
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data.success) {
                showUploadResult('success', data.message, data.data);
                refreshUploadedFiles();
            } else {
                showUploadResult('error', data.message);
            }
        }).catch(function (err) {
            showUploadResult('error', '上传失败，请稍后重试');
        });
    }

    /**
     * 显示上传结果
     * @param {string} type - 结果类型: success / error
     * @param {string} message - 消息文本
     * @param {object} [data] - 额外数据
     */
    function showUploadResult(type, message, data) {
        var area = document.getElementById('uploadResultArea');
        if (!area) return;

        var html = '';
        if (type === 'success') {
            html = '<div class="alert-success"><i class="fa fa-check"></i> ' + escapeHtml(message);
            if (data && data.filepath) {
                html += '<br><span class="upload-filepath">文件路径: <code>' + escapeHtml(data.filepath) + '</code></span>';
                html += '<br><a href="#" onclick="loadPage(\'' + escapeHtml(data.filepath) + '\')" class="tech-btn tech-btn-sm tech-btn-info" style="margin-top:8px;">';
                html += '<i class="fa fa-eye"></i> 查看此文件</a>';
            }
            html += '</div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i> ' + escapeHtml(message) + '</div>';
        }
        area.innerHTML = html;
    }

    /**
     * 刷新已上传文件列表
     */
    function refreshUploadedFiles() {
        var listArea = document.getElementById('uploadedFilesList');
        if (!listArea) return;

        // 通过API获取上传目录中的文件列表（此处简化处理，仅作为展示区域预留）
        listArea.style.display = 'block';
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
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码和上传文件，恢复初始状态</p>' +
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
