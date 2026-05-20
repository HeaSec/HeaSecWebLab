/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场交互脚本
 * 版本: v1.2.0
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
    'use strict';

    // 当前关卡配置
    var currentLevel = 1;
    var isFinalLevel = false;
    var nextPage = '';

    // 已选择的文件列表（包含自定义文件名）
    var selectedFiles = [];

    /**
     * 初始化靶场
     * @param {Object} config - 配置对象
     * @param {number} config.level - 关卡编号
     * @param {boolean} config.isFinalLevel - 是否是最后一关
     * @param {string} config.nextPage - 下一关页面URL
     */
    window.initFileDirectory = function(config) {
        currentLevel = config.level || 1;
        isFinalLevel = config.isFinalLevel || false;
        nextPage = config.nextPage || '';

        initDragUpload();
        bindUploadForm();
        bindResetButton();
        bindNextLevelButton();
        bindRenameButtons();
        listenSecretCardSuccess();
        bindSelectedFileActions();
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
                handleFileSelection(files[0]);
            }
        });

        // 传统文件选择事件
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                var files = e.target.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
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
                handleFileSelection(files[0]);
            }
        });
    }

    /**
     * 处理文件选择
     * @param {File} file - 选择的文件
     */
    function handleFileSelection(file) {
        // 重置已选择文件列表
        selectedFiles = [{
            file: file,
            customName: file.name,
            originalName: file.name
        }];

        // 更新拖拽区域显示
        updateDropzoneText(file);

        // 显示已选择文件列表
        displaySelectedFiles();

        // 同步文件到输入框
        syncFileInputs(file);
    }

    /**
     * 显示已选择的文件列表
     */
    function displaySelectedFiles() {
        var container = document.getElementById('selectedFileContainer');
        var list = document.getElementById('selectedFileList');

        if (!container || !list) return;

        list.innerHTML = '';

        selectedFiles.forEach(function(item, index) {
            var li = document.createElement('li');
            li.setAttribute('data-index', index);

            li.innerHTML = '<div class="file-info">' +
                '<i class="fa fa-file-o" style="color: #007BFF;"></i>' +
                '<span class="file-name-display">' + escapeHtml(item.customName) + '</span>' +
                '<span class="file-size">' + formatFileSize(item.file.size) + '</span>' +
                '</div>' +
                '<div class="file-actions">' +
                '<button type="button" class="rename-inline-btn" data-index="' + index + '">' +
                '<i class="fa fa-edit"></i> 重命名' +
                '</button>' +
                '<button type="button" class="remove-file-btn" data-index="' + index + '">' +
                '<i class="fa fa-times"></i>' +
                '</button>' +
                '</div>';

            list.appendChild(li);
        });

        container.style.display = 'block';
    }

    /**
     * 绑定已选择文件列表的操作事件
     */
    function bindSelectedFileActions() {
        // 使用事件委托处理重命名和删除按钮
        document.addEventListener('click', function(e) {
            var target = e.target;

            // 处理重命名按钮
            var renameBtn = target.closest('.rename-inline-btn');
            if (renameBtn) {
                e.preventDefault();
                var index = parseInt(renameBtn.getAttribute('data-index'));
                showInlineRename(index);
                return;
            }

            // 处理确认重命名按钮
            var confirmBtn = target.closest('.confirm-rename-btn');
            if (confirmBtn) {
                e.preventDefault();
                var li = confirmBtn.closest('li');
                var index = parseInt(li.getAttribute('data-index'));
                var input = li.querySelector('.rename-input-inline');
                var newName = input.value.trim();
                if (newName) {
                    selectedFiles[index].customName = newName;
                    displaySelectedFiles();
                    updateDropzoneText(selectedFiles[0].file);
                }
                return;
            }

            // 处理取消重命名按钮
            var cancelBtn = target.closest('.cancel-rename-btn');
            if (cancelBtn) {
                e.preventDefault();
                displaySelectedFiles();
                return;
            }

            // 处理删除按钮
            var removeBtn = target.closest('.remove-file-btn');
            if (removeBtn) {
                e.preventDefault();
                var index = parseInt(removeBtn.getAttribute('data-index'));
                selectedFiles.splice(index, 1);
                if (selectedFiles.length === 0) {
                    document.getElementById('selectedFileContainer').style.display = 'none';
                    resetDropzoneText();
                    // 清空文件输入框
                    var dropzoneInput = document.getElementById('fileInputDropzone');
                    var fileInput = document.getElementById('fileInput');
                    if (dropzoneInput) dropzoneInput.value = '';
                    if (fileInput) fileInput.value = '';
                } else {
                    displaySelectedFiles();
                    updateDropzoneText(selectedFiles[0].file);
                }
                return;
            }
        });
    }

    /**
     * 显示内联重命名输入框
     * @param {number} index - 文件索引
     */
    function showInlineRename(index) {
        var list = document.getElementById('selectedFileList');
        var li = list.querySelector('li[data-index="' + index + '"]');
        if (!li) return;

        var item = selectedFiles[index];
        li.innerHTML = '<div class="file-info" style="flex: 1; min-width: 150px;">' +
            '<i class="fa fa-file-o" style="color: #007BFF;"></i>' +
            '<input type="text" class="rename-input-inline" value="' + escapeHtml(item.customName) + '" style="flex: 1;">' +
            '</div>' +
            '<div class="file-actions">' +
            '<button type="button" class="confirm-rename-btn"><i class="fa fa-check"></i> 确认</button>' +
            '<button type="button" class="cancel-rename-btn"><i class="fa fa-times"></i> 取消</button>' +
            '</div>';

        // 聚焦到输入框并选中文本
        var input = li.querySelector('.rename-input-inline');
        setTimeout(function() {
            input.focus();
            input.select();
        }, 100);

        // Enter键确认
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var newName = input.value.trim();
                if (newName) {
                    selectedFiles[index].customName = newName;
                    displaySelectedFiles();
                    updateDropzoneText(selectedFiles[0].file);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                displaySelectedFiles();
            }
        });
    }

    /**
     * 重置拖拽区域文本
     */
    function resetDropzoneText() {
        var uploadDropzone = document.getElementById('uploadDropzone');
        if (!uploadDropzone) return;

        var textElement = uploadDropzone.querySelector('p');
        if (textElement) {
            textElement.textContent = '点击选择文件或拖拽文件到此处';
        }
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

            // 检查是否有已选择的文件（带自定义文件名）
            // 第三关不使用重命名功能，直接上传原始文件
            if (currentLevel !== 3 && selectedFiles.length === 0) {
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

            // 添加自定义文件名参数（第三关使用原始文件名）
            if (currentLevel !== 3 && selectedFiles.length > 0) {
                var customFileName = selectedFiles[0].customName;
                formData.append('customFileName', customFileName);
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
                    // 清空已选择文件列表
                    selectedFiles = [];
                    // 第三关没有已选择文件列表容器，需要检查是否存在
                    var container = document.getElementById('selectedFileContainer');
                    if (container) {
                        container.style.display = 'none';
                    }
                    resetDropzoneText();
                    // 延迟刷新文件列表
                    setTimeout(function() {
                        refreshFileList();
                    }, 500);
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
     * 绑定重命名按钮
     */
    function bindRenameButtons() {
        document.addEventListener('click', function(e) {
            var renameBtn = e.target.closest('.rename-btn');
            if (!renameBtn) return;

            e.preventDefault();
            var oldName = renameBtn.getAttribute('data-filename');
            showRenameModal(oldName);
        });
    }

    /**
     * 显示重命名模态框
     * @param {string} oldName - 原文件名
     */
    function showRenameModal(oldName) {
        // 创建模态框
        var modal = document.createElement('div');
        modal.className = 'rename-modal-overlay';
        modal.innerHTML = '<div class="rename-modal">' +
            '<div class="rename-modal-header">' +
            '<h4><i class="fa fa-edit"></i> 重命名文件</h4>' +
            '<button type="button" class="rename-modal-close">&times;</button>' +
            '</div>' +
            '<div class="rename-modal-body">' +
            '<div class="rename-form-group">' +
            '<label>原文件名:</label>' +
            '<input type="text" class="rename-input" id="oldFileName" value="' + escapeHtml(oldName) + '" readonly>' +
            '</div>' +
            '<div class="rename-form-group">' +
            '<label>新文件名:</label>' +
            '<input type="text" class="rename-input" id="newFileName" value="' + escapeHtml(oldName) + '" placeholder="请输入新文件名">' +
            '</div>' +
            '<small class="rename-hint"><i class="fa fa-info-circle"></i> 输入新文件名后点击确认按钮</small>' +
            '</div>' +
            '<div class="rename-modal-footer">' +
            '<button type="button" class="rename-cancel-btn">取消</button>' +
            '<button type="button" class="rename-confirm-btn"><i class="fa fa-check"></i> 确认</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(modal);

        // 聚焦到新文件名输入框并选中文本
        var newFileInput = modal.querySelector('#newFileName');
        setTimeout(function() {
            newFileInput.focus();
            newFileInput.select();
        }, 100);

        // 关闭模态框函数
        function closeModal() {
            document.body.removeChild(modal);
        }

        // 绑定关闭事件
        modal.querySelector('.rename-modal-close').addEventListener('click', closeModal);
        modal.querySelector('.rename-cancel-btn').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // ESC键关闭
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        });

        // 确认按钮事件
        modal.querySelector('.rename-confirm-btn').addEventListener('click', function() {
            var newName = newFileInput.value.trim();
            if (!newName) {
                showMessage('请输入新文件名', 'error');
                return;
            }
            if (newName === oldName) {
                showMessage('新文件名与原文件名相同', 'warning');
                return;
            }

            performRename(oldName, newName, modal);
        });

        // Enter键提交
        newFileInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                modal.querySelector('.rename-confirm-btn').click();
            }
        });
    }

    /**
     * 执行重命名操作
     * @param {string} oldName - 原文件名
     * @param {string} newName - 新文件名
     * @param {HTMLElement} modal - 模态框元素
     */
    function performRename(oldName, newName, modal) {
        var confirmBtn = modal.querySelector('.rename-confirm-btn');
        var originalText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

        fetch('api/level' + currentLevel + '/rename.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                oldName: oldName,
                newName: newName
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            showMessage(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                document.body.removeChild(modal);
                setTimeout(function() {
                    refreshFileList();
                }, 500);
            }
        })
        .catch(function(err) {
            showMessage('重命名失败，请稍后重试', 'error');
            console.error('Rename error:', err);
        })
        .finally(function() {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        });
    }

    /**
     * HTML转义
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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

            // 先调用初始化接口更新secret.php为目标关卡的密码并重置exec目录
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
