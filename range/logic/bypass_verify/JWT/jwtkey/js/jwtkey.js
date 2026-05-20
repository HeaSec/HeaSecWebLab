/**
 * HeaSec天积安全团队 - JWT密钥注入靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// ==================== Base64URL编解码函数 ====================

/**
 * Base64URL编码（支持Unicode字符）
 * @param {string} str - 要编码的字符串
 * @returns {string} Base64URL编码结果
 */
function base64UrlEncode(str) {
    return btoa(unescape(encodeURIComponent(str)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

/**
 * Base64URL解码（支持Unicode字符）
 * @param {string} str - Base64URL编码的字符串
 * @returns {string} 解码结果
 */
function base64UrlDecode(str) {
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    while (str.length % 4) {
        str += '=';
    }
    return decodeURIComponent(escape(atob(str)));
}

// ==================== 靶场交互逻辑 ====================

(function () {
    'use strict';

    var commonBasePath = '';
    var currentToken = null;
    var uploadedFiles = [];

    /**
     * 初始化靶场
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initJwtKey = function (basePath) {
        commonBasePath = basePath || '';

        bindLoginForm();
        bindLogoutButton();
        bindUploadForm();
        bindCustomReset();

        // 检查是否有保存的Token
        checkExistingToken();
    };

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value.trim();

            if (!username || !password) {
                showLoginError('请输入账号和密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        // 保存Token到localStorage
                        currentToken = data.token;
                        localStorage.setItem('jwtkey_token', data.token);

                        // 获取用户信息
                        fetchProfile(data.token);
                    } else {
                        showLoginError(data.message);
                        if (submitBtn) {
                            submitBtn.classList.remove('loading');
                        }
                    }
                })
                .catch(function (error) {
                    showLoginError('登录失败，请稍后重试');
                    if (submitBtn) {
                        submitBtn.classList.remove('loading');
                    }
                });
        });
    }

    /**
     * 获取用户信息
     * @param {string} token
     */
    function fetchProfile(token) {
        fetch('api/profile.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayUserInfo(data.data);

                    // 如果有成就解锁，刷新页面以更新成就卡片
                    if (data.achievement && data.achievement.unlocked) {
                        // 显示成就提示
                        showAchievementNotification(data.achievement);
                        // 延迟刷新页面以更新成就卡片
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    showLoginError('获取用户信息失败');
                }
            })
            .catch(function (error) {
                showLoginError('获取用户信息失败');
            })
            .finally(function () {
                var submitBtn = document.querySelector('#loginForm button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            });
    }

    /**
     * 显示用户信息
     * @param {Object} userData
     */
    function displayUserInfo(userData) {
        // 隐藏登录表单和任务提示
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('loginErrorArea').style.display = 'none';
        document.getElementById('taskHint').style.display = 'none';

        // 显示用户信息
        document.getElementById('userInfoArea').style.display = 'block';
        document.getElementById('displayUsername').textContent = userData.username;

        var roleElement = document.getElementById('displayRole');
        roleElement.textContent = userData.role === 'admin' ? '管理员' : '普通用户';
        roleElement.className = 'user-info-value ' + userData.role;

        // 显示文件上传区域
        document.getElementById('uploadArea').style.display = 'block';

        // 根据角色显示不同提示
        if (userData.role === 'admin') {
            document.getElementById('userHintArea').style.display = 'none';
        } else {
            document.getElementById('userHintArea').style.display = 'flex';
        }

        // 加载已上传文件列表
        loadUploadedFiles();
    }

    /**
     * 显示成就通知
     * @param {Object} achievement
     */
    function showAchievementNotification(achievement) {
        var container = document.getElementById('achievementNotification');
        if (!container) return;

        container.innerHTML = '<i class="fa fa-trophy" style="color: gold; margin-right: 8px;"></i>' +
            '<span>成就解锁：' + escapeHtml(achievement.name) + '</span>';
        container.style.display = 'flex';
    }

    /**
     * 显示登录错误
     * @param {string} message
     */
    function showLoginError(message) {
        var errorArea = document.getElementById('loginErrorArea');
        var errorMsg = document.getElementById('loginErrorMsg');
        if (errorArea && errorMsg) {
            errorMsg.textContent = message;
            errorArea.style.display = 'flex';
        }
    }

    /**
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            // 清除Token
            currentToken = null;
            localStorage.removeItem('jwtkey_token');

            // 重置UI
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('loginForm').reset();
            document.getElementById('loginErrorArea').style.display = 'none';
            document.getElementById('taskHint').style.display = 'flex';
            document.getElementById('userInfoArea').style.display = 'none';
            document.getElementById('uploadArea').style.display = 'none';
            document.getElementById('achievementNotification').style.display = 'none';
            uploadedFiles = [];
            updateUploadedList();
        });
    }

    /**
     * 绑定文件上传表单
     */
    function bindUploadForm() {
        var uploadForm = document.getElementById('uploadForm');
        if (!uploadForm) return;

        var fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var fileName = this.files[0] ? this.files[0].name : '选择文件...';
                document.getElementById('fileNameDisplay').textContent = fileName;
            });
        }

        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var fileInput = document.getElementById('fileInput');
            if (!fileInput.files || !fileInput.files[0]) {
                alert('请选择要上传的文件');
                return;
            }

            var uploadBtn = uploadForm.querySelector('button[type="submit"]');
            if (uploadBtn) {
                uploadBtn.classList.add('loading');
            }

            var formData = new FormData();
            formData.append('file', fileInput.files[0]);

            fetch('api/upload.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken
                },
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        uploadedFiles.push(data.data);
                        updateUploadedList();

                        // 清空文件选择
                        fileInput.value = '';
                        document.getElementById('fileNameDisplay').textContent = '选择文件...';
                    } else {
                        alert(data.message || '上传失败');
                    }
                })
                .catch(function (error) {
                    alert('上传失败，请稍后重试');
                })
                .finally(function () {
                    if (uploadBtn) {
                        uploadBtn.classList.remove('loading');
                    }
                });
        });
    }

    /**
     * 加载已上传文件列表（从页面数据）
     */
    function loadUploadedFiles() {
        // 这里可以从服务器获取已上传文件列表
        // 目前只在当前会话中维护
    }

    /**
     * 更新已上传文件列表显示
     */
    function updateUploadedList() {
        var listContainer = document.getElementById('uploadedList');
        if (!listContainer) return;

        if (uploadedFiles.length === 0) {
            listContainer.innerHTML = '<div class="empty-state"><i class="fa fa-inbox"></i><p>暂无上传文件</p></div>';
            return;
        }

        var html = '';
        uploadedFiles.forEach(function (file) {
            html += '<div class="upload-item">' +
                '<span class="upload-item-name"><i class="fa fa-file-text-o"></i> ' + escapeHtml(file.filename) + '</span>' +
                '<span class="upload-item-path">' + escapeHtml(file.path) + '</span>' +
                '</div>';
        });

        listContainer.innerHTML = html;
    }

    /**
     * 检查是否有保存的Token
     */
    function checkExistingToken() {
        var savedToken = localStorage.getItem('jwtkey_token');
        if (savedToken) {
            currentToken = savedToken;
            fetchProfile(savedToken);
        }
    }

    /**
     * 绑定自定义重置功能
     */
    function bindCustomReset() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        // 移除原有的事件监听器（通过克隆节点）
        var newResetBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newResetBtn, resetBtn);

        // 添加新的事件监听器
        newResetBtn.addEventListener('click', function () {
            // 使用模态框确认
            if (window.heasecModalManager) {
                window.heasecModalManager.showResetConfirm({
                    url: 'api/reset.php',
                    onConfirm: function (modal) {
                        var confirmBtn = modal.querySelector('.modal-confirm');
                        var originalText = confirmBtn.innerHTML;
                        confirmBtn.disabled = true;
                        confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 重置中...';

                        fetch('api/reset.php', {
                            method: 'POST'
                        })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                window.heasecModalManager.hideModal(modal);
                                if (data.success) {
                                    // 清除Token
                                    localStorage.removeItem('jwtkey_token');

                                    window.heasecModalManager.showSuccessMessage('重置成功，页面即将刷新', {
                                        onConfirm: function () {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    window.heasecModalManager.showModal('success_message', {
                                        title: '重置失败',
                                        content: '<div class="text-center"><i class="fa fa-times-circle" style="font-size: 48px; color: #dc3545; margin: 20px 0;"></i><p style="margin: 0; font-size: 16px; color: #333;">' + (data.message || '操作失败') + '</p></div>'
                                    });
                                    newResetBtn.classList.remove('loading');
                                }
                            })
                            .catch(function (error) {
                                window.heasecModalManager.hideModal(modal);
                                window.heasecModalManager.showModal('success_message', {
                                    title: '重置失败',
                                    content: '<div class="text-center"><i class="fa fa-times-circle" style="font-size: 48px; color: #dc3545; margin: 20px 0;"></i><p style="margin: 0; font-size: 16px; color: #333;">网络错误，请稍后重试</p></div>'
                                });
                                newResetBtn.classList.remove('loading');
                            })
                            .finally(function () {
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = originalText;
                            });

                        return false; // 不自动关闭模态框
                    }
                });
            } else {
                // 降级处理：使用原生 confirm
                if (!confirm('确定要重置靶场吗？这将清除所有数据并重新生成密钥。')) {
                    return;
                }

                newResetBtn.classList.add('loading');

                fetch('api/reset.php', {
                    method: 'POST'
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            localStorage.removeItem('jwtkey_token');
                            alert('重置成功，页面即将刷新');
                            location.reload();
                        } else {
                            alert('重置失败: ' + data.message);
                            newResetBtn.classList.remove('loading');
                        }
                    })
                    .catch(function (error) {
                        alert('重置失败，请稍后重试');
                        newResetBtn.classList.remove('loading');
                    });
            }
        });
    }

    /**
     * HTML转义函数，防止XSS
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
