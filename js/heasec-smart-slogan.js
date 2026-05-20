/**
 * HeaSec天积安全团队 - 智能口号显示管理组件
 * 实现标题和口号的水平排列，空间不足时智能隐藏口号
 *
 * @author 天积安全 HeavenlySecret
 * @version 1.0.0
 * @description 确保标题"WEB安全靶场平台"在任何情况下都完整显示，口号智能显示/隐藏
 */

(function(window, document) {
    'use strict';

    // HeaSec全局对象
    window.HeaSec = window.HeaSec || {};

    /**
     * 智能口号管理类
     */
    function SmartSloganManager(options) {
        this.options = this.extendOptions(options);
        this.container = null;
        this.titleElement = null;
        this.sloganElement = null;
        this.titleSection = null;
        this.isInitialized = false;
        this.resizeTimer = null;
        this.lastSloganVisible = true;
        this.breakpointCache = new Map(); // 缓存断点结果

        // HeaSec团队标识
        this.teamInfo = {
            name: '天积安全',
            englishName: 'HeavenlySecret',
            abbreviation: 'HeaSec',
            slogan: '日积寸功，乐享安全'
        };

        this.init();
    }

    /**
     * 默认配置
     */
    SmartSloganManager.prototype.defaultOptions = {
        containerSelector: '.title-slogan-container',
        titleSelector: '.main-title',
        sloganSelector: '.main-slogan',
        titleSectionSelector: '.title-section',
        minFontSize: 16,      // 最小字体大小(px)
        maxFontSize: 32,      // 最大字体大小(px)
        bufferWidth: 40,      // 缓冲宽度(px)，logo和版本号预留空间
        gapWidth: 20,         // 标题和口号之间的间隙(px)
        debounceDelay: 150,   // 防抖延迟(ms)
        enableLogging: false, // 是否启用日志
        hiddenClassName: 'heasec-slogan-hidden',
        transitionDuration: 300 // 过渡动画时长(ms)
    };

    /**
     * 合并配置选项
     */
    SmartSloganManager.prototype.extendOptions = function(options) {
        var merged = {};
        var defaults = this.defaultOptions;

        for (var key in defaults) {
            if (defaults.hasOwnProperty(key)) {
                merged[key] = options && options.hasOwnProperty(key) ? options[key] : defaults[key];
            }
        }

        return merged;
    };

    /**
     * 初始化组件
     */
    SmartSloganManager.prototype.init = function() {
        try {
            if (document.readyState === 'loading') {
                this.log('[HeaSec] DOM仍在加载中，等待DOMContentLoaded事件');
                var self = this;
                document.addEventListener('DOMContentLoaded', function() {
                    self.initializeComponent();
                });
                return;
            } else {
                this.initializeComponent();
            }
        } catch (error) {
            this.log('[HeaSec] 初始化失败：' + error.message);
        }
    };

    /**
     * 实际执行组件初始化
     */
    SmartSloganManager.prototype.initializeComponent = function() {
        try {
            // 查找必要元素
            this.container = document.querySelector(this.options.containerSelector);
            this.titleElement = document.querySelector(this.options.titleSelector);
            this.sloganElement = document.querySelector(this.options.sloganSelector);
            this.titleSection = document.querySelector(this.options.titleSectionSelector);

            if (!this.container || !this.titleElement || !this.sloganElement || !this.titleSection) {
                this.log('[HeaSec] 错误：未找到必要的DOM元素');
                this.log('[HeaSec] 容器：' + (this.container ? '找到' : '未找到'));
                this.log('[HeaSec] 标题：' + (this.titleElement ? '找到' : '未找到'));
                this.log('[HeaSec] 口号：' + (this.sloganElement ? '找到' : '未找到'));
                this.log('[HeaSec] 标题区域：' + (this.titleSection ? '找到' : '未找到'));
                return;
            }

            // 初始化样式
            this.initializeStyles();

            // 初始化布局
            this.initializeLayout();

            this.isInitialized = true;
            this.log('[HeaSec] 智能口号管理组件初始化成功');

            // 绑定事件
            this.bindEvents();

            // 延迟执行第一次调整
            var self = this;
            setTimeout(function() {
                self.adjustLayout();
            }, 100);

        } catch (error) {
            this.log('[HeaSec] 初始化失败：' + error.message);
        }
    };

    /**
     * 初始化样式
     */
    SmartSloganManager.prototype.initializeStyles = function() {
        // 容器样式 - 强制水平排列
        this.container.style.display = 'flex';
        this.container.style.flexDirection = 'row';
        this.container.style.flexWrap = 'nowrap';
        this.container.style.justifyContent = 'center';
        this.container.style.alignItems = 'center';
        this.container.style.gap = this.options.gapWidth + 'px';

        // 标题样式
        this.titleElement.style.whiteSpace = 'nowrap';
        this.titleElement.style.overflow = 'visible';
        this.titleElement.style.textOverflow = 'unset';
        this.titleElement.style.flexShrink = '1';
        this.titleElement.style.minWidth = '0';
        this.titleElement.style.transition = 'font-size 0.3s ease-in-out';

        // 口号样式
        this.sloganElement.style.whiteSpace = 'nowrap';
        this.sloganElement.style.overflow = 'visible';
        this.sloganElement.style.flexShrink = '0';
        this.sloganElement.style.transition = `opacity ${this.options.transitionDuration}ms ease-in-out, visibility ${this.options.transitionDuration}ms ease-in-out`;
        this.sloganElement.style.opacity = '1';
        this.sloganElement.style.visibility = 'visible';

        this.log('[HeaSec] 样式初始化完成');
    };

    /**
     * 初始化布局
     */
    SmartSloganManager.prototype.initializeLayout = function() {
        // 添加CSS类定义
        var styleElement = document.createElement('style');
        styleElement.textContent = this.getCSSText();
        document.head.appendChild(styleElement);

        this.log('[HeaSec] 布局初始化完成');
    };

    /**
     * 获取CSS文本
     */
    SmartSloganManager.prototype.getCSSText = function() {
        return `
            /* HeaSec智能口号隐藏样式 - 增强版本 */
            .${this.options.hiddenClassName} {
                opacity: 0 !important;
                visibility: hidden !important;
                width: 0 !important;
                min-width: 0 !important;
                max-width: 0 !important;
                flex-basis: 0 !important;
                flex-shrink: 1 !important;
                flex-grow: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                outline: none !important;
                line-height: 0 !important;
                font-size: 0 !important;
                display: block !important;
                position: static !important;
            }

            /* 确保容器始终水平排列 */
            .title-slogan-container.heasec-smart-layout {
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                justify-content: center !important;
                align-items: center !important;
            }

            /* 移动端优化 */
            @media (max-width: 768px) {
                .title-slogan-container.heasec-smart-layout {
                    gap: ${Math.max(10, this.options.gapWidth - 5)}px !important;
                }
            }

            @media (max-width: 480px) {
                .title-slogan-container.heasec-smart-layout {
                    gap: ${Math.max(8, this.options.gapWidth - 10)}px !important;
                }
            }

            /* 备用隐藏机制 - 针对小屏幕 */
            @media (max-width: 400px) {
                .main-slogan {
                    display: none !important;
                }
            }

            @media (max-width: 768px) {
                .title-slogan-container.heasec-smart-layout .main-slogan.${this.options.hiddenClassName} {
                    display: none !important;
                }
            }
        `;
    };

    /**
     * 绑定事件监听器
     */
    SmartSloganManager.prototype.bindEvents = function() {
        var self = this;

        // 窗口大小改变事件
        if (window.addEventListener) {
            window.addEventListener('resize', function() {
                self.handleResize();
            });
        } else if (window.attachEvent) {
            window.attachEvent('onresize', function() {
                self.handleResize();
            });
        }

        // 方向改变事件（移动设备）
        if (window.addEventListener) {
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    self.handleResize();
                }, 100);
            });
        }
    };

    /**
     * 处理窗口大小改变
     */
    SmartSloganManager.prototype.handleResize = function() {
        var self = this;

        // 防抖处理
        if (this.resizeTimer) {
            clearTimeout(this.resizeTimer);
        }

        this.resizeTimer = setTimeout(function() {
            self.adjustLayout();
        }, this.options.debounceDelay);
    };

    /**
     * 调整布局
     */
    SmartSloganManager.prototype.adjustLayout = function() {
        if (!this.isInitialized) {
            return;
        }

        try {
            // 添加智能布局类
            this.container.classList.add('heasec-smart-layout');

            // 计算可用空间
            var availableSpace = this.calculateAvailableSpace();
            var requiredSpace = this.calculateRequiredSpace();

            // 判断是否需要隐藏口号
            var shouldHideSlogan = requiredSpace > availableSpace;
            var cacheKey = availableSpace + '_' + requiredSpace;

            // 检查缓存
            if (this.breakpointCache.has(cacheKey)) {
                shouldHideSlogan = this.breakpointCache.get(cacheKey);
            } else {
                this.breakpointCache.set(cacheKey, shouldHideSlogan);
            }

            // 执行显示/隐藏操作
            this.toggleSlogan(!shouldHideSlogan);

            this.log('[HeaSec] 布局调整完成 - 可用空间：' + availableSpace + 'px，需要空间：' + requiredSpace + 'px，口号显示：' + !shouldHideSlogan);

        } catch (error) {
            this.log('[HeaSec] 调整布局失败：' + error.message);
        }
    };

    /**
     * 计算可用空间
     */
    SmartSloganManager.prototype.calculateAvailableSpace = function() {
        var titleSectionWidth = this.titleSection.getBoundingClientRect().width;
        var availableWidth = titleSectionWidth - this.options.bufferWidth;
        return Math.max(0, availableWidth);
    };

    /**
     * 计算需要的空间
     */
    SmartSloganManager.prototype.calculateRequiredSpace = function() {
        // 临时显示所有元素以获取准确宽度
        var sloganWasHidden = this.sloganElement.classList.contains(this.options.hiddenClassName);
        if (sloganWasHidden) {
            this.sloganElement.classList.remove(this.options.hiddenClassName);
        }

        // 获取标题和口号的实际宽度
        var titleWidth = this.getTextWidth(this.titleElement);
        var sloganWidth = this.getTextWidth(this.sloganElement);
        var totalWidth = titleWidth + sloganWidth + this.options.gapWidth;

        // 恢复原始状态
        if (sloganWasHidden) {
            this.sloganElement.classList.add(this.options.hiddenClassName);
        }

        return totalWidth;
    };

    /**
     * 获取文本宽度
     */
    SmartSloganManager.prototype.getTextWidth = function(element) {
        // 临时设置样式以获取准确宽度
        var originalDisplay = element.style.display;
        var originalVisibility = element.style.visibility;
        var originalPosition = element.style.position;

        element.style.display = 'inline-block';
        element.style.visibility = 'hidden';
        element.style.position = 'absolute';

        var width = element.scrollWidth || element.offsetWidth;

        // 恢复原始样式
        element.style.display = originalDisplay;
        element.style.visibility = originalVisibility;
        element.style.position = originalPosition;

        return width;
    };

    /**
     * 切换口号显示/隐藏
     */
    SmartSloganManager.prototype.toggleSlogan = function(show) {
        var isVisible = !this.sloganElement.classList.contains(this.options.hiddenClassName);

        if (show === isVisible) {
            return; // 状态无需改变
        }

        if (show) {
            this.sloganElement.classList.remove(this.options.hiddenClassName);
            // 显示时重置内联样式
            this.sloganElement.style.opacity = '1';
            this.sloganElement.style.visibility = 'visible';
            this.sloganElement.style.width = '';
            this.sloganElement.style.minWidth = '';
            this.sloganElement.style.maxWidth = '';
            this.sloganElement.style.overflow = '';
            this.sloganElement.style.margin = '';
            this.sloganElement.style.padding = '';
            this.log('[HeaSec] 口号显示');
        } else {
            this.sloganElement.classList.add(this.options.hiddenClassName);
            // 隐藏时强制应用内联样式，覆盖其他样式
            this.sloganElement.style.cssText += `
                width: 0 !important;
                min-width: 0 !important;
                max-width: 0 !important;
                flex-basis: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                opacity: 0 !important;
                visibility: hidden !important;
            `;
            this.log('[HeaSec] 口号隐藏');
        }

        this.lastSloganVisible = show;
    };

    /**
     * 获取当前状态
     */
    SmartSloganManager.prototype.getStatus = function() {
        if (!this.isInitialized) {
            return { initialized: false };
        }

        return {
            initialized: true,
            sloganVisible: !this.sloganElement.classList.contains(this.options.hiddenClassName),
            availableSpace: Math.round(this.calculateAvailableSpace()),
            requiredSpace: Math.round(this.calculateRequiredSpace()),
            windowWidth: window.innerWidth,
            containerWidth: Math.round(this.container.getBoundingClientRect().width),
            titleWidth: Math.round(this.getTextWidth(this.titleElement)),
            sloganWidth: Math.round(this.getTextWidth(this.sloganElement))
        };
    };

    /**
     * 手动刷新布局
     */
    SmartSloganManager.prototype.refresh = function() {
        this.breakpointCache.clear(); // 清除缓存
        this.adjustLayout();
    };

    /**
     * 销毁组件
     */
    SmartSloganManager.prototype.destroy = function() {
        if (this.resizeTimer) {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = null;
        }

        // 移除添加的类
        if (this.container) {
            this.container.classList.remove('heasec-smart-layout');
        }
        if (this.sloganElement) {
            this.sloganElement.classList.remove(this.options.hiddenClassName);
        }

        // 清除缓存
        this.breakpointCache.clear();

        this.isInitialized = false;
        this.log('[HeaSec] 智能口号管理组件已销毁');
    };

    /**
     * 日志输出
     */
    SmartSloganManager.prototype.log = function(message) {
        if (this.options.enableLogging && window.console && window.console.log) {
            console.log(message);
        }
    };

    /**
     * 初始化函数
     */
    SmartSloganManager.init = function(options) {
        return new SmartSloganManager(options);
    };

    // 导出到全局对象
    window.HeaSec.SmartSloganManager = SmartSloganManager;

    // 页面加载完成后自动初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            SmartSloganManager.init({
                enableLogging: false  // 生产环境关闭日志
            });
        });
    } else {
        SmartSloganManager.init({
            enableLogging: false  // 生产环境关闭日志
        });
    }

})(window, document);