/**
 * HeaSec 自定义模态框组件
 * 版本: v1.0.0
 * 创建日期: 2025-12-31
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 用于XSS过滤靶场的错误提示和消息显示
 */

(function() {
    'use strict';

    /**
     * 模态框类
     */
    var HeaSecModal = function() {
        this.modalElement = null;
        this.overlayElement = null;
        this.isVisible = false;
        this.config = {
            title: '',
            message: '',
            type: 'info',  // info, success, error, warning
            closable: true,
            closeOnOverlayClick: true,
            buttons: [],
            onShow: null,
            onClose: null
        };
    };

    /**
     * 显示模态框
     *
     * @param {Object} config 配置对象
     */
    HeaSecModal.prototype.show = function(config) {
        // 合并配置
        this.config = this._mergeConfig(config);

        // 创建模态框元素
        this._createModal();

        // 显示模态框
        this._show();

        // 绑定事件
        this._bindEvents();

        // 调用显示回调
        if (this.config.onShow) {
            this.config.onShow();
        }
    };

    /**
     * 隐藏模态框
     */
    HeaSecModal.prototype.hide = function() {
        if (!this.isVisible) {
            return;
        }

        var self = this;

        // 添加隐藏动画类
        this.modalElement.classList.add('heasec-modal-hiding');
        this.overlayElement.classList.add('heasec-overlay-hiding');

        // 等待动画结束后移除元素
        setTimeout(function() {
            if (self.modalElement && self.modalElement.parentNode) {
                self.modalElement.parentNode.removeChild(self.modalElement);
            }
            if (self.overlayElement && self.overlayElement.parentNode) {
                self.overlayElement.parentNode.removeChild(self.overlayElement);
            }

            self.isVisible = false;

            // 调用关闭回调
            if (self.config.onClose) {
                self.config.onClose();
            }
        }, 300);
    };

    /**
     * 创建模态框DOM元素
     */
    HeaSecModal.prototype._createModal = function() {
        // 如果已存在，先移除
        if (this.modalElement) {
            this.hide();
        }

        // 创建遮罩层
        this.overlayElement = document.createElement('div');
        this.overlayElement.className = 'heasec-modal-overlay';

        // 创建模态框容器
        this.modalElement = document.createElement('div');
        this.modalElement.className = 'heasec-modal-container';

        // 根据类型设置样式类
        var typeClass = 'heasec-modal-' + this.config.type;
        this.modalElement.classList.add(typeClass);

        // 构建模态框HTML
        var html = this._buildModalHTML();
        this.modalElement.innerHTML = html;

        // 添加到页面
        document.body.appendChild(this.overlayElement);
        document.body.appendChild(this.modalElement);
    };

    /**
     * 构建模态框HTML
     */
    HeaSecModal.prototype._buildModalHTML = function() {
        var html = '<div class="heasec-modal">';

        // 头部
        html += '<div class="heasec-modal-header">';
        html += '<h3 class="heasec-modal-title">' + this._escapeHtml(this.config.title) + '</h3>';
        if (this.config.closable) {
            html += '<button class="heasec-modal-close" type="button">&times;</button>';
        }
        html += '</div>';

        // 内容
        html += '<div class="heasec-modal-body">';
        html += '<div class="heasec-modal-message">' + this.config.message + '</div>';
        html += '</div>';

        // 按钮
        if (this.config.buttons && this.config.buttons.length > 0) {
            html += '<div class="heasec-modal-footer">';
            for (var i = 0; i < this.config.buttons.length; i++) {
                var btn = this.config.buttons[i];
                var btnClass = 'heasec-modal-btn heasec-modal-btn-' + (btn.type || 'default');
                html += '<button class="' + btnClass + '" data-action="' + btn.action + '">';
                html += this._escapeHtml(btn.text);
                html += '</button>';
            }
            html += '</div>';
        }

        html += '</div>';
        return html;
    };

    /**
     * 显示模态框（带动画）
     */
    HeaSecModal.prototype._show = function() {
        var self = this;

        // 先设置为透明，确保元素已添加到DOM
        this.overlayElement.style.opacity = '0';
        this.modalElement.style.opacity = '0';
        this.modalElement.style.transform = 'scale(0.9)';

        // 强制重绘
        this.overlayElement.offsetHeight;
        this.modalElement.offsetHeight;

        // 添加显示动画
        this.overlayElement.style.opacity = '1';
        this.modalElement.style.opacity = '1';
        this.modalElement.style.transform = 'scale(1)';

        this.isVisible = true;

        // 禁止页面滚动
        document.body.style.overflow = 'hidden';
    };

    /**
     * 绑定事件处理器
     */
    HeaSecModal.prototype._bindEvents = function() {
        var self = this;

        // 关闭按钮
        if (this.config.closable) {
            var closeBtn = this.modalElement.querySelector('.heasec-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    self.hide();
                });
            }
        }

        // 遮罩层点击关闭
        if (this.config.closeOnOverlayClick) {
            this.overlayElement.addEventListener('click', function() {
                self.hide();
            });
        }

        // 自定义按钮
        var buttons = this.modalElement.querySelectorAll('.heasec-modal-btn');
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', function(e) {
                var action = this.getAttribute('data-action');

                // 执行按钮回调
                if (self.config.buttons) {
                    for (var j = 0; j < self.config.buttons.length; j++) {
                        if (self.config.buttons[j].action === action &&
                            typeof self.config.buttons[j].callback === 'function') {
                            self.config.buttons[j].callback();
                            break;
                        }
                    }
                }

                // 默认关闭模态框
                if (action !== 'cancel') {
                    self.hide();
                }
            });
        }

        // ESC键关闭
        this._escapeKeyHandler = function(e) {
            if (e.keyCode === 27 && self.isVisible) {
                self.hide();
            }
        };
        document.addEventListener('keydown', this._escapeKeyHandler);
    };

    /**
     * 合并配置
     */
    HeaSecModal.prototype._mergeConfig = function(config) {
        var defaultConfig = {
            title: '提示',
            message: '',
            type: 'info',
            closable: true,
            closeOnOverlayClick: true,
            buttons: [],
            onShow: null,
            onClose: null
        };

        for (var key in config) {
            if (config.hasOwnProperty(key)) {
                defaultConfig[key] = config[key];
            }
        }

        return defaultConfig;
    };

    /**
     * HTML转义
     */
    HeaSecModal.prototype._escapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * 快捷方法：显示错误消息
     */
    HeaSecModal.showError = function(title, message, onClose) {
        var modal = new HeaSecModal();
        modal.show({
            title: title,
            message: message,
            type: 'error',
            closable: true,
            buttons: [
                {
                    text: '确定',
                    type: 'primary',
                    action: 'ok'
                }
            ],
            onClose: onClose
        });
    };

    /**
     * 快捷方法：显示成功消息
     */
    HeaSecModal.showSuccess = function(title, message, onClose) {
        var modal = new HeaSecModal();
        modal.show({
            title: title,
            message: message,
            type: 'success',
            closable: true,
            buttons: [
                {
                    text: '确定',
                    type: 'primary',
                    action: 'ok'
                }
            ],
            onClose: onClose
        });
    };

    /**
     * 快捷方法：显示警告消息
     */
    HeaSecModal.showWarning = function(title, message, onClose) {
        var modal = new HeaSecModal();
        modal.show({
            title: title,
            message: message,
            type: 'warning',
            closable: true,
            buttons: [
                {
                    text: '确定',
                    type: 'primary',
                    action: 'ok'
                }
            ],
            onClose: onClose
        });
    };

    /**
     * 快捷方法：显示信息消息
     */
    HeaSecModal.showInfo = function(title, message, onClose) {
        var modal = new HeaSecModal();
        modal.show({
            title: title,
            message: message,
            type: 'info',
            closable: true,
            buttons: [
                {
                    text: '确定',
                    type: 'primary',
                    action: 'ok'
                }
            ],
            onClose: onClose
        });
    };

    // 导出到全局
    window.HeaSecModal = HeaSecModal;

})();
