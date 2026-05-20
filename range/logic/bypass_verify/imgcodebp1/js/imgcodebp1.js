/**
 * HeaSec天积安全团队 - 图片验证码绕过1靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    // 全局变量
    var currentLevel = 1;
    var apiBasePath = 'api/';
    var isPassed = false;
    var commonBasePath = '';

    /**
     * 获取当前关卡的API路径
     */
    function getLevelApiPath() {
        return apiBasePath + 'level' + currentLevel + '/';
    }

    /**
     * 初始化靶场
     * @param {number} level 当前关卡
     * @param {string} basePath 公共组件基础路径
     */
    window.initImgCodeBp1 = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        // 绑定表单事件
        bindLoginFormEvent();

        // 绑定验证码刷新事件
        bindCaptchaRefresh();

        // 加载初始验证码
        refreshCaptcha();

        // 关卡特定初始化
        if (currentLevel === 2) {
            initLevel2();
        }
    };

    /**
     * 刷新验证码
     */
    function refreshCaptcha() {
        var captchaImg = document.getElementById('captchaImage');
        if (!captchaImg) return;

        // 第三关使用公共组件，直接更换图片URL
        if (currentLevel === 3) {
            var timestamp = new Date().getTime();
            captchaImg.src = getLevelApiPath() + 'get-captcha.php?t=' + timestamp;
            return;
        }

        // 第一关和第二关调用API获取
        fetch(getLevelApiPath() + 'get-captcha.php', {
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
                }
            })
            .catch(function (error) {
                console.error('获取验证码失败:', error);
            });
    }

    /**
     * 绑定验证码刷新事件
     */
    function bindCaptchaRefresh() {
        var captchaImg = document.getElementById('captchaImage');

        if (captchaImg) {
            captchaImg.addEventListener('click', function () {
                refreshCaptcha();
            });
        }
    }

    /**
     * 第二关特定初始化：验证码自动校验
     */
    function initLevel2() {
        var captchaInput = document.getElementById('captcha');
        var captchaVerified = document.getElementById('captchaVerified');

        if (!captchaInput || !captchaVerified) return;

        // 失去焦点时校验验证码
        captchaInput.addEventListener('blur', function () {
            var captcha = this.value.trim();
            if (!captcha) {
                captchaVerified.value = '0';
                return;
            }

            // 调用验证码校验接口
            fetch(getLevelApiPath() + 'check-captcha.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ captcha: captcha })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    captchaVerified.value = data.verified ? '1' : '0';
                })
                .catch(function (error) {
                    captchaVerified.value = '0';
                });
        });

        // 输入时清除校验状态
        captchaInput.addEventListener('input', function () {
            captchaVerified.value = '0';
        });
    }

    /**
     * 绑定登录表单事件
     */
    function bindLoginFormEvent() {
        var loginForm = document.getElementById('loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value.trim();
            var captcha = document.getElementById('captcha').value.trim();
            var submitBtn = loginForm.querySelector('button[type="submit"]');

            // 清除之前的结果
            hideResult();

            // 根据关卡执行不同的提交逻辑
            switch (currentLevel) {
                case 1:
                    handleLevel1Submit(username, password, captcha, submitBtn);
                    break;
                case 2:
                    handleLevel2Submit(username, password, captcha, submitBtn);
                    break;
                case 3:
                    handleLevel3Submit(username, password, captcha, submitBtn);
                    break;
            }
        });
    }

    /**
     * 第一关提交处理
     */
    function handleLevel1Submit(username, password, captcha, submitBtn) {
        if (!captcha) {
            showModal('error', '提示', '请输入验证码');
            return;
        }

        submitVerify({ username: username, password: password, captcha: captcha }, submitBtn);
    }

    /**
     * 第二关提交处理：携带前端校验结果
     */
    function handleLevel2Submit(username, password, captcha, submitBtn) {
        if (!password) {
            showModal('error', '提示', '请输入密码');
            return;
        }

        var captchaVerified = document.getElementById('captchaVerified');
        var verifiedValue = captchaVerified ? captchaVerified.value : '0';

        submitVerify({
            username: username,
            password: password,
            captcha: captcha,
            captcha_verified: verifiedValue
        }, submitBtn);
    }

    /**
     * 第三关提交处理
     */
    function handleLevel3Submit(username, password, captcha, submitBtn) {
        if (!password) {
            showModal('error', '提示', '请输入密码');
            return;
        }

        if (!captcha) {
            showModal('error', '提示', '请输入验证码');
            return;
        }

        submitVerify({ username: username, password: password, captcha: captcha }, submitBtn);
    }

    /**
     * 调用验证接口
     */
    function submitVerify(data, submitBtn) {
        setButtonLoading(submitBtn, true);

        fetch(getLevelApiPath() + 'verify.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                setButtonLoading(submitBtn, false);

                if (result.success && result.passed) {
                    isPassed = true;
                    showResult('success', result.message);
                    showNextLevelButton();

                    // 如果是第三关，显示恭喜弹窗
                    if (currentLevel === 3) {
                        showCongratsModal({
                            title: '🎉 恭喜你掌握了一个新技能',
                            message: '你掌握了图片验证码绕过攻击的实现方式',
                            buttonText: '继续学习',
                            enableNextRangeButton: true,
                            rangeCode: 'imgcodebp1',
                            updateLearningStatus: true,
                            updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                            learningStatus: '已掌握',
                            nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                            showParticles: true,
                            particleCount: 8,
                            animationDuration: 2000
                        });
                    }
                } else {
                    // 显示错误信息在页面上
                    showResult('error', result.message || '验证失败，请重试');

                    if (currentLevel !== 3) {
                        refreshCaptcha();
                    }
                }
            })
            .catch(function (error) {
                setButtonLoading(submitBtn, false);
                showResult('error', '网络请求失败，请重试');
                // 第三关不刷新验证码
                if (currentLevel !== 3) {
                    refreshCaptcha();
                }
            });
    }

    /**
     * 显示下一关按钮
     */
    function showNextLevelButton() {
        var nextBtn = document.getElementById('nextLevelBtn');
        if (nextBtn) {
            nextBtn.style.display = 'inline-flex';
        }
    }

    /**
     * 设置按钮加载状态
     */
    function setButtonLoading(btn, loading) {
        if (!btn) return;

        if (loading) {
            if (btn.classList.contains('loading')) return;
            btn.classList.add('loading');
            btn.disabled = true;
            var icon = btn.querySelector('i');
            if (icon) {
                btn.dataset.originalIcon = icon.className;
                icon.className = 'fa fa-spinner fa-spin';
            }
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
            var icon = btn.querySelector('i');
            if (icon && btn.dataset.originalIcon) {
                icon.className = btn.dataset.originalIcon;
            }
        }
    }

    /**
     * 显示结果
     */
    function showResult(type, message) {
        var resultArea = document.getElementById('resultArea');
        if (!resultArea) return;

        var iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        var alertClass = type === 'success' ? 'alert-success' : 'alert-error';

        resultArea.innerHTML = '<div class="alert ' + alertClass + '">' +
            '<div><i class="fa ' + iconClass + '"></i> ' +
            '<strong>' + escapeHtml(message) + '</strong></div>' +
            '</div>';
        resultArea.style.display = 'block';
    }

    /**
     * 隐藏结果
     */
    function hideResult() {
        var resultArea = document.getElementById('resultArea');
        if (resultArea) {
            resultArea.style.display = 'none';
            resultArea.innerHTML = '';
        }
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal(config) {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(config);
        } else {
            console.error('恭喜弹窗组件未加载');
            alert(config.message || '恭喜通关！');
        }
    }

    /**
     * 显示模态框
     */
    function showModal(type, title, message) {
        if (window.heasecModalManager && typeof window.heasecModalManager.showModal === 'function') {
            var iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            var iconColor = type === 'success' ? '#28a745' : '#dc3545';
            var escapedMessage = escapeHtml(message)
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            var content = '<div class="text-center">' +
                '<i class="fa ' + iconClass + '" style="font-size: 48px; color: ' + iconColor + '; margin: 20px 0; display: block;"></i>' +
                '<p style="margin: 0; font-size: 16px; color: #333; line-height: 1.6;">' + escapedMessage + '</p>' +
                '</div>';
            // 使用 success_message 模态框类型，并自定义标题和内容
            window.heasecModalManager.showModal('success_message', {
                title: title,
                content: content
            });
            return;
        }

        // 降级方案
        alert(message);
    }

    /**
     * HTML转义
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
