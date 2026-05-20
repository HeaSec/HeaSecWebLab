/**
 * HeaSec天积安全团队 - 路径穿越靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
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
     * @param {Array} files - 文件列表数据
     */
    window.initPathTrvl = function (level, basePath, files) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        renderFileList(files || []);
        bindDownloadButtons();
        bindVerifyForm();
        overrideResetButton();
    };

    /**
     * 渲染文件列表
     * @param {Array} files - 文件数据数组
     */
    function renderFileList(files) {
        var container = document.getElementById('fileList');
        if (!container) return;

        if (!files || files.length === 0) {
            container.innerHTML = '<div class="downloaded-empty"><i class="fa fa-folder-open"></i> 暂无可下载的文件</div>';
            return;
        }

        var html = '';
        files.forEach(function (file) {
            html += '<div class="file-item">' +
                '<div class="file-info">' +
                '<span class="file-name"><i class="fa fa-file-text-o"></i> ' + escapeHtml(file.name) + '</span>' +
                '</div>' +
                '<div class="file-meta">' +
                '<span class="file-size">' + formatFileSize(file.size) + '</span>' +
                '<button type="button" class="tech-btn tech-btn-info download-btn" data-filename="' + escapeHtml(file.name) + '">' +
                '<i class="fa fa-download"></i> 下载' +
                '</button>' +
                '</div>' +
                '</div>';
        });

        container.innerHTML = html;
    }

    /**
     * 绑定文件下载按钮事件
     */
    function bindDownloadButtons() {
        var container = document.getElementById('fileList');
        if (!container) return;

        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.download-btn');
            if (!btn) return;

            var filename = btn.getAttribute('data-filename');
            if (!filename) return;

            if (btn.classList.contains('loading')) return;

            btn.classList.add('loading');
            var originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 下载中';
            btn.disabled = true;

            triggerFileDownload(filename, function () {
                btn.classList.remove('loading');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }

    /**
     * 触发文件下载（根据关卡调用不同的下载接口）
     * 使用fetch请求，先判断响应类型，错误时友好提示而非跳转页面
     * @param {string} filename - 文件名
     * @param {Function} onDone - 完成回调（无论成功或失败）
     */
    function triggerFileDownload(filename, onDone) {
        var apiUrl = 'api/download-level' + currentLevel + '.php';
        var formData = new FormData();
        formData.append('filename', filename);

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
            .then(function (res) {
                var contentType = res.headers.get('Content-Type') || '';
                if (contentType.indexOf('application/json') !== -1) {
                    return res.json().then(function (data) {
                        throw new Error(data.message || '下载失败');
                    });
                }
                return res.blob().then(function (blob) {
                    var downloadName = filename;
                    var disposition = res.headers.get('Content-Disposition');
                    if (disposition) {
                        var matches = disposition.match(/filename\*=UTF-8''(.+)/i) ||
                            disposition.match(/filename="([^"]+)"/);
                        if (matches) {
                            downloadName = decodeURIComponent(matches[1]);
                        }
                    }
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = downloadName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            })
            .catch(function (err) {
                showDownloadError(err.message || '下载失败，请稍后重试');
            })
            .finally(function () {
                if (onDone) onDone();
            });
    }

    /**
     * 显示下载错误提示
     * @param {string} message - 错误信息
     */
    function showDownloadError(message) {
        var listSection = document.querySelector('.file-list-section');
        if (!listSection) {
            showNotification(message, 'error');
            return;
        }
        var alert = document.createElement('div');
        alert.className = 'alert-error';
        alert.style.marginBottom = '10px';
        alert.innerHTML = '<i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span>';
        listSection.insertBefore(alert, listSection.firstChild);
        setTimeout(function () {
            if (alert.parentNode) alert.parentNode.removeChild(alert);
        }, 3000);
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
                message: '你掌握了路径穿越漏洞的利用方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'pathtrvl',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了路径穿越漏洞的利用方式！');
        }
    }

    /**
     * 更新学习进度状态（第1/2关通关时调用）
     * @param {string} status - 学习状态值（学习中/已掌握）
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'pathtrvl',
                status: status
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success && !data.skipped) {
                    console.log('[HeaSec] 学习状态更新失败：' + (data.message || '未知错误'));
                }
            })
            .catch(function () {
                console.log('[HeaSec] 学习状态更新请求失败');
            });
    }

    /**
     * 覆盖公共头部重置按钮的行为
     * 因为路径穿越靶场不使用数据库，需要自定义重置逻辑
     */
    function overrideResetButton() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        // 克隆按钮以移除公共JS绑定的事件监听器
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
     */
    function showNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * 格式化文件大小
     */
    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return bytes + ' B';
    }

    /**
     * HTML转义函数
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
