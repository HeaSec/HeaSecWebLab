/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var isFinalLevel = false;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {boolean} finalLevel - 是否是最后一关
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initResetstepbp = function (level, finalLevel, basePath) {
        currentLevel = level || 1;
        isFinalLevel = finalLevel || false;
        commonBasePath = basePath || '';

        if (currentLevel === 1) {
            bindForgotPasswordModalLevel1();
        } else if (currentLevel === 2) {
            bindForgotPasswordModalLevel2();
        } else if (currentLevel === 3) {
            bindForgotPasswordModalLevel3();
        }

        bindVerifyForm();
        bindLoginForm();
    };

    /**
     * 绑定第一关忘记密码模态框（iframe方式）
     */
    function bindForgotPasswordModalLevel1() {
        var modal = document.getElementById('forgotPasswordModal');
        var btn = document.getElementById('forgotPasswordBtn');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var iframe = document.getElementById('resetFrame');

        if (btn && modal) {
            btn.addEventListener('click', function () {
                modal.style.display = 'flex';
                // 重置iframe到第一步
                if (iframe) {
                    iframe.src = 'reset/step1.php';
                }
            });
        }

        var closeModal = function () {
            if (modal) {
                modal.style.display = 'none';
                if (iframe) {
                    iframe.src = 'about:blank';
                }
            }
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // [HeaSec Update] 禁用点击遮罩层关闭模态框
        /*
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        */

        // 监听iframe消息（用于处理密码重置成功后刷新页面）
        window.addEventListener('message', function (e) {
            if (e.data === 'resetSuccess') {
                closeModal();
                location.reload();
            }
        });
    }

    /**
     * 绑定第二关忘记密码模态框（两步式）
     */
    function bindForgotPasswordModalLevel2() {
        var modal = document.getElementById('forgotPasswordModal');
        var btn = document.getElementById('forgotPasswordBtn');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var verifyPhoneForm = document.getElementById('verifyPhoneForm');
        var resetPasswordForm = document.getElementById('resetPasswordForm');
        var sendCodeBtn = document.getElementById('sendCodeBtn');
        var backToStep1Btn = document.getElementById('backToStep1Btn');

        if (btn && modal) {
            btn.addEventListener('click', function () {
                modal.style.display = 'flex';
                showStep(1);
            });
        }

        var closeModal = function () {
            if (modal) {
                modal.style.display = 'none';
                if (verifyPhoneForm) verifyPhoneForm.reset();
                if (resetPasswordForm) resetPasswordForm.reset();
                hideAllResults();
                clearCountdown(sendCodeBtn);
                showStep(1);
            }
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // [HeaSec Update] 禁用点击遮罩层关闭模态框
        /*
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        */

        // 绑定发送验证码按钮
        if (sendCodeBtn) {
            sendCodeBtn.addEventListener('click', function () {
                var username = document.getElementById('verify_username').value.trim();
                var phone = document.getElementById('verify_phone').value.trim();
                var captcha = document.getElementById('verify_captcha').value.trim();

                if (username && phone && captcha) {
                    sendCodeLevel2(username, phone, captcha, sendCodeBtn);
                } else {
                    showResult('verifyPhoneResultArea', false, '请填写完整信息');
                }
            });
        }

        // 绑定第一步表单提交
        if (verifyPhoneForm) {
            verifyPhoneForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitVerifyPhoneLevel2();
            });
        }

        // 绑定返回上一步按钮
        if (backToStep1Btn) {
            backToStep1Btn.addEventListener('click', function () {
                showStep(1);
            });
        }

        // 绑定第二步表单提交
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitResetPasswordLevel2();
            });
        }
    }

    /**
     * 绑定第三关忘记密码模态框（单步式 - 发送重置链接）
     */
    function bindForgotPasswordModalLevel3() {
        var modal = document.getElementById('forgotPasswordModal');
        var btn = document.getElementById('forgotPasswordBtn');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var sendResetLinkForm = document.getElementById('sendResetLinkForm');

        if (btn && modal) {
            btn.addEventListener('click', function () {
                modal.style.display = 'flex';
            });
        }

        var closeModal = function () {
            if (modal) {
                modal.style.display = 'none';
                if (sendResetLinkForm) sendResetLinkForm.reset();
                var resultArea = document.getElementById('sendResetLinkResultArea');
                if (resultArea) {
                    resultArea.style.display = 'none';
                    resultArea.innerHTML = '';
                }
            }
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // 绑定表单提交事件
        if (sendResetLinkForm) {
            sendResetLinkForm.addEventListener('submit', function (e) {
                e.preventDefault();

                var username = document.getElementById('reset_link_username').value.trim();
                if (!username) {
                    showResult('sendResetLinkResultArea', false, '请输入账号');
                    return;
                }

                var submitBtn = sendResetLinkForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                }

                fetch('api/level3/send-reset-link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: username })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showResult('sendResetLinkResultArea', data.success, data.message);
                        if (data.success) {
                            setTimeout(function () {
                                closeModal();
                            }, 1500);
                        }
                    })
                    .catch(function (error) {
                        showResult('sendResetLinkResultArea', false, '请求失败，请稍后重试');
                    })
                    .finally(function () {
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                        }
                    });
            });
        }
    }

    /**
     * 显示指定步骤
     */
    function showStep(step) {
        var verifyPhoneForm = document.getElementById('verifyPhoneForm');
        var resetPasswordForm = document.getElementById('resetPasswordForm');

        if (step === 1) {
            if (verifyPhoneForm) verifyPhoneForm.style.display = 'block';
            if (resetPasswordForm) resetPasswordForm.style.display = 'none';
        } else if (step === 2) {
            if (verifyPhoneForm) verifyPhoneForm.style.display = 'none';
            if (resetPasswordForm) resetPasswordForm.style.display = 'block';
        }
    }

    /**
     * 隐藏所有结果区域
     */
    function hideAllResults() {
        var areas = ['verifyPhoneResultArea', 'resetPasswordResultArea'];
        areas.forEach(function (id) {
            var area = document.getElementById(id);
            if (area) {
                area.style.display = 'none';
                area.innerHTML = '';
            }
        });
    }

    /**
     * 第二关发送验证码
     */
    function sendCodeLevel2(username, phone, captcha, btn) {
        btn.classList.add('loading');

        fetch('api/level2/send-code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, phone: phone, captcha: captcha })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                showResult('verifyPhoneResultArea', data.success, data.message);
                if (data.success) {
                    startCountdown(btn, 60);
                    // 刷新验证码图片
                    var captchaImg = document.getElementById('captchaImg');
                    if (captchaImg) {
                        captchaImg.src = 'api/captcha.php?t=' + Date.now();
                    }
                }
            })
            .catch(function (error) {
                showResult('verifyPhoneResultArea', false, '请求失败，请稍后重试');
            })
            .finally(function () {
                btn.classList.remove('loading');
            });
    }

    /**
     * 第二关验证手机
     */
    function submitVerifyPhoneLevel2() {
        var username = document.getElementById('verify_username').value.trim();
        var phone = document.getElementById('verify_phone').value.trim();
        var smsCaptcha = document.getElementById('verify_sms_captcha').value.trim();
        var imgCaptcha = document.getElementById('verify_captcha').value.trim();

        if (!username || !phone || !smsCaptcha || !imgCaptcha) {
            showResult('verifyPhoneResultArea', false, '请填写完整信息');
            return;
        }

        var submitBtn = document.querySelector('#verifyPhoneForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('loading');
        }

        fetch('api/level2/verify-phone.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                phone: phone,
                sms_captcha: smsCaptcha,
                captcha: imgCaptcha
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                showResult('verifyPhoneResultArea', data.success, data.message);
                if (data.success) {
                    setTimeout(function () {
                        showStep(2);
                        // 将账号填入第二步隐藏字段
                        var resetUsernameInput = document.getElementById('reset_username');
                        if (resetUsernameInput) {
                            resetUsernameInput.value = username;
                        }
                    }, 1000);
                }
            })
            .catch(function (error) {
                showResult('verifyPhoneResultArea', false, '请求失败，请稍后重试');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 第二关提交重置密码
     */
    function submitResetPasswordLevel2() {
        var username = document.getElementById('reset_username').value.trim();
        var password = document.getElementById('reset_password').value.trim();
        var confirmPassword = document.getElementById('reset_confirm_password').value.trim();

        if (!username || !password || !confirmPassword) {
            showResult('resetPasswordResultArea', false, '请填写完整信息');
            return;
        }

        var submitBtn = document.querySelector('#resetPasswordForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('loading');
        }

        fetch('api/level2/reset-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                password: password,
                confirm_password: confirmPassword
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                showResult('resetPasswordResultArea', data.success, data.message);
                if (data.success) {
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                }
            })
            .catch(function (error) {
                showResult('resetPasswordResultArea', false, '请求失败，请稍后重试');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                // 表单会通过POST提交，不需要额外处理
            });
        }
    }

    /**
     * 绑定通关密码验证表单
     */
    function bindVerifyForm() {
        var form = document.getElementById('verifyForm');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var passcode = document.getElementById('passcode').value.trim();

                if (!passcode) {
                    showResult('verifyResultArea', false, '请输入通关密码');
                    return;
                }

                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                }

                fetch('api/level' + currentLevel + '/verify-passcode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ passcode: passcode })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        var resultArea = document.getElementById('verifyResultArea');
                        if (resultArea) {
                            if (data.passed) {
                                resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(data.message) + '</span></div>';

                                // 显示下一关按钮
                                var nextLevelBtn = document.getElementById('nextLevelBtn');
                                if (nextLevelBtn) {
                                    nextLevelBtn.style.display = 'inline-flex';
                                }

                                // 如果是最后一关（第二关），直接显示恭喜弹窗
                                if (isFinalLevel) {
                                    try {
                                        showCongratsModal({
                                            title: '🎉 恭喜你掌握了一个新技能',
                                            message: '你掌握了密码重置流程绕过漏洞的利用方式',
                                            buttonText: '继续学习',
                                            enableNextRangeButton: true,
                                            rangeCode: 'resetstepbp',
                                            nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                                            updateLearningStatus: true,
                                            updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                                            learningStatus: '已掌握',
                                            showParticles: true,
                                            particleCount: 8,
                                            animationDuration: 2000
                                        });
                                    } catch (e) {
                                        console.error('[HeaSec] Congrats modal error:', e);
                                    }
                                }
                            } else {
                                resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                            }
                            resultArea.style.display = 'block';
                        }
                    })
                    .catch(function (error) {
                        var resultArea = document.getElementById('verifyResultArea');
                        if (resultArea) {
                            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>验证失败，请稍后重试</span></div>';
                            resultArea.style.display = 'block';
                        }
                    })
                    .finally(function () {
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                        }
                    });
            });
        }
    }

    /**
     * 显示结果
     */
    function showResult(areaId, success, message) {
        var resultArea = document.getElementById(areaId);
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
     * 开始倒计时
     */
    function startCountdown(btn, seconds) {
        var originalHtml = btn.innerHTML;
        var remaining = seconds;

        btn.classList.add('countdown');
        btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

        var countdownTimer = setInterval(function () {
            remaining--;
            btn.innerHTML = '<i class="fa fa-clock-o"></i> ' + remaining + 's';

            if (remaining <= 0) {
                clearInterval(countdownTimer);
                btn.classList.remove('countdown');
                btn.innerHTML = originalHtml;
            }
        }, 1000);
    }

    /**
     * 清除倒计时
     */
    function clearCountdown(btn) {
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

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal(config) {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(config);
        } else {
            console.error('[HeaSec] 恭喜弹窗组件未加载');
            alert(config.message || '恭喜通关！');
        }
    }

    // 将函数暴露到全局作用域，以便在API响应中调用
    window.showCongratsModal = showCongratsModal;
})();
