/**
 * HeaSec天积安全团队 - 图片验证码绕过2靶场前端脚本
 * 版本: v1.0.2
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    /**
     * 靶场功能模块
     */
    var ImgCodeBP2 = {
        // 初始化
        init: function () {
            this.loadCaptcha();
            this.bindEvents();
        },

        // 绑定事件
        bindEvents: function () {
            var self = this;

            // 验证码图片点击刷新
            var captchaImg = document.getElementById('captchaImage');
            if (captchaImg) {
                captchaImg.addEventListener('click', function () {
                    self.loadCaptcha();
                });
            }



            // 表单提交校验
            var loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function (e) {
                    var captchaInput = document.getElementById('captcha');
                    if (captchaInput && captchaInput.value.trim() === '') {
                        e.preventDefault();
                        self.showMessage('验证码不能为空', 'error');
                        captchaInput.focus();
                        return false;
                    }
                });
            }
        },

        // 显示提示消息
        showMessage: function (message, type) {
            var resultDiv = document.getElementById('heasec-verifyResult');
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.className = 'heasec-verify-' + type;
                resultDiv.innerHTML = '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'times-circle') + '"></i>' +
                    message;

                // 3秒后自动隐藏
                setTimeout(function () {
                    resultDiv.style.display = 'none';
                }, 3000);
            }
        },

        // 加载验证码
        loadCaptcha: function () {
            var captchaImg = document.getElementById('captchaImage');

            fetch('api/get-captcha.php', {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.success && data.image) {
                        captchaImg.src = data.image;
                    } else {
                        console.error('加载验证码失败');
                    }
                })
                .catch(function (error) {
                    console.error('加载验证码出错:', error);
                });
        }
    };

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ImgCodeBP2.init();
        });
    } else {
        ImgCodeBP2.init();
    }

    // 暴露到全局（使用项目统一的命名空间）
    window.HeaSec = window.HeaSec || {};
    window.HeaSec.ImgCodeBP2 = ImgCodeBP2;

})();
