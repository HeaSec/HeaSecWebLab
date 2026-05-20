/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initResetlink = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        bindForgotPasswordModal();
        bindAddFriendModal();
        bindVerifyForm();
        bindLoginForm();
        // 检查并显示已添加的好友信息
        checkAndDisplayFriendInfo();
    };

    /**
     * 绑定忘记密码模态框
     */
    function bindForgotPasswordModal() {
        var modal = document.getElementById('forgotPasswordModal');
        var btn = document.getElementById('forgotPasswordBtn');
        var closeBtn = modal.querySelector('.heasec-modal-close');
        var cancelBtn = modal.querySelector('.modal-cancel');
        var form = document.getElementById('forgotPasswordForm');

        if (btn) {
            btn.addEventListener('click', function () {
                modal.style.display = 'flex';
            });
        }

        var closeModal = function () {
            modal.style.display = 'none';
            // 清空表单和结果区域
            form.reset();
            var resultArea = document.getElementById('resetResultArea');
            if (resultArea) {
                resultArea.style.display = 'none';
                resultArea.innerHTML = '';
            }
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // 点击模态框外部关闭
        // [HeaSec Update] 禁用点击遮罩层关闭模态框
        /*
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        */

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var username = document.getElementById('reset_username').value.trim();
                if (username) {
                    sendResetRequest(username);
                }
            });
        }
    }

    /**
     * 发送密码重置请求
     * @param {string} username - 用户名
     */
    function sendResetRequest(username) {
        var resultArea = document.getElementById('resetResultArea');
        var submitBtn = document.querySelector('#forgotPasswordForm button[type="submit"]');

        // 显示加载状态
        if (submitBtn) {
            submitBtn.classList.add('loading');
        }

        fetch('api/level' + currentLevel + '/send-reset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (resultArea) {
                    if (data.success) {
                        var displayMessage = escapeHtml(data.message);
                        // 第三关：显示时间戳和有效期提示
                        if (currentLevel === 3 && data.timestamp) {
                            var timestampDisplay = new Date(data.timestamp * 1000).toLocaleString('zh-CN');
                            displayMessage += '<br><br><strong>生成时间戳：</strong>' + data.timestamp + '<br>';
                            displayMessage += '<strong>时间：</strong>' + timestampDisplay + '<br>';
                            displayMessage += '<strong class="highlight-info">⚠️ 重要：密码重置链接1小时内有效</strong>';
                        }
                        resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + displayMessage + '</span></div>';
                    } else {
                        resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                    }
                    resultArea.style.display = 'block';
                }
            })
            .catch(function (error) {
                if (resultArea) {
                    resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请求失败，请稍后重试</span></div>';
                    resultArea.style.display = 'block';
                }
            })
            .finally(function () {
                // 移除加载状态
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 绑定添加好友模态框
     */
    function bindAddFriendModal() {
        var modal = document.getElementById('addFriendModal');
        var btn = document.getElementById('addFriendBtn');
        var closeBtn = modal ? modal.querySelector('.heasec-modal-close') : null;
        var cancelBtn = modal ? modal.querySelector('.modal-cancel') : null;
        var form = document.getElementById('addFriendForm');

        if (btn && modal) {
            btn.addEventListener('click', function () {
                modal.style.display = 'flex';
            });
        }

        var closeModal = function () {
            if (modal) {
                modal.style.display = 'none';
                // 清空表单和结果区域
                if (form) {
                    form.reset();
                }
                var resultArea = document.getElementById('addFriendResultArea');
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

        // 点击模态框外部关闭
        // [HeaSec Update] 禁用点击遮罩层关闭模态框
        /*
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }
        */

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var username = document.getElementById('friend_username').value.trim();
                if (username) {
                    sendSearchFriendRequest(username);
                }
            });
        }
    }

    /**
     * 发送搜索好友请求
     * @param {string} username - 好友账号
     */
    function sendSearchFriendRequest(username) {
        var resultArea = document.getElementById('addFriendResultArea');
        var submitBtn = document.querySelector('#addFriendForm button[type="submit"]');

        // 显示加载状态
        if (submitBtn) {
            submitBtn.classList.add('loading');
        }

        fetch('api/level' + currentLevel + '/add-friend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'search', username: username })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (resultArea) {
                    if (data.success && data.action === 'search') {
                        // 保存搜索到的好友信息
                        currentSearchedFriend = data.friend;
                        // 显示好友信息和确认添加按钮
                        var html = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                        html += '<div class="friend-result-confirm" style="margin-top: 15px; padding: 15px; background: #f5f5f5; border-radius: 4px;">';
                        html += '<div class="info-row"><span class="info-label">账号：</span><span class="info-value">' + escapeHtml(data.friend.username) + '</span></div>';
                        html += '<div class="info-row"><span class="info-label">用户ID：</span><span class="info-value">' + escapeHtml(data.friend.user_id) + '</span></div>';
                        html += '<div class="info-row"><span class="info-label">手机号：</span><span class="info-value">' + escapeHtml(data.friend.phone) + '</span></div>';
                        html += '<button type="button" class="tech-btn tech-btn-primary" id="confirmAddFriendBtn" style="margin-top: 10px; width: 100%;">';
                        html += '<i class="fa fa-check"></i> 确认添加';
                        html += '</button>';
                        html += '</div>';
                        resultArea.innerHTML = html;
                        // 绑定确认添加按钮事件
                        var confirmBtn = document.getElementById('confirmAddFriendBtn');
                        if (confirmBtn) {
                            confirmBtn.addEventListener('click', function () {
                                confirmAddFriend();
                            });
                        }
                    } else {
                        resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                    }
                    resultArea.style.display = 'block';
                }
            })
            .catch(function (error) {
                if (resultArea) {
                    resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请求失败，请稍后重试</span></div>';
                    resultArea.style.display = 'block';
                }
            })
            .finally(function () {
                // 移除加载状态
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 确认添加好友
     */
    function confirmAddFriend() {
        if (!currentSearchedFriend) {
            return;
        }

        var resultArea = document.getElementById('addFriendResultArea');
        var confirmBtn = document.getElementById('confirmAddFriendBtn');

        // 显示加载状态
        if (confirmBtn) {
            confirmBtn.classList.add('loading');
        }

        fetch('api/level' + currentLevel + '/add-friend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', username: currentSearchedFriend.username })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (resultArea) {
                    if (data.success && data.refresh) {
                        resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                        // 刷新页面
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                        if (confirmBtn) {
                            confirmBtn.classList.remove('loading');
                        }
                    }
                    resultArea.style.display = 'block';
                }
            })
            .catch(function (error) {
                if (resultArea) {
                    resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请求失败，请稍后重试</span></div>';
                    if (confirmBtn) {
                        confirmBtn.classList.remove('loading');
                    }
                    resultArea.style.display = 'block';
                }
            });
    }

    /**
     * 显示好友信息
     * @param {Object} friend - 好友信息对象
     */
    function displayFriendInfo(friend) {
        var displayArea = document.getElementById('friendInfoDisplay');

        if (friend && displayArea) {
            document.getElementById('friendUsernameDisplay').textContent = friend.username;
            document.getElementById('friendUserIdDisplay').textContent = friend.user_id;
            document.getElementById('friendPhoneDisplay').textContent = friend.phone;
            displayArea.style.display = 'block';
        }
    }

    /**
     * 检查并显示已添加的好友信息（页面加载时）
     */
    function checkAndDisplayFriendInfo() {
        var section = document.getElementById('addFriendSection');
        if (!section) {
            return;
        }

        // 检查session中是否有好友信息（通过PHP在页面中输出的数据）
        var friendData = section.getAttribute('data-friend-info');
        if (friendData) {
            try {
                var friend = JSON.parse(friendData);
                displayFriendInfo(friend);
            } catch (e) {
                // 忽略解析错误
            }
        }
    }

    /**
     * 存储当前搜索到的好友信息，用于确认添加
     */
    var currentSearchedFriend = null;

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
                    var resultArea = document.getElementById('verifyResultArea');
                    if (resultArea) {
                        resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请输入通关密码</span></div>';
                        resultArea.style.display = 'block';
                    }
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

                                // 如果是第三关（最后一关），直接显示恭喜弹窗
                                if (currentLevel === 3) {
                                    showCongratsModal({
                                        title: '🎉 恭喜你掌握了一个新技能',
                                        message: '你掌握了密码重置凭证可猜测攻击的实现方式',
                                        buttonText: '继续学习',
                                        enableNextRangeButton: true,
                                        rangeCode: 'resetlink',
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
     * HTML转义函数，防止XSS
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
            console.error('[HeaSec] 恭喜弹窗组件未加载');
            // 降级处理：显示简单提示
            alert(config.message || '恭喜通关！');
        }
    }
})();
