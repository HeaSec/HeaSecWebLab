/**
 * HeaSec天积安全团队 - JavaScript语言基础靶场JavaScript文件
 * 版本: v1.1.0
 * 创建日期: 2025-12-16
 * 更新日期: 2025-12-30
 * 团队: 天积安全 (HeavenlySecret)
 * 优化：代码编辑器懒加载，仅在展开时加载
 */

// 懒加载代码编辑器
function lazyLoadCodeEditor(placeholderElement) {
    // 检查是否已加载
    if (placeholderElement.getAttribute('data-loaded') === 'true') {
        return;
    }

    // 标记为正在加载
    placeholderElement.setAttribute('data-loaded', 'loading');

    // 获取配置数据
    const configData = placeholderElement.getAttribute('data-config');
    if (!configData) {
        return;
    }

    const config = JSON.parse(configData);

    // 更新占位符为加载中状态
    const loadingDiv = placeholderElement.querySelector('.heasec-code-editor-loading');
    if (loadingDiv) {
        loadingDiv.innerHTML = '<i class="fa fa-spinner fa-spin"></i><span>加载编辑器中...</span>';
    }

    // 构建完整的编辑器HTML（与PHP的renderCodeEditor函数输出相同的结构）
    const componentId = placeholderElement.id.replace('_placeholder', '');
    const editorId = 'codeEditor_' + componentId;
    const previewId = 'codePreview_' + componentId;

    // 获取默认代码
    let defaultCode = '';
    if (typeof config.defaultCode === 'string') {
        defaultCode = config.defaultCode;
    } else if (config.defaultCode && config.defaultCode[config.defaultLanguage]) {
        defaultCode = config.defaultCode[config.defaultLanguage];
    }

    // 转义HTML特殊字符
    const escapedCode = defaultCode.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    const editorHTML = `
    <div class="heasec-code-editor" id="${componentId}" data-theme="${config.theme}" data-auto-height="${config.autoHeight}">
        <div class="heasec-code-editor-header">
            <h3>
                <i class="${config.cardIcon}"></i>
                ${config.cardTitle}
            </h3>
        </div>

        <div class="heasec-code-editor-body">
            <div class="heasec-code-editor-main">
                <div class="heasec-code-editor-pane heasec-editor-pane">
                    <div class="heasec-editor-header">
                        <span class="heasec-editor-title">
                            <i class="fa fa-code"></i>
                            原始代码
                        </span>
                        <div class="heasec-editor-header-buttons">
                            <button class="heasec-editor-actions" onclick="HeaSecCodeEditor.toggleFullscreen('${componentId}')" title="放大/缩小">
                                <i class="fa fa-search-plus"></i>
                            </button>
                            <button class="heasec-editor-actions" onclick="HeaSecCodeEditor.runCode('${componentId}')" title="运行代码">
                                <i class="fa fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="heasec-code-editor-container">
                        <div class="heasec-code-editor-wrapper">
                            <div class="heasec-line-numbers" id="${editorId}_linenumbers">
                                <div class="heasec-line-number">1</div>
                            </div>
                            <textarea
                                id="${editorId}"
                                class="heasec-code-textarea"
                                spellcheck="false"
                                autocomplete="off"
                                autocorrect="off"
                                autocapitalize="off"
                                data-language="${config.defaultLanguage}"
                                placeholder="在这里输入你的代码...">${escapedCode}</textarea>
                            <pre class="heasec-code-highlight" id="${editorId}_highlight" aria-hidden="true"><code></code></pre>
                        </div>
                    </div>
                </div>

                <div class="heasec-code-editor-divider"></div>

                <div class="heasec-code-editor-pane heasec-preview-pane">
                    <div class="heasec-preview-header">
                        <span class="heasec-preview-title">
                            <i class="fa fa-eye"></i>
                            预览效果
                        </span>
                        <button class="heasec-preview-refresh" onclick="HeaSecCodeEditor.refreshPreview('${componentId}')" title="刷新预览">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                    <div class="heasec-preview-content">
                        <iframe id="${previewId}" class="heasec-preview-frame" frameborder="0"></iframe>
                    </div>
                </div>
            </div>

            <div class="heasec-code-editor-actions">
                <button type="button" class="heasec-btn heasec-btn-primary" onclick="HeaSecCodeEditor.runCode('${componentId}')">
                    <i class="${config.runButtonIcon}"></i>
                    ${config.runButtonText}
                </button>
                <button type="button" class="heasec-btn heasec-btn-secondary" onclick="HeaSecCodeEditor.clearCode('${componentId}')">
                    <i class="${config.clearButtonIcon}"></i>
                    ${config.clearButtonText}
                </button>
                <button type="button" class="heasec-btn heasec-btn-secondary" onclick="HeaSecCodeEditor.resetCode('${componentId}')">
                    <i class="${config.resetButtonIcon}"></i>
                    ${config.resetButtonText}
                </button>
            </div>
        </div>
    </div>`;

    // 替换占位符为完整的编辑器
    placeholderElement.outerHTML = editorHTML;

    // 等待DOM更新后初始化编辑器
    setTimeout(function() {
        if (window.HeaSecCodeEditor) {
            // 初始化编辑器
            window.HeaSecCodeEditor.init(componentId, {
                editorId: editorId,
                previewId: previewId,
                height: config.height,
                fontSize: config.fontSize,
                theme: config.theme,
                syntaxHighlighting: config.syntaxHighlighting,
                autoHeight: config.autoHeight,
                minHeight: config.minHeight,
                maxHeight: config.maxHeight,
                languages: config.languages,
                defaultLanguage: config.defaultLanguage,
                defaultCode: config.defaultCode,
                layout: config.layout,
                splitRatio: config.splitRatio
            });
        }
    }, 50);
}

// 可折叠区域切换函数
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const isExpanding = !section.classList.contains('expanded');

    section.classList.toggle('expanded');

    // 如果是展开区域，处理懒加载编辑器
    if (isExpanding) {
        // 查找该区域内所有懒加载占位符
        const placeholders = section.querySelectorAll('.heasec-code-editor-placeholder:not([data-loaded="true"]):not([data-loaded="loading"])');
        placeholders.forEach(function(placeholder) {
            lazyLoadCodeEditor(placeholder);
        });

        // 如果区域内已有已加载的编辑器，需要等待CSS动画完成后重新调整高度
        setTimeout(function() {
            const codeEditors = section.querySelectorAll('[id^="heasec_code_editor_"]');
            codeEditors.forEach(function(editorElement) {
                if (editorElement.id && window.HeaSecCodeEditor && window.HeaSecCodeEditor.editors && window.HeaSecCodeEditor.editors[editorElement.id]) {
                    window.HeaSecCodeEditor._adjustHeight(editorElement.id);
                }
            });
        }, 300); // 等待CSS动画完成
    }
}

// 显示掌握恭喜消息功能
function showMasteryCongrats() {
    // 确保星星系统组件已加载
    if (typeof HeaSecCongratsModal !== 'undefined') {
        // 显示恭喜消息弹窗
        HeaSecCongratsModal.show({
            title: '🎉 恭喜你掌握了一个新技能',
            message: '你理解了JavaScript的基本语法！',
            buttonText: '继续学习',
            showParticles: true,
            particleCount: 10,
            animationDuration: 2500,
            enableNextRangeButton: true,    // 启用下一靶场按钮
            rangeCode: 'javascript',        // 当前靶场代码
            updateStatusApiUrl: heasecConfig.commonBasePath + 'api/update-learning-status.php',
            nextRangeApiUrl: heasecConfig.commonBasePath + 'api/next-range.php',
            onClose: function() {
                // 弹窗关闭回调
            },
            onContinue: function() {
                // 更新学习状态为已掌握
                updateLearningStatus();
            }
        });
    } else {
        // 如果星星系统组件未加载，显示简单的alert
        alert('🎉 恭喜你掌握了JavaScript基础\n\n你已经掌握了JavaScript的基本语法和常用操作！\n\n系统正在记录你的学习状态...');
        // 更新学习状态
        updateLearningStatus();
    }
}

// 更新学习状态功能
function updateLearningStatus() {
    // 发送请求更新学习状态
    const xhr = new XMLHttpRequest();
    xhr.open('POST', heasecConfig.commonBasePath + 'api/update-learning-status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // 显示成功提示
                        showSuccessMessage('学习状态已更新为"已掌握"');
                        // 禁用掌握按钮，避免重复点击
                        const masteryBtn = document.getElementById('javascriptMasteryBtn');
                        if (masteryBtn) {
                            masteryBtn.disabled = true;
                            masteryBtn.innerHTML = '<i class="fa fa-check"></i> 已掌握';
                            masteryBtn.style.opacity = '0.7';
                            masteryBtn.style.cursor = 'not-allowed';
                        }
                    } else {
                        showErrorMessage('学习状态更新失败，请稍后重试');
                    }
                } catch (e) {
                    showErrorMessage('服务器响应异常，请稍后重试');
                }
            } else {
                showErrorMessage('请求失败，请检查网络连接');
            }
        }
    };
    xhr.onerror = function() {
        showErrorMessage('网络连接失败，请稍后重试');
    };

    // 使用JSON格式发送数据
    const data = JSON.stringify({
        code: 'javascript',
        status: '已掌握',
        timestamp: Date.now()
    });
    xhr.send(data);
}

// 显示成功消息
function showSuccessMessage(message) {
    // 创建成功提示元素
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success heasec-alert';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;';
    alertDiv.innerHTML = '<i class="fa fa-check-circle"></i> ' + message;
    document.body.appendChild(alertDiv);

    // 3秒后自动移除
    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

// 显示错误消息
function showErrorMessage(message) {
    // 创建错误提示元素
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger heasec-alert';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;';
    alertDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + message;
    document.body.appendChild(alertDiv);

    // 5秒后自动移除
    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}


// 页面加载时默认展开第一个区域
document.addEventListener('DOMContentLoaded', function() {
    // Prism.js会自动处理语法高亮，无需手动调用

    // 等待所有代码编辑器初始化完成后再调整高度
    setTimeout(function() {
        if (window.HeaSecCodeEditor) {
            // 对所有代码编辑器进行高度调整
            for (let editorId in window.HeaSecCodeEditor.editors) {
                window.HeaSecCodeEditor._adjustHeight(editorId);
            }
        }
    }, 100); // 给编辑器初始化留出时间

    // 可以默认展开第一个区域，其他区域折叠
    // 如果需要默认展开所有区域，可以取消下面的注释
    /*
    for (let i = 1; i <= 7; i++) {
        const section = document.getElementById('section' + i);
        if (section) {
            section.classList.add('expanded');
            // 展开后也需要调整高度
            setTimeout(function() {
                const codeEditors = section.querySelectorAll('[id^="heasec_code_editor_"]');
                codeEditors.forEach(function(editorElement) {
                    if (editorElement.id && window.HeaSecCodeEditor.editors[editorElement.id]) {
                        window.HeaSecCodeEditor._adjustHeight(editorElement.id);
                    }
                });
            }, 300);
        }
    }
    */
});