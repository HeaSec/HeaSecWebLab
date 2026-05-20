/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场交互脚本
 * 版本: v1.0.0
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
    window.initCuosi = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindBusinessForm();
        bindVerifyForm();
    };

    /**
     * 绑定业务表单事件
     */
    function bindBusinessForm() {
        if (currentLevel === 1) {
            bindLevel1Forms();
        } else if (currentLevel === 2) {
            bindMessageForm();
        } else if (currentLevel === 3) {
            bindProductForm();
        }
    }

    // ========== 第一关：登录 + 修改密码 ==========

    /**
     * 绑定第一关表单（登录、修改密码、退出）
     */
    function bindLevel1Forms() {
        // 登录表单
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var usernameInput = document.getElementById('username');
                var passwordInput = document.getElementById('password');
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

                fetch('api/process-login.php', {
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
        }

        // 修改密码表单
        var changePwdForm = document.getElementById('changePwdForm');
        if (changePwdForm) {
            changePwdForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var oldPwdInput = document.getElementById('oldPassword');
                var newPwdInput = document.getElementById('newPassword');
                if (!oldPwdInput || !newPwdInput) return;

                var oldPassword = oldPwdInput.value;
                var newPassword = newPwdInput.value;

                if (!oldPassword || !newPassword) {
                    showBusinessResult(false, '请输入原密码和新密码');
                    return;
                }

                var btn = document.getElementById('changePwdBtn');
                setButtonLoading(btn, true);

                var formData = new FormData();
                formData.append('old_password', oldPassword);
                formData.append('new_password', newPassword);

                fetch('api/process-changepwd.php', {
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
        }

        // 退出按钮
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                var formData = new FormData();
                formData.append('action', 'logout');
                fetch('api/process-login.php', {
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
        }
    }

    // ========== 第二关：留言板 ==========

    /**
     * 绑定第二关留言发布表单
     */
    function bindMessageForm() {
        var form = document.getElementById('messageForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var contentInput = document.getElementById('content');
            if (!contentInput) return;

            var content = contentInput.value.trim();
            if (!content) {
                showBusinessResult(false, '请输入留言内容');
                return;
            }

            var btn = document.getElementById('messageBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('content', content);

            fetch('api/process-level2.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success && res.data && res.data.success) {
                    showBusinessResult(true, res.message, 'success');
                } else if (res.success && res.data && !res.data.success) {
                    showBusinessResult(true, res.message, 'warning');
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
    }

    // ========== 第三关：商品查询 ==========

    /**
     * 第三关：自动从URL参数加载商品列表
     */
    function bindProductForm() {
        var params = new URLSearchParams(window.location.search);
        var orderBy = params.get('order_by') || 'id';
        var direction = params.get('direction') || 'ASC';

        // 更新页面上的排序信息提示
        var sortInfo = document.getElementById('currentSortInfo');
        if (sortInfo) {
            sortInfo.textContent = orderBy + ' ' + direction;
        }

        loadProducts(orderBy, direction);
    }

    /**
     * 发起GET请求加载商品列表
     * @param {string} orderBy - 排序字段
     * @param {string} direction - 排序方向
     */
    function loadProducts(orderBy, direction) {
        var url = 'api/process-level3.php?order_by=' + encodeURIComponent(orderBy) + '&direction=' + encodeURIComponent(direction);

        fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (res.success && res.data && res.data.products) {
                renderProductTable(res.data.products);
            } else {
                showBusinessResult(false, res.message || '查询失败');
            }
        })
        .catch(function () {
            showBusinessResult(false, '请求失败，请稍后重试');
        });
    }

    /**
     * 渲染商品列表表格
     * @param {Array} products - 商品数据数组
     */
    function renderProductTable(products) {
        var area = document.getElementById('productResultArea');
        if (!area) return;

        if (!products || products.length === 0) {
            area.innerHTML = '<div class="alert-warning" style="margin-top:15px;"><i class="fa fa-info-circle"></i><span>暂无商品数据</span></div>';
            area.style.display = 'block';
            return;
        }

        var html = '<table class="heasec-product-table">';
        html += '<thead><tr><th>ID</th><th>商品名称</th><th>价格</th><th>库存</th></tr></thead>';
        html += '<tbody>';
        for (var i = 0; i < products.length; i++) {
            var p = products[i];
            html += '<tr>';
            html += '<td>' + escapeHtml(String(p.id)) + '</td>';
            html += '<td>' + escapeHtml(p.name) + '</td>';
            html += '<td>' + escapeHtml(p.price) + '</td>';
            html += '<td>' + escapeHtml(String(p.stock)) + '</td>';
            html += '</tr>';
        }
        html += '</tbody></table>';

        area.innerHTML = html;
        area.style.display = 'block';
    }

    // ========== 通用功能 ==========

    /**
     * 显示业务操作结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {string} [type] - 结果类型（success/warning/info）
     */
    function showBusinessResult(success, message, type) {
        var resultArea = getResultArea();
        if (!resultArea) return;

        var html = '';
        if (success) {
            var cssClass = 'alert-success';
            var icon = 'fa-check-circle';
            if (type === 'warning') {
                cssClass = 'alert-error';
                icon = 'fa-times-circle';
            } else if (type === 'info') {
                cssClass = 'alert-info';
                icon = 'fa-info-circle';
            }
            html = '<div class="' + cssClass + '"><i class="fa ' + icon + '"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 获取当前关卡的结果展示区域
     * @returns {HTMLElement|null}
     */
    function getResultArea() {
        if (currentLevel === 1) return document.getElementById('userResultArea');
        if (currentLevel === 2) return document.getElementById('messageResultArea');
        if (currentLevel === 3) return document.getElementById('productResultArea');
        return null;
    }

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
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了不同SQL语句类型的注入利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'cuosi',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了不同SQL语句类型的注入利用技巧！');
        }
    }

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
                code: 'cuosi',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    }

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
    }

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
    }
})();
