/**
 * HeaSec天积安全团队 - 代码注入靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
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
    window.initCodeInj = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        // 绑定通关验证表单
        bindVerifyForm();
        // 覆盖重置按钮
        overrideResetButton();

        // 根据关卡绑定不同的交互
        if (currentLevel === 1) {
            bindLevel1Events();
        } else if (currentLevel === 2) {
            bindLevel2Events();
        } else if (currentLevel === 3) {
            bindLevel3Events();
        }
    };

    // ==========================================
    // 第一关：主题模板编辑器
    // ==========================================

    /**
     * 绑定第一关事件
     */
    function bindLevel1Events() {
        // 保存模板
        var saveBtn = document.getElementById('saveTemplateBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var name = document.getElementById('templateName').value.trim();
                var content = document.getElementById('templateContent').value.trim();
                if (!name || !content) {
                    showNotification('模板名称和内容不能为空', 'error');
                    return;
                }
                saveBtn.classList.add('loading');
                var originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 保存中';

                var formData = new FormData();
                formData.append('template_name', name);
                formData.append('template_content', content);

                fetch('api/save-template.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        loadTemplateList();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(function () {
                    showNotification('保存失败，请稍后重试', 'error');
                })
                .finally(function () {
                    saveBtn.classList.remove('loading');
                    saveBtn.innerHTML = originalText;
                });
            });
        }

        // 初始加载模板列表
        loadTemplateList();
    }

    /**
     * 加载模板列表
     */
    function loadTemplateList() {
        var listArea = document.getElementById('templateListArea');
        if (!listArea) return;

        fetch('api/list-templates.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.templates && data.templates.length > 0) {
                var html = '';
                for (var i = 0; i < data.templates.length; i++) {
                    var t = data.templates[i];
                    html += '<div class="template-item">';
                    html += '<span class="template-name">' + escapeHtml(t.filename) + '</span>';
                    html += '<div class="template-actions">';
                    html += '<button class="tech-btn tech-btn-info tech-btn-sm preview-btn" data-file="' + escapeHtml(t.filename) + '"><i class="fa fa-eye"></i> 预览</button>';
                    html += '<button class="tech-btn tech-btn-danger tech-btn-sm delete-btn" data-file="' + escapeHtml(t.filename) + '"><i class="fa fa-trash"></i> 删除</button>';
                    html += '</div>';
                    html += '</div>';
                }
                listArea.innerHTML = html;
                bindTemplateListEvents();
            } else {
                listArea.innerHTML = '<p style="color: #6c757d; font-size: 13px;">暂无已保存的模板</p>';
            }
        })
        .catch(function () {
            listArea.innerHTML = '<p style="color: #dc3545; font-size: 13px;">加载模板列表失败</p>';
        });
    }

    /**
     * 绑定模板列表中的按钮事件
     */
    function bindTemplateListEvents() {
        // 预览按钮
        var previewBtns = document.querySelectorAll('.preview-btn');
        for (var i = 0; i < previewBtns.length; i++) {
            previewBtns[i].addEventListener('click', function () {
                var file = this.getAttribute('data-file');
                previewTemplate(file);
            });
        }

        // 删除按钮
        var deleteBtns = document.querySelectorAll('.delete-btn');
        for (var j = 0; j < deleteBtns.length; j++) {
            deleteBtns[j].addEventListener('click', function () {
                var file = this.getAttribute('data-file');
                if (confirm('确定要删除模板 ' + file + ' 吗？')) {
                    deleteTemplate(file);
                }
            });
        }
    }

    /**
     * 预览模板
     * @param {string} file - 模板文件名
     */
    function previewTemplate(file) {
        var previewArea = document.getElementById('previewArea');
        var previewContent = document.getElementById('previewContent');
        if (!previewArea || !previewContent) return;

        previewContent.innerHTML = '<p style="color: #6c757d;">渲染中...</p>';
        previewArea.style.display = 'block';

        fetch('api/preview-template.php?file=' + encodeURIComponent(file))
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                previewContent.innerHTML = '';
                previewContent.appendChild(document.createTextNode(data.content));
            } else {
                previewContent.innerHTML = '<p style="color: #dc3545;">' + escapeHtml(data.message) + '</p>';
            }
        })
        .catch(function () {
            previewContent.innerHTML = '<p style="color: #dc3545;">预览失败，请稍后重试</p>';
        });
    }

    /**
     * 删除模板
     * @param {string} file - 模板文件名
     */
    function deleteTemplate(file) {
        fetch('api/delete-template.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file: file })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                showNotification(data.message, 'success');
                loadTemplateList();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(function () {
            showNotification('删除失败，请稍后重试', 'error');
        });
    }

    // ==========================================
    // 第二关：用户信息管理与数据备份
    // ==========================================

    /**
     * 绑定第二关事件
     */
    function bindLevel2Events() {
        // 加载用户信息
        loadUserInfo();
        // 加载备份列表
        loadBackupList();

        // 保存简介
        var saveBioBtn = document.getElementById('saveBioBtn');
        if (saveBioBtn) {
            saveBioBtn.addEventListener('click', function () {
                var bio = document.getElementById('bioInput').value.trim();
                if (bio === '') {
                    showResult('profileResultArea', false, '简介内容不能为空');
                    return;
                }
                saveBioBtn.classList.add('loading');
                var originalText = saveBioBtn.innerHTML;
                saveBioBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 保存中';

                fetch('api/update-profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bio: bio })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    showResult('profileResultArea', data.success, data.message);
                    if (data.success) {
                        loadUserInfo();
                    }
                })
                .catch(function () {
                    showResult('profileResultArea', false, '保存失败，请稍后重试');
                })
                .finally(function () {
                    saveBioBtn.classList.remove('loading');
                    saveBioBtn.innerHTML = originalText;
                });
            });
        }

        // 执行备份
        var backupBtn = document.getElementById('executeBackupBtn');
        if (backupBtn) {
            backupBtn.addEventListener('click', function () {
                var table = document.getElementById('backupTable').value;
                var filename = document.getElementById('backupFilename').value.trim();
                if (!filename) {
                    showResult('backupResultArea', false, '请输入备份文件名');
                    return;
                }
                backupBtn.classList.add('loading');
                var originalText = backupBtn.innerHTML;
                backupBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 备份中';

                fetch('api/execute-backup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table: table, filename: filename })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    showResult('backupResultArea', data.success, data.message);
                    if (data.success) {
                        loadBackupList();
                    }
                })
                .catch(function () {
                    showResult('backupResultArea', false, '备份失败，请稍后重试');
                })
                .finally(function () {
                    backupBtn.classList.remove('loading');
                    backupBtn.innerHTML = originalText;
                });
            });
        }
    }

    /**
     * 加载用户信息
     */
    function loadUserInfo() {
        fetch('api/get-user-info.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.data) {
                var user = data.data;
                var usernameEl = document.getElementById('displayUsername');
                var emailEl = document.getElementById('displayEmail');
                var bioEl = document.getElementById('displayBio');
                var bioInput = document.getElementById('bioInput');

                if (usernameEl) usernameEl.textContent = user.username || '';
                if (emailEl) emailEl.textContent = user.email || '';
                if (bioEl) bioEl.textContent = user.bio || '';
                if (bioInput) bioInput.value = user.bio || '';
            }
        })
        .catch(function () {});
    }

    /**
     * 加载备份文件列表
     */
    function loadBackupList() {
        var listArea = document.getElementById('backupListArea');
        if (!listArea) return;

        fetch('api/list-backups.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.backups && data.backups.length > 0) {
                var html = '';
                for (var i = 0; i < data.backups.length; i++) {
                    var b = data.backups[i];
                    html += '<div class="backup-item">';
                    html += '<span class="backup-name">' + escapeHtml(b.filename) + '</span>';
                    html += '<div class="backup-actions">';
                    html += '<a class="tech-btn tech-btn-info tech-btn-sm" href="backups/' + encodeURIComponent(b.filename) + '" target="_blank"><i class="fa fa-eye"></i> 查看</a>';
                    html += '<button class="tech-btn tech-btn-danger tech-btn-sm delete-backup-btn" data-file="' + escapeHtml(b.filename) + '"><i class="fa fa-trash"></i> 删除</button>';
                    html += '</div>';
                    html += '</div>';
                }
                listArea.innerHTML = html;
                bindBackupListEvents();
            } else {
                listArea.innerHTML = '<p style="color: #6c757d; font-size: 13px;">暂无备份文件</p>';
            }
        })
        .catch(function () {
            listArea.innerHTML = '<p style="color: #dc3545; font-size: 13px;">加载备份列表失败</p>';
        });
    }

    /**
     * 绑定备份列表按钮事件
     */
    function bindBackupListEvents() {
        var deleteBtns = document.querySelectorAll('.delete-backup-btn');
        for (var i = 0; i < deleteBtns.length; i++) {
            deleteBtns[i].addEventListener('click', function () {
                var file = this.getAttribute('data-file');
                if (confirm('确定要删除备份文件 ' + file + ' 吗？')) {
                    deleteBackup(file);
                }
            });
        }
    }

    /**
     * 删除备份文件
     * @param {string} file - 文件名
     */
    function deleteBackup(file) {
        fetch('api/delete-backup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file: file })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                showNotification(data.message, 'success');
                loadBackupList();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(function () {
            showNotification('删除请求失败', 'error');
        });
    }

    // ==========================================
    // 第三关：日志分析器
    // ==========================================

    /**
     * 绑定第三关事件
     */
    function bindLevel3Events() {
        // 设置日志文件名
        var setLogBtn = document.getElementById('setLogConfigBtn');
        if (setLogBtn) {
            setLogBtn.addEventListener('click', function () {
                var filename = document.getElementById('logFilename').value.trim();
                if (!filename) {
                    showNotification('请输入日志文件名', 'error');
                    return;
                }
                setLogBtn.classList.add('loading');
                var originalText = setLogBtn.innerHTML;
                setLogBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 设置中';

                fetch('api/set-log-config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ log_filename: filename })
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        loadLogContent();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(function () {
                    showNotification('设置失败，请稍后重试', 'error');
                })
                .finally(function () {
                    setLogBtn.classList.remove('loading');
                    setLogBtn.innerHTML = originalText;
                });
            });
        }

        // 刷新日志内容
        var refreshBtn = document.getElementById('refreshLogBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                loadLogContent();
            });
        }

        // 初始加载日志内容
        loadLogContent();
    }

    /**
     * 加载日志内容
     */
    function loadLogContent() {
        var logViewArea = document.getElementById('logViewArea');
        if (!logViewArea) return;

        fetch('api/view-log.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success && data.data) {
                logViewArea.textContent = data.data.content || '日志文件为空';
            } else {
                logViewArea.textContent = '加载日志内容失败';
            }
        })
        .catch(function () {
            logViewArea.textContent = '加载日志内容失败，请稍后重试';
        });
    }

    // ==========================================
    // 公共功能
    // ==========================================

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
                message: '你掌握了PHP代码注入漏洞的利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'code_inj',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了PHP代码注入漏洞的利用技巧！');
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
                code: 'code_inj',
                status: status
            })
        })
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
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码、模板、备份和日志文件，恢复初始状态</p>' +
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
     * 显示操作结果到指定区域
     * @param {string} areaId - 结果区域ID
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     */
    function showResult(areaId, success, message) {
        var area = document.getElementById(areaId);
        if (!area) return;

        if (success) {
            area.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            area.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
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
