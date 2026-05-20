/**
 * HeaSec天积安全团队 - 文件包含进阶靶场前端交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-17
 * 团队: 天积安全 (HeavenlySecret)
 */
var HeaSec = HeaSec || {};
HeaSec.LfiAdv = (function () {
    'use strict';

    var currentApiUrl = 'api/load-template.php';

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 导航链接点击
        var navLinks = document.querySelectorAll('.doc-nav-link');
        for (var i = 0; i < navLinks.length; i++) {
            navLinks[i].addEventListener('click', function (e) {
                e.preventDefault();
                var page = this.getAttribute('data-page');
                if (page) {
                    loadTemplate(page);
                }
            });
        }

        // 加载按钮点击
        var loadBtn = document.getElementById('loadBtn');
        if (loadBtn) {
            loadBtn.addEventListener('click', function () {
                var input = document.getElementById('templateInput');
                if (input && input.value.trim()) {
                    loadTemplate(input.value.trim());
                }
            });
        }

        // 回车键加载
        var templateInput = document.getElementById('templateInput');
        if (templateInput) {
            templateInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var val = this.value.trim();
                    if (val) {
                        loadTemplate(val);
                    }
                }
            });
        }

        // 拖拽上传交互
        var dropZone = document.getElementById('dropZone');
        var fileInput = document.getElementById('fileInput');
        if (dropZone && fileInput) {
            // 点击拖拽区域触发文件选择
            dropZone.addEventListener('click', function () {
                fileInput.click();
            });

            // 选择文件后自动上传
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    uploadFile();
                }
            });

            // 拖拽事件
            dropZone.addEventListener('dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });

            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');

                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    uploadFile();
                }
            });
        }

        // 重置按钮覆盖
        overrideResetButton();
    }

    /**
     * 加载模板
     */
    function loadTemplate(template) {
        var contentArea = document.getElementById('contentArea');
        if (contentArea) {
            contentArea.innerHTML = '<div class="content-placeholder"><i class="fa fa-spinner fa-spin"></i><p>加载中...</p></div>';
            contentArea.style.display = 'block';
        }

        var url = currentApiUrl + '?template=' + encodeURIComponent(template);

        // php://input 需要 POST 请求
        if (template.indexOf('php://input') !== -1) {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: ''
            }).then(function (res) { return res.json(); })
                .then(function (data) {
                    handleLoadResponse(data, template);
                }).catch(function (err) {
                    showContent('error', '请求失败：' + err.message);
                });
            return;
        }

        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                handleLoadResponse(data, template);
            }).catch(function (err) {
                showContent('error', '请求失败：' + err.message);
            });
    }

    /**
     * 处理加载响应
     */
    function handleLoadResponse(data, template) {
        if (data.success) {
            showContent('content', data.data.content);
        } else {
            showContent('error', data.message);
        }
        // 刷新成就状态
        refreshAchievementStatus();
    }

    /**
     * 展示内容
     */
    function showContent(type, content) {
        var contentArea = document.getElementById('contentArea');
        if (!contentArea) return;
        contentArea.style.display = 'block';

        if (type === 'error') {
            contentArea.innerHTML = '<div style="color: #dc3545; padding: 10px;"><i class="fa fa-exclamation-circle"></i> ' + escapeHtml(content) + '</div>';
        } else {
            // 检查是否是 base64 编码内容（php://filter 的结果）
            if (content && content.length > 50 && /^[A-Za-z0-9+\/=\s]+$/.test(content.trim())) {
                contentArea.innerHTML = '<div style="padding: 10px;"><strong style="color: #17a2b8;">Base64 编码内容：</strong><pre style="background: #f1f3f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; word-break: break-all; white-space: pre-wrap;">' + escapeHtml(content) + '</pre></div>';
            } else {
                contentArea.innerHTML = content || '<div class="content-placeholder"><i class="fa fa-file-o"></i><p>模板内容为空</p></div>';
            }
        }
    }

    /**
     * 上传文件
     */
    function uploadFile() {
        var fileInput = document.getElementById('fileInput');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showUploadResult('error', '请选择要上传的文件');
            return;
        }

        var file = fileInput.files[0];
        var formData = new FormData();
        formData.append('userfile', file);

        fetch('api/upload-file.php', {
            method: 'POST',
            body: formData
        }).then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showUploadResult('success', data.message, data.data);
                    // 清空文件选择
                    fileInput.value = '';
                } else {
                    showUploadResult('error', data.message);
                }
                // 刷新状态
                refreshAchievementStatus();
            }).catch(function (err) {
                showUploadResult('error', '上传请求失败');
            });
    }

    /**
     * 显示上传结果
     */
    function showUploadResult(type, message, data) {
        var resultArea = document.getElementById('uploadResultArea');
        if (!resultArea) return;

        var html = '<div class="upload-result ' + type + '">';
        html += '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'times-circle') + '"></i> ';
        html += escapeHtml(message);

        if (type === 'success' && data) {
            html += '<span class="file-path">文件路径：' + escapeHtml(data.filepath) + '</span>';
        }

        html += '</div>';
        resultArea.innerHTML = html;
    }

    /**
     * 包含已上传文件
     */
    function includeUploadedFile(el) {
        var path = el.getAttribute('data-path');
        if (path) {
            // 填入输入框并加载
            var input = document.getElementById('templateInput');
            if (input) {
                input.value = path;
            }
            loadTemplate(path);
        }
    }

    /**
     * 刷新已上传文件列表
     */
    function refreshUploadedFiles(files) {
        var listEl = document.getElementById('uploadedFilesList');
        if (!listEl) return;

        if (!files || files.length === 0) {
            listEl.innerHTML = '';
            return;
        }

        var html = '<h4><i class="fa fa-list"></i> 已上传文件</h4>';
        for (var i = 0; i < files.length; i++) {
            html += '<div class="file-item">';
            html += '<span class="file-name">' + escapeHtml(files[i].filename) + '</span>';
            html += '<span class="file-path-hint">(' + escapeHtml(files[i].filepath) + ')</span>';
            html += '</div>';
        }
        listEl.innerHTML = html;
    }

    /**
     * 刷新成就状态
     */
    function refreshAchievementStatus() {
        fetch('api/get-status.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    refreshUploadedFiles(data.data.uploaded_files);

                    var card = document.querySelector('[data-config]');
                    if (card) {
                        try {
                            var config = JSON.parse(card.getAttribute('data-config'));
                            var previousCount = config.achievedCount || 0;
                            var newCount = data.data.achieved_count;
                            var thresholds = config.thresholds || [1, 3, 5];

                            if (newCount > previousCount) {
                                config.achievedCount = newCount;
                                card.setAttribute('data-config', JSON.stringify(config));

                                document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                                    detail: { starCount: newCount }
                                }));
                            }

                            // 刷新星星视觉状态
                            refreshStarVisuals(card, newCount, thresholds);

                            // 刷新记录列表
                            if (data.data.records) {
                                refreshRecordList(card, data.data.records);
                            }
                        } catch (e) {}
                    }

                    // 更新进度提示
                    var progressEl = card ? card.querySelector('.tech-info-panel .alert-info.mb-2 span') : null;
                    if (progressEl && data.data.progress_hint) {
                        progressEl.textContent = data.data.progress_hint;
                    }
                }
            }).catch(function () {});
    }

    /**
     * 刷新星星的解锁/锁定视觉状态
     */
    function refreshStarVisuals(card, achievedCount, thresholds) {
        var starContainer = card.querySelector('[data-heasec-star]');
        if (!starContainer) return;

        var starEls = starContainer.querySelectorAll('.heasec-star');
        var basePath = '';

        // 推导星星资源基础路径
        var parentConfig;
        try { parentConfig = JSON.parse(card.getAttribute('data-config')); } catch (e) { parentConfig = {}; }
        if (parentConfig.commonBasePath) {
            basePath = parentConfig.commonBasePath + 'components/star-system/';
        }

        for (var i = 0; i < starEls.length; i++) {
            var starEl = starEls[i];
            var threshold = thresholds[i] || (i + 1);
            var isUnlocked = achievedCount >= threshold;
            var state = isUnlocked ? 'gold' : 'gray';

            starEl.className = 'heasec-star heasec-star-' + state;
            starEl.setAttribute('aria-label', '成就星星 ' + (i + 1) + ', ' + (isUnlocked ? '已解锁' : '未解锁'));

            var img = starEl.querySelector('.star-svg');
            if (img) {
                img.src = basePath + 'assets/svg/star-' + state + '.svg';
            }
        }
    }

    /**
     * 刷新协议记录列表
     */
    function refreshRecordList(card, records) {
        var infoGrid = card.querySelector('.info-grid');
        if (!infoGrid) return;

        var recordLabel = '协议';

        var headerHtml = '<div class="info-item" style="background: rgba(0,0,0,0.03); border: none; padding: 6px 12px; min-height: auto;">' +
            '<span class="info-label" style="font-size: 12px; font-weight: bold; color: #666;">' + escapeHtml(recordLabel) + '</span>' +
            '<span class="info-label" style="font-size: 12px; font-weight: bold; color: #666;">次数</span></div>';

        if (!records || records.length === 0) {
            infoGrid.innerHTML = headerHtml + '<div class="info-item"><span class="info-label">暂无记录</span><span class="info-value"></span></div>';
            return;
        }

        var html = headerHtml;
        for (var j = 0; j < records.length; j++) {
            var r = records[j];
            var name = r.name || '未知';
            var desc = r.desc || '';
            var count = r.count || 0;

            html += '<div class="info-item">';
            if (desc) {
                html += '<div style="display: flex; align-items: flex-start; justify-content: space-between; width: 100%; gap: 8px;">';
                html += '<div style="flex: 1; min-width: 0;">';
                html += '<span class="record-name-badge">' + escapeHtml(name) + '</span>';
                html += '<div class="record-desc">' + escapeHtml(desc) + '</div>';
                html += '</div>';
                html += '<span class="info-value" style="flex-shrink: 0;"><span class="badge badge-success" style="font-size: 11px;">' + count + '</span></span>';
                html += '</div>';
            } else {
                html += '<span class="info-label" style="font-size: 13px;">' + escapeHtml(name) + '：</span>';
                html += '<span class="info-value"><span class="badge badge-success" style="font-size: 11px;">' + count + '</span></span>';
            }
            html += '</div>';
        }
        infoGrid.innerHTML = html;
    }

    /**
     * 覆盖重置按钮行为
     */
    function overrideResetButton() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (resetBtn) {
            // 移除原有事件（通过克隆节点）
            var newBtn = resetBtn.cloneNode(true);
            resetBtn.parentNode.replaceChild(newBtn, resetBtn);

            newBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm('确定要重置靶场数据吗？所有成就记录和上传文件将被清除。')) {
                    fetch('api/reset.php', { method: 'POST' })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('重置失败：' + data.message);
                            }
                        }).catch(function () {
                            alert('重置请求失败');
                        });
                }
            });
        }
    }

    /**
     * HTML 转义
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    return {
        init: function () {
            bindEvents();
            loadTemplate('templates/default.php');
            refreshAchievementStatus();
        },
        loadTemplate: loadTemplate,
        uploadFile: uploadFile,
        includeUploadedFile: includeUploadedFile
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    HeaSec.LfiAdv.init();
});
