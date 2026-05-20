/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场交互脚本
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    window.HeaSec = window.HeaSec || {};
    window.HeaSec.team = {
        name: '天积安全',
        englishName: 'HeavenlySecret',
        abbreviation: 'HeaSec',
        slogan: '日积寸功，乐享安全'
    };

    var currentLevel = 1;
    var commonBasePath = '';

    var columnMap = {
        1: { keys: ['id', 'product', 'amount'], labels: ['编号', '服务项目', '金额'] },
        2: { keys: ['id', 'name', 'price'], labels: ['编号', '商品名称', '价格'] },
        3: { keys: ['id', 'log_type', 'message'], labels: ['编号', '日志类型', '日志内容'] }
    };

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.HeaSec.initSpecsi = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindBusinessForms();
        bindVerifyForm();
        bindLogoutButton();
    };
    window.initSpecsi = window.HeaSec.initSpecsi;

    /**
     * 根据关卡绑定业务表单
     */
    function bindBusinessForms() {
        if (currentLevel === 1) {
            bindLoginForm('api/process-login.php');
            bindRegisterForm();
            bindOrderButton();
        } else if (currentLevel === 2) {
            bindLoginForm('api/process-login2.php');
            bindSearchForm();
        } else if (currentLevel === 3) {
            bindLoginForm('api/process-login3.php');
            bindLogForm();
        }
    };

    /**
     * 绑定登录表单
     * @param {string} apiUrl - 登录接口URL
     */
    function bindLoginForm(apiUrl) {
        var form = document.getElementById('loginForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var usernameInput = document.getElementById('loginUsername');
            var passwordInput = document.getElementById('loginPassword');
            if (!usernameInput || !passwordInput) return;

            var username = usernameInput.value.trim();
            var password = passwordInput.value;

            if (!username || !password) {
                showBusinessResult(false, '请输入用户名和密码');
                return;
            }

            var btn = document.getElementById('loginBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    showBusinessResult(true, res.message);
                    setTimeout(function () {
                        window.location.reload();
                    }, 800);
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    };

    /**
     * 绑定注册表单（仅第一关）
     */
    function bindRegisterForm() {
        var form = document.getElementById('registerForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var usernameInput = document.getElementById('regUsername');
            var passwordInput = document.getElementById('regPassword');
            var emailInput = document.getElementById('regEmail');
            if (!usernameInput || !passwordInput || !emailInput) return;

            var username = usernameInput.value.trim();
            var password = passwordInput.value;
            var email = emailInput.value.trim();

            if (!username || !password) {
                showBusinessResult(false, '请输入用户名和密码');
                return;
            }

            var btn = document.getElementById('registerBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('email', email || 'test@test.com');

            fetch('api/process-register.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    showBusinessResult(true, res.message);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    };

    /**
     * 绑定订单查询按钮（仅第一关）
     */
    function bindOrderButton() {
        var btn = document.getElementById('orderBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            setButtonLoading(btn, true);

            var formData = new FormData();

            fetch('api/process-orders.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.results && res.data.results.length > 0) {
                        renderResultTable(res.data.results);
                    } else {
                        showBusinessResult(true, res.message);
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    };

    /**
     * 绑定商品搜索表单（仅第二关）
     */
    function bindSearchForm() {
        var form = document.getElementById('searchForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var input = document.getElementById('searchKeyword');
            if (!input) return;

            var keyword = input.value.trim();
            if (!keyword) {
                showBusinessResult(false, '请输入商品名称');
                return;
            }

            var btn = document.getElementById('searchBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('keyword', keyword);

            fetch('api/process-search.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.results && res.data.results.length > 0) {
                        renderResultTable(res.data.results);
                    } else {
                        showBusinessResult(true, res.message);
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    };

    /**
     * 绑定日志查询表单（仅第三关，GET方式）
     */
    function bindLogForm() {
        var form = document.getElementById('logForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var input = document.getElementById('logId');
            if (!input) return;

            var id = input.value.trim();
            if (!id) {
                showBusinessResult(false, '请输入日志ID');
                return;
            }

            var btn = document.getElementById('logBtn');
            setButtonLoading(btn, true);

            // GET方式提交：不进行额外编码，以支持双重URL编码payload绕过WAF
            var apiUrl = 'api/process-logs.php?id=' + id;

            fetch(apiUrl, {
                method: 'GET'
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.results && res.data.results.length > 0) {
                        renderResultTable(res.data.results);
                    } else {
                        showBusinessResult(true, res.message);
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    };

    /**
     * 渲染查询结果表格
     * @param {Array} results - 查询结果数组
     */
    function renderResultTable(results) {
        var resultArea = document.getElementById('queryResultArea') || document.getElementById('userResultArea');
        if (!resultArea) return;

        var map = columnMap[currentLevel];
        if (!map) return;

        var html = '<table class="heasec-result-table"><thead><tr>';
        for (var h = 0; h < map.labels.length; h++) {
            html += '<th>' + escapeHtml(map.labels[h]) + '</th>';
        }
        html += '</tr></thead><tbody>';

        for (var i = 0; i < results.length; i++) {
            html += '<tr>';
            for (var j = 0; j < map.keys.length; j++) {
                var val = results[i][map.keys[j]];
                html += '<td>' + escapeHtml(val !== null && val !== undefined ? val : '') + '</td>';
            }
            html += '</tr>';
        }

        html += '</tbody></table>';
        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    };

    /**
     * 显示业务操作结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     */
    function showBusinessResult(success, message) {
        var resultArea = document.getElementById('queryResultArea');
        if (!resultArea) {
            resultArea = document.getElementById('userResultArea');
        }
        if (!resultArea) return;

        var html = '';
        if (success) {
            html = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    };

    /**
     * 绑定退出按钮
     */
    function bindLogoutButton() {
        var btn = document.getElementById('logoutBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var apiUrl = '';
            if (currentLevel === 1) {
                apiUrl = 'api/process-login.php';
            } else if (currentLevel === 2) {
                apiUrl = 'api/process-login2.php';
            } else {
                apiUrl = 'api/process-login3.php';
            }

            var formData = new FormData();
            formData.append('action', 'logout');

            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(function () {
                window.location.reload();
            })
            .catch(function () {
                window.location.reload();
            });
        });
    };

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
    };

    /**
     * 显示验证结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
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
    };

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了SQL注入中二次注入、宽字节注入和双URL编码绕过三种进阶技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'specsi',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了SQL注入中二次注入、宽字节注入和双URL编码绕过三种进阶技巧！');
        }
    };

    /**
     * 更新学习进度状态
     * @param {string} status - 学习状态值
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'specsi',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    };

    /**
     * 设置按钮加载状态
     * @param {HTMLElement} btn - 按钮元素
     * @param {boolean} loading - 是否加载中
     */
    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.classList.add('loading');
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中';
        } else {
            btn.classList.remove('loading');
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }
    };

    /**
     * HTML转义函数
     * @param {string} text - 原始文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    };
})();
