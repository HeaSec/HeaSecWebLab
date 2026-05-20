/**
 * HeaSec天积安全团队 - 基础流程绕过靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-01-17
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
    window.initBvbase = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        // 绑定表单事件
        bindApplyFormEvent();
        bindVerifyFormEvent();

        // 关卡特定初始化
        if (currentLevel === 3) {
            initLevel3();
        }
    };

    /**
     * 第三关特定初始化：手机号输入时自动校验
     */
    function initLevel3() {
        var phoneInput = document.getElementById('phone');
        var phoneCheck = document.getElementById('phoneCheck');
        var phoneStatus = document.getElementById('phoneStatus');

        if (!phoneInput || !phoneCheck) return;

        // 失去焦点时校验
        phoneInput.addEventListener('blur', function () {
            var phone = this.value.trim();
            if (!phone) {
                // updatePhoneStatus('', ''); // 隐藏前端反馈
                phoneCheck.value = '';
                return;
            }

            // 调用第二关校验接口
            fetch(getLevelApiPath() + 'check-phone.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ phone: phone })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    phoneCheck.value = data.status;
                    // updatePhoneStatus(data.status, data.message); // 隐藏前端反馈
                })
                .catch(function (error) {
                    phoneCheck.value = 'error';
                    // updatePhoneStatus('error', '网络请求失败'); // 隐藏前端反馈
                });
        });

        // 输入时清除状态
        phoneInput.addEventListener('input', function () {
            phoneCheck.value = '';
            // updatePhoneStatus('', ''); // 隐藏前端反馈
        });
    }

    /**
     * 更新手机号校验状态显示
     */
    function updatePhoneStatus(status, message) {
        var phoneStatus = document.getElementById('phoneStatus');
        if (!phoneStatus) return;

        if (!status) {
            phoneStatus.style.display = 'none';
            phoneStatus.textContent = '';
            phoneStatus.className = 'phone-status';
            return;
        }

        phoneStatus.style.display = 'block';
        phoneStatus.textContent = message;

        if (status === 'success') {
            phoneStatus.className = 'phone-status success';
        } else if (status === 'fail' || status === 'error') {
            phoneStatus.className = 'phone-status error';
        } else {
            phoneStatus.className = 'phone-status pending';
        }
    }

    /**
     * 绑定领奖申请表单事件
     */
    function bindApplyFormEvent() {
        var applyForm = document.getElementById('applyForm');
        if (!applyForm) return;

        applyForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var phone = document.getElementById('phone').value.trim();
            var submitBtn = applyForm.querySelector('button[type="submit"]');

            // 清除之前的结果
            hideResult();

            // 根据关卡执行不同的校验逻辑
            switch (currentLevel) {
                case 1:
                    handleLevel1Submit(phone, submitBtn);
                    break;
                case 2:
                    handleLevel2Submit(phone, submitBtn);
                    break;
                case 3:
                    handleLevel3Submit(phone, submitBtn);
                    break;
            }
        });
    }

    /**
     * 第一关提交处理：前端校验，后端不校验范围
     */
    function handleLevel1Submit(phone, submitBtn) {
        // 前端校验：只允许110开头的11位手机号
        if (!/^110\d{8}$/.test(phone)) {
            showResult('error', '手机号不在领奖范围内，只允许110开头的11位手机号申请领奖', 'apply');
            return;
        }

        // 调用第一关发送接口
        sendCode(phone, submitBtn);
    }

    /**
     * 第二关提交处理：前端先校验，通过后由前端调用发送接口
     */
    function handleLevel2Submit(phone, submitBtn) {
        // 设置加载状态
        setButtonLoading(submitBtn, true);

        // 先调用第二关校验接口
        fetch(getLevelApiPath() + 'check-phone.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone: phone })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.allowed) {
                    // 校验通过，前端调用第二关发送接口
                    sendCode(phone, submitBtn);
                } else {
                    setButtonLoading(submitBtn, false);
                    showResult('error', data.message || '手机号不在领奖范围内，只允许110开头的11位手机号申请领奖', 'apply');
                }
            })
            .catch(function (error) {
                setButtonLoading(submitBtn, false);
                showResult('error', '网络请求失败，请重试', 'apply');
            });
    }

    /**
     * 第三关提交处理：依赖隐藏字段值
     */
    function handleLevel3Submit(phone, submitBtn) {
        var phoneCheck = document.getElementById('phoneCheck');
        var checkValue = phoneCheck ? phoneCheck.value : '';

        // 检查是否已校验
        if (!checkValue) {
            showResult('error', '请先输入手机号完成校验', 'apply');
            return;
        }

        // 调用第三关发送接口，携带隐藏字段值
        sendCodeWithCheck(phone, checkValue, submitBtn);
    }

    /**
     * 调用发送验证码接口（第一关和第二关使用）
     */
    function sendCode(phone, submitBtn) {
        setButtonLoading(submitBtn, true);

        fetch(getLevelApiPath() + 'send-code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone: phone })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                setButtonLoading(submitBtn, false);

                if (data.success) {
                    // 显示成功弹窗
                    showModal('success', '发送成功', data.message);
                } else {
                    showResult('error', data.message || '发送失败', 'apply');
                }
            })
            .catch(function (error) {
                setButtonLoading(submitBtn, false);
                showResult('error', '网络请求失败，请重试', 'apply');
            });
    }

    /**
     * 调用发送验证码接口（第三关使用，携带隐藏字段）
     */
    function sendCodeWithCheck(phone, phoneCheck, submitBtn) {
        setButtonLoading(submitBtn, true);

        fetch(getLevelApiPath() + 'send-code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                phone: phone,
                phone_check: phoneCheck
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                setButtonLoading(submitBtn, false);

                if (data.success) {
                    // 显示成功弹窗
                    showModal('success', '发送成功', data.message);
                } else {
                    showResult('error', data.message || '发送失败', 'apply');
                }
            })
            .catch(function (error) {
                setButtonLoading(submitBtn, false);
                showResult('error', '网络请求失败，请重试', 'apply');
            });
    }

    /**
     * 绑定礼品兑换表单事件
     */
    function bindVerifyFormEvent() {
        var verifyForm = document.getElementById('verifyForm');
        if (!verifyForm) return;

        verifyForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var code = document.getElementById('code').value.trim();
            var submitBtn = verifyForm.querySelector('button[type="submit"]');

            if (!code) {
                showResult('error', '请输入通关密码', 'verify');
                return;
            }

            // 调用验证接口
            verifyCode(code, submitBtn);
        });
    }

    /**
     * 调用验证密码接口
     */
    function verifyCode(code, submitBtn) {
        setButtonLoading(submitBtn, true);
        hideResult('verify'); // 只隐藏验证区域

        fetch(getLevelApiPath() + 'verify-code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code: code })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                setButtonLoading(submitBtn, false);

                if (data.passed) {
                    isPassed = true;
                    showResult('success', data.message, 'verify');
                    showNextLevelButton();

                    // 如果是第三关，显示恭喜弹窗
                    if (currentLevel === 3) {
                        showCongratsModal({
                            title: '🎉 恭喜你掌握了一个新技能',
                            message: '你掌握了基础流程绕过攻击的实现方式',
                            buttonText: '继续学习',
                            enableNextRangeButton: true,
                            rangeCode: 'bvbase',
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
                    showResult('error', data.message || '验证失败', 'verify');
                }
            })
            .catch(function (error) {
                setButtonLoading(submitBtn, false);
                showResult('error', '网络请求失败，请重试', 'verify');
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
            if (btn.classList.contains('loading')) return; // Prevent double-loading state
            btn.classList.add('loading');
            btn.disabled = true;
            var icon = btn.querySelector('i');
            if (icon) {
                btn.dataset.originalIcon = icon.className;
                icon.className = 'fa fa-spinner fa-spin'; // Added fa-spin for animation
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
     * @param {string} type - 消息类型 'success' 或 'error'
     * @param {string} message - 消息内容
     * @param {string} target - 目标区域 'apply' 或 'verify'，默认为 'verify'
     */
    function showResult(type, message, target) {
        target = target || 'verify';
        var resultAreaId = target === 'apply' ? 'applyResultArea' : 'verifyResultArea';
        var resultArea = document.getElementById(resultAreaId);
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
     * @param {string} target - 目标区域 'apply' 或 'verify'，不指定则隐藏所有
     */
    function hideResult(target) {
        var areas = [];
        if (!target) {
            // 隐藏所有区域
            areas = ['applyResultArea', 'verifyResultArea'];
        } else {
            areas = [target === 'apply' ? 'applyResultArea' : 'verifyResultArea'];
        }

        for (var i = 0; i < areas.length; i++) {
            var resultArea = document.getElementById(areas[i]);
            if (resultArea) {
                resultArea.style.display = 'none';
                resultArea.innerHTML = '';
            }
        }
    }

    /**
     * 显示恭喜弹窗
     * @param {object} config - 弹窗配置对象
     */
    function showCongratsModal(config) {
        // 使用星星系统的恭喜弹窗组件
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(config);
        } else {
            console.error(' 恭喜弹窗组件未加载');
            // 降级处理：显示简单提示
            alert(config.message || '恭喜通关！');
        }
    }

    /**
     * 显示模态框
     */
    function showModal(type, title, message) {
        // 使用公共组件的模态框，如果可用
        if (window.heasecModalManager && typeof window.heasecModalManager.showModal === 'function') {
            // 构造正确的content参数
            var escapedMessage = escapeHtml(message)
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            var content = '<div class="text-center">' +
                '<i class="fa fa-check-circle success-icon" style="font-size: 48px; color: #28a745; margin: 20px 0;"></i>' +
                '<p class="success-message-content" style="margin: 0; font-size: 16px; color: #333; line-height: 1.6;">' + escapedMessage + '</p>' +
                '</div>';
            window.heasecModalManager.showModal('success_message', {
                title: title,
                content: content
            });
            return;
        }

        // 降级方案：使用alert
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
