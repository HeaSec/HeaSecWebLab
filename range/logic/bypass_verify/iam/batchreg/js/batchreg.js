/**
 * HeaSec天积安全团队 - 批量注册靶场交互脚本
 * Batch Registration Range Interactive Script
 * 版本: v1.0.0
 * 创建日期: 2026-02-14
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var modal = null;
    var successModal = null;
    var registerBtn = null;
    var closeBtn = null;
    var cancelBtn = null;
    var successBtn = null;
    var registerForm = null;
    var sendSmsBtn = null;
    var countdownTimer = null;

    /**
     * 初始化靶场
     */
    function init() {
        modal = document.getElementById('registerModal');
        successModal = document.getElementById('registerSuccessModal');
        registerBtn = document.getElementById('registerBtn');
        closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        successBtn = document.getElementById('registerSuccessBtn');
        registerForm = document.getElementById('registerForm');
        sendSmsBtn = document.getElementById('sendSmsBtn');

        bindRegisterModal();
        bindSuccessModal();
        bindSendSms();
        bindRegisterForm();
    }

    /**
     * 绑定注册模态框
     */
    function bindRegisterModal() {
        if (registerBtn && modal) {
            registerBtn.addEventListener('click', function () {
                modal.style.display = 'flex';
                // 刷新验证码
                refreshCaptcha();
            });
        }

        var closeModal = function () {
            if (modal) {
                modal.style.display = 'none';
                if (registerForm) {
                    registerForm.reset();
                }
                hideResult();
                clearCountdown();
            }
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }
    }

    /**
     * 绑定成功模态框
     */
    function bindSuccessModal() {
        if (successBtn) {
            successBtn.addEventListener('click', function () {
                location.reload();
            });
        }
    }

    /**
     * 绑定发送短信验证码
     */
    function bindSendSms() {
        if (sendSmsBtn) {
            sendSmsBtn.addEventListener('click', function () {
                var phone = document.getElementById('reg_phone').value.trim();
                var captcha = document.getElementById('reg_captcha').value.trim();

                if (!phone) {
                    showResult(false, '请输入手机号');
                    return;
                }

                if (!captcha) {
                    showResult(false, '请输入图片验证码');
                    return;
                }

                if (!/^1[3-9]\d{9}$/.test(phone)) {
                    showResult(false, '手机号格式不正确');
                    return;
                }

                sendSmsBtn.classList.add('loading');
                sendSmsBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 发送中...';

                fetch('api/send-sms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone: phone, captcha: captcha })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showResult(data.success, data.message);
                        if (data.success) {
                            startCountdown(60);
                        } else {
                            resetSendSmsBtn();
                            refreshCaptcha();
                        }
                    })
                    .catch(function (error) {
                        showResult(false, '请求失败，请稍后重试');
                        resetSendSmsBtn();
                    })
                    .finally(function () {
                        sendSmsBtn.classList.remove('loading');
                    });
            });
        }
    }

    /**
     * 绑定注册表单
     */
    function bindRegisterForm() {
        if (registerForm) {
            registerForm.addEventListener('submit', function (e) {
                e.preventDefault();

                var username = document.getElementById('reg_username').value.trim();
                var nickname = document.getElementById('reg_nickname').value.trim();
                var phone = document.getElementById('reg_phone').value.trim();
                var captcha = document.getElementById('reg_captcha').value.trim();
                var smsCode = document.getElementById('reg_sms_code').value.trim();
                var password = document.getElementById('reg_password').value.trim();
                var confirmPassword = document.getElementById('reg_confirm_password').value.trim();

                // 前端验证
                if (!username || !nickname || !phone || !captcha || !smsCode || !password || !confirmPassword) {
                    showResult(false, '请填写完整信息');
                    return;
                }

                if (!/^1[3-9]\d{9}$/.test(phone)) {
                    showResult(false, '手机号格式不正确');
                    return;
                }

                if (password !== confirmPassword) {
                    showResult(false, '两次密码不一致');
                    return;
                }

                if (password.length < 6) {
                    showResult(false, '密码长度不能少于6位');
                    return;
                }

                var submitBtn = registerForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 注册中...';
                }

                fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: username,
                        nickname: nickname,
                        phone: phone,
                        captcha: captcha,
                        sms_code: smsCode,
                        password: password,
                        confirm_password: confirmPassword
                    })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            // 关闭注册模态框
                            if (modal) {
                                modal.style.display = 'none';
                            }
                            // 显示成功模态框
                            if (successModal) {
                                successModal.style.display = 'flex';
                            }
                        } else {
                            showResult(false, data.message);
                            if (submitBtn) {
                                submitBtn.classList.remove('loading');
                                submitBtn.innerHTML = '<i class="fa fa-check"></i> 注册';
                            }
                        }
                    })
                    .catch(function (error) {
                        showResult(false, '请求失败，请稍后重试');
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                            submitBtn.innerHTML = '<i class="fa fa-check"></i> 注册';
                        }
                    });
            });
        }
    }

    /**
     * 刷新验证码
     */
    function refreshCaptcha() {
        var captchaImg = document.getElementById('captchaImg');
        if (captchaImg) {
            captchaImg.src = 'api/captcha.php?t=' + Date.now();
        }
    }

    /**
     * 开始倒计时
     */
    function startCountdown(seconds) {
        var remaining = seconds;
        sendSmsBtn.classList.add('countdown');
        sendSmsBtn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

        countdownTimer = setInterval(function () {
            remaining--;
            sendSmsBtn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

            if (remaining <= 0) {
                clearCountdown();
            }
        }, 1000);
    }

    /**
     * 清除倒计时
     */
    function clearCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        resetSendSmsBtn();
    }

    /**
     * 重置发送短信按钮
     */
    function resetSendSmsBtn() {
        if (sendSmsBtn) {
            sendSmsBtn.classList.remove('countdown');
            sendSmsBtn.innerHTML = '<i class="fa fa-paper-plane"></i> 获取验证码';
        }
    }

    /**
     * 显示结果
     */
    function showResult(success, message) {
        var resultArea = document.getElementById('registerResultArea');
        if (resultArea) {
            if (success) {
                resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
            } else {
                resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
            }
            resultArea.style.display = 'block';
        }
    }

    /**
     * 隐藏结果
     */
    function hideResult() {
        var resultArea = document.getElementById('registerResultArea');
        if (resultArea) {
            resultArea.style.display = 'none';
            resultArea.innerHTML = '';
        }
    }

    /**
     * HTML转义函数，防止XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', init);
})();
