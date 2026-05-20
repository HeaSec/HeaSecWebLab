/**
 * HeaSec天积安全团队 - 短信验证码绕过靶场脚本
 * SMS Bypass Range JavaScript
 * 版本: v1.0.0
 * 创建日期: 2026-01-21
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
    'use strict';

    /**
     * 短信验证码绕过靶场模块
     */
    var SmsbypassRange = {
        // 配置
        config: {
            countdownTime: 60,  // 倒计时时间（秒）
            apiUrl: 'api/send-code.php'
        },

        // DOM元素
        elements: {
            sendCodeBtn: null,
            codeInput: null,
            loginForm: null,
            messageContainer: null,
            adminUsername: null,  // admin用户名隐藏字段
            adminPhone: null       // admin手机号隐藏字段
        },

        // 状态
        state: {
            countdownTimer: null,
            isCountingDown: false
        },

        /**
         * 初始化
         */
        init: function() {
            this.bindElements();
            this.bindEvents();
        },

        /**
         * 绑定DOM元素
         */
        bindElements: function() {
            this.elements.sendCodeBtn = document.getElementById('sendCodeBtn');
            this.elements.codeInput = document.getElementById('code');
            this.elements.loginForm = document.getElementById('loginForm');
            this.elements.messageContainer = document.getElementById('messageContainer');
            this.elements.adminUsername = document.getElementById('adminUsername');
            this.elements.adminPhone = document.getElementById('adminPhone');
        },

        /**
         * 绑定事件
         */
        bindEvents: function() {
            var self = this;

            // 发送验证码按钮点击事件
            if (this.elements.sendCodeBtn) {
                this.elements.sendCodeBtn.addEventListener('click', function() {
                    self.sendCode();
                });
            }

            // 表单提交前校验
            if (this.elements.loginForm) {
                this.elements.loginForm.addEventListener('submit', function(e) {
                    if (self.elements.codeInput && self.elements.codeInput.value.trim() === '') {
                        e.preventDefault();
                        self.showMessage('warning', '请输入验证码');
                    }
                });
            }
        },

        /**
         * 发送验证码
         */
        sendCode: function() {
            var self = this;
            var btn = this.elements.sendCodeBtn;
            var originalText = btn.innerHTML;

            // 如果正在倒计时，不执行
            if (this.state.isCountingDown) {
                return;
            }

            // 从隐藏字段获取admin用户名和手机号
            var adminUsername = this.elements.adminUsername ? this.elements.adminUsername.value : 'admin';
            var adminPhone = this.elements.adminPhone ? this.elements.adminPhone.value : '11066668888';

            // 禁用按钮并显示加载状态
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 发送中...';

            // 发送请求
            fetch(this.config.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: adminUsername,
                    phone: adminPhone  // 从隐藏字段读取原手机号
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    self.showMessage('success', data.message);
                    // 开始倒计时
                    self.startCountdown(btn, originalText);
                } else {
                    self.showMessage('error', data.message);
                    // 发送失败时恢复按钮
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(function() {
                self.showMessage('error', '发送失败：网络错误');
                // 发送失败时恢复按钮
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        },

        /**
         * 开始倒计时
         */
        startCountdown: function(btn, originalText) {
            var self = this;
            var countdown = this.config.countdownTime;

            this.state.isCountingDown = true;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + countdown + '秒后重试';

            this.state.countdownTimer = setInterval(function() {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(self.state.countdownTimer);
                    self.state.isCountingDown = false;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                } else {
                    btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + countdown + '秒后重试';
                }
            }, 1000);
        },

        /**
         * 显示消息提示
         * @param {string} type - 消息类型：success, error, warning, info
         * @param {string} message - 消息内容
         * @param {number} duration - 显示时长（毫秒），0表示不自动关闭
         */
        showMessage: function(type, message, duration) {
            var container = this.elements.messageContainer;
            if (!container) return;

            // 默认5秒后自动关闭
            if (typeof duration === 'undefined') {
                duration = 5000;
            }

            // 清除现有消息
            container.innerHTML = '';

            // 创建消息元素
            var msgDiv = document.createElement('div');
            msgDiv.className = 'heasec-verify-' + type;

            // 添加图标
            var icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            msgDiv.innerHTML = '<i class="fa ' + icons[type] + '"></i> ' + this.escapeHtml(message);
            container.appendChild(msgDiv);

            // 自动关闭
            if (duration > 0) {
                setTimeout(function() {
                    msgDiv.classList.add('heasec-message-fadeout');
                    setTimeout(function() {
                        if (msgDiv.parentNode) {
                            msgDiv.remove();
                        }
                    }, 300);
                }, duration);
            }
        },

        /**
         * HTML转义
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // 暴露到全局命名空间
    window.HeaSec = window.HeaSec || {};
    window.HeaSec.SmsbypassRange = SmsbypassRange;

    // DOM加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        SmsbypassRange.init();
    });
})();
