/**
 * HeaSec天积安全团队 - 条件竞争上传靶场交互脚本
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
    'use strict';

    // 当前关卡配置
    var currentLevel = 1;
    var isFinalLevel = false;
    var nextPage = '';

    // 第二关状态轮询相关
    var statusCheckInterval = null;

    /**
     * 初始化靶场
     * @param {Object} config - 配置对象
     * @param {number} config.level - 关卡编号
     * @param {boolean} config.isFinalLevel - 是否是最后一关
     * @param {string} config.nextPage - 下一关页面URL
     */
    window.initRaceCondition = function(config) {
        currentLevel = config.level || 1;
        isFinalLevel = config.isFinalLevel || false;
        nextPage = config.nextPage || '';

        initDragUpload();
        bindUploadForm();
        bindResetButton();
        bindNextLevelButton();
        listenSecretCardSuccess();

        // 第二关初始化头像状态轮询
        if (currentLevel === 2) {
            initAvatarStatusPolling();
        }
    };

    /**
     * 监听密码验证成功事件
     */
    function listenSecretCardSuccess() {
        document.addEventListener('heasec:secretcard:success', function(e) {
            var containerId = e.detail.containerId;

            // 从输入框获取秘密值
            var inputId = 'secret_' + containerId;
            var input = document.getElementById(inputId);
            var secret = input ? input.value.trim() : '';

            if (!secret) {
                console.error('无法获取秘密值');
                return;
            }

            // 调用服务器API设置通关状态
            fetch('api/level' + currentLevel + '/verify-secret.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ secret: secret })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    // 显示下一关按钮区域
                    var nextLevelSection = document.getElementById('nextLevelSection');
                    if (nextLevelSection) {
                        nextLevelSection.style.display = 'block';
                        // 滚动到下一关按钮区域
                        nextLevelSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    console.error('服务器验证失败:', data.message);
                }
            })
            .catch(function(err) {
                console.error('验证请求失败:', err);
            });
        });
    }

    /**
     * 初始化第二关头像状态轮询
     */
    function initAvatarStatusPolling() {
        // 检查页面是否已经显示审核通过或审核拒绝状态
        // 如果是，则不需要启动轮询，避免无限刷新
        var container = document.getElementById('avatarContainer');
        if (container) {
            var hasApproved = container.querySelector('.avatar-approved');
            var hasRejected = container.querySelector('.avatar-rejected');
            if (hasApproved || hasRejected) {
                // 已经是最终状态，无需轮询
                return;
            }
        }

        // 检查初始状态
        checkAvatarStatus();

        // 每秒轮询一次状态
        statusCheckInterval = setInterval(checkAvatarStatus, 1000);
    }

    /**
     * 检查头像审核状态
     */
    function checkAvatarStatus() {
        // 先检查页面是否已经显示最终状态，如果是则停止轮询
        var container = document.getElementById('avatarContainer');
        if (container) {
            var hasApproved = container.querySelector('.avatar-approved');
            var hasRejected = container.querySelector('.avatar-rejected');
            if (hasApproved || hasRejected) {
                // 已经是最终状态，停止轮询
                stopStatusPolling();
                return;
            }
        }

        fetch('api/level2/check-status.php')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    updateAvatarDisplay(data);
                }
            })
            .catch(function(err) {
                // 静默失败，不显示错误
            });
    }

    /**
     * 更新头像显示区域
     * @param {Object} data - 状态数据
     */
    function updateAvatarDisplay(data) {
        var container = document.getElementById('avatarContainer');
        if (!container) return;

        var status = data.status;

        if (status === 'approved' && data.avatar_url) {
            // 审核通过 - 显示头像
            container.innerHTML = '<div class="avatar-approved">' +
                '<img src="' + data.avatar_url + '" alt="头像" class="avatar-image">' +
                '<p class="avatar-filename">' + escapeHtml(data.original_name) + '</p>' +
                '<span class="avatar-status approved"><i class="fa fa-check-circle"></i> 审核通过</span>' +
                '</div>';
            // 停止轮询
            stopStatusPolling();
            // 刷新文件列表
            setTimeout(refreshFileList, 1000);
        } else if (status === 'pending') {
            // 审核中 - 更新剩余时间
            var remainingTimeEl = document.getElementById('remainingTime');
            if (remainingTimeEl) {
                remainingTimeEl.textContent = data.remaining_time;
            }
        } else if (status === 'rejected') {
            // 审核拒绝
            container.innerHTML = '<div class="avatar-rejected">' +
                '<div class="avatar-placeholder">' +
                '<i class="fa fa-times-circle fa-3x"></i>' +
                '<p>审核未通过</p>' +
                '</div>' +
                '<p class="avatar-filename">' + escapeHtml(data.original_name) + '</p>' +
                '<span class="avatar-status rejected"><i class="fa fa-times-circle"></i> 非图片格式，已拒绝</span>' +
                '</div>';
            // 停止轮询
            stopStatusPolling();
        } else if (status === 'none') {
            // 无上传记录 - 显示默认状态（页面已渲染，无需更新）
        }
    }

    /**
     * 停止状态轮询
     */
    function stopStatusPolling() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
    }

    /**
     * HTML转义
     * @param {string} text - 原始文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 初始化拖拽上传功能
     */
    function initDragUpload() {
        var uploadDropzone = document.getElementById('uploadDropzone');
        var dropzoneInput = document.getElementById('fileInputDropzone');
        var fileInput = document.getElementById('fileInput');

        if (!uploadDropzone || !dropzoneInput) {
            return;
        }

        // 点击拖拽区域触发文件选择
        uploadDropzone.addEventListener('click', function(e) {
            if (e.target !== dropzoneInput) {
                dropzoneInput.click();
            }
        });

        // 拖拽区域文件选择事件
        dropzoneInput.addEventListener('change', function(e) {
            var files = e.target.files;
            if (files.length > 0) {
                updateDropzoneText(files[0]);
                syncFileInputs(files[0]);
            }
        });

        // 传统文件选择事件
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                var files = e.target.files;
                if (files.length > 0) {
                    syncFileInputs(files[0]);
                }
            });
        }

        // 拖拽事件处理
        uploadDropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });

        uploadDropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });

        uploadDropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');

            var files = e.dataTransfer.files;
            if (files.length > 0) {
                var file = files[0];

                // 同步文件到输入框
                syncFileInputs(file);

                // 更新拖拽区域显示
                updateDropzoneText(file);
            }
        });
    }

    /**
     * 同步文件到拖拽输入框
     * @param {File} file - 文件对象
     */
    function syncFileInputs(file) {
        var dropzoneInput = document.getElementById('fileInputDropzone');

        // 创建一个新的FileList对象（通过DataTransfer）
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);

        if (dropzoneInput) {
            dropzoneInput.files = dataTransfer.files;
        }
    }

    /**
     * 更新拖拽区域文本
     * @param {File} file - 文件对象
     */
    function updateDropzoneText(file) {
        var uploadDropzone = document.getElementById('uploadDropzone');
        if (!uploadDropzone) return;

        var textElement = uploadDropzone.querySelector('p');
        var fileSize = formatFileSize(file.size);

        if (textElement) {
            textElement.innerHTML = '<strong>已选择文件:</strong><br>' +
                file.name + '<br><small>大小: ' + fileSize + '</small>';
        }
    }

    /**
     * 格式化文件大小
     * @param {number} bytes - 字节数
     * @returns {string} 格式化后的文件大小
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';

        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * 绑定上传表单
     */
    function bindUploadForm() {
        var form = document.getElementById('uploadForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(form);
            var fileInput = document.getElementById('fileInputDropzone');

            if (!fileInput || !fileInput.files.length) {
                showMessage('请选择文件', 'error');
                return;
            }

            // 检查文件大小（1MB限制）
            var file = fileInput.files[0];
            var maxSize = 1 * 1024 * 1024; // 1MB
            if (file.size > maxSize) {
                showMessage('文件大小超过限制（最大1MB）', 'error');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 上传中...';

            fetch('api/level' + currentLevel + '/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    // 第二关特殊处理：启动状态轮询
                    if (currentLevel === 2) {
                        // 立即检查一次状态
                        setTimeout(function() {
                            checkAvatarStatus();
                            // 确保轮询正在运行
                            if (!statusCheckInterval) {
                                initAvatarStatusPolling();
                            }
                        }, 500);
                    } else {
                        // 其他关卡：延迟刷新文件列表
                        setTimeout(function() {
                            refreshFileList();
                        }, 500);
                    }
                }
            })
            .catch(function(err) {
                showMessage('上传失败，请稍后重试', 'error');
                console.error('Upload error:', err);
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    /**
     * 绑定重置按钮
     */
    function bindResetButton() {
        var resetBtn = document.getElementById('resetBtn');
        if (!resetBtn) return;

        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (!confirm('确认删除全部已上传的文件吗？')) {
                return;
            }

            var originalText = resetBtn.innerHTML;
            resetBtn.disabled = true;
            resetBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

            fetch('api/level' + currentLevel + '/reset.php', {
                method: 'POST'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                showMessage(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(function() {
                        refreshFileList();
                    }, 500);
                }
            })
            .catch(function(err) {
                showMessage('重置失败，请稍后重试', 'error');
                console.error('Reset error:', err);
            })
            .finally(function() {
                resetBtn.disabled = false;
                resetBtn.innerHTML = originalText;
            });
        });
    }

    /**
     * 绑定下一关按钮
     */
    function bindNextLevelButton() {
        var nextLevelBtn = document.getElementById('nextLevelBtn');
        if (!nextLevelBtn) return;

        nextLevelBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // 计算目标关卡
            var targetLevel = currentLevel + 1;

            // 先调用初始化接口更新secret.php为目标关卡的密码
            fetch('api/init-level.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ targetLevel: targetLevel })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                // 初始化成功后跳转到下一关
                window.location.href = nextPage;
            })
            .catch(function(err) {
                // 即使出错也跳转
                console.error('Init level error:', err);
                window.location.href = nextPage;
            });
        });
    }

    /**
     * 刷新文件列表
     */
    function refreshFileList() {
        location.reload();
    }

    /**
     * 显示消息提示
     * @param {string} message - 消息内容
     * @param {string} type - 消息类型 (success|error|info|warning)
     */
    function showMessage(message, type) {
        var messageArea = document.getElementById('messageArea');
        if (!messageArea) {
            // 如果没有消息区域，使用alert
            alert(message);
            return;
        }

        messageArea.innerHTML = '<div class="message ' + type + '">' +
            '<i class="fa ' + getIconForType(type) + '"></i> ' +
            message + '</div>';

        // 5秒后自动隐藏
        setTimeout(function() {
            messageArea.innerHTML = '';
        }, 5000);
    }

    /**
     * 获取消息类型对应的图标
     * @param {string} type - 消息类型
     * @returns {string} 图标类名
     */
    function getIconForType(type) {
        var icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle',
            'warning': 'fa-exclamation-triangle'
        };
        return icons[type] || 'fa-info-circle';
    }

    // 添加键盘快捷键支持
    document.addEventListener('keydown', function(e) {
        // Ctrl+O 打开文件选择
        if (e.ctrlKey && e.key === 'o') {
            e.preventDefault();
            var dropzoneInput = document.getElementById('fileInputDropzone');
            if (dropzoneInput) {
                dropzoneInput.click();
            }
        }
        // Ctrl+Enter 提交上传表单
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            var uploadForm = document.getElementById('uploadForm');
            if (uploadForm) {
                uploadForm.dispatchEvent(new Event('submit'));
            }
        }
    });

})();
