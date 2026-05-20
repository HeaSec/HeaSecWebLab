/**
 * HeaSec天积安全团队 - XXE绕过靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var selectedFile = null;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initXxeBypass = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindFileSelect();
        bindImportButton();
        bindDownloadTemplate();
        bindVerifyForm();
        overrideResetButton();
        refreshImportedData();
    };

    /**
     * 绑定拖拽上传区域事件
     */
    function bindFileSelect() {
        var dropZone = document.getElementById('dropZone');
        var fileInput = document.getElementById('xmlFileInput');
        var fileNameDisplay = document.getElementById('fileNameDisplay');
        var fileInfoArea = document.getElementById('fileInfoArea');
        var dropzoneContent = dropZone ? dropZone.querySelector('.dropzone-content') : null;
        var clearFileBtn = document.getElementById('clearFileBtn');

        if (!dropZone || !fileInput) return;

        // 阻止默认拖拽行为
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
            dropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        // 拖入高亮效果
        dropZone.addEventListener('dragenter', function () {
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragover', function () {
            dropZone.classList.add('drag-over');
        });

        // 离开移除高亮
        dropZone.addEventListener('dragleave', function (e) {
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('drag-over');
            }
        });

        // 文件释放处理
        dropZone.addEventListener('drop', function (e) {
            dropZone.classList.remove('drag-over');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        // 点击选择文件
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                handleFileSelect(fileInput.files[0]);
            }
        });

        // 清除已选文件
        if (clearFileBtn) {
            clearFileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                clearSelectedFile();
            });
        }

        /**
         * 处理文件选择
         * @param {File} file - 选中的文件对象
         */
        function handleFileSelect(file) {
            selectedFile = file;
            if (fileNameDisplay) {
                fileNameDisplay.textContent = file.name;
            }

            if (dropzoneContent) dropzoneContent.style.display = 'none';
            if (fileInfoArea) fileInfoArea.style.display = 'flex';
            dropZone.classList.add('has-file');
        }

        /**
         * 清除已选文件
         */
        function clearSelectedFile() {
            selectedFile = null;
            fileInput.value = '';
            if (fileNameDisplay) fileNameDisplay.textContent = '';

            if (dropzoneContent) dropzoneContent.style.display = '';
            if (fileInfoArea) fileInfoArea.style.display = 'none';
            dropZone.classList.remove('has-file');
        }
    }

    /**
     * 绑定导入按钮事件
     */
    function bindImportButton() {
        var importBtn = document.getElementById('importBtn');
        if (!importBtn) return;

        importBtn.addEventListener('click', function () {
            if (!selectedFile) {
                showImportResult(false, '请先选择XML文件');
                return;
            }

            if (!selectedFile.name.toLowerCase().endsWith('.xml')) {
                showImportResult(false, '仅支持XML文件格式');
                return;
            }

            importBtn.classList.add('loading');
            var originalText = importBtn.innerHTML;
            importBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 导入中';

            triggerImport(selectedFile, function () {
                importBtn.classList.remove('loading');
                importBtn.innerHTML = originalText;
                selectedFile = null;
                var fileInput = document.getElementById('xmlFileInput');
                if (fileInput) fileInput.value = '';
                var fileNameDisplay = document.getElementById('fileNameDisplay');
                if (fileNameDisplay) fileNameDisplay.textContent = '';
                var fileInfoArea = document.getElementById('fileInfoArea');
                var dropzoneContent = document.querySelector('.dropzone-content');
                if (fileInfoArea) fileInfoArea.style.display = 'none';
                if (dropzoneContent) dropzoneContent.style.display = '';
                var dropZone = document.getElementById('dropZone');
                if (dropZone) dropZone.classList.remove('has-file');
            });
        });
    }

    /**
     * 触发XML文件导入
     * @param {File} file - XML文件对象
     * @param {Function} onDone - 完成回调
     */
    function triggerImport(file, onDone) {
        var apiUrl = 'api/process-level' + currentLevel + '.php';
        var formData = new FormData();
        formData.append('xml_file', file);

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                showImportResult(true, data.message, data);
                refreshImportedData();
            } else {
                showImportResult(false, data.message, data);
            }
        })
        .catch(function () {
            showImportResult(false, '导入失败，请稍后重试');
        })
        .finally(function () {
            if (onDone) onDone();
        });
    }

    /**
     * 显示导入结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {object} data - 响应数据
     */
    function showImportResult(success, message, data) {
        var resultArea = document.getElementById('resultArea');
        if (!resultArea) return;

        var html = '';
        if (success) {
            html = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';

            // 三关均显示完整解析结果表格
            if (data && data.product) {
                html += buildProductTable(data.product);
            }
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';

            // 显示错误详情
            if (data && data.errors && data.errors.length > 0) {
                html += buildErrorDetails(data.errors);
            }
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 构建商品解析结果表格
     * @param {object} product - 商品数据
     * @returns {string} HTML字符串
     */
    function buildProductTable(product) {
        return '<div class="result-section">' +
            '<h4><i class="fa fa-table"></i> 解析结果</h4>' +
            '<table class="product-result-table">' +
            '<tr><th>商品名称</th><td>' + escapeHtml(product.name || '') + '</td></tr>' +
            '<tr><th>分类</th><td>' + escapeHtml(product.category || '') + '</td></tr>' +
            '<tr><th>价格</th><td>' + escapeHtml(product.price || '') + '</td></tr>' +
            '<tr><th>描述</th><td>' + escapeHtml(product.description || '') + '</td></tr>' +
            '</table></div>';
    }

    /**
     * 构建错误详情展示
     * @param {Array} errors - 错误信息数组
     * @returns {string} HTML字符串
     */
    function buildErrorDetails(errors) {
        var errorLines = errors.map(function (err) { return escapeHtml(err); }).join('\n');
        return '<div class="result-section">' +
            '<h4><i class="fa fa-bug"></i> 错误详情</h4>' +
            '<div class="error-details">' + errorLines + '</div></div>';
    }

    /**
     * 刷新已导入数据表格
     */
    function refreshImportedData() {
        fetch('api/get-imported-data.php?level=' + currentLevel)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                renderImportedTable(data.products || []);
            }
        });
    }

    /**
     * 渲染已导入数据表格
     * @param {Array} products - 商品数据数组
     */
    function renderImportedTable(products) {
        var container = document.getElementById('importedData');
        if (!container) return;

        if (!products || products.length === 0) {
            container.innerHTML = '<div class="data-empty"><i class="fa fa-database"></i> 暂无导入数据</div>';
            return;
        }

        var html = '<table class="data-table">' +
            '<thead><tr>' +
            '<th>商品名称</th><th>分类</th><th>价格</th><th>导入时间</th>' +
            '</tr></thead><tbody>';

        products.forEach(function (p) {
            html += '<tr>' +
                '<td>' + escapeHtml(p.name || '') + '</td>' +
                '<td>' + escapeHtml(p.category || '') + '</td>' +
                '<td>' + escapeHtml(p.price || '') + '</td>' +
                '<td>' + escapeHtml(p.import_time || '') + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    /**
     * 绑定下载模板按钮事件
     */
    function bindDownloadTemplate() {
        var downloadBtn = document.getElementById('downloadTemplateBtn');
        if (!downloadBtn) return;

        downloadBtn.addEventListener('click', function () {
            var a = document.createElement('a');
            a.href = 'api/download-template.php';
            a.download = 'product-template.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
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
                message: '你掌握了XXE漏洞中绕过过滤机制的技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'xxebypass',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了XXE漏洞中绕过过滤机制的技巧！');
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
                code: 'xxebypass',
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
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码和导入数据，恢复初始状态</p>' +
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
     * HTML转义函数
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
