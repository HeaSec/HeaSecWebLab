/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场交互脚本
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
    window.initMixedsi = function (level, basePath) {
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
            bindLevel2Form();
        } else if (currentLevel === 3) {
            bindLevel3Form();
        }
    }

    // ========== 第一关：新闻搜索 + 登录 ==========

    /**
     * 绑定第一关表单（搜索、登录、退出）
     */
    function bindLevel1Forms() {
        // 搜索表单
        var searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var keywordInput = document.getElementById('keyword');
                if (!keywordInput) return;

                var keyword = keywordInput.value.trim();
                if (!keyword) {
                    showResult('resultArea', false, '请输入搜索关键词');
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
                        renderSearchResults(res.data);
                    } else {
                        showResult('resultArea', false, res.message);
                        hideArea('searchResultArea');
                    }
                })
                .catch(function () {
                    showResult('resultArea', false, '请求失败，请稍后重试');
                })
                .finally(function () {
                    setButtonLoading(btn, false);
                });
            });
        }

        // 登录表单
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var usernameInput = document.getElementById('loginUsername');
                var passwordInput = document.getElementById('loginPassword');
                if (!usernameInput || !passwordInput) return;

                var username = usernameInput.value.trim();
                var password = passwordInput.value;

                if (!username || !password) {
                    showResult('resultArea', false, '请输入用户名和密码');
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
                        showResult('resultArea', true, res.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 800);
                    } else {
                        showResult('resultArea', false, res.message);
                    }
                })
                .catch(function () {
                    showResult('resultArea', false, '请求失败，请稍后重试');
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

    /**
     * 渲染搜索结果列表
     */
    function renderSearchResults(data) {
        var area = document.getElementById('searchResultArea');
        if (!area || !data) return;

        var news = data.news || [];
        if (news.length === 0) {
            area.innerHTML = '<div class="alert-warning" style="margin-top:15px;"><i class="fa fa-info-circle"></i><span>未找到匹配的新闻</span></div>';
            area.style.display = 'block';
            return;
        }

        var html = '<div class="heasec-news-list">';
        for (var i = 0; i < news.length; i++) {
            var item = news[i];
            html += '<div class="heasec-news-item">';
            html += '<div class="heasec-news-title">' + escapeHtml(item.title) + '</div>';
            html += '<div class="heasec-news-content">' + escapeHtml(item.content) + '</div>';
            html += '</div>';
        }
        html += '</div>';

        area.innerHTML = html;
        area.style.display = 'block';
    }

    // ========== 第二关：商品查询 ==========

    /**
     * 绑定第二关商品查询表单
     */
    function bindLevel2Form() {
        var form = document.getElementById('productForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var idInput = document.getElementById('productId');
            if (!idInput) return;

            var id = idInput.value.trim();
            if (!id) {
                showResult('productResultArea', false, '请输入商品ID');
                return;
            }

            var btn = document.getElementById('queryBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('id', id);

            fetch('api/process-level2.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success && res.data && res.data.product) {
                    renderProductDetail(res.data.product);
                } else {
                    showResult('productResultArea', false, res.message);
                }
            })
            .catch(function () {
                showResult('productResultArea', false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 渲染商品详情
     */
    function renderProductDetail(product) {
        var area = document.getElementById('productResultArea');
        if (!area) return;

        var html = '<div class="heasec-product-detail">';
        html += '<div class="product-name">' + escapeHtml(product.name) + '</div>';
        html += '<dl class="product-info">';
        html += '<dt>商品ID：</dt><dd>' + escapeHtml(String(product.id)) + '</dd>';
        html += '<dt>价格：</dt><dd>￥' + escapeHtml(String(product.price)) + '</dd>';
        html += '<dt>库存：</dt><dd>' + escapeHtml(String(product.stock)) + '</dd>';
        html += '<dt>描述：</dt><dd>' + escapeHtml(product.description || '') + '</dd>';
        html += '</dl></div>';

        area.innerHTML = html;
        area.style.display = 'block';
    }

    // ========== 第三关：订单查询 ==========

    /**
     * 绑定第三关订单查询表单
     */
    function bindLevel3Form() {
        var form = document.getElementById('orderForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var orderNoInput = document.getElementById('orderNo');
            if (!orderNoInput) return;

            var orderNo = orderNoInput.value.trim();
            if (!orderNo) {
                showResult('orderResultArea', false, '请输入订单号');
                return;
            }

            var btn = document.getElementById('queryBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('order_no', orderNo);

            fetch('api/process-level3.php', {
                method: 'POST',
                body: formData,
                timeout: 15000
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success && res.data && res.data.order) {
                    renderOrderDetail(res.data.order);
                } else if (res.success) {
                    showResult('orderResultArea', false, '未找到该订单');
                } else {
                    showResult('orderResultArea', false, res.message);
                }
            })
            .catch(function () {
                showResult('orderResultArea', false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 渲染订单详情
     */
    function renderOrderDetail(order) {
        var area = document.getElementById('orderResultArea');
        if (!area) return;

        var html = '<div class="heasec-order-detail">';
        html += '<dl class="order-info">';
        html += '<dt>订单ID：</dt><dd>' + escapeHtml(String(order.id)) + '</dd>';
        html += '<dt>订单号：</dt><dd>' + escapeHtml(order.order_no) + '</dd>';
        html += '<dt>状态：</dt><dd>' + escapeHtml(order.status) + '</dd>';
        html += '<dt>创建时间：</dt><dd>' + escapeHtml(order.created_at || '') + '</dd>';
        html += '</dl></div>';

        area.innerHTML = html;
        area.style.display = 'block';
    }

    // ========== 通用功能 ==========

    /**
     * 显示操作结果
     */
    function showResult(areaId, success, message) {
        var area = document.getElementById(areaId);
        if (!area) return;

        if (success) {
            area.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            area.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        area.style.display = 'block';
    }

    /**
     * 隐藏区域
     */
    function hideArea(areaId) {
        var area = document.getElementById(areaId);
        if (area) {
            area.style.display = 'none';
            area.innerHTML = '';
        }
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
                title: '恭喜你完成了SQL注入综合实战',
                message: '你已掌握在多层过滤环境下综合运用多种SQL注入技术的能力',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'mixedsi',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你完成了SQL注入综合实战\n\n你已掌握在多层过滤环境下综合运用多种SQL注入技术的能力！');
        }
    }

    /**
     * 更新学习进度状态
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'mixedsi',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    }

    /**
     * 设置按钮加载状态
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
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
