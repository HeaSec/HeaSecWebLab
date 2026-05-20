/**
 * HeaSec天积安全团队 - 自适应标题组件
 * 实现标题字体大小的动态缩放以适应容器宽度
 *
 * @author 天积安全 HeavenlySecret
 * @version 1.0.0
 * @description 防止标题在窄屏时被截断，通过动态调整字体大小确保完整显示
 */

(function(window, document) {
    'use strict';

    // HeaSec全局对象
    window.HeaSec = window.HeaSec || {};

    /**
     * 自适应标题类
     */
    function AdaptiveTitle(options) {
        this.options = this.extendOptions(options);
        this.container = null;
        this.titleElement = null;
        this.originalFontSize = 0;
        this.minFontSize = this.options.minFontSize;
        this.maxFontSize = this.options.maxFontSize;
        this.isInitialized = false;
        this.resizeTimer = null;
        this.windowWidth = 0;

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
    AdaptiveTitle.prototype.defaultOptions = {
        containerSelector: '.heasec-title-container',
        titleSelector: '.heasec-title-no-wrap, .main-title',
        minFontSize: 14,      // 最小字体大小(px)
        maxFontSize: 32,      // 最大字体大小(px)
        stepSize: 1,          // 字体调整步长(px)
        bufferWidth: 20,      // 缓冲宽度(px)，防止紧贴容器边缘
        debounceDelay: 100,   // 防抖延迟(ms)
        enableLogging: false  // 是否启用日志
    };

    /**
     * 合并配置选项
     */
    AdaptiveTitle.prototype.extendOptions = function(options) {
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
    AdaptiveTitle.prototype.init = function() {
        try {
            // 增加DOM准备检查
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
    AdaptiveTitle.prototype.initializeComponent = function() {
        try {
            this.container = document.querySelector(this.options.containerSelector);
            this.titleElement = document.querySelector(this.options.titleSelector);

            if (!this.container || !this.titleElement) {
                this.log('[HeaSec] 错误：未找到容器或标题元素');
                this.log('[HeaSec] 容器选择器：' + this.options.containerSelector);
                this.log('[HeaSec] 标题选择器：' + this.options.titleSelector);
                return;
            }

            // 获取原始字体大小
            var computedStyle = window.getComputedStyle(this.titleElement);
            this.originalFontSize = parseFloat(computedStyle.fontSize);

            // 设置初始字体大小
            this.titleElement.style.fontSize = this.originalFontSize + 'px';
            this.titleElement.style.transition = 'font-size 0.3s ease-in-out';

            // 移除可能导致截断的CSS属性
            this.titleElement.style.whiteSpace = 'nowrap';
            this.titleElement.style.overflow = 'visible';
            this.titleElement.style.textOverflow = 'unset';

            this.isInitialized = true;
            this.log('[HeaSec] 自适应标题组件初始化成功');
            this.log('[HeaSec] 原始字体大小：' + this.originalFontSize + 'px');

            // 绑定事件
            this.bindEvents();

            // 延迟执行第一次调整，确保布局完全稳定
            var self = this;
            setTimeout(function() {
                self.adjustFontSize();
            }, 100);

        } catch (error) {
            this.log('[HeaSec] 初始化失败：' + error.message);
        }
    };

    /**
     * 绑定事件监听器
     */
    AdaptiveTitle.prototype.bindEvents = function() {
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
    AdaptiveTitle.prototype.handleResize = function() {
        var self = this;

        // 防抖处理
        if (this.resizeTimer) {
            clearTimeout(this.resizeTimer);
        }

        this.resizeTimer = setTimeout(function() {
            self.adjustFontSize();
        }, this.options.debounceDelay);
    };

    /**
     * 调整字体大小
     */
    AdaptiveTitle.prototype.adjustFontSize = function() {
        if (!this.isInitialized) {
            return;
        }

        try {
            var containerWidth = this.getContainerWidth();
            var titleWidth = this.getTitleWidth();
            var currentFontSize = parseFloat(this.titleElement.style.fontSize) || this.originalFontSize;

            // 如果容器宽度足够，使用较大的字体
            if (titleWidth <= containerWidth - this.options.bufferWidth) {
                this.tryIncreaseFontSize(containerWidth);
            }
            // 如果容器宽度不足，减小字体
            else {
                this.tryDecreaseFontSize(containerWidth);
            }

            this.log('[HeaSec] 字体大小已调整至：' + this.titleElement.style.fontSize);

        } catch (error) {
            this.log('[HeaSec] 调整字体大小失败：' + error.message);
        }
    };

    /**
     * 尝试增大字体
     */
    AdaptiveTitle.prototype.tryIncreaseFontSize = function(containerWidth) {
        var currentFontSize = parseFloat(this.titleElement.style.fontSize);
        var newFontSize = currentFontSize;

        while (newFontSize < this.maxFontSize) {
            newFontSize += this.options.stepSize;
            this.titleElement.style.fontSize = newFontSize + 'px';

            if (this.getTitleWidth() > containerWidth - this.options.bufferWidth) {
                // 超出容器，回退到上一个大小
                newFontSize -= this.options.stepSize;
                break;
            }
        }

        this.titleElement.style.fontSize = newFontSize + 'px';
    };

    /**
     * 尝试减小字体
     */
    AdaptiveTitle.prototype.tryDecreaseFontSize = function(containerWidth) {
        var currentFontSize = parseFloat(this.titleElement.style.fontSize);
        var newFontSize = currentFontSize;

        while (newFontSize > this.minFontSize) {
            newFontSize -= this.options.stepSize;
            this.titleElement.style.fontSize = newFontSize + 'px';

            if (this.getTitleWidth() <= containerWidth - this.options.bufferWidth) {
                // 适合容器，停止减小
                break;
            }
        }

        // 如果即使最小字体也不适合，保持最小字体
        if (newFontSize < this.minFontSize) {
            newFontSize = this.minFontSize;
        }

        this.titleElement.style.fontSize = newFontSize + 'px';
    };

    /**
     * 获取容器宽度
     */
    AdaptiveTitle.prototype.getContainerWidth = function() {
        if (!this.container) {
            return 0;
        }

        var rect = this.container.getBoundingClientRect();
        return rect.width || this.container.offsetWidth;
    };

    /**
     * 获取标题宽度
     */
    AdaptiveTitle.prototype.getTitleWidth = function() {
        if (!this.titleElement) {
            return 0;
        }

        // 临时移除任何宽度限制以获取真实宽度
        var originalWidth = this.titleElement.style.width;
        var originalOverflow = this.titleElement.style.overflow;

        this.titleElement.style.width = 'auto';
        this.titleElement.style.overflow = 'visible';

        var width = this.titleElement.scrollWidth || this.titleElement.offsetWidth;

        // 恢复原始设置
        this.titleElement.style.width = originalWidth;
        this.titleElement.style.overflow = originalOverflow;

        return width;
    };

    /**
     * 获取当前字体大小
     */
    AdaptiveTitle.prototype.getCurrentFontSize = function() {
        if (!this.titleElement) {
            return 0;
        }

        return parseFloat(this.titleElement.style.fontSize) || 0;
    };

    /**
     * 手动刷新字体大小
     */
    AdaptiveTitle.prototype.refresh = function() {
        this.adjustFontSize();
    };

    /**
     * 销毁组件
     */
    AdaptiveTitle.prototype.destroy = function() {
        if (this.resizeTimer) {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = null;
        }

        // 恢复原始字体大小
        if (this.titleElement && this.originalFontSize) {
            this.titleElement.style.fontSize = this.originalFontSize + 'px';
        }

        this.isInitialized = false;
        this.log('[HeaSec] 自适应标题组件已销毁');
    };

    /**
     * 日志输出
     */
    AdaptiveTitle.prototype.log = function(message) {
        if (this.options.enableLogging && window.console && window.console.log) {
            console.log(message);
        }
    };

    /**
     * 初始化函数
     */
    AdaptiveTitle.init = function(options) {
        return new AdaptiveTitle(options);
    };

    // 导出到全局对象
    window.HeaSec.AdaptiveTitle = AdaptiveTitle;

    // 页面加载完成后自动初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            AdaptiveTitle.init({
                enableLogging: false  // 生产环境关闭日志
            });
        });
    } else {
        AdaptiveTitle.init({
            enableLogging: false  // 生产环境关闭日志
        });
    }

})(window, document);