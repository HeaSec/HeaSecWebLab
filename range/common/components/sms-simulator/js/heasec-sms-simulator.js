/**
 * HeaSec天积安全团队 - 手机短信模拟器管理器JavaScript
 * SMS Simulator Manager JavaScript
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * HeaSecSmsSimulatorManager - 手机短信模拟器管理器类
 */
function HeaSecSmsSimulatorManager(config) {
    // 配置参数
    this.config = config || {};
    this.apiBasePath = this.config.apiBasePath || 'api/';
    this.commonBasePath = this.config.commonBasePath || '../../../common/';
    this.showLogTab = this.config.showLogTab !== undefined ? this.config.showLogTab : false;

    // 状态管理
    this.state = {
        currentTab: 'phones',
        currentPhoneId: null,
        phoneList: [],
        smsList: [],
        selectedPhoneIds: [],
        selectedSmsIds: [],
        logList: [],
        selectedLogIds: [],
        logPage: 1,
        logPageSize: 20,
        logTotal: 0,
        logTotalPages: 0,
        logSearch: {
            phone: '',
            sender: '',
            status: ''
        }
    };

    // 初始化
    this.init();
}

/**
 * 初始化方法
 */
HeaSecSmsSimulatorManager.prototype.init = function () {
    var self = this;

    // 绑定标签页切换事件
    this.bindTabEvents();

    // 绑定手机号管理事件
    this.bindPhoneManagementEvents();

    // 绑定短信管理事件
    this.bindSmsManagementEvents();

    // 绑定日志管理事件
    if (this.showLogTab) {
        this.bindLogManagementEvents();
    }

    // 绑定数据库初始化模态框事件
    this.bindDbInitModalEvents();

    // 绑定数据库重置模态框事件
    this.bindDbResetModalEvents();

    // 绑定使用说明模态框事件
    this.bindHelpModalEvents();
};

/**
 * 初始化页面状态（检查数据库、设置默认标签页等）
 */
HeaSecSmsSimulatorManager.prototype.initializePageState = function () {
    var self = this;

    // 检查数据库状态
    this.ajaxRequest('check-status', {}, 'GET')
        .then(function (data) {
            var dbInitialized = data.data.db_initialized;
            var defaultPhone = data.data.default_phone;

            if (!dbInitialized) {
                // 数据库未初始化，显示初始化模态框
                self.showDbInitModal();
            } else {
                // 数据库已初始化，隐藏模态框
                self.hideDbInitModal();

                // 加载手机号列表
                self.loadPhoneList().then(function () {
                    // 如果有默认手机号，切换到短信标签页并加载
                    if (defaultPhone && defaultPhone.id) {
                        self.state.currentPhoneId = defaultPhone.id;
                        self.updateDefaultPhoneBadge(defaultPhone.phone_number);
                        self.switchToTab('sms');
                        self.loadSmsList(defaultPhone.id);
                    }
                });
            }
        })
        .catch(function (error) {
            // 状态检查失败，显示初始化模态框以防万一
            console.error('状态检查失败:', error.message);
            self.showDbInitModal();
        });
};

/**
 * 绑定标签页切换事件
 */
HeaSecSmsSimulatorManager.prototype.bindTabEvents = function () {
    var self = this;
    var tabItems = document.querySelectorAll('.heasec-sms-tabs .tab-item');
    var tabPanes = document.querySelectorAll('.heasec-sms-content .tab-pane');

    for (var i = 0; i < tabItems.length; i++) {
        tabItems[i].addEventListener('click', function (e) {
            e.preventDefault();
            var tabName = this.getAttribute('data-tab');

            // 切换标签页状态
            for (var j = 0; j < tabItems.length; j++) {
                tabItems[j].classList.remove('active');
            }
            this.classList.add('active');

            // 切换内容显示
            for (var k = 0; k < tabPanes.length; k++) {
                tabPanes[k].classList.remove('active');
            }
            document.getElementById('tab-' + tabName).classList.add('active');

            // 更新当前标签页状态
            self.state.currentTab = tabName;

            // 根据标签页类型执行相应操作
            if (tabName === 'sms' && self.state.currentPhoneId) {
                // 切换到短信标签页，加载短信列表
                self.loadSmsList(self.state.currentPhoneId);
            } else if (tabName === 'logs' && self.showLogTab) {
                // 切换到日志标签页，加载日志列表
                self.loadLogList();
            }
        });
    }
};

/**
 * 绑定手机号管理事件
 */
HeaSecSmsSimulatorManager.prototype.bindPhoneManagementEvents = function () {
    var self = this;

    // 添加手机号按钮
    var btnAddPhone = document.getElementById('btnAddPhone');
    if (btnAddPhone) {
        btnAddPhone.addEventListener('click', function () {
            self.showPhoneModal();
        });
    }

    // 批量添加按钮
    var btnBatchAddPhones = document.getElementById('btnBatchAddPhones');
    if (btnBatchAddPhones) {
        btnBatchAddPhones.addEventListener('click', function () {
            self.showBatchAddPhonesModal();
        });
    }

    // 刷新按钮
    var btnRefreshPhones = document.getElementById('btnRefreshPhones');
    if (btnRefreshPhones) {
        btnRefreshPhones.addEventListener('click', function () {
            self.loadPhoneList();
        });
    }

    // 批量删除按钮
    var btnBatchDeletePhones = document.getElementById('btnBatchDeletePhones');
    if (btnBatchDeletePhones) {
        btnBatchDeletePhones.addEventListener('click', function () {
            self.batchDeletePhones();
        });
    }

    // 全选复选框
    var selectAllPhones = document.getElementById('selectAllPhones');
    if (selectAllPhones) {
        selectAllPhones.addEventListener('change', function () {
            self.toggleSelectAllPhones(this.checked);
        });
    }

    // 手机号模态框事件
    this.bindPhoneModalEvents();

    // 批量添加模态框事件
    this.bindBatchAddPhonesModalEvents();
};

/**
 * 绑定手机号模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindPhoneModalEvents = function () {
    var self = this;
    var phoneModal = document.getElementById('phoneModal');
    var phoneForm = document.getElementById('phoneForm');

    // 关闭按钮
    var closeBtn = document.getElementById('closePhoneModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            self.hidePhoneModal();
        });
    }

    // 取消按钮
    var cancelBtn = document.getElementById('cancelPhoneModal');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            self.hidePhoneModal();
        });
    }

    // 确定按钮
    var confirmBtn = document.getElementById('confirmPhoneModal');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            self.savePhone();
        });
    }

    // 遮罩层点击关闭
    var overlay = phoneModal ? phoneModal.querySelector('.modal-overlay') : null;
    // [HeaSec Update] 禁用点击遮罩层关闭模态框
    /*
    if (overlay) {
        overlay.addEventListener('click', function () {
            self.hidePhoneModal();
        });
    }
    */

    // 表单提交
    if (phoneForm) {
        phoneForm.addEventListener('submit', function (e) {
            e.preventDefault();
            self.savePhone();
        });
    }
};

/**
 * 绑定短信管理事件
 */
HeaSecSmsSimulatorManager.prototype.bindSmsManagementEvents = function () {
    var self = this;

    // 可搜索手机号选择器事件
    this.bindSearchablePhoneSelect();

    // 刷新按钮
    var btnRefreshSms = document.getElementById('btnRefreshSms');
    if (btnRefreshSms) {
        btnRefreshSms.addEventListener('click', function () {
            if (self.state.currentPhoneId) {
                self.loadSmsList(self.state.currentPhoneId);
            }
        });
    }

    // 全部已读按钮
    var btnMarkAllRead = document.getElementById('btnMarkAllRead');
    if (btnMarkAllRead) {
        btnMarkAllRead.addEventListener('click', function () {
            if (self.state.currentPhoneId) {
                self.markAllAsRead(self.state.currentPhoneId);
            }
        });
    }

    // 批量删除按钮
    var btnBatchDeleteSms = document.getElementById('btnBatchDeleteSms');
    if (btnBatchDeleteSms) {
        btnBatchDeleteSms.addEventListener('click', function () {
            self.batchDeleteSms();
        });
    }

    // 短信详情模态框事件
    this.bindSmsDetailModalEvents();
};

/**
 * 绑定可搜索手机号选择器事件
 */
HeaSecSmsSimulatorManager.prototype.bindSearchablePhoneSelect = function () {
    var self = this;
    var searchInput = document.getElementById('phoneSearchInput');
    var toggleBtn = document.getElementById('phoneSelectToggle');
    var dropdown = document.getElementById('phoneDropdown');

    if (!searchInput || !toggleBtn || !dropdown) return;

    // 切换下拉框显示/隐藏
    toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        self.togglePhoneDropdown();
    });

    // 点击输入框显示下拉框
    searchInput.addEventListener('focus', function () {
        self.showPhoneDropdown();
    });

    // 输入搜索
    searchInput.addEventListener('input', function () {
        self.filterPhoneDropdown(this.value.trim());
    });

    // 键盘导航
    searchInput.addEventListener('keydown', function (e) {
        self.handlePhoneKeyNavigation(e);
    });

    // 点击外部关闭下拉框
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#phoneSearchableSelect')) {
            self.hidePhoneDropdown();
        }
    });
};

/**
 * 切换下拉框显示状态
 */
HeaSecSmsSimulatorManager.prototype.togglePhoneDropdown = function () {
    var dropdown = document.getElementById('phoneDropdown');
    var toggleBtn = document.getElementById('phoneSelectToggle');

    if (!dropdown || !toggleBtn) return;

    if (dropdown.style.display === 'none') {
        this.showPhoneDropdown();
    } else {
        this.hidePhoneDropdown();
    }
};

/**
 * 显示下拉框
 */
HeaSecSmsSimulatorManager.prototype.showPhoneDropdown = function () {
    var dropdown = document.getElementById('phoneDropdown');
    var toggleBtn = document.getElementById('phoneSelectToggle');

    if (dropdown) {
        dropdown.style.display = 'block';
        this.renderPhoneDropdown();
    }

    if (toggleBtn) {
        toggleBtn.classList.add('open');
    }
};

/**
 * 隐藏下拉框
 */
HeaSecSmsSimulatorManager.prototype.hidePhoneDropdown = function () {
    var dropdown = document.getElementById('phoneDropdown');
    var toggleBtn = document.getElementById('phoneSelectToggle');

    if (dropdown) {
        dropdown.style.display = 'none';
    }

    if (toggleBtn) {
        toggleBtn.classList.remove('open');
    }

    // 清除焦点状态
    this.clearPhoneDropdownFocus();
};

/**
 * 渲染手机号下拉列表
 */
HeaSecSmsSimulatorManager.prototype.renderPhoneDropdown = function (filterText) {
    var dropdownList = document.getElementById('phoneDropdownList');
    if (!dropdownList) return;

    var self = this;
    filterText = filterText || '';

    if (!this.state.phoneList || this.state.phoneList.length === 0) {
        dropdownList.innerHTML = '<div class="dropdown-item no-results">暂无手机号</div>';
        return;
    }

    var filteredPhones = this.state.phoneList;
    if (filterText) {
        filteredPhones = this.state.phoneList.filter(function (phone) {
            return phone.phone_number.indexOf(filterText) !== -1;
        });
    }

    if (filteredPhones.length === 0) {
        dropdownList.innerHTML = '<div class="dropdown-item no-results">未找到匹配的手机号</div>';
        return;
    }

    var html = '';
    for (var i = 0; i < filteredPhones.length; i++) {
        var phone = filteredPhones[i];
        var isSelected = this.state.currentPhoneId === phone.id;
        var isDefault = phone.is_default == 1;

        html += '<div class="dropdown-item' + (isSelected ? ' selected' : '') + '" data-id="' + phone.id + '" data-index="' + i + '">';
        html += '<span class="phone-number">' + this.escapeHtml(phone.phone_number) + '</span>';
        if (isDefault) {
            html += '<span class="phone-badge">默认</span>';
        }
        html += '</div>';
    }

    dropdownList.innerHTML = html;

    // 绑定点击事件
    var items = dropdownList.querySelectorAll('.dropdown-item:not(.no-results)');
    for (var j = 0; j < items.length; j++) {
        (function (item) {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                var phoneId = parseInt(item.getAttribute('data-id'));
                self.selectPhone(phoneId);
            });

            // 悬停高亮
            item.addEventListener('mouseenter', function () {
                item.classList.add('focused');
            });

            item.addEventListener('mouseleave', function () {
                item.classList.remove('focused');
            });
        })(items[j]);
    }
};

/**
 * 过滤下拉列表
 */
HeaSecSmsSimulatorManager.prototype.filterPhoneDropdown = function (filterText) {
    this.showPhoneDropdown();
    this.renderPhoneDropdown(filterText);
};

/**
 * 选择手机号
 */
HeaSecSmsSimulatorManager.prototype.selectPhone = function (phoneId) {
    var phone = this.findPhoneById(phoneId);
    if (!phone) return;

    this.state.currentPhoneId = phoneId;

    // 更新输入框显示
    var searchInput = document.getElementById('phoneSearchInput');
    if (searchInput) {
        searchInput.value = phone.phone_number;
    }

    // 加载短信列表
    this.loadSmsList(phoneId);
    this.updateSmsToolbarState();

    // 隐藏下拉框
    this.hidePhoneDropdown();
};

/**
 * 处理键盘导航
 */
HeaSecSmsSimulatorManager.prototype.handlePhoneKeyNavigation = function (e) {
    var dropdown = document.getElementById('phoneDropdown');
    if (!dropdown || dropdown.style.display === 'none') return;

    var items = dropdown.querySelectorAll('.dropdown-item:not(.no-results)');
    if (items.length === 0) return;

    var focusedItem = dropdown.querySelector('.dropdown-item.focused');

    switch (e.keyCode) {
        case 38: // 上箭头
            e.preventDefault();
            if (focusedItem) {
                var prevItem = focusedItem.previousElementSibling;
                if (prevItem && !prevItem.classList.contains('no-results')) {
                    focusedItem.classList.remove('focused');
                    prevItem.classList.add('focused');
                }
            } else {
                items[0].classList.add('focused');
            }
            break;

        case 40: // 下箭头
            e.preventDefault();
            if (focusedItem) {
                var nextItem = focusedItem.nextElementSibling;
                if (nextItem && !nextItem.classList.contains('no-results')) {
                    focusedItem.classList.remove('focused');
                    nextItem.classList.add('focused');
                }
            } else {
                items[0].classList.add('focused');
            }
            break;

        case 13: // Enter
            e.preventDefault();
            if (focusedItem) {
                var phoneId = parseInt(focusedItem.getAttribute('data-id'));
                this.selectPhone(phoneId);
            }
            break;

        case 27: // Escape
            e.preventDefault();
            this.hidePhoneDropdown();
            break;
    }
};

/**
 * 清除下拉框焦点
 */
HeaSecSmsSimulatorManager.prototype.clearPhoneDropdownFocus = function () {
    var dropdown = document.getElementById('phoneDropdown');
    if (!dropdown) return;

    var focusedItems = dropdown.querySelectorAll('.dropdown-item.focused');
    for (var i = 0; i < focusedItems.length; i++) {
        focusedItems[i].classList.remove('focused');
    }
};

/**
 * 绑定短信详情模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindSmsDetailModalEvents = function () {
    var self = this;

    // 关闭按钮
    var closeBtn = document.getElementById('closeSmsDetailModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            self.hideSmsDetailModal();
        });
    }

    // 取消按钮
    var cancelBtn = document.getElementById('cancelSmsDetailModal');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            self.hideSmsDetailModal();
        });
    }

    // 遮罩层点击关闭
    var smsDetailModal = document.getElementById('smsDetailModal');
    var overlay = smsDetailModal ? smsDetailModal.querySelector('.modal-overlay') : null;
    // [HeaSec Update] 禁用点击遮罩层关闭模态框
    /*
    if (overlay) {
        overlay.addEventListener('click', function () {
            self.hideSmsDetailModal();
        });
    }
    */
};

/**
 * Ajax请求封装
 */
HeaSecSmsSimulatorManager.prototype.ajaxRequest = function (apiName, data, method) {
    method = method || 'POST';
    var url = this.apiBasePath + apiName + '.php';

    var options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (method === 'POST' && data) {
        options.body = JSON.stringify(data);
    } else if (method === 'GET' && data) {
        var params = [];
        for (var key in data) {
            params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        url += '?' + params.join('&');
    }

    return fetch(url, options)
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data.success) {
                throw new Error(data.message || '操作失败');
            }
            return data;
        });
};

/**
 * 显示通知消息
 */
HeaSecSmsSimulatorManager.prototype.showNotification = function (message, type) {
    type = type || 'info';

    // 使用全局通知函数（如果存在）
    if (typeof showNotification === 'function') {
        showNotification(message, type);
    } else {
        alert(message);
    }
};

/**
 * ========================================
 * 手机号管理相关方法
 * ========================================
 */

/**
 * 加载手机号列表
 */
HeaSecSmsSimulatorManager.prototype.loadPhoneList = function () {
    var self = this;

    // 显示加载中
    var tbody = document.getElementById('phonesTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="7"><div class="loading-indicator"><i class="fa fa-spinner fa-spin"></i> 正在加载手机号列表...</div></td></tr>';
    }

    // 发送请求并返回Promise
    return this.ajaxRequest('phone-list', {}, 'GET')
        .then(function (data) {
            self.state.phoneList = data.data || [];
            self.renderPhoneList(self.state.phoneList);
            self.updatePhoneSelect(self.state.phoneList);
        })
        .catch(function (error) {
            self.showNotification('加载手机号列表失败：' + error.message, 'error');
            if (tbody) {
                tbody.innerHTML = '<tr class="loading-row"><td colspan="7" style="text-align:center;color:#dc3545;">加载失败</td></tr>';
            }
        });
};

/**
 * 渲染手机号列表
 */
HeaSecSmsSimulatorManager.prototype.renderPhoneList = function (phones) {
    var tbody = document.getElementById('phonesTableBody');
    if (!tbody) return;

    if (!phones || phones.length === 0) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="7" style="text-align:center;color:#999;">暂无手机号</td></tr>';
        return;
    }

    var html = '';
    for (var i = 0; i < phones.length; i++) {
        var phone = phones[i];
        var isDefault = phone.is_default == 1;
        var status = phone.status == 1;
        var smsCount = phone.sms_count || 0;

        html += '<tr data-id="' + phone.id + '">';
        html += '<td><input type="checkbox" class="phone-checkbox" value="' + phone.id + '" /></td>';
        html += '<td>' + this.escapeHtml(phone.phone_number) + '</td>';
        html += '<td><span class="badge ' + (status ? 'badge-success' : 'badge-warning') + '">' + (status ? '启用' : '禁用') + '</span></td>';
        html += '<td>' + (isDefault ? '<span class="badge badge-default">是</span>' : '否') + '</td>';
        html += '<td><span class="badge badge-info">' + smsCount + '</span></td>';
        html += '<td>' + this.formatDateTime(phone.created_at) + '</td>';
        html += '<td><div class="action-buttons">';
        html += '<button class="btn-xs btn-primary" onclick="window.HeaSec.SmsSimulator.editPhone(' + phone.id + ')"><i class="fa fa-edit"></i> 编辑</button>';
        html += '<button class="btn-xs btn-warning" onclick="window.HeaSec.SmsSimulator.setDefaultPhone(' + phone.id + ')" ' + (isDefault ? 'disabled' : '') + '><i class="fa fa-star"></i> 设为默认</button>';
        html += '<button class="btn-xs btn-info" onclick="window.HeaSec.SmsSimulator.clearPhoneSms(' + phone.id + ')"><i class="fa fa-trash-o"></i> 清空短信</button>';
        html += '<button class="btn-xs btn-danger" onclick="window.HeaSec.SmsSimulator.deletePhone(' + phone.id + ')"><i class="fa fa-trash"></i> 删除</button>';
        html += '</div></td>';
        html += '</tr>';
    }

    tbody.innerHTML = html;

    // 绑定复选框事件
    this.bindPhoneCheckboxEvents();
};

/**
 * 绑定手机号复选框事件
 */
HeaSecSmsSimulatorManager.prototype.bindPhoneCheckboxEvents = function () {
    var self = this;
    var checkboxes = document.querySelectorAll('.phone-checkbox');

    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', function () {
            self.updateSelectedPhoneIds();
            self.updatePhoneToolbarState();
        });
    }
};

/**
 * 更新选中的手机号ID列表
 */
HeaSecSmsSimulatorManager.prototype.updateSelectedPhoneIds = function () {
    var checkboxes = document.querySelectorAll('.phone-checkbox:checked');
    var ids = [];
    for (var i = 0; i < checkboxes.length; i++) {
        ids.push(parseInt(checkboxes[i].value));
    }
    this.state.selectedPhoneIds = ids;
};

/**
 * 更新手机号工具栏状态
 */
HeaSecSmsSimulatorManager.prototype.updatePhoneToolbarState = function () {
    var btnBatchDelete = document.getElementById('btnBatchDeletePhones');
    if (btnBatchDelete) {
        btnBatchDelete.disabled = this.state.selectedPhoneIds.length === 0;
    }
};

/**
 * 全选/取消全选手机号
 */
HeaSecSmsSimulatorManager.prototype.toggleSelectAllPhones = function (checked) {
    var checkboxes = document.querySelectorAll('.phone-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checked;
    }
    this.updateSelectedPhoneIds();
    this.updatePhoneToolbarState();
};

/**
 * 显示手机号模态框
 */
HeaSecSmsSimulatorManager.prototype.showPhoneModal = function (phoneId) {
    var modal = document.getElementById('phoneModal');
    var title = document.getElementById('phoneModalTitle');
    var idInput = document.getElementById('phoneId');
    var numberInput = document.getElementById('phoneNumber');

    if (!modal) return;

    // 重置表单
    if (idInput) idInput.value = '';
    if (numberInput) numberInput.value = '';

    if (phoneId) {
        // 编辑模式
        if (title) title.textContent = '编辑手机号';
        if (idInput) idInput.value = phoneId;

        // 加载手机号信息
        var phone = this.findPhoneById(phoneId);
        if (phone && numberInput) {
            numberInput.value = phone.phone_number;
        }
    } else {
        // 添加模式
        if (title) title.textContent = '添加手机号';
    }

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // 聚焦输入框
    setTimeout(function () {
        if (numberInput) numberInput.focus();
    }, 100);
};

/**
 * 隐藏手机号模态框
 */
HeaSecSmsSimulatorManager.prototype.hidePhoneModal = function () {
    var modal = document.getElementById('phoneModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

/**
 * 保存手机号（添加或编辑）
 */
HeaSecSmsSimulatorManager.prototype.savePhone = function () {
    var self = this;
    var idInput = document.getElementById('phoneId');
    var numberInput = document.getElementById('phoneNumber');

    var id = idInput ? parseInt(idInput.value) : 0;
    var phoneNumber = numberInput ? numberInput.value.trim() : '';

    // 验证手机号格式
    if (!phoneNumber) {
        this.showNotification('请输入手机号', 'error');
        return;
    }

    // 增加：限制不能以110开头
    if (phoneNumber.indexOf('110') === 0) {
        this.showNotification('保留号段(110)不允许注册', 'error');
        return;
    }

    if (!/^1[3-9]\d{9}$/.test(phoneNumber)) {
        this.showNotification('手机号格式不正确，请输入11位手机号（1开头）', 'error');
        return;
    }

    var data = {
        phone_number: phoneNumber
    };

    if (id > 0) {
        data.id = id;
    }

    var apiName = id > 0 ? 'phone-edit' : 'phone-add';

    this.ajaxRequest(apiName, data)
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.hidePhoneModal();
            self.loadPhoneList();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 编辑手机号
 */
HeaSecSmsSimulatorManager.prototype.editPhone = function (phoneId) {
    this.showPhoneModal(phoneId);
};

/**
 * 设置默认手机号
 */
HeaSecSmsSimulatorManager.prototype.setDefaultPhone = function (phoneId) {
    var self = this;

    this.ajaxRequest('phone-set-default', { id: phoneId })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.loadPhoneList();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 清空手机号短信
 */
HeaSecSmsSimulatorManager.prototype.clearPhoneSms = function (phoneId) {
    var self = this;

    if (!confirm('确定要清空该手机号的所有短信吗？此操作不可恢复！')) {
        return;
    }

    this.ajaxRequest('phone-clear-sms', { id: phoneId })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            if (self.state.currentPhoneId === phoneId) {
                self.loadSmsList(phoneId);
            }
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 删除手机号
 */
HeaSecSmsSimulatorManager.prototype.deletePhone = function (phoneId) {
    var self = this;

    if (!confirm('确定要删除该手机号吗？此操作将同时删除关联的所有短信记录，且不可恢复！')) {
        return;
    }

    this.ajaxRequest('phone-delete', { id: phoneId })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.loadPhoneList();

            // 如果删除的是当前查看的手机号，清空短信列表
            if (self.state.currentPhoneId === phoneId) {
                self.state.currentPhoneId = null;
                self.renderSmsList([]);
                self.updateSmsStats(0, 0);
            }
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 批量删除手机号
 */
HeaSecSmsSimulatorManager.prototype.batchDeletePhones = function () {
    var self = this;
    var ids = this.state.selectedPhoneIds;

    if (ids.length === 0) {
        this.showNotification('请先选择要删除的手机号', 'warning');
        return;
    }

    // 检查是否包含默认手机号（防止误删）
    var hasDefaultPhone = false;
    var defaultPhoneInfo = [];
    for (var i = 0; i < this.state.phoneList.length; i++) {
        if (ids.indexOf(this.state.phoneList[i].id) !== -1 && this.state.phoneList[i].is_default == 1) {
            hasDefaultPhone = true;
            defaultPhoneInfo.push(this.state.phoneList[i].phone_number);
        }
    }

    if (hasDefaultPhone) {
        this.showNotification('不能删除默认手机号：' + defaultPhoneInfo.join(', ') + '，请先取消选择默认手机号或设置其他手机号为默认', 'error');
        return;
    }

    if (!confirm('确定要删除选中的 ' + ids.length + ' 个手机号吗？此操作将同时删除关联的所有短信记录，且不可恢复！')) {
        return;
    }

    // 逐个删除
    var deleteCount = 0;
    var errorCount = 0;
    var deleteNext = function (index) {
        if (index >= ids.length) {
            if (errorCount > 0) {
                self.showNotification('删除完成：成功 ' + deleteCount + ' 个，失败 ' + errorCount + ' 个', 'warning');
            } else {
                self.showNotification('成功删除 ' + deleteCount + ' 个手机号', 'success');
            }
            self.loadPhoneList();
            return;
        }

        self.ajaxRequest('phone-delete', { id: ids[index] })
            .then(function (response) {
                deleteCount++;
                deleteNext(index + 1);
            })
            .catch(function (error) {
                errorCount++;
                deleteNext(index + 1);
            });
    };

    deleteNext(0);
};

/**
 * 根据ID查找手机号
 */
HeaSecSmsSimulatorManager.prototype.findPhoneById = function (phoneId) {
    for (var i = 0; i < this.state.phoneList.length; i++) {
        if (this.state.phoneList[i].id === phoneId) {
            return this.state.phoneList[i];
        }
    }
    return null;
};

/**
 * ========================================
 * 短信管理相关方法
 * ========================================
 */

/**
 * 加载短信列表
 */
HeaSecSmsSimulatorManager.prototype.loadSmsList = function (phoneId) {
    var self = this;

    // 显示加载中
    var loadingIndicator = document.getElementById('smsLoadingIndicator');
    var emptyMessage = document.getElementById('smsEmptyMessage');
    var smsList = document.getElementById('smsList');

    if (loadingIndicator) loadingIndicator.style.display = 'flex';
    if (emptyMessage) emptyMessage.style.display = 'none';
    if (smsList) smsList.innerHTML = '';

    // 发送请求
    this.ajaxRequest('sms-list', { phone_id: phoneId }, 'GET')
        .then(function (data) {
            self.state.smsList = data.data.sms_list || [];
            self.renderSmsList(self.state.smsList);
            self.updateSmsStats(data.data.total_count, data.data.unread_count);

            if (loadingIndicator) loadingIndicator.style.display = 'none';
        })
        .catch(function (error) {
            self.showNotification('加载短信列表失败：' + error.message, 'error');
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (emptyMessage) {
                emptyMessage.style.display = 'flex';
                emptyMessage.innerHTML = '<i class="fa fa-exclamation-triangle"></i><p>加载失败</p>';
            }
        });
};

/**
 * 渲染短信列表
 */
HeaSecSmsSimulatorManager.prototype.renderSmsList = function (smsList) {
    var emptyMessage = document.getElementById('smsEmptyMessage');
    var smsListContainer = document.getElementById('smsList');

    if (!smsList || smsList.length === 0) {
        if (emptyMessage) {
            emptyMessage.style.display = 'flex';
            emptyMessage.innerHTML = '<i class="fa fa-inbox"></i><p>暂无短信记录</p>';
        }
        if (smsListContainer) smsListContainer.innerHTML = '';
        return;
    }

    if (emptyMessage) emptyMessage.style.display = 'none';

    var html = '';
    for (var i = 0; i < smsList.length; i++) {
        var sms = smsList[i];
        var isUnread = sms.is_read == 0;

        html += '<div class="sms-item' + (isUnread ? ' unread' : ' is-read') + '" data-id="' + sms.id + '">';
        html += '<div class="sms-item-checkbox"><input type="checkbox" class="sms-checkbox" value="' + sms.id + '" /></div>';
        html += '<div class="sms-item-content" onclick="window.HeaSec.SmsSimulator.showSmsDetail(' + sms.id + ')">';
        html += '<div class="sms-item-header">';
        // 已读/未读状态标识（放在发送人左边）
        html += '<span class="sms-status-badge ' + (isUnread ? 'status-unread' : 'status-read') + '">';
        html += (isUnread ? '未读' : '已读');
        html += '</span>';
        html += '<span class="sms-item-sender">' + this.escapeHtml(sms.sender) + '</span>';
        html += '<span class="sms-item-time">' + this.formatDateTime(sms.created_at) + '</span>';
        html += '</div>';
        html += '<div class="sms-item-body">';
        html += '<div class="sms-bubble">' + this.escapeHtml(sms.message_content) + '</div>';
        // 检测验证码并添加复制按钮（放在气泡右侧）
        var verificationCode = this.extractVerificationCode(sms.message_content);
        if (verificationCode) {
            html += '<button class="sms-copy-code-btn" onclick="event.stopPropagation();window.HeaSec.SmsSimulator.copyVerificationCode(\'' + verificationCode + '\', this)">';
            html += '<i class="fa fa-copy"></i> 复制验证码';
            html += '</button>';
        }
        html += '</div>';
        html += '</div>';
        html += '</div>';
    }

    if (smsListContainer) smsListContainer.innerHTML = html;

    // 绑定复选框事件
    this.bindSmsCheckboxEvents();
};

/**
 * 绑定短信复选框事件
 */
HeaSecSmsSimulatorManager.prototype.bindSmsCheckboxEvents = function () {
    var self = this;
    var checkboxes = document.querySelectorAll('.sms-checkbox');

    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', function (e) {
            e.stopPropagation();
            self.updateSelectedSmsIds();
            self.updateSmsToolbarState();
        });

        // 阻止点击冒泡
        checkboxes[i].addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
};

/**
 * 更新选中的短信ID列表
 */
HeaSecSmsSimulatorManager.prototype.updateSelectedSmsIds = function () {
    var checkboxes = document.querySelectorAll('.sms-checkbox:checked');
    var ids = [];
    for (var i = 0; i < checkboxes.length; i++) {
        ids.push(parseInt(checkboxes[i].value));
    }
    this.state.selectedSmsIds = ids;
};

/**
 * 更新短信工具栏状态
 */
HeaSecSmsSimulatorManager.prototype.updateSmsToolbarState = function () {
    var btnMarkAllRead = document.getElementById('btnMarkAllRead');
    var btnBatchDeleteSms = document.getElementById('btnBatchDeleteSms');

    var hasPhone = this.state.currentPhoneId > 0;
    var hasSelection = this.state.selectedSmsIds.length > 0;

    if (btnMarkAllRead) btnMarkAllRead.disabled = !hasPhone;
    if (btnBatchDeleteSms) btnBatchDeleteSms.disabled = !hasSelection;
};

/**
 * 更新短信统计信息
 */
HeaSecSmsSimulatorManager.prototype.updateSmsStats = function (totalCount, unreadCount) {
    var totalCountEl = document.getElementById('totalCount');
    var unreadCountEl = document.getElementById('unreadCount');

    if (totalCountEl) totalCountEl.textContent = totalCount;
    if (unreadCountEl) unreadCountEl.textContent = unreadCount;
};

/**
 * 更新手机号选择下拉框
 */
HeaSecSmsSimulatorManager.prototype.updatePhoneSelect = function (phones) {
    var searchInput = document.getElementById('phoneSearchInput');
    if (!searchInput) return;

    if (!phones || phones.length === 0) {
        searchInput.value = '';
        searchInput.placeholder = '暂无手机号';
        searchInput.disabled = true;
        return;
    }

    searchInput.disabled = false;
    searchInput.placeholder = '搜索手机号...';

    // 如果有当前选中的手机号，更新显示
    if (this.state.currentPhoneId) {
        var currentPhone = this.findPhoneById(this.state.currentPhoneId);
        if (currentPhone) {
            searchInput.value = currentPhone.phone_number;
        }
    } else if (searchInput.value === '' || searchInput.placeholder === '正在加载...') {
        // 清空输入框
        searchInput.value = '';
    }
};

/**
 * 显示短信详情
 */
HeaSecSmsSimulatorManager.prototype.showSmsDetail = function (smsId) {
    var sms = this.findSmsById(smsId);
    if (!sms) return;

    var modal = document.getElementById('smsDetailModal');
    var senderEl = document.getElementById('smsSender');
    var timeEl = document.getElementById('smsTime');
    var contentEl = document.getElementById('smsContent');

    if (senderEl) senderEl.textContent = sms.sender;
    if (timeEl) timeEl.textContent = this.formatDateTime(sms.created_at);
    if (contentEl) contentEl.textContent = sms.message_content;

    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // 标记为已读
    if (sms.is_read == 0) {
        this.markSmsAsRead(smsId);
    }
};

/**
 * 隐藏短信详情模态框
 */
HeaSecSmsSimulatorManager.prototype.hideSmsDetailModal = function () {
    var modal = document.getElementById('smsDetailModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

/**
 * 标记短信为已读
 */
HeaSecSmsSimulatorManager.prototype.markSmsAsRead = function (smsId) {
    var self = this;

    this.ajaxRequest('sms-mark-read', { id: smsId })
        .then(function (response) {
            // 更新本地状态
            var sms = self.findSmsById(smsId);
            if (sms) {
                sms.is_read = 1;
                sms.read_at = new Date().toISOString();
            }

            // 更新UI
            var smsItem = document.querySelector('.sms-item[data-id="' + smsId + '"]');
            if (smsItem) {
                smsItem.classList.remove('unread');
                smsItem.classList.add('is-read');
            }

            // 重新加载短信列表以更新统计
            if (self.state.currentPhoneId) {
                self.loadSmsList(self.state.currentPhoneId);
            }
        })
        .catch(function (error) {
            console.error('标记已读失败:', error.message);
        });
};

/**
 * 全部标记为已读
 */
HeaSecSmsSimulatorManager.prototype.markAllAsRead = function (phoneId) {
    var self = this;

    this.ajaxRequest('sms-mark-all-read', { phone_id: phoneId })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.loadSmsList(phoneId);
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 批量删除短信
 */
HeaSecSmsSimulatorManager.prototype.batchDeleteSms = function () {
    var self = this;
    var ids = this.state.selectedSmsIds;

    if (ids.length === 0) {
        this.showNotification('请先选择要删除的短信', 'warning');
        return;
    }

    if (!confirm('确定要删除选中的 ' + ids.length + ' 条短信吗？此操作不可恢复！')) {
        return;
    }

    this.ajaxRequest('sms-delete', { ids: ids })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.state.selectedSmsIds = [];
            if (self.state.currentPhoneId) {
                self.loadSmsList(self.state.currentPhoneId);
            }
            self.updateSmsToolbarState();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 根据ID查找短信
 */
HeaSecSmsSimulatorManager.prototype.findSmsById = function (smsId) {
    for (var i = 0; i < this.state.smsList.length; i++) {
        if (this.state.smsList[i].id === smsId) {
            return this.state.smsList[i];
        }
    }
    return null;
};

/**
 * ========================================
 * 工具方法
 * ========================================
 */

/**
 * 从短信内容中提取验证码
 * 支持格式：验证码：XXX，验证码: XXX, 验证码是XXX等
 */
HeaSecSmsSimulatorManager.prototype.extractVerificationCode = function (message) {
    if (!message) return null;

    // 匹配验证码格式：支持中文/英文前缀，支持括号，支持4-32位验证码
    var patterns = [
        // 1. 高置信度：括号内的验证码，如 【123456】, [AbCd12]
        /【([0-9a-zA-Z]{4,32})】/,
        /\[([0-9a-zA-Z]{4,32})\]/,

        // 2. 中文语境：支持多种前缀和分隔符 (冒号、是、为、空格等)
        // 验证码：123456, 校验码是 123456, 激活码为 123456, 动态码 123456
        /(?:验证码|校验码|激活码|动态码)(?:[：:]|是[：:]?|为[：:]?|\s+)\s*([0-9a-zA-Z]{4,32})/,

        // 3. 英文语境：支持 verification code, vcode, code 等
        // verification code: 123456, code is 123456, vcode: 123456
        /(?:verification code|vcode|code)(?:[：:]|\s+is|\s+)\s*([0-9a-zA-Z]{4,32})/i
    ];

    for (var i = 0; i < patterns.length; i++) {
        var match = message.match(patterns[i]);
        if (match && match[1]) {
            return match[1];
        }
    }

    return null;
};

/**
 * 复制验证码到剪贴板
 */
HeaSecSmsSimulatorManager.prototype.copyVerificationCode = function (code, buttonElement) {
    var self = this;

    // 使用现代API复制到剪贴板
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code)
            .then(function () {
                self.showCopySuccess(buttonElement, code);
            })
            .catch(function () {
                // 降级到传统方法
                self.fallbackCopyToClipboard(code, buttonElement);
            });
    } else {
        // 降级到传统方法
        this.fallbackCopyToClipboard(code, buttonElement);
    }
};

/**
 * 降级的复制方法（兼容旧浏览器）
 */
HeaSecSmsSimulatorManager.prototype.fallbackCopyToClipboard = function (code, buttonElement) {
    var textArea = document.createElement('textarea');
    textArea.value = code;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    textArea.style.top = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            this.showCopySuccess(buttonElement, code);
        } else {
            this.showNotification('复制失败，请手动复制', 'error');
        }
    } catch (err) {
        this.showNotification('复制失败，请手动复制', 'error');
    }

    document.body.removeChild(textArea);
};

/**
 * 显示复制成功提示
 */
HeaSecSmsSimulatorManager.prototype.showCopySuccess = function (buttonElement, code) {
    var originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = '<i class="fa fa-check"></i> 已复制';
    buttonElement.classList.add('copied');

    var self = this;
    setTimeout(function () {
        buttonElement.innerHTML = originalText;
        buttonElement.classList.remove('copied');
    }, 2000);

    this.showNotification('验证码 ' + code + ' 已复制到剪贴板', 'success');
};

/**
 * 转义HTML特殊字符
 */
HeaSecSmsSimulatorManager.prototype.escapeHtml = function (text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

/**
 * 格式化日期时间（显示完整的年月日时分秒）
 */
HeaSecSmsSimulatorManager.prototype.formatDateTime = function (datetime) {
    if (!datetime) return '-';

    var date = new Date(datetime);

    // 返回完整的年月日时分秒格式
    return date.getFullYear() + '-' +
        (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
        date.getDate().toString().padStart(2, '0') + ' ' +
        date.getHours().toString().padStart(2, '0') + ':' +
        date.getMinutes().toString().padStart(2, '0') + ':' +
        date.getSeconds().toString().padStart(2, '0');
};

/**
 * ========================================
 * 批量添加手机号相关方法
 * ========================================
 */

/**
 * 绑定批量添加模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindBatchAddPhonesModalEvents = function () {
    var self = this;

    // 关闭按钮
    var closeBtn = document.getElementById('closeBatchAddModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            self.hideBatchAddPhonesModal();
        });
    }

    // 取消按钮
    var cancelBtn = document.getElementById('cancelBatchAddModal');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            self.hideBatchAddPhonesModal();
        });
    }

    // 确定按钮
    var confirmBtn = document.getElementById('confirmBatchAddModal');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            self.batchAddPhones();
        });
    }

    // 遮罩层点击关闭
    var modal = document.getElementById('batchAddPhonesModal');
    var overlay = modal ? modal.querySelector('.modal-overlay') : null;
    if (overlay) {
        overlay.addEventListener('click', function () {
            self.hideBatchAddPhonesModal();
        });
    }

    // 表单提交
    var form = document.getElementById('batchAddPhonesForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            self.batchAddPhones();
        });
    }
};

/**
 * 显示批量添加模态框
 */
HeaSecSmsSimulatorManager.prototype.showBatchAddPhonesModal = function () {
    var modal = document.getElementById('batchAddPhonesModal');
    var textarea = document.getElementById('phonesTextarea');

    if (!modal) return;

    // 清空输入框
    if (textarea) textarea.value = '';

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // 聚焦输入框
    setTimeout(function () {
        if (textarea) textarea.focus();
    }, 100);
};

/**
 * 隐藏批量添加模态框
 */
HeaSecSmsSimulatorManager.prototype.hideBatchAddPhonesModal = function () {
    var modal = document.getElementById('batchAddPhonesModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

/**
 * 批量添加手机号
 */
HeaSecSmsSimulatorManager.prototype.batchAddPhones = function () {
    var self = this;
    var textarea = document.getElementById('phonesTextarea');
    var confirmBtn = document.getElementById('confirmBatchAddModal');

    if (!textarea) return;

    var phonesText = textarea.value.trim();

    if (!phonesText) {
        this.showNotification('请输入手机号', 'error');
        return;
    }

    // 禁用确定按钮，防止重复提交
    if (confirmBtn) confirmBtn.disabled = true;

    var data = {
        phones: phonesText
    };

    this.ajaxRequest('phone-batch-add', data)
        .then(function (response) {
            self.hideBatchAddPhonesModal();
            self.showBatchAddResult(response.data);
            self.loadPhoneList();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        })
        .finally(function () {
            if (confirmBtn) confirmBtn.disabled = false;
        });
};

/**
 * 显示批量添加结果
 */
HeaSecSmsSimulatorManager.prototype.showBatchAddResult = function (data) {
    if (!data) return;

    var message = '批量添加完成：\n\n';
    message += '总计：' + data.total + ' 个\n';
    message += '成功：' + data.success + ' 个\n';
    message += '失败：' + data.failed + ' 个\n';

    if (data.failed > 0 && data.failed_list && data.failed_list.length > 0) {
        message += '\n失败详情：\n';
        for (var i = 0; i < data.failed_list.length; i++) {
            var item = data.failed_list[i];
            message += '• ' + item.phone + ' - ' + item.reason + '\n';
        }
    }

    this.showNotification(message, data.failed > 0 ? 'warning' : 'success');
};

/**
 * ========================================
 * 日志管理相关方法
 * ========================================
 */

/**
 * 绑定日志管理事件
 */
HeaSecSmsSimulatorManager.prototype.bindLogManagementEvents = function () {
    var self = this;

    // 搜索按钮
    var btnSearchLog = document.getElementById('btnSearchLog');
    if (btnSearchLog) {
        btnSearchLog.addEventListener('click', function () {
            self.state.logPage = 1;
            self.loadLogList();
        });
    }

    // 重置搜索按钮
    var btnResetSearch = document.getElementById('btnResetSearch');
    if (btnResetSearch) {
        btnResetSearch.addEventListener('click', function () {
            self.resetLogSearch();
        });
    }

    // 刷新按钮
    var btnRefreshLog = document.getElementById('btnRefreshLog');
    if (btnRefreshLog) {
        btnRefreshLog.addEventListener('click', function () {
            self.loadLogList();
        });
    }

    // 批量删除按钮
    var btnBatchDeleteLog = document.getElementById('btnBatchDeleteLog');
    if (btnBatchDeleteLog) {
        btnBatchDeleteLog.addEventListener('click', function () {
            self.batchDeleteLogs();
        });
    }

    // 清空日志按钮
    var btnClearLog = document.getElementById('btnClearLog');
    if (btnClearLog) {
        btnClearLog.addEventListener('click', function () {
            self.clearAllLogs();
        });
    }

    // 全选复选框
    var selectAllLogs = document.getElementById('selectAllLogs');
    if (selectAllLogs) {
        selectAllLogs.addEventListener('change', function () {
            self.toggleSelectAllLogs(this.checked);
        });
    }
};

/**
 * 加载日志列表
 */
HeaSecSmsSimulatorManager.prototype.loadLogList = function () {
    var self = this;

    // 显示加载中
    var tbody = document.getElementById('logsTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="8"><div class="loading-indicator"><i class="fa fa-spinner fa-spin"></i> 正在加载日志列表...</div></td></tr>';
    }

    // 构建查询参数
    var params = {
        page: this.state.logPage,
        page_size: this.state.logPageSize
    };

    if (this.state.logSearch.phone) {
        params.phone = this.state.logSearch.phone;
    }
    if (this.state.logSearch.sender) {
        params.sender = this.state.logSearch.sender;
    }
    if (this.state.logSearch.status !== '') {
        params.status = this.state.logSearch.status;
    }

    // 发送请求
    this.ajaxRequest('sms-log-list', params, 'GET')
        .then(function (data) {
            self.state.logList = data.data.logs || [];
            self.state.logTotal = data.data.total;
            self.state.logTotalPages = data.data.total_pages;
            self.renderLogList(self.state.logList);
            self.updatePagination();
            self.updateLogToolbarState();
        })
        .catch(function (error) {
            self.showNotification('加载日志列表失败：' + error.message, 'error');
            if (tbody) {
                tbody.innerHTML = '<tr class="loading-row"><td colspan="8" style="text-align:center;color:#dc3545;">加载失败</td></tr>';
            }
        });
};

/**
 * 渲染日志列表
 */
HeaSecSmsSimulatorManager.prototype.renderLogList = function (logs) {
    var tbody = document.getElementById('logsTableBody');
    if (!tbody) return;

    if (!logs || logs.length === 0) {
        tbody.innerHTML = '<tr class="loading-row"><td colspan="8" style="text-align:center;color:#999;">暂无日志记录</td></tr>';
        return;
    }

    var html = '';
    for (var i = 0; i < logs.length; i++) {
        var log = logs[i];
        var isSuccess = log.send_status === '已发送';

        html += '<tr data-id="' + log.id + '">';
        html += '<td><input type="checkbox" class="log-checkbox" value="' + log.id + '" /></td>';
        html += '<td>' + this.escapeHtml(log.phone_number) + '</td>';
        html += '<td>' + this.escapeHtml(log.sender) + '</td>';
        html += '<td title="' + this.escapeHtml(log.message_content) + '">' + this.escapeHtml(log.message_content) + '</td>';
        html += '<td><span class="badge ' + (isSuccess ? 'badge-success' : 'badge-warning') + '">' + this.escapeHtml(log.send_status) + '</span></td>';
        html += '<td>' + (log.detail_info ? this.escapeHtml(log.detail_info) : '-') + '</td>';
        html += '<td>' + this.escapeHtml(log.ip_address || '-') + '</td>';
        html += '<td>' + this.formatDateTime(log.created_at) + '</td>';
        html += '</tr>';
    }

    tbody.innerHTML = html;

    // 绑定复选框事件
    this.bindLogCheckboxEvents();
};

/**
 * 绑定日志复选框事件
 */
HeaSecSmsSimulatorManager.prototype.bindLogCheckboxEvents = function () {
    var self = this;
    var checkboxes = document.querySelectorAll('.log-checkbox');

    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', function () {
            self.updateSelectedLogIds();
            self.updateLogToolbarState();
        });
    }
};

/**
 * 更新选中的日志ID列表
 */
HeaSecSmsSimulatorManager.prototype.updateSelectedLogIds = function () {
    var checkboxes = document.querySelectorAll('.log-checkbox:checked');
    var ids = [];
    for (var i = 0; i < checkboxes.length; i++) {
        ids.push(parseInt(checkboxes[i].value));
    }
    this.state.selectedLogIds = ids;
};

/**
 * 更新日志工具栏状态
 */
HeaSecSmsSimulatorManager.prototype.updateLogToolbarState = function () {
    var btnBatchDelete = document.getElementById('btnBatchDeleteLog');
    if (btnBatchDelete) {
        btnBatchDelete.disabled = this.state.selectedLogIds.length === 0;
    }

    // 从搜索框获取搜索条件
    var phoneInput = document.getElementById('logSearchPhone');
    var senderInput = document.getElementById('logSearchSender');
    var statusSelect = document.getElementById('logSearchStatus');

    if (phoneInput) this.state.logSearch.phone = phoneInput.value.trim();
    if (senderInput) this.state.logSearch.sender = senderInput.value.trim();
    if (statusSelect) this.state.logSearch.status = statusSelect.value;
};

/**
 * 更新分页控件
 */
HeaSecSmsSimulatorManager.prototype.updatePagination = function () {
    var self = this;
    var totalCountEl = document.getElementById('logTotalCount');
    var paginationControls = document.getElementById('logPaginationControls');

    if (totalCountEl) {
        totalCountEl.textContent = this.state.logTotal;
    }

    if (!paginationControls) return;

    var html = '';

    // 上一页按钮
    html += '<button class="pagination-btn" ' + (this.state.logPage <= 1 ? 'disabled' : '') + ' data-page="' + (this.state.logPage - 1) + '"><i class="fa fa-chevron-left"></i></button>';

    // 页码按钮
    var maxPagesToShow = 5;
    var startPage = Math.max(1, this.state.logPage - Math.floor(maxPagesToShow / 2));
    var endPage = Math.min(this.state.logTotalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    if (startPage > 1) {
        html += '<button class="pagination-btn" data-page="1">1</button>';
        if (startPage > 2) {
            html += '<span class="pagination-ellipsis">...</span>';
        }
    }

    for (var i = startPage; i <= endPage; i++) {
        html += '<button class="pagination-btn ' + (i === this.state.logPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
    }

    if (endPage < this.state.logTotalPages) {
        if (endPage < this.state.logTotalPages - 1) {
            html += '<span class="pagination-ellipsis">...</span>';
        }
        html += '<button class="pagination-btn" data-page="' + this.state.logTotalPages + '">' + this.state.logTotalPages + '</button>';
    }

    // 下一页按钮
    html += '<button class="pagination-btn" ' + (this.state.logPage >= this.state.logTotalPages ? 'disabled' : '') + ' data-page="' + (this.state.logPage + 1) + '"><i class="fa fa-chevron-right"></i></button>';

    paginationControls.innerHTML = html;

    // 绑定分页按钮事件
    var pageButtons = paginationControls.querySelectorAll('.pagination-btn');
    for (var i = 0; i < pageButtons.length; i++) {
        pageButtons[i].addEventListener('click', function () {
            var page = parseInt(this.getAttribute('data-page'));
            if (page && page !== self.state.logPage) {
                self.state.logPage = page;
                self.loadLogList();
            }
        });
    }
};

/**
 * 全选/取消全选日志
 */
HeaSecSmsSimulatorManager.prototype.toggleSelectAllLogs = function (checked) {
    var checkboxes = document.querySelectorAll('.log-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checked;
    }
    this.updateSelectedLogIds();
    this.updateLogToolbarState();
};

/**
 * 重置日志搜索
 */
HeaSecSmsSimulatorManager.prototype.resetLogSearch = function () {
    this.state.logSearch = {
        phone: '',
        sender: '',
        status: ''
    };
    this.state.logPage = 1;

    var phoneInput = document.getElementById('logSearchPhone');
    var senderInput = document.getElementById('logSearchSender');
    var statusSelect = document.getElementById('logSearchStatus');

    if (phoneInput) phoneInput.value = '';
    if (senderInput) senderInput.value = '';
    if (statusSelect) statusSelect.value = '';

    this.loadLogList();
};

/**
 * 批量删除日志
 */
HeaSecSmsSimulatorManager.prototype.batchDeleteLogs = function () {
    var self = this;
    var ids = this.state.selectedLogIds;

    if (ids.length === 0) {
        this.showNotification('请先选择要删除的日志', 'warning');
        return;
    }

    if (!confirm('确定要删除选中的 ' + ids.length + ' 条日志吗？此操作不可恢复！')) {
        return;
    }

    this.ajaxRequest('sms-log-batch-delete', { log_ids: ids })
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.state.selectedLogIds = [];
            self.loadLogList();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * 清空所有日志
 */
HeaSecSmsSimulatorManager.prototype.clearAllLogs = function () {
    var self = this;

    if (!confirm('确定要清空所有日志吗？此操作不可恢复！')) {
        return;
    }

    this.ajaxRequest('sms-log-clear', {})
        .then(function (response) {
            self.showNotification(response.message, 'success');
            self.state.selectedLogIds = [];
            self.loadLogList();
        })
        .catch(function (error) {
            self.showNotification(error.message, 'error');
        });
};

/**
 * ========================================
 * 页面初始化相关方法
 * ========================================
 */

/**
 * 绑定使用说明模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindHelpModalEvents = function () {
    var self = this;

    // 显示使用说明按钮
    var btnShowHelp = document.getElementById('btnShowHelp');
    if (btnShowHelp) {
        btnShowHelp.addEventListener('click', function () {
            self.showHelpModal();
        });
    }

    // 关闭按钮
    var btnClose = document.getElementById('btnCloseHelpModal');
    if (btnClose) {
        btnClose.addEventListener('click', function () {
            self.hideHelpModal();
        });
    }

    // 确认按钮
    var btnConfirm = document.getElementById('btnConfirmHelpModal');
    if (btnConfirm) {
        btnConfirm.addEventListener('click', function () {
            self.hideHelpModal();
        });
    }

    // 遮罩层点击关闭
    var modal = document.getElementById('helpModal');
    var overlay = modal ? modal.querySelector('.modal-overlay') : null;
    if (overlay) {
        overlay.addEventListener('click', function () {
            self.hideHelpModal();
        });
    }
};

/**
 * 显示使用说明模态框
 */
HeaSecSmsSimulatorManager.prototype.showHelpModal = function () {
    var modal = document.getElementById('helpModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
};

/**
 * 隐藏使用说明模态框
 */
HeaSecSmsSimulatorManager.prototype.hideHelpModal = function () {
    var modal = document.getElementById('helpModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

/**
 * ========================================
 * 数据库初始化相关方法
 * ========================================
 */

/**
 * 显示数据库初始化模态框
 */
HeaSecSmsSimulatorManager.prototype.showDbInitModal = function () {
    var modal = document.getElementById('dbInitModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
};

/**
 * 隐藏数据库初始化模态框
 */
HeaSecSmsSimulatorManager.prototype.hideDbInitModal = function () {
    var modal = document.getElementById('dbInitModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

/**
 * 绑定数据库初始化模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindDbInitModalEvents = function () {
    var self = this;

    // 刷新页面按钮
    var btnReload = document.getElementById('btnReloadPage');
    if (btnReload) {
        btnReload.addEventListener('click', function () {
            location.reload();
        });
    }

    // 确认初始化按钮
    var btnInit = document.getElementById('btnInitDatabase');
    if (btnInit) {
        btnInit.addEventListener('click', function () {
            // 禁用按钮，防止重复点击
            btnInit.disabled = true;
            btnInit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 正在初始化...';

            // 发送初始化请求
            self.ajaxRequest('init-database', {}, 'POST')
                .then(function (data) {
                    self.showNotification('数据库初始化成功！页面即将刷新...', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                })
                .catch(function (error) {
                    self.showNotification('数据库初始化失败：' + error.message, 'error');
                    btnInit.disabled = false;
                    btnInit.innerHTML = '<i class="fa fa-check"></i> 确认初始化';
                });
        });
    }

    // 遮罩层点击关闭（可选，如果不允许点击遮罩关闭，可以移除）
    var modal = document.getElementById('dbInitModal');
    var overlay = modal ? modal.querySelector('.modal-overlay') : null;
    if (overlay) {
        overlay.addEventListener('click', function () {
            // 数据库初始化模态框不允许点击遮罩关闭
            // self.hideDbInitModal();
        });
    }
};

/**
 * 绑定数据库重置模态框事件
 */
HeaSecSmsSimulatorManager.prototype.bindDbResetModalEvents = function () {
    var self = this;

    // 重置按钮点击事件
    var btnReset = document.getElementById('btnResetDatabase');
    if (btnReset) {
        btnReset.addEventListener('click', function () {
            self.showDbResetModal();
        });
    }

    // 关闭按钮
    var btnClose = document.getElementById('btnCloseResetModal');
    if (btnClose) {
        btnClose.addEventListener('click', function () {
            self.hideDbResetModal();
        });
    }

    // 取消按钮
    var btnCancel = document.getElementById('btnCancelReset');
    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            self.hideDbResetModal();
        });
    }

    // 确认输入框验证
    var inputConfirm = document.getElementById('resetConfirmInput');
    var btnConfirm = document.getElementById('btnConfirmReset');
    if (inputConfirm && btnConfirm) {
        inputConfirm.addEventListener('input', function () {
            if (this.value === 'RESET_SMS') {
                btnConfirm.disabled = false;
            } else {
                btnConfirm.disabled = true;
            }
        });
    }

    // 确认重置按钮
    if (btnConfirm) {
        btnConfirm.addEventListener('click', function () {
            var inputValue = inputConfirm ? inputConfirm.value : '';
            if (inputValue === 'RESET_SMS') {
                self.resetDatabase();
            }
        });
    }
};

/**
 * 显示数据库重置模态框
 */
HeaSecSmsSimulatorManager.prototype.showDbResetModal = function () {
    var modal = document.getElementById('dbResetModal');
    if (modal) {
        modal.style.display = 'block';

        // 清空输入框
        var inputConfirm = document.getElementById('resetConfirmInput');
        var btnConfirm = document.getElementById('btnConfirmReset');
        if (inputConfirm) {
            inputConfirm.value = '';
        }
        if (btnConfirm) {
            btnConfirm.disabled = true;
        }
    }
};

/**
 * 隐藏数据库重置模态框
 */
HeaSecSmsSimulatorManager.prototype.hideDbResetModal = function () {
    var modal = document.getElementById('dbResetModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

/**
 * 重置短信模拟器数据库
 */
HeaSecSmsSimulatorManager.prototype.resetDatabase = function () {
    var self = this;

    // 禁用确认按钮
    var btnConfirm = document.getElementById('btnConfirmReset');
    if (btnConfirm) {
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 重置中...';
    }

    // 调用前台网站的重置API
    fetch('/heasecdev/api/heasec/reset_database.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'confirm=YES_RESET_DATABASE&reset_sms_simulator=1'
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                self.showNotification('短信模拟器数据库重置成功！页面即将刷新...', 'success');
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                self.showNotification('数据库重置失败：' + data.message, 'error');
                if (btnConfirm) {
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = '<i class="fa fa-refresh"></i> 确认重置';
                }
            }
        })
        .catch(function (error) {
            self.showNotification('数据库重置失败：' + error.message, 'error');
            if (btnConfirm) {
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fa fa-refresh"></i> 确认重置';
            }
        });
};

/**
 * 更新默认手机号徽章
 */
HeaSecSmsSimulatorManager.prototype.updateDefaultPhoneBadge = function (phoneNumber) {
    var headerActions = document.getElementById('headerActions');
    if (!headerActions) return;

    // 清空现有内容
    headerActions.innerHTML = '';

    if (phoneNumber) {
        var badge = document.createElement('span');
        badge.className = 'default-phone-badge';
        badge.innerHTML = '<i class="fa fa-star"></i> 默认手机号: ' + this.escapeHtml(phoneNumber);
        headerActions.appendChild(badge);
    }
};

/**
 * 切换到指定标签页
 */
HeaSecSmsSimulatorManager.prototype.switchToTab = function (tabName) {
    var tabItems = document.querySelectorAll('.heasec-sms-tabs .tab-item');
    var tabPanes = document.querySelectorAll('.heasec-sms-content .tab-pane');

    // 移除所有激活状态
    for (var i = 0; i < tabItems.length; i++) {
        tabItems[i].classList.remove('active');
    }
    for (var j = 0; j < tabPanes.length; j++) {
        tabPanes[j].classList.remove('active');
    }

    // 激活指定标签页
    for (var k = 0; k < tabItems.length; k++) {
        if (tabItems[k].getAttribute('data-tab') === tabName) {
            tabItems[k].classList.add('active');
            break;
        }
    }

    var targetPane = document.getElementById('tab-' + tabName);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    // 更新当前标签页状态
    this.state.currentTab = tabName;

    // 根据标签页类型执行相应操作
    if (tabName === 'logs' && this.showLogTab && this.state.logList.length === 0) {
        // 切换到日志标签页，加载日志列表
        this.loadLogList();
    }
};
