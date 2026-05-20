/**
 * HeaSec天积安全团队 - 用户覆盖靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var config = {
        isLoggedIn: false,
        needAdminVerify: false
    };

    var timers = {};

    /**
     * 初始化靶场
     * @param {object} options - 配置选项
     */
    window.initUseroverride = function (options) {
        config = Object.assign(config, options || {});

        bindLoginTabs();
        bindUsernameLoginForm();
        bindPhoneLoginForm();
        bindRegisterModal();
        bindAdminVerifyModal();
        bindLogoutButton();

        // 如果需要管理员验证，自动弹出模态框
        if (config.needAdminVerify) {
            setTimeout(function () {
                showModal('adminVerifyModal');
            }, 500);
        }
    };

    /**
     * 绑定登录Tab切换
     */
    function bindLoginTabs() {
        var tabs = document.querySelectorAll('.login-tab');
        var forms = document.querySelectorAll('.login-form');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var targetTab = this.getAttribute('data-tab');

                // 切换Tab激活状态
                tabs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');

                // 切换表单显示
                forms.forEach(function (form) {
                    if (form.id === targetTab + 'LoginForm') {
                        form.classList.add('active');
                    } else {
                        form.classList.remove('active');
                    }
                });
            });
        });
    }

    /**
     * 绑定用户名密码登录表单
     */
    function bindUsernameLoginForm() {
        var form = document.getElementById('usernameLoginForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value.trim();
            var captcha = document.getElementById('usernameCaptcha').value.trim();

            if (!username || !password) {
                showResult('usernameLoginResult', false, '请填写用户名和密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    captcha: captcha
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (data.need_admin_verify) {
                            showResult('usernameLoginResult', true, data.message);
                            setTimeout(function () {
                                showModal('adminVerifyModal');
                            }, 1000);
                        } else {
                            showResult('usernameLoginResult', true, data.message);
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        showResult('usernameLoginResult', false, data.message);

                        // 如果需要显示图片验证码
                        if (data.need_captcha) {
                            document.getElementById('usernameCaptchaGroup').style.display = 'block';
                            refreshCaptcha('usernameCaptchaImg');
                        }
                    }
                })
                .catch(function (error) {
                    showResult('usernameLoginResult', false, '请求失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 绑定手机号登录表单
     */
    function bindPhoneLoginForm() {
        var form = document.getElementById('phoneLoginForm');
        if (!form) return;

        // 发送短信验证码按钮
        var sendSmsBtn = document.getElementById('sendSmsCodeBtn');
        if (sendSmsBtn) {
            sendSmsBtn.addEventListener('click', function () {
                var phone = document.getElementById('phone').value.trim();
                var captcha = document.getElementById('phoneCaptcha').value.trim();

                if (!phone) {
                    showResult('phoneLoginResult', false, '请输入手机号');
                    return;
                }

                sendSmsBtn.classList.add('loading');

                fetch('api/send-sms-code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone: phone,
                        captcha: captcha
                    })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showResult('phoneLoginResult', data.success, data.message);
                        if (data.success) {
                            startCountdown(sendSmsBtn, 60, 'sms');
                            // 如果需要显示图片验证码
                            if (data.need_captcha) {
                                document.getElementById('phoneCaptchaGroup').style.display = 'block';
                                refreshCaptcha('phoneCaptchaImg');
                            }
                        }
                    })
                    .catch(function (error) {
                        showResult('phoneLoginResult', false, '请求失败，请稍后重试');
                    })
                    .finally(function () {
                        sendSmsBtn.classList.remove('loading');
                    });
            });
        }

        // 表单提交
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var phone = document.getElementById('phone').value.trim();
            var smsCode = document.getElementById('smsCode').value.trim();
            var captcha = document.getElementById('phoneCaptcha').value.trim();

            if (!phone || !smsCode) {
                showResult('phoneLoginResult', false, '请填写手机号和验证码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/login-by-phone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    phone: phone,
                    sms_code: smsCode,
                    captcha: captcha
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (data.need_admin_verify) {
                            showResult('phoneLoginResult', true, data.message);
                            setTimeout(function () {
                                showModal('adminVerifyModal');
                            }, 1000);
                        } else {
                            showResult('phoneLoginResult', true, data.message);
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        showResult('phoneLoginResult', false, data.message);

                        // 如果需要显示图片验证码
                        if (data.need_captcha) {
                            document.getElementById('phoneCaptchaGroup').style.display = 'block';
                            refreshCaptcha('phoneCaptchaImg');
                        }
                    }
                })
                .catch(function (error) {
                    showResult('phoneLoginResult', false, '请求失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 绑定注册模态框
     */
    function bindRegisterModal() {
        var modal = document.getElementById('registerModal');
        var registerBtns = document.querySelectorAll('#registerBtn, #registerBtn2');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var form = document.getElementById('registerForm');

        // 打开模态框
        registerBtns.forEach(function (btn) {
            if (btn) {
                btn.addEventListener('click', function () {
                    showModal('registerModal');
                    refreshCaptcha('regCaptchaImg');
                });
            }
        });

        // 关闭模态框
        var closeModal = function () {
            hideModal('registerModal');
            if (form) form.reset();
            hideResult('registerResult');
        };

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // 表单提交
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var username = document.getElementById('regUsername').value.trim();
                var phone = document.getElementById('regPhone').value.trim();
                var captcha = document.getElementById('regCaptcha').value.trim();
                var password = document.getElementById('regPassword').value.trim();
                var confirmPassword = document.getElementById('regConfirmPassword').value.trim();

                if (!username || !phone || !captcha || !password || !confirmPassword) {
                    showResult('registerResult', false, '请填写完整信息');
                    return;
                }

                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.classList.add('loading');

                // 先检查手机号是否存在
                fetch('api/check-phone.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone: phone,
                        captcha: captcha
                    })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            showResult('registerResult', false, data.message);
                            refreshCaptcha('regCaptchaImg');
                            return;
                        }

                        // 根据返回结果判断是否允许注册
                        if (data.exists === 't') {
                            showResult('registerResult', false, '手机已存在，不能重复注册');
                            refreshCaptcha('regCaptchaImg');
                            return;
                        }

                        // 继续注册流程
                        return fetch('api/register.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                username: username,
                                phone: phone,
                                password: password,
                                confirm_password: confirmPassword,
                                captcha: captcha
                            })
                        });
                    })
                    .then(function (res) {
                        if (res) return res.json();
                        return null;
                    })
                    .then(function (data) {
                        if (data) {
                            showResult('registerResult', data.success, data.message);
                            if (data.success) {
                                setTimeout(function () {
                                    closeModal();
                                    alert('注册成功，请使用新账号登录');
                                }, 1500);
                            } else {
                                refreshCaptcha('regCaptchaImg');
                            }
                        }
                    })
                    .catch(function (error) {
                        showResult('registerResult', false, '请求失败，请稍后重试');
                        refreshCaptcha('regCaptchaImg');
                    })
                    .finally(function () {
                        if (submitBtn) submitBtn.classList.remove('loading');
                    });
            });
        }
    }

    /**
     * 绑定管理员二次验证模态框
     */
    function bindAdminVerifyModal() {
        var modal = document.getElementById('adminVerifyModal');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var form = document.getElementById('adminVerifyForm');
        var sendCodeBtn = document.getElementById('sendAdminCodeBtn');

        // 关闭模态框
        var closeModal = function () {
            hideModal('adminVerifyModal');
            if (form) form.reset();
            hideResult('adminVerifyResult');
            clearCountdown(sendCodeBtn, 'admin');
        };

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // 发送验证码
        if (sendCodeBtn) {
            sendCodeBtn.addEventListener('click', function () {
                sendCodeBtn.classList.add('loading');

                fetch('api/send-admin-code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showResult('adminVerifyResult', data.success, data.message);
                        if (data.success) {
                            startCountdown(sendCodeBtn, 60, 'admin');
                        }
                    })
                    .catch(function (error) {
                        showResult('adminVerifyResult', false, '请求失败，请稍后重试');
                    })
                    .finally(function () {
                        sendCodeBtn.classList.remove('loading');
                    });
            });
        }

        // 表单提交
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var code = document.getElementById('adminCode').value.trim();

                if (!code) {
                    showResult('adminVerifyResult', false, '请输入验证码');
                    return;
                }

                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.classList.add('loading');

                fetch('api/admin-verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showResult('adminVerifyResult', data.success, data.message);
                        if (data.success) {
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        }
                    })
                    .catch(function (error) {
                        showResult('adminVerifyResult', false, '请求失败，请稍后重试');
                    })
                    .finally(function () {
                        if (submitBtn) submitBtn.classList.remove('loading');
                    });
            });
        }
    }

    /**
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            if (!confirm('确定要退出登录吗？')) return;

            fetch('api/logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    location.reload();
                })
                .catch(function (error) {
                    alert('退出失败，请稍后重试');
                });
        });
    }

    /**
     * 显示结果
     */
    function showResult(areaId, success, message) {
        var area = document.getElementById(areaId);
        if (!area) return;

        if (success) {
            area.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            area.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        area.style.display = 'block';
    }

    /**
     * 隐藏结果
     */
    function hideResult(areaId) {
        var area = document.getElementById(areaId);
        if (area) {
            area.style.display = 'none';
            area.innerHTML = '';
        }
    }

    /**
     * 显示模态框
     */
    function showModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    /**
     * 隐藏模态框
     */
    function hideModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * 刷新验证码图片
     */
    function refreshCaptcha(imgId) {
        var img = document.getElementById(imgId);
        if (img) {
            img.src = 'api/captcha.php?t=' + Date.now();
        }
    }

    /**
     * 开始倒计时
     */
    function startCountdown(btn, seconds, type) {
        var originalHtml = btn.innerHTML;
        var remaining = seconds;
        var timerId = type + 'CountdownTimer';

        btn.classList.add('countdown');
        btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

        timers[timerId] = setInterval(function () {
            remaining--;
            btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

            if (remaining <= 0) {
                clearInterval(timers[timerId]);
                btn.classList.remove('countdown');
                btn.innerHTML = originalHtml;
            }
        }, 1000);
    }

    /**
     * 清除倒计时
     */
    function clearCountdown(btn, type) {
        var timerId = type + 'CountdownTimer';
        if (timers[timerId]) {
            clearInterval(timers[timerId]);
            timers[timerId] = null;
        }
        if (btn) {
            btn.classList.remove('countdown');
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> 获取验证码';
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
})();
