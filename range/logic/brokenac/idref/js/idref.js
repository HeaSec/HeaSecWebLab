/**
 * HeaSec天积安全团队 - 水平越权基础靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
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
    // 存储当前登录用户的数据（用于profile API调用）
    var currentUserData = null;

    window.initIdref = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        bindLoginForm();
        bindVerifyForm();
        bindAddFriendModal();

        // 如果页面已经是登录状态，加载用户信息
        var userInfoContainer = document.getElementById('userInfoContainer');
        if (userInfoContainer && userInfoContainer.getAttribute('data-logged-in') === 'true') {
            loadUserInfo();
        }
    };

    /**
     * 根据关卡获取profile API参数
     * @param {object} userData - 登录返回的用户数据
     * @returns {object} API参数
     */
    function getProfileParams(userData) {
        switch (currentLevel) {
            case 1:
                return { id: userData.num_id };
            case 2:
                // 第二关使用手机号的base64编码
                return { token: btoa(userData.phone) };
            case 3:
                return { uid: userData.user_id };
            default:
                return {};
        }
    }

    /**
     * 根据关卡获取profile API的URL
     * @param {object} params - 参数对象
     * @returns {string} API URL
     */
    function buildProfileUrl(params) {
        var queryString = '';
        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                if (queryString) queryString += '&';
                queryString += key + '=' + encodeURIComponent(params[key]);
            }
        }
        return 'api/level' + currentLevel + '/profile.php?' + queryString;
    }

    /**
     * 加载用户信息（通过profile API）
     */
    function loadUserInfo() {
        if (!currentUserData) {
            // 尝试从页面获取用户标识数据
            var userInfoContainer = document.getElementById('userInfoContainer');
            if (userInfoContainer) {
                var userIdentifiers = userInfoContainer.getAttribute('data-user-identifiers');
                if (userIdentifiers) {
                    try {
                        currentUserData = JSON.parse(userIdentifiers);
                    } catch (e) {
                        return;
                    }
                }
            }
        }

        if (!currentUserData) {
            return;
        }

        var params = getProfileParams(currentUserData);
        var url = buildProfileUrl(params);

        fetch(url, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayUserInfo(data.data);
                }
            })
            .catch(function (error) {
                console.error('[HeaSec] 获取用户信息失败');
            });
    }

    /**
     * 显示用户信息
     * @param {object} userData - 用户数据
     */
    function displayUserInfo(userData) {
        var infoContainer = document.getElementById('userInfoDisplay');
        if (!infoContainer) return;

        var html = '';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-user"></i> 姓名：</span><span class="info-value">' + escapeHtml(userData.name) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-phone"></i> 手机号：</span><span class="info-value">' + escapeHtml(userData.phone) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-id-card"></i> 身份证号：</span><span class="info-value">' + escapeHtml(userData.idcard) + '</span></div>';

        // 根据关卡显示不同的标识符
        if (currentLevel === 1 && userData.num_id) {
            html += '<div class="info-row"><span class="info-label"><i class="fa fa-hashtag"></i> 数字ID：</span><span class="info-value">' + escapeHtml(userData.num_id) + '</span></div>';
        } else if (currentLevel === 3 && userData.uid) {
            html += '<div class="info-row"><span class="info-label"><i class="fa fa-hashtag"></i> 用户ID：</span><span class="info-value">' + escapeHtml(userData.uid) + '</span></div>';
        }

        // 如果有通关密码（管理员用户）
        if (userData.passcode) {
            html += '<div class="info-row highlight"><span class="info-label"><i class="fa fa-trophy"></i> 通关密码：</span><span class="info-value passcode">' + escapeHtml(userData.passcode) + '</span></div>';
        }

        infoContainer.innerHTML = html;
        infoContainer.style.display = 'block';

        // 隐藏加载状态
        var loadingEl = document.getElementById('userInfoLoading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var loginForm = document.getElementById('loginForm');

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var account = document.getElementById('account').value.trim();
                var password = document.getElementById('password').value.trim();

                if (!account || !password) {
                    var resultArea = document.getElementById('loginResultArea');
                    if (resultArea) {
                        resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请输入账号和密码</span></div>';
                        resultArea.style.display = 'block';
                    }
                    return;
                }

                var submitBtn = loginForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                }

                fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        level: currentLevel,
                        account: account,
                        password: password
                    })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            // 保存用户数据用于后续profile API调用
                            currentUserData = data.data;

                            // 隐藏登录表单，显示用户信息区域
                            var loginSection = document.getElementById('loginSection');
                            var userInfoSection = document.getElementById('userInfoSection');
                            var userInfoContainer = document.getElementById('userInfoContainer');

                            if (loginSection) loginSection.style.display = 'none';
                            if (userInfoSection) userInfoSection.style.display = 'block';
                            if (userInfoContainer) {
                                userInfoContainer.setAttribute('data-logged-in', 'true');
                            }

                            // 更新卡片标题
                            var cardHeader = document.querySelector('.tech-card-header h3');
                            if (cardHeader) {
                                cardHeader.innerHTML = '<i class="fa fa-user"></i> 用户信息';
                            }

                            // 动态创建用户信息显示区域（解决AJAX登录后元素不存在的问题）
                            var userInfoContainer = document.getElementById('userInfoContainer');
                            if (userInfoContainer) {
                                // 检查是否存在userInfoDisplay元素，不存在则创建
                                var userInfoDisplay = document.getElementById('userInfoDisplay');
                                var userInfoLoading = document.getElementById('userInfoLoading');

                                if (!userInfoDisplay) {
                                    userInfoDisplay = document.createElement('div');
                                    userInfoDisplay.id = 'userInfoDisplay';
                                    userInfoDisplay.style.display = 'none';
                                    userInfoContainer.appendChild(userInfoDisplay);
                                }

                                if (!userInfoLoading) {
                                    userInfoLoading = document.createElement('div');
                                    userInfoLoading.id = 'userInfoLoading';
                                    userInfoLoading.style.cssText = 'text-align: center; padding: 20px;';
                                    userInfoLoading.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 加载用户信息...';
                                    userInfoContainer.insertBefore(userInfoLoading, userInfoDisplay);
                                }
                            }

                            // 调用profile API获取用户信息
                            loadUserInfo();

                            if (submitBtn) {
                                submitBtn.classList.remove('loading');
                            }
                        } else {
                            var resultArea = document.getElementById('loginResultArea');
                            if (resultArea) {
                                resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                                resultArea.style.display = 'block';
                            }
                            if (submitBtn) {
                                submitBtn.classList.remove('loading');
                            }
                        }
                    })
                    .catch(function (error) {
                        var resultArea = document.getElementById('loginResultArea');
                        if (resultArea) {
                            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>请求失败，请稍后重试</span></div>';
                            resultArea.style.display = 'block';
                        }
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                        }
                    });
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

                                // 如果是第三关（最后一关），显示恭喜弹窗
                                if (currentLevel === 3) {
                                    showCongratsModal({
                                        title: '🎉 恭喜你掌握了一个新技能',
                                        message: '你掌握了水平越权攻击的实现方式',
                                        buttonText: '继续学习',
                                        enableNextRangeButton: true,
                                        rangeCode: 'idref',
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
     * 绑定添加好友模态框
     */
    function bindAddFriendModal() {
        var modal = document.getElementById('addFriendModal');
        var btn = document.getElementById('addFriendBtn');

        if (!modal || !btn) {
            return;
        }

        var closeBtn = modal.querySelector('.heasec-modal-close');
        var cancelBtn = modal.querySelector('.modal-cancel');
        var form = document.getElementById('addFriendForm');

        btn.addEventListener('click', function () {
            modal.style.display = 'flex';
        });

        var closeModal = function () {
            modal.style.display = 'none';
            if (form) {
                form.reset();
            }
            var resultArea = document.getElementById('addFriendResultArea');
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

        if (submitBtn) {
            submitBtn.classList.add('loading');
        }

        fetch('api/level3/add-friend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (resultArea) {
                    if (data.success) {
                        var html = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(data.message) + '</span></div>';
                        html += '<div class="friend-result" style="margin-top: 15px; padding: 15px; background: #f5f5f5; border-radius: 4px;">';
                        html += '<div class="info-row"><span class="info-label">账号：</span><span class="info-value">' + escapeHtml(data.data.username) + '</span></div>';
                        html += '<div class="info-row"><span class="info-label">用户ID：</span><span class="info-value">' + escapeHtml(data.data.uid) + '</span></div>';
                        html += '<div class="info-row"><span class="info-label">姓名：</span><span class="info-value">' + escapeHtml(data.data.name) + '</span></div>';
                        html += '</div>';
                        resultArea.innerHTML = html;
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
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * HTML转义函数，防止XSS
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return String(text);
        }
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
