/**
 * HeaSec天积安全团队 - SOAP与XML安全靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initSoapXmlRange = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        if (currentLevel === 1) {
            initLevel1();
        } else if (currentLevel === 2) {
            initLevel2();
        } else if (currentLevel === 3) {
            initLevel3();
        }

        bindVerifyForm();
        overrideResetButton();
    };

    /**
     * 构造SOAP XML请求体
     * @param {string} operation SOAP操作名
     * @param {object} params 参数键值对
     * @returns {string} SOAP XML字符串
     */
    function buildSoapEnvelope(operation, params) {
        var body = '';
        for (var key in params) {
            body += '<' + key + '>' + params[key] + '</' + key + '>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>' +
               '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">' +
               '<soap:Body>' +
               '<' + operation + '>' + body + '</' + operation + '>' +
               '</soap:Body>' +
               '</soap:Envelope>';
    }

    /**
     * 发送SOAP请求
     * @param {string} apiUrl API地址
     * @param {string} operation SOAP操作名
     * @param {object} params 参数
     * @param {function} callback 回调函数
     */
    function sendSoapRequest(apiUrl, operation, params, callback) {
        var xml = buildSoapEnvelope(operation, params);
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'text/xml' },
            body: xml
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            callback(data);
        }).catch(function () {
            callback({ success: false, message: '请求失败，请稍后重试' });
        });
    }

    /**
     * 对输入值进行安全处理（第二关专用）
     * 将单引号编码为 HTML 实体
     * @param {string} value 输入值
     * @returns {string} 处理后的值
     */
    function sanitizeInput(value) {
        return value.replace(/'/g, '&#39;');
    }

    // ==========================================
    // 第一关初始化
    // ==========================================

    function initLevel1() {
        bindTabSwitch();
        bindLevel1Register();
        bindLevel1Login();
        bindLogout('level1');
        checkLoginStatus(1);
    }

    /**
     * 绑定功能切换标签
     */
    function bindTabSwitch() {
        var tabs = document.querySelectorAll('.tab-switch button');
        var regForm = document.getElementById('registerForm');
        var loginForm = document.getElementById('loginFormLevel1');

        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');

                var target = tab.getAttribute('data-tab');
                if (target === 'register') {
                    if (regForm) regForm.style.display = 'block';
                    if (loginForm) loginForm.style.display = 'none';
                } else if (target === 'login') {
                    if (regForm) regForm.style.display = 'none';
                    if (loginForm) loginForm.style.display = 'block';
                }
            });
        });
    }

    /**
     * 绑定第一关注册表单
     */
    function bindLevel1Register() {
        var form = document.getElementById('registerForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('reg-username').value.trim();
            var password = document.getElementById('reg-password').value.trim();
            var confirmPwd = document.getElementById('reg-confirm-password').value.trim();

            if (!username || !password) {
                showResult(false, '用户名和密码不能为空');
                return;
            }

            if (password !== confirmPwd) {
                showResult(false, '两次输入的密码不一致');
                return;
            }

            sendSoapRequest('api/level1/register.php', 'Register', {
                username: username,
                password: password
            }, function (data) {
                showResult(data.success, data.message);
            });
        });
    }

    /**
     * 绑定第一关登录表单
     */
    function bindLevel1Login() {
        var form = document.getElementById('loginFormLevel1');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var username = document.getElementById('login-username-l1').value.trim();
            var password = document.getElementById('login-password-l1').value.trim();

            if (!username || !password) {
                showResult(false, '用户名和密码不能为空');
                return;
            }

            sendSoapRequest('api/level1/login.php', 'Login', {
                username: username,
                password: password
            }, function (data) {
                if (data.success) {
                    handleLoginSuccess(1, data);
                } else {
                    showResult(false, data.message);
                }
            });
        });
    }

    // ==========================================
    // 第二关初始化
    // ==========================================

    function initLevel2() {
        bindLevel2Login();
        bindLogout('level2');
        checkLoginStatus(2);
    }

    /**
     * 绑定第二关登录表单（带单引号编码）
     */
    function bindLevel2Login() {
        var form = document.getElementById('loginFormLevel2');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var rawUsername = document.getElementById('login-username-l2').value.trim();
            var rawPassword = document.getElementById('login-password-l2').value.trim();

            if (!rawUsername || !rawPassword) {
                showResult(false, '用户名和密码不能为空');
                return;
            }

            // 第二关：对输入值进行单引号编码
            sendSoapRequest('api/level2/login.php', 'Login', {
                username: sanitizeInput(rawUsername),
                password: sanitizeInput(rawPassword)
            }, function (data) {
                if (data.success) {
                    handleLoginSuccess(2, data);
                } else {
                    showResult(false, data.message);
                }
            });
        });
    }

    // ==========================================
    // 第三关初始化
    // ==========================================

    function initLevel3() {
        bindLevel3Search();
    }

    /**
     * 绑定第三关商品搜索表单
     */
    function bindLevel3Search() {
        var form = document.getElementById('searchForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var keyword = document.getElementById('search-keyword').value.trim();

            sendSoapRequest('api/level3/query.php', 'QueryProducts', {
                keyword: keyword
            }, function (data) {
                if (data.success) {
                    showSearchResults(data.products || [], data);

                    // 如果检测到SSRF通关密码，显示在结果区域
                    if (data.ssrf_detected && data.passcode) {
                        showResult(true, '检测到内部API响应，通关密码：' + data.passcode);
                    }
                } else {
                    showResult(false, data.message);
                }
            });
        });
    }

    /**
     * 显示搜索结果
     * @param {Array} products 商品列表
     * @param {object} data 完整响应数据
     */
    function showSearchResults(products, data) {
        var container = document.getElementById('searchResults');
        if (!container) return;

        if (!products || products.length === 0) {
            if (data.ssrf_detected) {
                container.innerHTML = '';
                container.style.display = 'none';
                return;
            }
            container.innerHTML = '<div class="data-empty"><i class="fa fa-search"></i> 未找到匹配的商品</div>';
            container.style.display = 'block';
            return;
        }

        var html = '<table class="data-table">' +
            '<thead><tr>' +
            '<th>商品名称</th><th>分类</th><th>价格</th>' +
            '</tr></thead><tbody>';

        products.forEach(function (p) {
            html += '<tr>' +
                '<td>' + escapeHtml(p.name || '') + '</td>' +
                '<td>' + escapeHtml(p.category || '') + '</td>' +
                '<td>' + escapeHtml(String(p.price || '')) + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
        container.style.display = 'block';
    }

    // ==========================================
    // 通用登录处理
    // ==========================================

    /**
     * 处理登录成功
     * @param {number} level 关卡编号
     * @param {object} data 响应数据
     */
    function handleLoginSuccess(level, data) {
        var titleEl = document.getElementById('mainCardTitle');
        if (titleEl) titleEl.textContent = '用户信息';

        // 隐藏表单区域
        var formSection = document.getElementById('formSection');
        if (formSection) formSection.style.display = 'none';

        // 显示用户信息区域
        var userInfo = document.getElementById('userInfo');
        if (userInfo) userInfo.style.display = 'block';

        // 填充基本信息
        var displayUsername = document.getElementById('displayUsername');
        var displayRole = document.getElementById('displayRole');
        if (displayUsername) displayUsername.textContent = data.data.username;
        if (displayRole) displayRole.textContent = data.data.role === 'admin' ? '管理员' : '普通用户';

        // 显示退出按钮
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        // 清除结果区域
        showResult(true, data.message);

        // 自动调用profile接口获取完整用户信息
        fetchProfile(level);
    }

    /**
     * 获取用户详细信息
     * @param {number} level 关卡编号
     */
    function fetchProfile(level) {
        fetch('api/level' + level + '/profile.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    var displayUsername = document.getElementById('displayUsername');
                    var displayRole = document.getElementById('displayRole');
                    if (displayUsername) displayUsername.textContent = data.data.username;
                    if (displayRole) displayRole.textContent = data.data.role === 'admin' ? '管理员' : '普通用户';

                    // 管理员显示通关密码
                    if (data.data.role === 'admin' && data.data.passcode) {
                        var passcodeDisplay = document.getElementById('passcodeDisplay');
                        var displayPasscode = document.getElementById('displayPasscode');
                        if (passcodeDisplay) passcodeDisplay.style.display = 'flex';
                        if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                    }
                }
            });
    }

    /**
     * 检查登录状态
     * @param {number} level 关卡编号
     */
    function checkLoginStatus(level) {
        fetch('api/get-status.php?level=' + level)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.isLoggedIn) {
                    handleLoginSuccess(level, data);
                }
            });
    }

    /**
     * 绑定退出登录按钮
     * @param {string} levelKey 关卡标识 (level1 / level2)
     */
    function bindLogout(levelKey) {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            fetch('api/' + levelKey + '/logout.php')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        location.reload();
                    }
                });
        });
    }

    // ==========================================
    // 通关验证
    // ==========================================

    /**
     * 绑定通关密码验证表单
     */
    function bindVerifyForm() {
        var form = document.getElementById('verifyForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var passcode = document.getElementById('passcode').value.trim();
            if (!passcode) {
                showVerifyResult(false, '请输入通关密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    level: currentLevel,
                    passcode: passcode
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showVerifyResult(true, data.message);
                    if (currentLevel === 3) {
                        showCongratsModal();
                    } else {
                        updateLearningStatus('学习中');
                        var nextBtn = document.getElementById('nextLevelBtn');
                        if (nextBtn) nextBtn.style.display = 'inline-flex';
                    }
                } else {
                    showVerifyResult(false, data.message || '通关密码错误');
                }
            })
            .catch(function () {
                showVerifyResult(false, '验证失败，请稍后重试');
            })
            .finally(function () {
                if (submitBtn) submitBtn.classList.remove('loading');
            });
        });
    }

    /**
     * 显示验证结果
     */
    function showVerifyResult(success, message) {
        var resultArea = document.getElementById('verifyResultArea');
        if (!resultArea) return;

        if (success) {
            resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        resultArea.style.display = 'block';
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了SOAP服务中XML参数注入、XPath注入和XXE+SSRF攻击的技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'soapxml',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了SOAP服务中XML参数注入、XPath注入和XXE+SSRF攻击的技巧！');
        }
    }

    /**
     * 更新学习进度状态
     * @param {string} status 学习状态值
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'soapxml',
                status: status
            })
        }).catch(function () {});
    }

    // ==========================================
    // 重置按钮
    // ==========================================

    /**
     * 覆盖公共头部重置按钮的行为
     */
    function overrideResetButton() {
        var resetBtn = document.getElementById('resetDatabaseBtn');
        if (!resetBtn) return;

        var newBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newBtn, resetBtn);

        newBtn.addEventListener('click', function () {
            if (window.heasecModalManager) {
                window.heasecModalManager.showModal('reset_confirm', {
                    content: '<div class="text-center">' +
                        '<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin: 20px 0;"></i>' +
                        '<p style="margin: 0; font-size: 16px; color: #333;">确定要重置靶场数据吗？</p>' +
                        '<p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">重置将清空所有通关密码和用户数据，恢复初始状态</p>' +
                        '</div>',
                    onConfirm: function () {
                        fetch('api/reset.php', { method: 'POST' })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.success) {
                                showNotification('重置成功', 'success');
                                setTimeout(function () { location.reload(); }, 1500);
                            } else {
                                showNotification(data.message || '重置失败', 'error');
                            }
                        })
                        .catch(function () {
                            showNotification('重置失败，请稍后重试', 'error');
                        });
                    }
                });
            } else {
                if (confirm('确定要重置靶场数据吗？')) {
                    fetch('api/reset.php', { method: 'POST' })
                    .then(function () { location.reload(); });
                }
            }
        });
    }

    // ==========================================
    // 工具函数
    // ==========================================

    /**
     * 显示操作结果
     */
    function showResult(success, message) {
        var resultArea = document.getElementById('resultArea');
        if (!resultArea) return;

        if (success) {
            resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        resultArea.style.display = 'block';
    }

    /**
     * 显示通知
     */
    function showNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * HTML转义函数
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
