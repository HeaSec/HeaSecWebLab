(function () {
    'use strict';

    var initialState = window.HeaSec && window.HeaSec.privescInitialState ? window.HeaSec.privescInitialState : {};
    var state = {
        current: initialState.state ? initialState.state : { logged_in: false, user: null, users: [] },
        viewedUser: initialState.state && initialState.state.user ? initialState.state.user : null,
        cookieType: initialState.cookieType || ''
    };

    var apiBase = 'api/';

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function showMessage(type, message) {
        var containers = document.querySelectorAll('.heasec-page-message');
        if (!containers || containers.length === 0) {
            return;
        }

        if (!message) {
            containers.forEach(function (container) {
                container.innerHTML = '';
            });
            return;
        }

        var className = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-error');
        var icon = type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'times-circle');
        var html = '<div class="alert ' + className + '" style="margin-top: 15px; margin-bottom: 0;"><div><i class="fa fa-' + icon + '"></i><strong>' + escapeHtml(message) + '</strong></div></div>';

        containers.forEach(function (container) {
            container.innerHTML = html;
            window.clearTimeout(container._hideTimer);
            container._hideTimer = window.setTimeout(function () {
                container.innerHTML = '';
            }, 3500);
        });
    }

    /**
     * 在指定模态框内显示消息
     * @param {string} containerId - 消息容器元素ID
     * @param {string} type - 消息类型：success/warning/error
     * @param {string} message - 消息内容
     */
    function showModalMessage(containerId, type, message) {
        var container = $(containerId);
        if (!container) {
            return;
        }

        if (!message) {
            container.innerHTML = '';
            return;
        }

        var className = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-error');
        var icon = type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'times-circle');
        var html = '<div class="alert ' + className + '" style="margin-top: 10px; margin-bottom: 0;"><div><i class="fa fa-' + icon + '"></i><strong>' + escapeHtml(message) + '</strong></div></div>';

        container.innerHTML = html;
        window.clearTimeout(container._hideTimer);
        container._hideTimer = window.setTimeout(function () {
            container.innerHTML = '';
        }, 3500);
    }


    function request(url, options) {
        options = options || {};
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: '响应解析失败', data: {} };
            });
        });
    }

    function jsonPost(url, data) {
        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json;charset=UTF-8' },
            body: JSON.stringify(data || {})
        });
    }



    function isSameUser(left, right) {
        if (!left || !right) {
            return false;
        }

        if (left.user_id != null && right.user_id != null) {
            return Number(left.user_id) === Number(right.user_id);
        }

        if (!left.username || !right.username) {
            return false;
        }

        return String(left.username) === String(right.username);
    }

    /**
     * 获取 Cookie 值
     * @param {string} name Cookie 名称
     * @return {string}
     */
    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return '';
    }

    function syncState(newState) {
        if (!newState) {
            return;
        }

        var previousCurrentUser = state.current && state.current.user ? state.current.user : null;
        var previousViewedUser = state.viewedUser;

        state.current = newState;

        // 登录后同步更新 cookieType
        if (newState.logged_in) {
            state.cookieType = getCookie('type');
        }

        if (!newState.logged_in) {
            state.viewedUser = null;
            renderState();
            return;
        }

        // 如果已登录但没有用户信息，或用户信息不完整（缺少详细字段），则获取完整用户信息
        if (!newState.user || newState.user.name === undefined) {
            renderState();
            fetchCurrentUserInfo();
            return;
        }

        if (!previousViewedUser || isSameUser(previousViewedUser, previousCurrentUser) || isSameUser(previousViewedUser, newState.user)) {
            state.viewedUser = newState.user;
        }

        renderState();
        checkAndFetchUserList();
    }

    function setText(id, value) {
        var element = $(id);
        if (element) {
            element.textContent = value == null || value === '' ? '暂无' : String(value);
        }
    }

    function setValue(id, value) {
        var element = $(id);
        if (element) {
            element.value = value == null ? '' : String(value);
        }
    }

    function toggleHidden(id, hidden) {
        var element = $(id);
        if (element) {
            element.classList.toggle('is-hidden', !!hidden);
        }
    }

    function renderCurrentUser(user) {
        setText('currentUsername', user ? user.username : '');
        setText('currentRoleName', user ? user.role_name : '');
        setText('addressIdText', user ? user.address_id : '');
        setText('addressText', user ? user.address : '');
        setValue('queryUsername', user ? user.username : '');
        setValue('editName', user ? user.name : '');
        setValue('editPhone', user ? user.phone : '');
        setValue('editRole', user ? user.role : 0);
        setValue('addressId', user ? user.address_id : '');
        setValue('addressValue', user ? user.address : '');
        setValue('userHash', user ? user.user_hash : '');
        // 根据cookieType控制角色字段的disabled属性
        var roleSelect = $('editRole');
        if (roleSelect) {
            roleSelect.disabled = state.cookieType !== '2';
        }
        renderAvatar(user);
    }

    function renderViewedUser(user) {
        setText('profileUsername', user ? user.username : '');
        setText('profileName', user ? user.name : '');
        setText('profilePhone', user ? user.phone : '');
        setText('profileRole', user ? user.role_name : '');
        setText('profileAddressId', user ? user.address_id : '');
        setText('profileAddress', user ? user.address : '');
    }

    function renderAvatar(user) {
        var avatarPreview = $('avatarPreview');
        var avatarEmpty = $('avatarEmpty');
        var filenameText = $('avatarFilenameText');
        var hasAvatar = !!(user && user.avatar_url);

        if (filenameText) {
            filenameText.textContent = user && user.avatar ? user.avatar : '无';
        }

        if (!avatarPreview || !avatarEmpty) {
            return;
        }

        if (hasAvatar) {
            avatarPreview.src = user.avatar_url;
            avatarPreview.classList.remove('is-hidden');
            avatarEmpty.classList.add('is-hidden');
        } else {
            avatarPreview.src = '';
            avatarPreview.classList.add('is-hidden');
            avatarEmpty.classList.remove('is-hidden');
        }
    }

    function renderAdminUsers(users) {
        var container = $('adminUserTable');
        if (!container) {
            return;
        }

        if (!users || !users.length) {
            container.innerHTML = '<div class="heasec-user-row no-data"><div class="heasec-user-info"><strong>暂无用户</strong><span>当前没有可管理的用户记录</span></div></div>';
            return;
        }

        container.innerHTML = users.map(function (user) {
            var toggleText = Number(user.status) === 1 ? '停用' : '启用';
            var hasAvatar = !!(user.avatar_url && user.avatar_url !== '');
            var avatarHtml = hasAvatar 
                ? '<img src="' + escapeHtml(user.avatar_url) + '" alt="avatar">'
                : '<div class="no-avatar-placeholder">暂无头像</div>';
            
            return '<div class="heasec-user-row">'
                + '<div class="heasec-user-info-group">'
                + '  <div class="heasec-user-avatar-small">' + avatarHtml + '</div>'
                + '  <div class="heasec-user-main-info">'
                + '    <div class="heasec-user-username">' + escapeHtml(user.username) + '</div>'
                + '    <div class="heasec-user-role-badge ' + (Number(user.role) === 2 ? 'admin' : 'user') + '">' + escapeHtml(user.role_name) + '</div>'
                + '  </div>'
                + '</div>'
                + '<div class="heasec-user-contact">'
                + '  <div class="heasec-user-name-info">姓名：' + escapeHtml(user.name || '未填写') + '</div>'
                + '  <div class="heasec-user-phone-info"><i class="fa fa-phone"></i> ' + escapeHtml(user.phone || '未填写') + '</div>'
                + '</div>'
                + '<div class="heasec-user-status-group">'
                + '  <div class="heasec-user-status-label status-' + user.status + '">' + escapeHtml(user.status_name) + '</div>'
                + '</div>'
                + '<div class="heasec-user-actions">'
                + '  <button type="button" class="tech-btn tech-btn-warning" data-action="role" data-user-id="' + escapeHtml(user.user_id) + '" data-role="' + (Number(user.role) === 2 ? '0' : '2') + '" title="' + (Number(user.role) === 2 ? '降为普通用户' : '设为管理员') + '"><i class="fa fa-user-secret"></i></button>'
                + '  <button type="button" class="tech-btn tech-btn-warning" data-action="status" data-user-id="' + escapeHtml(user.user_id) + '" title="' + toggleText + '"><i class="fa fa-power-off"></i></button>'
                + '  <button type="button" class="tech-btn tech-btn-danger" data-action="delete" data-user-id="' + escapeHtml(user.user_id) + '" title="删除用户"><i class="fa fa-trash"></i></button>'
                + '</div>'
                + '</div>';
        }).join('');
    }

    function renderState() {
        var current = state.current || { logged_in: false, user: null, users: [] };
        var viewedUser = state.viewedUser;
        toggleHidden('guestPanel', !!current.logged_in);
        toggleHidden('userPanel', !current.logged_in);
        toggleHidden('adminPanel', !(current.logged_in && state.cookieType === '2'));
        renderCurrentUser(current.user || null);
        renderViewedUser(viewedUser || current.user || null);
        renderAdminUsers(current.users || []);
    }

    function handleJsonResponse(response, successMessage) {
        if (!response || response.success !== true) {
            showMessage(response && response.data && response.data.already_submitted ? 'warning' : 'error', response && response.message ? response.message : '请求失败');
            return false;
        }

        showMessage('success', successMessage || response.message || '操作成功');
        return true;
    }

    /**
     * 获取完整会话状态（含用户详情+用户列表+漏洞卡片数据）
     */
    function fetchSessionState() {
        request(apiBase + 'session-state.php').then(function (response) {
            if (response && response.success && response.data && response.data.state) {
                syncState(response.data.state);
            }
        });
    }

    function bindGuestForms() {
        var loginForm = $('loginForm');
        var registerForm = $('registerForm');
        var registerBtn = $('registerBtn');
        var registerModal = $('registerModal');
        var registerSuccessModal = $('registerSuccessModal');

        // Modal close buttons
        var closeBtns = document.querySelectorAll('.heasec-modal-close, .modal-cancel');
        closeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = btn.closest('.heasec-modal');
                if (modal) {
                    modal.style.display = 'none';
                    if (modal.id === 'registerModal' && registerForm) {
                        registerForm.reset();
                        showModalMessage('registerModalMessage', 'success', '');
                    }
                }
            });
        });

        // SUCCESS Modal confirm button
        var registerSuccessBtn = $('registerSuccessBtn');
        if (registerSuccessBtn) {
            registerSuccessBtn.addEventListener('click', function () {
                if (registerSuccessModal) {
                    registerSuccessModal.style.display = 'none';
                }
            });
        }

        if (registerBtn && registerModal) {
            registerBtn.addEventListener('click', function () {
                showModalMessage('registerModalMessage', 'success', '');
                registerModal.style.display = 'flex';
            });
        }

        if (loginForm) {
            loginForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var formData = new FormData(loginForm);
                jsonPost(apiBase + 'login.php', {
                    username: formData.get('username'),
                    password: formData.get('password')
                }).then(function (response) {
                    if (handleJsonResponse(response, '登录成功')) {
                        loginForm.reset();
                        // 登录成功后获取完整会话状态
                        fetchSessionState();
                    }
                });
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var formData = new FormData(registerForm);
                var password = String(formData.get('password') || '');
                var confirmPassword = String(formData.get('confirm_password') || '');
                var name = String(formData.get('name') || '').trim();
                var phone = String(formData.get('phone') || '').trim();

                if (!name) {
                    showModalMessage('registerModalMessage', 'error', '请输入姓名');
                    return;
                }

                if (!phone) {
                    showModalMessage('registerModalMessage', 'error', '请输入手机号');
                    return;
                }

                if (password !== confirmPassword) {
                    showModalMessage('registerModalMessage', 'error', '两次输入的密码不一致');
                    return;
                }

                jsonPost(apiBase + 'register.php', {
                    username: formData.get('username'),
                    password: password,
                    type: formData.get('type') || '0',
                    name: name,
                    phone: phone
                }).then(function (response) {
                    if (response && response.success === true) {
                        registerForm.reset();
                        showModalMessage('registerModalMessage', 'success', '');
                        if (registerModal) registerModal.style.display = 'none';
                        if (registerSuccessModal) registerSuccessModal.style.display = 'flex';
                        showMessage('success', response.message || '注册成功');
                    } else {
                        showModalMessage('registerModalMessage', 'error', response && response.message ? response.message : '注册失败');
                    }
                });
            });
        }
    }

    function bindUserForms() {
        var logoutBtn = $('logoutBtn');
        var profileForm = $('profileForm');
        var addressForm = $('addressForm');
        var passwordForm = $('passwordForm');
        var avatarForm = $('avatarForm');
        var avatarFile = $('avatarFile');
        var avatarUploadArea = $('avatarUploadArea');
        var avatarFilenameDisplay = $('avatarFilenameDisplay');
        var deleteAvatarBtn = $('deleteAvatarBtn');
        var adminUserTable = $('adminUserTable');

        var toggleProfileFormBtn = $('toggleProfileFormBtn');
        var toggleAddressFormBtn = $('toggleAddressFormBtn');
        var togglePasswordFormBtn = $('togglePasswordFormBtn');

        var editProfileSection = $('editProfileSection');
        var editAddressSection = $('editAddressSection');
        var editPasswordSection = $('editPasswordSection');

        var avatarDisplayBtn = $('avatarDisplayBtn');
        var editAvatarSection = $('editAvatarSection');

        function hideAllSections() {
            if (editProfileSection) editProfileSection.classList.add('is-hidden');
            if (editAddressSection) editAddressSection.classList.add('is-hidden');
            if (editPasswordSection) editPasswordSection.classList.add('is-hidden');
            if (editAvatarSection) editAvatarSection.classList.add('is-hidden');
        }

        if (toggleProfileFormBtn) {
            toggleProfileFormBtn.addEventListener('click', function () {
                var isHidden = editProfileSection.classList.contains('is-hidden');
                hideAllSections();
                if (isHidden) editProfileSection.classList.remove('is-hidden');
            });
        }
        if (toggleAddressFormBtn) {
            toggleAddressFormBtn.addEventListener('click', function () {
                var isHidden = editAddressSection.classList.contains('is-hidden');
                hideAllSections();
                if (isHidden) editAddressSection.classList.remove('is-hidden');
            });
        }
        if (togglePasswordFormBtn) {
            togglePasswordFormBtn.addEventListener('click', function () {
                var isHidden = editPasswordSection.classList.contains('is-hidden');
                hideAllSections();
                if (isHidden) editPasswordSection.classList.remove('is-hidden');
            });
        }

        document.querySelectorAll('.cancel-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', hideAllSections);
        });

        if (avatarDisplayBtn && editAvatarSection) {
            avatarDisplayBtn.addEventListener('click', function () {
                var isHidden = editAvatarSection.classList.contains('is-hidden');
                hideAllSections();
                if (isHidden) editAvatarSection.classList.remove('is-hidden');
            });
        }

        if (avatarFile && avatarUploadArea && avatarFilenameDisplay) {
            avatarFile.addEventListener('change', function () {
                var file = this.files[0];
                if (file) {
                    avatarFilenameDisplay.textContent = file.name;
                    avatarUploadArea.classList.add('has-file');
                } else {
                    avatarFilenameDisplay.textContent = '未选择任何文件';
                    avatarUploadArea.classList.remove('has-file');
                }
            });

            avatarUploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                avatarUploadArea.classList.add('dragover');
            });

            avatarUploadArea.addEventListener('dragleave', function (e) {
                e.preventDefault();
                avatarUploadArea.classList.remove('dragover');
            });

            avatarUploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                avatarUploadArea.classList.remove('dragover');
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    avatarFile.files = e.dataTransfer.files;
                    var event = new Event('change');
                    avatarFile.dispatchEvent(event);
                }
            });
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                jsonPost(apiBase + 'logout.php', {}).then(function (response) {
                    if (handleJsonResponse(response, '退出成功')) {
                        // 退出成功后手动重置为未登录状态
                        syncState({ logged_in: false, user: null, users: [] });
                    }
                });
            });
        }

        if (profileForm) {
            profileForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var data = {
                    name: $('editName') ? $('editName').value.trim() : '',
                    phone: $('editPhone') ? $('editPhone').value.trim() : ''
                };
                var roleGroup = $('profileRoleGroup');
                if (roleGroup && !roleGroup.classList.contains('is-hidden')) {
                    data.role = $('editRole') ? $('editRole').value : '0';
                }

                jsonPost(apiBase + 'update-profile.php', data).then(function (response) {
                    if (handleJsonResponse(response, '个人信息更新成功')) {
                        hideAllSections();
                        // 更新成功后主动刷新用户信息
                        fetchCurrentUserInfo();
                    }
                });
            });
        }

        if (addressForm) {
            addressForm.addEventListener('submit', function (event) {
                event.preventDefault();
                jsonPost(apiBase + 'update-address.php', {
                    address_id: $('addressId') ? $('addressId').value.trim() : '',
                    address: $('addressValue') ? $('addressValue').value.trim() : ''
                }).then(function (response) {
                    if (handleJsonResponse(response, '地址更新成功')) {
                        hideAllSections();
                        // 更新成功后主动刷新用户信息（含地址）
                        fetchCurrentUserInfo();
                    }
                });
            });
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', function (event) {
                event.preventDefault();
                jsonPost(apiBase + 'change-password.php', {
                    user_hash: $('userHash') ? $('userHash').value.trim() : '',
                    new_password: $('newPassword') ? $('newPassword').value : '',
                    confirm_password: $('confirmPassword') ? $('confirmPassword').value : ''
                }).then(function (response) {
                    if (handleJsonResponse(response, '密码修改成功')) {
                        setValue('newPassword', '');
                        setValue('confirmPassword', '');
                        hideAllSections();
                    }
                });
            });
        }

        if (avatarForm) {
            avatarForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var formData = new FormData(avatarForm);
                request(apiBase + 'upload-avatar.php', {
                    method: 'POST',
                    body: formData
                }).then(function (response) {
                    if (!response || response.success !== true) {
                        showMessage('error', response && response.message ? response.message : '头像上传失败');
                        return;
                    }

                    showMessage('success', response.message || '头像上传成功');
                    avatarForm.reset();
                    if (avatarFilenameDisplay) avatarFilenameDisplay.textContent = '未选择任何文件';
                    if (avatarUploadArea) avatarUploadArea.classList.remove('has-file');
                    hideAllSections();
                    // 上传成功后主动刷新用户信息（含头像）
                    fetchCurrentUserInfo();
                });
            });
        }

        if (deleteAvatarBtn) {
            deleteAvatarBtn.addEventListener('click', function () {
                var currentUser = state.current && state.current.user ? state.current.user : null;
                var filename = currentUser && currentUser.avatar ? currentUser.avatar : '';

                if (!filename || filename === '无') {
                    showMessage('warning', '当前没有可删除的头像');
                    return;
                }

                jsonPost(apiBase + 'delete-avatar.php', { filename: filename }).then(function (response) {
                    if (!response || response.success !== true) {
                        showMessage('error', response && response.message ? response.message : '头像删除失败');
                        return;
                    }

                    showMessage('success', response.message || '头像删除成功');
                    hideAllSections();
                    // 删除成功后主动刷新用户信息
                    fetchCurrentUserInfo();
                });
            });
        }

        if (adminUserTable) {
            adminUserTable.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-action]');
                if (!button) {
                    return;
                }

                var action = button.getAttribute('data-action');
                var userId = button.getAttribute('data-user-id');
                var url = '';
                var payload = { user_id: userId };

                if (action === 'role') {
                    url = apiBase + 'update-user-role.php';
                    payload.role = button.getAttribute('data-role');
                } else if (action === 'status') {
                    url = apiBase + 'toggle-user-status.php';
                } else if (action === 'delete') {
                    url = apiBase + 'delete-user.php';
                }

                if (!url) {
                    return;
                }

                jsonPost(url, payload).then(function (response) {
                    if (handleJsonResponse(response, response && response.message ? response.message : '操作成功')) {
                        // 管理操作成功后刷新用户列表和当前用户信息
                        fetchUserList();
                        fetchCurrentUserInfo();
                    }
                });
            });
        }
    }

    function bindResetHook() {
        document.addEventListener('click', function (event) {
            var confirmBtn = event.target.closest('.modal-confirm');
            if (!confirmBtn) {
                return;
            }

            var modal = confirmBtn.closest('.heasec-modal');
            if (!modal || modal.id !== 'resetModal') {
                return;
            }

            window.setTimeout(function () {
                request(apiBase + 'session-state.php').then(function (response) {
                    if (response && response.success && response.data) {
                        if (response.data.state) {
                            syncState(response.data.state);
                        }
                    }
                });
            }, 800);
        });
    }

    /**
     * 获取当前用户信息
     */
    function fetchCurrentUserInfo() {
        // 显式传递当前用户的username参数
        var username = state.current && state.current.user && state.current.user.username ? state.current.user.username : '';
        if (!username) {
            return;
        }
        request(apiBase + 'get-user-info.php?username=' + encodeURIComponent(username)).then(function (response) {
            if (response && response.success && response.data && response.data.user) {
                state.current.user = response.data.user;
                if (!state.viewedUser || isSameUser(state.viewedUser, state.current.user)) {
                    state.viewedUser = response.data.user;
                }
                renderState();
                checkAndFetchUserList();
            }
        });
    }

    /**
     * 检查是否需要获取用户信息
     * 当已登录但没有用户信息，或用户信息不完整（缺少详细字段）时获取
     */
    function checkAndFetchUserInfo() {
        var current = state.current || { logged_in: false, user: null, users: [] };
        if (current.logged_in && (!current.user || current.user.name === undefined)) {
            fetchCurrentUserInfo();
            return true;
        }
        return false;
    }

    /**
     * 获取用户列表（用于 cookieType === '2' 时显示用户管理卡片）
     */
    function fetchUserList() {
        request(apiBase + 'get-user-list.php').then(function (response) {
            if (response && response.success && response.data && response.data.users) {
                state.current.users = response.data.users;
                renderState();
            }
        });
    }

    /**
     * 检查是否需要获取用户列表
     * 当已登录且 cookieType === '2' 且当前没有用户列表时获取
     */
    function checkAndFetchUserList() {
        var current = state.current || { logged_in: false, user: null, users: [] };
        if (current.logged_in && state.cookieType === '2' && (!current.users || current.users.length === 0)) {
            fetchUserList();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderState();
        bindGuestForms();
        bindUserForms();
        bindResetHook();
        // 如果已登录但没有用户信息，先获取用户信息
        // checkAndFetchUserList 会在 fetchCurrentUserInfo 完成后调用
        if (!checkAndFetchUserInfo()) {
            checkAndFetchUserList();
        }
    });
})();
