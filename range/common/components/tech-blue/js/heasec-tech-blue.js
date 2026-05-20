/**
 * HeaSec天积安全团队 - 科技蓝UI组件库JavaScript
 * Tech Blue UI Component Library JavaScript
 * 版本: v1.0.0
 * 创建日期: 2025-11-11
 * 团队: 天积安全 (HeavenlySecret)
 * 描述: 科技蓝风格UI组件的交互功能库
 */

// 创建全局对象
window.HeaSec = window.HeaSec || {};
window.HeaSec.TechBlue = {

    /**
     * 配置选项
     */
    config: {
        animations: {
            enabled: true,
            pageLoad: true,
            hoverEffects: true,
            transitions: true
        },
        performance: {
            gpuAcceleration: true,
            reduceMotion: false,
            debounceDelay: 100
        },
        accessibility: {
            keyboardNavigation: true,
            focusManagement: true,
            ariaLabels: true
        }
    },

    /**
     * 组件缓存
     */
    cache: {
        modals: new Map(),
        alerts: new Map(),
        collapses: new Map(),
        animations: new Map()
    },

    /**
     * 初始化函数
     */
    init: function(options = {}) {
        // 合并配置
        this.config = this.deepMerge(this.config, options);

        // 检查用户偏好
        this.checkUserPreferences();

        // 初始化各个组件
        this.initModals();
        this.initAlerts();
        this.initCollapses();
        this.initAnimations();
        this.initButtons();
        this.initForms();
        this.initKeyboardNavigation();

        // 触发初始化完成事件
        this.dispatchEvent('heasec:techblue:initialized', {
            timestamp: Date.now(),
            config: this.config
        });

        // [HeaSec Log Cleanup - 2025-11-22]
    // console.log('[HeaSec TechBlue] 组件库初始化完成 - 天积安全团队 v1.0.0');
    },

    /**
     * 深度合并对象
     */
    deepMerge: function(target, source) {
        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                target[key] = target[key] || {};
                this.deepMerge(target[key], source[key]);
            } else {
                target[key] = source[key];
            }
        }
        return target;
    },

    /**
     * 检查用户偏好
     */
    checkUserPreferences: function() {
        // 检查减少动画偏好
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.config.animations.enabled = false;
            this.config.performance.reduceMotion = true;
        }

        // 检查暗色模式偏好
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        // 监听偏好变化
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
            this.config.animations.enabled = !e.matches;
            this.config.performance.reduceMotion = e.matches;
            this.updateAnimationSettings();
        });
    },

    /**
     * 更新动画设置
     */
    updateAnimationSettings: function() {
        const root = document.documentElement;
        root.style.setProperty('--heasec-tech-animations-enabled', this.config.animations.enabled ? '1' : '0');

        if (this.config.performance.reduceMotion) {
            document.body.classList.add('heasec-tech-no-animations');
        } else {
            document.body.classList.remove('heasec-tech-no-animations');
        }
    },

    /**
     * 初始化模态框组件
     */
    initModals: function() {
        const modals = document.querySelectorAll('.heasec-modal');

        modals.forEach((modal, index) => {
            const modalId = modal.id || `heasec-tech-modal-${index}`;
            modal.id = modalId;

            this.cache.modals.set(modalId, modal);

            // 绑定关闭事件
            const closeBtn = modal.querySelector('.modal-close');
            const overlay = modal.querySelector('.modal-overlay');

            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeModal(modalId));
            }

            if (overlay) {
                overlay.addEventListener('click', () => this.closeModal(modalId));
            }

            // ESC键关闭
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal(modalId);
                }
            };

            modal.addEventListener('keydown', handleEscape);

            // 保存事件处理器引用
            modal._heasecEscapeHandler = handleEscape;
        });
    },

    /**
     * 创建模态框
     */
    createModal: function(title, content, options = {}) {
        const defaults = {
            size: 'medium', // small, medium, large
            closeOnOverlay: true,
            showCloseButton: true,
            footer: null,
            className: '',
            onShow: null,
            onClose: null
        };

        const config = Object.assign(defaults, options);
        const modalId = `heasec-tech-modal-${Date.now()}`;

        const modalHtml = `
            <div class="heasec-modal ${config.className}" id="${modalId}">
                <div class="modal-overlay"></div>
                <div class="modal-container ${config.size ? `modal-${config.size}` : ''}">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        ${config.showCloseButton ? '<button class="modal-close"><i class="fa fa-times"></i></button>' : ''}
                    </div>
                    <div class="modal-content">${content}</div>
                    ${config.footer ? `<div class="modal-footer">${config.footer}</div>` : ''}
                </div>
            </div>
        `;

        // 添加到页面
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // 获取新创建的模态框
        const modal = document.getElementById(modalId);
        this.cache.modals.set(modalId, modal);

        // 重新初始化事件
        this.initModals();

        // 显示模态框
        modal.classList.add('show');

        // 防止页面滚动
        document.body.style.overflow = 'hidden';

        // 触发显示回调
        if (config.onShow) {
            config.onShow(modal);
        }

        // 触发事件
        this.dispatchEvent('heasec:techblue:modal:shown', { modalId, title, config });

        return modalId;
    },

    /**
     * 关闭模态框
     */
    closeModal: function(modalId) {
        const modal = this.cache.modals.get(modalId) || document.getElementById(modalId);

        if (modal) {
            // 移除show类
            modal.classList.remove('show');

            // 添加关闭动画
            modal.style.opacity = '0';

            setTimeout(() => {
                modal.remove();
                this.cache.modals.delete(modalId);
                document.body.style.overflow = '';

                // 触发事件
                this.dispatchEvent('heasec:techblue:modal:closed', { modalId });
            }, 300);
        }
    },

    /**
     * 初始化提示框组件
     */
    initAlerts: function() {
        const alerts = document.querySelectorAll('.heasec-tech-alert');

        alerts.forEach((alert, index) => {
            const alertId = alert.id || `heasec-tech-alert-${index}`;
            alert.id = alertId;

            this.cache.alerts.set(alertId, alert);

            // 绑定关闭事件
            const closeBtn = alert.querySelector('.heasec-tech-alert-close');

            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.closeAlert(alertId));
            }

            // 自动关闭（如果有data-auto-close属性）
            const autoClose = alert.getAttribute('data-auto-close');
            if (autoClose) {
                setTimeout(() => this.closeAlert(alertId), parseInt(autoClose));
            }
        });
    },

    /**
     * 显示提示框
     */
    showAlert: function(message, type = 'info', title = '', options = {}) {
        // 兼容旧调用方式：第4个参数可能是duration数值
        if (typeof options === 'number') {
            options = { duration: options };
        }

        const defaults = {
            duration: 3000, // 默认3秒自动关闭
            dismissible: true,
            icon: false, // 默认不显示图标
            position: 'top-right' // top-left, top-center, top-right
        };

        const config = Object.assign(defaults, options);
        const alertId = `heasec-tech-alert-${Date.now()}`;

        const iconMap = {
            success: 'fa-check-circle',
            warning: 'fa-exclamation-triangle',
            danger: 'fa-times-circle',
            info: 'fa-info-circle'
        };

        const iconHtml = config.icon ? `<i class="fa ${iconMap[type]}"></i>` : '';

        const alertHtml = `
            <div class="heasec-tech-alert heasec-tech-alert-${type}" id="${alertId}" data-auto-close="${config.duration}">
                ${iconHtml ? `<div class="heasec-tech-alert-icon">${iconHtml}</div>` : ''}
                <div class="heasec-tech-alert-content">
                    ${title ? `<div class="heasec-tech-alert-title">${title}</div>` : ''}
                    <div class="heasec-tech-alert-message">${message}</div>
                </div>
                ${config.dismissible ? '<button class="heasec-tech-alert-close"><i class="fa fa-times"></i></button>' : ''}
            </div>
        `;

        // 确定容器
        let container;
        const existingContainer = document.querySelector(`.heasec-tech-alert-container-${config.position}`);

        if (existingContainer) {
            container = existingContainer;
        } else {
            container = document.createElement('div');
            container.className = `heasec-tech-alert-container heasec-tech-alert-container-${config.position}`;
            container.style.cssText = this.getAlertContainerStyles(config.position);
            document.body.appendChild(container);
        }

        // 添加到容器
        container.insertAdjacentHTML('afterbegin', alertHtml);

        // 获取新创建的提示框
        const alert = document.getElementById(alertId);
        this.cache.alerts.set(alertId, alert);

        // 绑定关闭事件
        const closeBtn = alert.querySelector('.heasec-tech-alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeAlert(alertId));
        }

        // 自动关闭
        if (config.duration > 0) {
            setTimeout(() => this.closeAlert(alertId), config.duration);
        }

        // 添加进入动画
        setTimeout(() => {
            alert.classList.add('heasec-tech-animate-fadeInDown');
        }, 10);

        // 触发事件
        this.dispatchEvent('heasec:techblue:alert:shown', { alertId, type, message, title });

        return alertId;
    },

    /**
     * 关闭提示框
     */
    closeAlert: function(alertId) {
        const alert = this.cache.alerts.get(alertId) || document.getElementById(alertId);

        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';

            setTimeout(() => {
                alert.remove();
                this.cache.alerts.delete(alertId);

                // 触发事件
                this.dispatchEvent('heasec:techblue:alert:closed', { alertId });
            }, 300);
        }
    },

    /**
     * 获取提示框容器样式
     */
    getAlertContainerStyles: function(position) {
        const baseStyles = {
            position: 'fixed',
            zIndex: '1080',
            maxWidth: '400px',
            width: '100%'
        };

        const positionStyles = {
            'top-right': {
                top: '20px',
                right: '20px'
            },
            'top-left': {
                top: '20px',
                left: '20px'
            },
            'top-center': {
                top: '20px',
                left: '50%',
                transform: 'translateX(-50%)'
            }
        };

        return Object.assign(baseStyles, positionStyles[position] || positionStyles['top-right']);
    },

    /**
     * 初始化可折叠组件
     */
    initCollapses: function() {
        // 确保DOM完全加载后再执行
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this._initCollapsesElements();
            });
        } else {
            // 如果DOM已经加载完成，延迟执行以确保所有元素都已渲染
            setTimeout(() => {
                this._initCollapsesElements();
            }, 100);
        }
    },

    /**
     * 初始化可折叠组件元素
     */
    _initCollapsesElements: function() {
        const collapses = document.querySelectorAll('.heasec-tech-collapse');
        // [HeaSec Log Cleanup - 2025-11-22]
        // console.log(`[HeaSec TechBlue] 找到 ${collapses.length} 个可折叠容器`);

        collapses.forEach((collapse, index) => {
            const collapseId = collapse.id || `heasec-tech-collapse-${index}`;
            collapse.id = collapseId;

            this.cache.collapses.set(collapseId, collapse);

            const headers = collapse.querySelectorAll('.heasec-tech-collapse-header');
            // [HeaSec Log Cleanup - 2025-11-22]
            // console.log(`容器 ${collapseId} 中找到 ${headers.length} 个头部`);

            headers.forEach((header, headerIndex) => {
                const itemId = `${collapseId}-item-${headerIndex}`;
                const item = header.closest('.heasec-tech-collapse-item');

                if (item) {
                    item.id = itemId;
                    // [HeaSec Log Cleanup - 2025-11-22]
                    // console.log(`绑定事件到项目: ${itemId}`);

                    // 移除现有的事件监听器（如果有）
                    const newHeader = header.cloneNode(true);
                    header.parentNode.replaceChild(newHeader, header);

                    // 绑定点击事件
                    newHeader.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        // [HeaSec Log Cleanup - 2025-11-22]
                        // console.log(`点击事件触发: ${itemId}`);
                        this.toggleCollapseItem(itemId);
                    });

                    // 键盘支持
                    newHeader.setAttribute('tabindex', '0');
                    newHeader.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.toggleCollapseItem(itemId);
                        }
                    });
                }
            });
        });
    },

    /**
     * 切换折叠项
     */
    toggleCollapseItem: function(itemId) {
        const item = document.getElementById(itemId);
        if (!item) return;

        const isActive = item.classList.contains('active');
        const collapse = item.closest('.heasec-tech-collapse');
        const isAccordion = collapse.classList.contains('heasec-tech-collapse-accordion');

        // 手风琴模式：关闭其他项
        if (isAccordion && !isActive) {
            const otherItems = collapse.querySelectorAll('.heasec-tech-collapse-item.active');
            otherItems.forEach(otherItem => {
                if (otherItem !== item) {
                    this.closeCollapseItem(otherItem.id);
                }
            });
        }

        // 切换当前项
        if (isActive) {
            this.closeCollapseItem(itemId);
        } else {
            this.openCollapseItem(itemId);
        }
    },

    /**
     * 打开折叠项
     */
    openCollapseItem: function(itemId) {
        const item = document.getElementById(itemId);
        if (!item) return;

        const content = item.querySelector('.heasec-tech-collapse-content');
        const body = item.querySelector('.heasec-tech-collapse-body');

        item.classList.add('active');

        if (content && body) {
            // 先设置为可见以获取正确的高度
            content.style.maxHeight = 'none';
            content.style.padding = 'var(--heasec-tech-space-5)';
            const bodyHeight = body.scrollHeight || body.offsetHeight;

            // 使用requestAnimationFrame确保样式更新
            requestAnimationFrame(() => {
                content.style.maxHeight = bodyHeight + 'px';
            });
        }

        // 触发事件
        this.dispatchEvent('heasec:techblue:collapse:opened', { itemId });
    },

    /**
     * 关闭折叠项
     */
    closeCollapseItem: function(itemId) {
        const item = document.getElementById(itemId);
        if (!item) return;

        const content = item.querySelector('.heasec-tech-collapse-content');

        item.classList.remove('active');

        if (content) {
            // 先获取当前高度用于动画
            const currentHeight = content.scrollHeight || content.offsetHeight;

            // 设置过渡动画
            content.style.transition = 'max-height 0.3s ease, padding 0.3s ease';

            // 执行关闭动画
            requestAnimationFrame(() => {
                content.style.maxHeight = currentHeight + 'px';
                requestAnimationFrame(() => {
                    content.style.maxHeight = '0';
                    content.style.padding = '0';
                });
            });
        }

        // 触发事件
        this.dispatchEvent('heasec:techblue:collapse:closed', { itemId });
    },

    /**
     * 初始化动画系统
     */
    initAnimations: function() {
        if (!this.config.animations.enabled) return;

        // 页面加载动画
        if (this.config.animations.pageLoad) {
            this.initPageLoadAnimations();
        }

        // 悬停效果
        if (this.config.animations.hoverEffects) {
            this.initHoverEffects();
        }

        // 过渡动画
        if (this.config.animations.transitions) {
            this.initTransitions();
        }
    },

    /**
     * 初始化页面加载动画
     */
    initPageLoadAnimations: function() {
        const body = document.body;
        body.classList.add('heasec-tech-page-animate');

        // 延迟移除动画类，避免影响后续操作
        setTimeout(() => {
            body.classList.remove('heasec-tech-page-animate');
        }, 2000);
    },

    /**
     * 初始化悬停效果
     */
    initHoverEffects: function() {
        const hoverElements = document.querySelectorAll('.heasec-tech-hover-lift, .heasec-tech-hover-glow, .heasec-tech-hover-scale');

        hoverElements.forEach(element => {
            // 确保GPU加速
            if (this.config.performance.gpuAcceleration) {
                element.classList.add('heasec-tech-gpu-accelerated');
            }

            // 预加载变换属性
            element.classList.add('heasec-tech-will-change-transform');
        });
    },

    /**
     * 初始化过渡动画
     */
    initTransitions: function() {
        // 为动态添加的元素添加过渡
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        this.addTransitionsToElement(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // 为现有元素添加过渡
        document.querySelectorAll('.heasec-tech-btn, .heasec-tech-card, .heasec-tech-alert').forEach(element => {
            this.addTransitionsToElement(element);
        });
    },

    /**
     * 为元素添加过渡效果
     */
    addTransitionsToElement: function(element) {
        if (element.classList && !element.classList.contains('heasec-tech-transition-added')) {
            element.classList.add('heasec-tech-transition-added');

            // 根据元素类型添加适当的过渡类
            if (element.classList.contains('heasec-tech-btn')) {
                element.classList.add('heasec-tech-will-change-transform');
            }
        }
    },

    /**
     * 初始化按钮组件
     */
    initButtons: function() {
        const buttons = document.querySelectorAll('.heasec-tech-btn');

        buttons.forEach(button => {
            // 禁用状态处理
            button.addEventListener('click', (e) => {
                if (button.disabled) {
                    e.preventDefault();
                    return;
                }

                // 添加点击涟漪效果
                if (this.config.animations.enabled) {
                    this.createRippleEffect(button, e);
                }

                // 触发按钮事件
                this.dispatchEvent('heasec:techblue:button:clicked', {
                    button: button,
                    originalEvent: e
                });
            });

            // 键盘支持
            if (!button.hasAttribute('tabindex')) {
                button.setAttribute('tabindex', '0');
            }

            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });
    },

    /**
     * 创建涟漪效果
     */
    createRippleEffect: function(button, event) {
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.className = 'heasec-tech-ripple';
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            animation: heasec-tech-buttonRipple 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `;

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        // 清理涟漪元素
        setTimeout(() => {
            ripple.remove();
        }, 600);
    },

    /**
     * 初始化表单组件
     */
    initForms: function() {
        const forms = document.querySelectorAll('.heasec-tech-form');

        forms.forEach(form => {
            // 表单验证增强
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showAlert('请检查表单填写是否正确', 'warning', '表单验证失败');
                }
            });

            // 输入框焦点效果
            const inputs = form.querySelectorAll('.heasec-tech-form-input, .heasec-tech-form-textarea, .heasec-tech-form-select');

            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    input.parentElement.classList.add('heasec-tech-form-focused');
                });

                input.addEventListener('blur', () => {
                    input.parentElement.classList.remove('heasec-tech-form-focused');
                });

                // 输入验证
                input.addEventListener('input', () => {
                    this.validateInput(input);
                });
            });
        });
    },

    /**
     * 验证表单
     */
    validateForm: function(form) {
        const inputs = form.querySelectorAll('[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });

        return isValid;
    },

    /**
     * 验证输入
     */
    validateInput: function(input) {
        const isValid = input.checkValidity();

        if (isValid) {
            input.classList.remove('heasec-tech-form-error');
            input.classList.add('heasec-tech-form-success');
        } else {
            input.classList.remove('heasec-tech-form-success');
            input.classList.add('heasec-tech-form-error');
        }

        return isValid;
    },

    /**
     * 初始化键盘导航
     */
    initKeyboardNavigation: function() {
        if (!this.config.accessibility.keyboardNavigation) return;

        document.addEventListener('keydown', (e) => {
            // Tab键导航增强
            if (e.key === 'Tab') {
                this.handleTabNavigation(e);
            }

            // 快捷键支持
            if (e.ctrlKey || e.metaKey) {
                this.handleKeyboardShortcuts(e);
            }
        });
    },

    /**
     * 处理Tab导航
     */
    handleTabNavigation: function(event) {
        // 确保焦点元素可见
        setTimeout(() => {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.classList.contains('heasec-tech-btn')) {
                this.ensureElementVisible(activeElement);
            }
        }, 0);
    },

    /**
     * 处理键盘快捷键
     */
    handleKeyboardShortcuts: function(event) {
        // Ctrl+M: 关闭所有模态框
        if (event.key === 'm') {
            this.cache.modals.forEach((modal, id) => {
                this.closeModal(id);
            });
        }

        // Ctrl+A: 关闭所有提示框
        if (event.key === 'a') {
            this.cache.alerts.forEach((alert, id) => {
                this.closeAlert(id);
            });
        }
    },

    /**
     * 确保元素可见
     */
    ensureElementVisible: function(element) {
        const rect = element.getBoundingClientRect();
        const isInViewport = (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= window.innerHeight &&
            rect.right <= window.innerWidth
        );

        if (!isInViewport) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    },

    /**
     * 触发自定义事件
     */
    dispatchEvent: function(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: detail,
            bubbles: true,
            cancelable: true
        });

        document.dispatchEvent(event);

        return event;
    },

    /**
     * 工具函数：防抖
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * 工具函数：节流
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// 页面加载完成后自动初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        HeaSec.TechBlue.init();
    });
} else {
    HeaSec.TechBlue.init();
}

// 暴露到全局作用域
window.HeaSecTechBlue = HeaSec.TechBlue;