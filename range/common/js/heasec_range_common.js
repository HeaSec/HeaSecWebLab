/**
 * HeaSec天积安全团队 - 靶场公共JavaScript功能
 * Common JavaScript Functions for Ranges
 * 版本: v1.0.0
 * 创建日期: 2025-10-26
 * 团队: 天积安全 (HeavenlySecret)
 */

// 弹窗控制功能
document.addEventListener('DOMContentLoaded', function () {
    // [HeaSec Log Cleanup - 2025-11-22]
    // console.log('HeaSec靶场公共脚本加载完成 - 天积安全团队');

    // 获取DOM元素
    const rangeInfoBtn = document.getElementById('rangeInfoBtn');
    // 移除旧的模态框DOM引用，现在使用动态创建的模态框

    // 添加页面加载动画
    document.body.style.opacity = '0';
    setTimeout(function () {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    }, 100);

    // 弹窗控制函数
    function showModal() {
        // 使用新的模态框管理器
        if (window.heasecModalManager) {
            window.heasecModalManager.showRangeInfo({
                onLoadContent: function (modalContent) {
                    loadMarkdownContent(modalContent);
                }
            });
        } else {
            console.warn('[HeaSec] 模态框管理器未初始化');
        }
    }

    function hideModal() {
        // 使用新的模态框管理器
        if (window.heasecModalManager) {
            window.heasecModalManager.hideModal('range_info');
        }
    }

    // 简单的Markdown解析器（优化版 - 支持换行）
    function parseMarkdown(markdown) {
        // 先提取代码块，避免内部内容被处理
        const codeBlocks = [];
        let html = markdown.replace(/```(\w+)?\n([\s\S]*?)```/g, function (match, lang, code) {
            const placeholder = '___CODE_BLOCK_' + codeBlocks.length + '___';
            codeBlocks.push('<pre><code>' + code.trim() + '</code></pre>');
            return placeholder;
        });

        // 处理标题（需要独立成行）
        html = html.replace(/^# (.*$)/gim, '\n<h1>$1</h1>\n');
        html = html.replace(/^## (.*$)/gim, '\n<h2>$1</h2>\n');
        html = html.replace(/^### (.*$)/gim, '\n<h3>$1</h3>\n');
        html = html.replace(/^#### (.*$)/gim, '\n<h4>$1</h4>\n');

        // 处理引用（整行引用）
        html = html.replace(/^> (.*$)/gim, '<blockquote>$1</blockquote>');

        // 处理无序列表
        html = html.replace(/^[\-\*] (.+)$/gim, '<li>$1</li>');

        // 处理有序列表
        html = html.replace(/^\d+\. (.+)$/gim, '<li>$1</li>');

        // 合并连续的列表项
        html = html.replace(/(<li>.*<\/li>)\n(?=<li>)/g, '$1');

        // 处理粗体和斜体
        html = html.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
        html = html.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
        html = html.replace(/___(.+?)___/g, '<strong><em>$1</em></strong>');
        html = html.replace(/__([^\n]+)__/g, '<strong>$1</strong>');
        html = html.replace(/_([^\n]+)_/g, '<em>$1</em>');

        // 处理行内代码
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

        // 处理链接
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

        // 处理表格（简单支持）
        html = html.replace(/\|(.+)\|/g, function (match, content) {
            if (content.includes('---')) return ''; // 跳过分隔行
            const cells = content.split('|').filter(c => c.trim());
            return '<tr>' + cells.map(cell => `<td>${cell.trim()}</td>`).join('') + '</tr>';
        });
        // 包裹表格行
        html = html.replace(/(<tr>(?!.*<tr>).+<\/tr>)/gs, '<table>$1</table>');

        // 恢复代码块
        html = html.replace(/___CODE_BLOCK_(\d+)___/g, function (match, index) {
            return '\n' + codeBlocks[index] + '\n';
        });

        // 处理段落和换行
        // 双换行表示段落分隔，单换行转换为<br>
        const lines = html.split('\n');
        const result = [];
        let inParagraph = false;
        let paragraphContent = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();

            // 空行：结束当前段落
            if (line === '') {
                if (inParagraph) {
                    result.push('<p>' + paragraphContent.join('<br>') + '</p>');
                    paragraphContent = [];
                    inParagraph = false;
                }
                continue;
            }

            // HTML块元素（标题、列表、引用、代码块、表格）独立成行
            if (line.match(/^<(h[1-6]|ul|\/ul|li|blockquote|pre|code|table|tr)/)) {
                // 结束当前段落
                if (inParagraph) {
                    result.push('<p>' + paragraphContent.join('<br>') + '</p>');
                    paragraphContent = [];
                    inParagraph = false;
                }
                // 块元素独立输出
                result.push(line);
                continue;
            }

            // 普通文本行：加入段落内容
            inParagraph = true;
            paragraphContent.push(line);
        }

        // 处理最后一个段落
        if (inParagraph) {
            result.push('<p>' + paragraphContent.join('<br>') + '</p>');
        }

        html = result.join('\n');

        // 清理多余的标签
        html = html.replace(/<p><(h[1-6]|ul|li|blockquote|pre|table)/g, '<$1');
        html = html.replace(/<\/(h[1-6]|ul|li|blockquote|pre|table)><\/p>/g, '</$1>');
        html = html.replace(/<p><\/p>/g, ''); // 移除空段落

        return html;
    }

    // 加载Markdown内容（支持多级目录查找）
    function loadMarkdownContent(targetModalContent = null) {
        const contentContainer = targetModalContent || modalContent;
        if (!contentContainer) return;

        // 显示加载指示器
        contentContainer.innerHTML = `
            <div class="loading-indicator">
                <i class="fa fa-spinner fa-spin"></i>
                正在加载内容...
            </div>
        `;

        // 生成可能的路径列表（从当前目录向上查找）
        const possiblePaths = [];
        let pathPrefix = '';
        const currentPath = window.location.pathname;
        const depth = (currentPath.match(/\//g) || []).length - (currentPath.endsWith('/') ? 1 : 0);

        for (let i = 0; i <= depth; i++) {
            possiblePaths.push(pathPrefix + 'readme.md');
            pathPrefix += '../';
        }

        // 按顺序尝试加载 readme.md
        tryLoadFromPaths(possiblePaths, 0, contentContainer);
    }

    // 递归尝试从多个路径加载文件
    function tryLoadFromPaths(paths, index, container) {
        if (index >= paths.length) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                    <h3>加载失败</h3>
                    <p>无法加载靶场说明文档，请稍后重试。</p>
                    <p style="font-size: 14px; color: #6c757d; margin-top: 15px;">错误信息: 在所有可能的路径中都未找到 readme.md 文件</p>
                </div>
            `;
            return;
        }

        fetch(paths[index])
            .then(response => {
                if (!response.ok) {
                    throw new Error('not found');
                }
                return response.text();
            })
            .then(markdownText => {
                const htmlContent = parseMarkdown(markdownText);
                container.innerHTML = htmlContent;
                enhanceCodeBlocks(container);
            })
            .catch(() => {
                tryLoadFromPaths(paths, index + 1, container);
            });
    }

    // 增强代码块样式 - 添加复制功能
    function enhanceCodeBlocks(targetModalContent = null) {
        const contentContainer = targetModalContent || modalContent;
        if (!contentContainer) return;

        const codeBlocks = contentContainer.querySelectorAll('pre code');
        codeBlocks.forEach(block => {
            // 添加复制按钮
            const pre = block.parentElement;
            const copyBtn = document.createElement('button');
            copyBtn.className = 'code-copy-btn';
            copyBtn.innerHTML = '<i class="fa fa-copy"></i> 复制';
            copyBtn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: #007bff;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 12px;
                cursor: pointer;
                z-index: 10;
                transition: all 0.3s ease;
            `;

            pre.style.position = 'relative';
            pre.appendChild(copyBtn);

            copyBtn.addEventListener('click', function () {
                navigator.clipboard.writeText(block.textContent).then(() => {
                    copyBtn.innerHTML = '<i class="fa fa-check"></i> 已复制';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="fa fa-copy"></i> 复制';
                    }, 2000);
                }).catch(() => {
                    // 降级方案
                    const textArea = document.createElement('textarea');
                    textArea.value = block.textContent;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    copyBtn.innerHTML = '<i class="fa fa-check"></i> 已复制';
                    setTimeout(() => {
                        copyBtn.innerHTML = '<i class="fa fa-copy"></i> 复制';
                    }, 2000);
                });
            });

            copyBtn.addEventListener('mouseenter', function () {
                this.style.background = '#0056b3';
            });

            copyBtn.addEventListener('mouseleave', function () {
                this.style.background = '#007bff';
            });
        });
    }

    // 事件监听器
    if (rangeInfoBtn) {
        rangeInfoBtn.addEventListener('click', showModal);
    }



    // 智能标题处理 - 防止重叠
    function adjustTitleDisplay() {
        const headerContent = document.querySelector('.header-content');
        const titleSection = document.querySelector('.title-section');
        const mainTitle = document.querySelector('.main-title');
        const logoSection = document.querySelector('.logo-section');
        const versionSection = document.querySelector('.version-section');

        if (!headerContent || !titleSection || !mainTitle) return;

        const windowWidth = window.innerWidth;
        const headerWidth = headerContent.offsetWidth;
        const logoWidth = logoSection ? logoSection.offsetWidth : 60;
        const versionWidth = versionSection ? versionSection.offsetWidth : 140;
        const availableWidth = headerWidth - logoWidth - versionWidth - 40; // 40px for gaps

        // 获取标题文本的实际宽度
        const titleText = mainTitle.textContent;
        const tempSpan = document.createElement('span');
        tempSpan.style.cssText = `
            position: absolute;
            visibility: hidden;
            white-space: nowrap;
            font-size: ${getComputedStyle(mainTitle).fontSize};
            font-weight: ${getComputedStyle(mainTitle).fontWeight};
            font-family: ${getComputedStyle(mainTitle).fontFamily};
            letter-spacing: ${getComputedStyle(mainTitle).letterSpacing};
        `;
        tempSpan.textContent = titleText;
        document.body.appendChild(tempSpan);
        const titleWidth = tempSpan.offsetWidth;
        document.body.removeChild(tempSpan);

        // 如果标题太长，调整字体大小或添加省略号
        if (titleWidth > availableWidth) {
            const maxFontSize = parseInt(getComputedStyle(mainTitle).fontSize);
            const scaleFactor = availableWidth / titleWidth;
            const newFontSize = Math.max(maxFontSize * scaleFactor, 12); // 最小12px

            if (newFontSize < maxFontSize - 2) {
                // 如果字体缩小太多，则使用省略号
                mainTitle.style.fontSize = maxFontSize + 'px';
                const maxLength = Math.floor(titleText.length * scaleFactor * 0.9);
                if (maxLength < titleText.length) {
                    mainTitle.textContent = titleText.substring(0, maxLength) + '...';
                }
            } else {
                // 适当缩小字体
                mainTitle.style.fontSize = newFontSize + 'px';
            }
        }

        // [HeaSec Log Cleanup - 2025-11-22]
        // 记录调试信息
        // console.log(`标题调整信息 - 窗口: ${windowWidth}px, 可用宽度: ${availableWidth}px, 标题宽度: ${titleWidth}px`);
    }

    // 页面加载时调整
    adjustTitleDisplay();

    // 窗口大小改变时重新调整
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustTitleDisplay, 100);
    });

    // 每5秒检查一次，确保布局正常
    setInterval(adjustTitleDisplay, 5000);
});





// 显示通知消息（替代alert）
function showNotification(message, type) {
    type = type || 'info';

    // 创建通知元素
    var notification = document.createElement('div');
    notification.className = 'heasec-notification heasec-notification-' + type;
    notification.innerHTML = message;

    // 设置样式
    notification.style.cssText = [
        'position: fixed',
        'top: 20px',
        'right: 20px',
        'background: ' + getNotificationColor(type),
        'color: white',
        'padding: 15px 20px',
        'border-radius: 8px',
        'box-shadow: 0 4px 12px rgba(0,0,0,0.15)',
        'z-index: 10000',
        'max-width: 300px',
        'font-size: 14px',
        'opacity: 0',
        'transform: translateX(100%)',
        'transition: all 0.3s ease'
    ].join(';');

    // 添加到页面
    document.body.appendChild(notification);

    // 显示动画
    setTimeout(function () {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

    // 自动隐藏
    setTimeout(function () {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(function () {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// 获取通知颜色
function getNotificationColor(type) {
    var colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    return colors[type] || colors.info;
}

// 全局函数 - 可以被其他脚本调用
window.HeaSecRange = {
    // 显示弹窗
    showModal: function () {
        const modal = document.getElementById('rangeInfoModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    // 隐藏弹窗
    hideModal: function () {
        const modal = document.getElementById('rangeInfoModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    },

    // 解析Markdown（优化版 - 支持换行）
    parseMarkdown: function (markdown) {
        // 先提取代码块，避免内部内容被处理
        const codeBlocks = [];
        let html = markdown.replace(/```(\w+)?\n([\s\S]*?)```/g, function (match, lang, code) {
            const placeholder = '___CODE_BLOCK_' + codeBlocks.length + '___';
            codeBlocks.push('<pre><code>' + code.trim() + '</code></pre>');
            return placeholder;
        });

        // 处理标题（需要独立成行）
        html = html.replace(/^# (.*$)/gim, '\n<h1>$1</h1>\n');
        html = html.replace(/^## (.*$)/gim, '\n<h2>$1</h2>\n');
        html = html.replace(/^### (.*$)/gim, '\n<h3>$1</h3>\n');
        html = html.replace(/^#### (.*$)/gim, '\n<h4>$1</h4>\n');

        // 处理引用（整行引用）
        html = html.replace(/^> (.*$)/gim, '<blockquote>$1</blockquote>');

        // 处理无序列表
        html = html.replace(/^[\-\*] (.+)$/gim, '<li>$1</li>');

        // 处理有序列表
        html = html.replace(/^\d+\. (.+)$/gim, '<li>$1</li>');

        // 合并连续的列表项
        html = html.replace(/(<li>.*<\/li>)\n(?=<li>)/g, '$1');

        // 处理粗体和斜体
        html = html.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
        html = html.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
        html = html.replace(/___(.+?)___/g, '<strong><em>$1</em></strong>');
        html = html.replace(/__([^\n]+)__/g, '<strong>$1</strong>');
        html = html.replace(/_([^\n]+)_/g, '<em>$1</em>');

        // 处理行内代码
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

        // 处理链接
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

        // 处理表格（简单支持）
        html = html.replace(/\|(.+)\|/g, function (match, content) {
            if (content.includes('---')) return ''; // 跳过分隔行
            const cells = content.split('|').filter(c => c.trim());
            return '<tr>' + cells.map(cell => `<td>${cell.trim()}</td>`).join('') + '</tr>';
        });
        // 包裹表格行
        html = html.replace(/(<tr>(?!.*<tr>).+<\/tr>)/gs, '<table>$1</table>');

        // 恢复代码块
        html = html.replace(/___CODE_BLOCK_(\d+)___/g, function (match, index) {
            return '\n' + codeBlocks[index] + '\n';
        });

        // 处理段落和换行
        // 双换行表示段落分隔，单换行转换为<br>
        const lines = html.split('\n');
        const result = [];
        let inParagraph = false;
        let paragraphContent = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();

            // 空行：结束当前段落
            if (line === '') {
                if (inParagraph) {
                    result.push('<p>' + paragraphContent.join('<br>') + '</p>');
                    paragraphContent = [];
                    inParagraph = false;
                }
                continue;
            }

            // HTML块元素（标题、列表、引用、代码块、表格）独立成行
            if (line.match(/^<(h[1-6]|ul|\/ul|li|blockquote|pre|code|table|tr)/)) {
                // 结束当前段落
                if (inParagraph) {
                    result.push('<p>' + paragraphContent.join('<br>') + '</p>');
                    paragraphContent = [];
                    inParagraph = false;
                }
                // 块元素独立输出
                result.push(line);
                continue;
            }

            // 普通文本行：加入段落内容
            inParagraph = true;
            paragraphContent.push(line);
        }

        // 处理最后一个段落
        if (inParagraph) {
            result.push('<p>' + paragraphContent.join('<br>') + '</p>');
        }

        html = result.join('\n');

        // 清理多余的标签
        html = html.replace(/<p><(h[1-6]|ul|li|blockquote|pre|table)/g, '<$1');
        html = html.replace(/<\/(h[1-6]|ul|li|blockquote|pre|table)><\/p>/g, '</$1>');
        html = html.replace(/<p><\/p>/g, ''); // 移除空段落

        return html;
    }
};

/**
 * HeaSec天积安全团队 - 保守式模态框管理器
 * Conservative Modal Manager
 * 版本: v1.0.0
 * 创建日期: 2025-11-26
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 注意：此组件完全独立，不修改任何现有功能
 * 仅提供统一的模态框创建和管理能力
 */

// 新增：HeaSecModalManager 类（完全独立，不影响现有功能）
window.HeaSecModalManager = class {
    constructor() {
        this.modals = new Map();
        // 完全复制现有的配置和行为
        this.defaultConfig = {
            showCloseButton: true,
            closeOnOverlay: false,
            closeOnEscape: true,
            animationDuration: 300 // 保持现有动画时长
        };

        // 模态框模板 - 完全复制现有的HTML结构
        this.templates = {
            reset_confirm: {
                id: 'resetModal',
                title: '确认重置靶场',
                showClose: true,
                content: `
                    <div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404;">
                        <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>此操作将清空所有靶场数据！</strong>
                        </div>
                    </div>
                    <p style="margin-bottom: 16px;">确认要重置靶场吗？此操作将：</p>
                    <ul style="margin-bottom: 16px; padding-left: 20px;">
                        <li>清空所有用户数据</li>
                        <li>重置成就系统进度</li>
                        <li>清空历史记录</li>
                    </ul>
                    <p style="font-size: 13px; color: #6c757d; margin: 0;">
                        <i class="fa fa-info-circle"></i> 仅影响当前靶场，不会影响其他系统。
                    </p>
                `,
                footer: `
                    <button class="btn btn-secondary modal-cancel">
                        <i class="fa fa-times"></i>
                        取消
                    </button>
                    <button class="btn btn-danger modal-confirm">
                        <i class="fa fa-refresh"></i>
                        确认重置
                    </button>
                `
            },
            no_database: {
                id: 'noDatabaseModal',
                title: '靶场说明',
                showClose: true,
                content: `
                    <div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460;">
                        <div>
                            <i class="fa fa-info-circle"></i>
                            <strong>本靶场未使用数据库</strong>
                        </div>
                    </div>
                    <p style="margin-bottom: 16px;">本靶场不需要数据库支持，无需进行重置操作。</p>
                `,
                footer: `
                    <button class="btn btn-primary modal-confirm">
                        <i class="fa fa-check"></i>
                        知道了
                    </button>
                `
            },
            success_message: {
                id: 'successModal',
                title: '操作成功',
                showClose: false,
                content: `
                    <div class="text-center">
                        <i class="fa fa-check-circle success-icon" style="font-size: 48px; color: #28a745; margin: 20px 0;"></i>
                        <p class="success-message-content" style="margin: 0; font-size: 16px; color: #333;">重置成功</p>
                    </div>
                `,
                footer: `
                    <button class="btn btn-primary modal-confirm">
                        确定
                    </button>
                `
            },
            database_status: {
                id: 'databaseStatusModal',
                title: '数据库状态',
                showClose: true,
                content: '', // 动态内容
                footer: '' // 动态内容
            },
            range_info: {
                id: 'rangeInfoModal',
                title: '靶场说明',
                showClose: true,
                content: `
                    <!-- Markdown内容将在这里动态加载 -->
                    <div class="loading-indicator">
                        <i class="fa fa-spinner fa-spin"></i>
                        正在加载内容...
                    </div>
                `,
                footer: `
                    <button class="btn btn-primary modal-confirm">
                        <i class="fa fa-check"></i>
                        我知道了
                    </button>
                `
            }
        };
    }

    // 核心：完全复制现有模态框的HTML结构和样式
    createModalFromTemplate(type, data = {}) {
        const template = this.templates[type];
        if (!template) {
            console.error('[HeaSec] 未知的模态框类型:', type);
            return null;
        }

        // 完全按照现有的HTML结构创建模态框
        const modalHtml = `
            <div class="heasec-modal" id="${template.id}" style="display: none;">
                <div class="modal-overlay"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h3 class="modal-title">${template.title}</h3>
                        ${template.showClose ? `
                            <button class="modal-close">
                                <i class="fa fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                    <div class="modal-content">
                        ${data.content || template.content}
                    </div>
                    <div class="modal-footer">
                        ${data.footer || template.footer}
                    </div>
                </div>
            </div>
        `;

        // 创建DOM元素
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = modalHtml.trim();
        const modal = tempDiv.firstElementChild;

        // 添加到页面（但不显示）
        document.body.appendChild(modal);

        // 存储模态框引用
        this.modals.set(type, modal);

        return modal;
    }

    // 完全兼容现有的事件处理逻辑
    bindEvents(modal, config = {}) {
        // 确保模态框已经添加到DOM中
        if (!modal || !modal.parentNode) {
            console.warn('[HeaSec] 模态框未添加到DOM中，跳过事件绑定');
            return;
        }

        const overlay = modal.querySelector('.modal-overlay');
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('.modal-cancel');
        const confirmBtn = modal.querySelector('.modal-confirm');

        // ESC键关闭
        if (config.closeOnEscape !== false) {
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    this.hideModal(modal);
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        }

        // 点击遮罩关闭
        if (overlay && config.closeOnOverlay !== false) {
            overlay.addEventListener('click', () => this.hideModal(modal));
        }

        // 关闭按钮
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideModal(modal));
        }

        // 取消按钮
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hideModal(modal));
        }

        // 确认按钮回调
        if (confirmBtn) {
            confirmBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                console.log('[HeaSec] 确认按钮被点击');

                if (config.onConfirm) {
                    const result = config.onConfirm(modal);
                    if (result !== false) {
                        this.hideModal(modal);
                    }
                } else {
                    // 默认行为：关闭模态框
                    this.hideModal(modal);
                }
            });
        }
    }

    // 保持现有动画效果的方法 - 完全复制现有的显示动画
    animateShow(modal) {
        // 使用现有的显示方式，保持完全一致
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // 保持现有动画效果的方法 - 完全复制现有的隐藏动画
    animateHide(modal) {
        // 使用现有的隐藏方式，保持完全一致
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // 显示模态框
    showModal(type, config = {}) {
        let modal = this.modals.get(type);

        // 如果模态框不存在，创建它
        if (!modal) {
            modal = this.createModalFromTemplate(type, config);
            if (!modal) return false;

            // 只在新创建时绑定事件，避免重复绑定
            this.bindEvents(modal, config);
        }

        // 显示模态框
        this.animateShow(modal);

        return modal;
    }

    // 隐藏模态框
    hideModal(typeOrModal) {
        let modal;

        if (typeof typeOrModal === 'string') {
            modal = this.modals.get(typeOrModal);
        } else if (typeOrModal && typeOrModal.nodeType) {
            modal = typeOrModal;
        }

        if (modal) {
            this.animateHide(modal);
            return true;
        }

        return false;
    }

    // 便捷方法：显示重置确认模态框
    showResetConfirm(config = {}) {
        return this.showModal('reset_confirm', {
            onConfirm: (modal) => {
                if (config.onConfirm) {
                    return config.onConfirm(modal);
                }
                // 默认处理：发送重置请求
                return this.handleResetAction(modal, config);
            },
            ...config
        });
    }

    // 便捷方法：显示无数据库提示模态框
    showNoDatabaseHint(config = {}) {
        return this.showModal('no_database', config);
    }

    // 便捷方法：显示成功消息模态框
    showSuccessMessage(message, config = {}) {
        return this.showModal('success_message', {
            content: `
                <div class="text-center">
                    <i class="fa fa-check-circle success-icon" style="font-size: 48px; color: #28a745; margin: 20px 0;"></i>
                    <p class="success-message-content" style="margin: 0; font-size: 16px; color: #333;">${message}</p>
                </div>
            `,
            ...config
        });
    }

    // 便捷方法：显示数据库状态模态框
    showDatabaseStatus(status, config = {}) {
        const statusConfig = this.getDatabaseStatusConfig(status, config);
        return this.showModal('database_status', statusConfig);
    }

    // 便捷方法：显示靶场说明模态框
    showRangeInfo(config = {}) {
        const modal = this.showModal('range_info', config);

        // 如果存在自定义内容加载函数，则调用它
        if (config.onLoadContent && modal) {
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                config.onLoadContent(modalContent);
            }
        }

        return modal;
    }

    // 获取数据库状态配置
    getDatabaseStatusConfig(status, config) {
        const configs = {
            connection_failed: {
                title: '数据库连接失败',
                content: `
                    <div class="alert alert-danger" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                        <i class="fa fa-exclamation-circle"></i>
                        <strong>连接错误：</strong>${config.error || '未知错误'}
                    </div>
                    <p>请检查数据库服务是否正常运行，或联系管理员。</p>
                `,
                footer: `
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i class="fa fa-refresh"></i>
                        重新检查
                    </button>
                `
            },
            database_missing: {
                title: '需要创建数据库',
                content: `
                    <div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460;">
                        <i class="fa fa-info-circle"></i>
                        <strong>需要创建数据库</strong>
                    </div>
                    <p>首次访问此靶场，需要创建数据库并初始化数据表。</p>
                `,
                footer: `
                    <button class="btn btn-secondary modal-cancel">
                        <i class="fa fa-times"></i>
                        取消
                    </button>
                    <button class="btn btn-primary modal-confirm">
                        <i class="fa fa-play-circle"></i> 开始初始化
                    </button>
                `
            },
            table_missing: {
                title: '需要初始化靶场',
                content: `
                    <div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460;">
                        <i class="fa fa-info-circle"></i>
                        <strong>需要初始化靶场</strong>
                    </div>
                    <p>数据表不存在，需要初始化数据库以创建必要的数据表。</p>
                `,
                footer: `
                    <button class="btn btn-secondary modal-cancel">
                        <i class="fa fa-times"></i>
                        取消
                    </button>
                    <button class="btn btn-primary modal-confirm">
                        <i class="fa fa-play-circle"></i> 开始初始化
                    </button>
                `
            }
        };

        // 合并配置，确保传递进来的回调函数（如onConfirm）不会被丢失
        const baseConfig = configs[status] || configs.connection_failed;
        return Object.assign({}, baseConfig, config);
    }

    // 处理重置动作
    handleResetAction(modal, config) {
        const confirmBtn = modal.querySelector('.modal-confirm');
        if (!confirmBtn) return false;

        const originalText = confirmBtn.innerHTML;

        // 防止重复点击
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 重置中...';

        const action = config.action || 'reset';
        const url = config.url || (window.location.href + (window.location.href.indexOf('?') === -1 ? '?' : '&') + 'action=' + action);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                this.hideModal(modal);
                if (data.success) {
                    // 触发靶场重置事件，通知各组件清除状态
                    document.dispatchEvent(new CustomEvent('heasec:rangeReset'));
                    this.showSuccessMessage(data.message || '操作成功');
                    if (config.onSuccess) {
                        config.onSuccess(data);
                    }
                    // 默认1.5秒后刷新页面
                    if (config.autoReload !== false) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    alert('操作失败：' + data.message);
                    if (config.onError) {
                        config.onError(data);
                    }
                }
            })
            .catch(error => {
                this.hideModal(modal);
                alert('操作失败：网络错误');
                if (config.onError) {
                    config.onError(error);
                }
            })
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });

        return false; // 不自动关闭模态框，让异步处理决定
    }

    // 销毁模态框
    destroyModal(type) {
        const modal = this.modals.get(type);
        if (modal && modal.parentNode) {
            modal.parentNode.removeChild(modal);
            this.modals.delete(type);
            return true;
        }
        return false;
    }

    // 销毁所有模态框
    destroyAllModals() {
        this.modals.forEach((modal, type) => {
            this.destroyModal(type);
        });
    }
};

// 创建全局实例，但不替换现有功能
window.heasecModalManager = new HeaSecModalManager();

// 兼容性全局函数（可选使用，不影响现有功能）
window.HeaSecModal = {
    showResetConfirm: (config) => window.heasecModalManager.showResetConfirm(config),
    showNoDatabaseHint: (config) => window.heasecModalManager.showNoDatabaseHint(config),
    showSuccessMessage: (message, config) => window.heasecModalManager.showSuccessMessage(message, config),
    showDatabaseStatus: (status, config) => window.heasecModalManager.showDatabaseStatus(status, config),
    showRangeInfo: (config) => window.heasecModalManager.showRangeInfo(config),
    hideModal: (type) => window.heasecModalManager.hideModal(type),
    destroyModal: (type) => window.heasecModalManager.destroyModal(type)
};

/**
 * HeaSec 靶场初始化器
 * 处理重置按钮和数据库状态检查逻辑
 * 从header.php迁移至此，提供统一的初始化接口
 */
window.HeaSecRangeInitializer = class {
    /**
     * 初始化靶场功能
     * @param {Object} config - 配置对象
     * @param {boolean} config.useDatabase - 是否使用数据库
     * @param {string} config.dbStatus - 数据库状态
     * @param {string} config.dbError - 数据库错误信息
     * @param {Object} config.resetConfig - 重置按钮配置（可选）
     */
    static initialize(config = {}) {
        // 默认配置
        const defaultConfig = {
            useDatabase: false,
            dbStatus: 'normal',
            dbError: '',
            resetConfig: {
                action: 'reset',
                autoRefresh: true,
                refreshDelay: 1500
            }
        };

        // 合并配置
        const finalConfig = Object.assign({}, defaultConfig, config);

        // 确保DOM已加载
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this._initializeRange(finalConfig);
            });
        } else {
            this._initializeRange(finalConfig);
        }
    }

    /**
     * 内部初始化方法
     * @private
     */
    static _initializeRange(config) {
        this._initializeResetButton(config);
        this._initializeDatabaseStatus(config);
    }

    /**
     * 初始化重置按钮
     * @private
     */
    static _initializeResetButton(config) {
        const resetBtn = document.getElementById('resetDatabaseBtn');

        if (!resetBtn) {
            return; // 没有重置按钮，直接返回
        }

        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!config.useDatabase) {
                // 不使用数据库，显示提示模态框
                if (window.heasecModalManager) {
                    window.heasecModalManager.showNoDatabaseHint();
                } else {
                    console.warn('[HeaSec] 模态框管理器未初始化');
                }
            } else {
                // 使用数据库，显示确认重置模态框
                if (window.heasecModalManager) {
                    window.heasecModalManager.showResetConfirm({
                        action: config.resetConfig.action,
                        url: config.resetConfig.url || (window.location.href + (window.location.href.indexOf('?') === -1 ? '?' : '&') + 'action=' + config.resetConfig.action),
                        onSuccess: function (data) {
                            if (config.resetConfig.autoRefresh) {
                                setTimeout(() => location.reload(), config.resetConfig.refreshDelay);
                            }
                        },
                        onError: function (error) {
                            console.error('重置失败:', error);
                        }
                    });
                } else {
                    console.warn('[HeaSec] 模态框管理器未初始化');
                }
            }
        });
    }

    /**
     * 初始化数据库状态检查
     * @private
     */
    static _initializeDatabaseStatus(config) {
        // 如果数据库状态正常，不显示模态框
        if (config.dbStatus === 'normal') {
            return;
        }

        // 检查模态框管理器是否可用
        if (!window.heasecModalManager) {
            console.warn('[HeaSec] 模态框管理器未初始化，跳过数据库状态检查');
            return;
        }

        try {
            window.heasecModalManager.showDatabaseStatus(config.dbStatus, {
                error: config.dbError,
                onConfirm: function (modal) {
                    // 处理数据库初始化/修复操作
                    return HeaSecRangeInitializer._handleDatabaseConfirm(modal, config.dbStatus, config);
                }
            });
        } catch (e) {
            console.error('[HeaSec] 数据库状态模态框创建失败:', e);
        }
    }

    /**
     * 处理数据库确认操作
     * @private
     */
    static _handleDatabaseConfirm(modal, dbStatus, config) {
        const confirmBtn = modal.querySelector('.modal-confirm');
        if (!confirmBtn) {
            return false;
        }

        const originalText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

        // 根据状态确定操作类型，优先使用靶场自定义恢复接口
        const action = (dbStatus === 'database_missing' || dbStatus === 'table_missing') ? 'init' : 'reset';
        const customUrl = config && config.resetConfig && typeof config.resetConfig.url === 'string'
            ? config.resetConfig.url.trim()
            : '';
        const url = customUrl || (window.location.href + (window.location.href.indexOf('?') === -1 ? '?' : '&') + 'action=' + action);

        // 执行异步操作
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (window.heasecModalManager) {
                    window.heasecModalManager.hideModal(modal);
                }

                if (data.success) {
                    // 触发靶场重置事件，通知各组件清除状态
                    document.dispatchEvent(new CustomEvent('heasec:rangeReset'));
                    if (window.heasecModalManager) {
                        // 显示成功消息，并在用户点击确认后刷新页面
                        window.heasecModalManager.showSuccessMessage(data.message || '操作成功', {
                            onConfirm: () => {
                                location.reload();
                            }
                        });
                    }
                } else {
                    alert('操作失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('[HeaSec] 数据库初始化失败:', error);
                if (window.heasecModalManager) {
                    window.heasecModalManager.hideModal(modal);
                }
                alert('操作失败：' + error.message);
            })
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });

        return false; // 不自动关闭模态框
    }
};

// 自动初始化：如果页面定义了全局配置对象，则自动初始化
document.addEventListener('DOMContentLoaded', function () {
    if (window.HeaSecConfig && window.HeaSecRangeInitializer) {
        window.HeaSecRangeInitializer.initialize(window.HeaSecConfig);
    }
});