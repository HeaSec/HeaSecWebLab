/**
 * HeaSec天积安全团队 - 异常数据处理靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var currentProducts = []; // 保存当前关卡的商品列表

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initAnomdata = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();

        if (currentLevel === 1) {
            bindWithdrawButton();
            bindRefreshTransactions();
        } else if (currentLevel === 2) {
            bindPurchaseButton();
            bindRefreshOrders();
        } else if (currentLevel === 3) {
            bindPurchaseButton();
            bindRefreshOrders();
        }
    };

    /**
     * 显示消息模态框
     * @param {string} message - 消息内容
     * @param {string} type - 消息类型
     * @param {Function} onClose - 关闭回调
     */
    function showMessageModal(message, type, onClose) {
        if (window.heasecModalManager) {
            var icon, color;
            switch (type) {
                case 'success':
                    icon = 'fa-check-circle';
                    color = '#28a745';
                    break;
                case 'error':
                    icon = 'fa-times-circle';
                    color = '#dc3545';
                    break;
                case 'warning':
                    icon = 'fa-exclamation-triangle';
                    color = '#ffc107';
                    break;
                default:
                    icon = 'fa-info-circle';
                    color = '#17a2b8';
            }
            window.heasecModalManager.showModal('success_message', {
                content: '<div class="text-center">' +
                    '<i class="fa ' + icon + '" style="font-size: 48px; color: ' + color + '; margin: 20px 0;"></i>' +
                    '<p style="margin: 0; font-size: 16px; color: #333;">' + escapeHtml(message) + '</p>' +
                    '</div>',
                onConfirm: onClose
            });
        } else {
            alert(message);
            if (onClose) onClose();
        }
    }

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

            fetch('api/level' + currentLevel + '/login.php', {
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
                        displayUserInfo(data.data);
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
     * 显示用户信息
     * @param {Object} userData
     */
    function displayUserInfo(userData) {
        // 隐藏登录表单
        var loginForm = document.getElementById('loginForm');
        var loginErrorArea = document.getElementById('loginErrorArea');
        if (loginForm) loginForm.style.display = 'none';
        if (loginErrorArea) loginErrorArea.style.display = 'none';

        // 显示用户信息
        var userInfoArea = document.getElementById('userInfoArea');
        if (userInfoArea) userInfoArea.style.display = 'block';

        // 更新卡片标题
        var mainCardTitle = document.getElementById('mainCardTitle');
        if (mainCardTitle) mainCardTitle.textContent = '用户信息';

        // 显示退出按钮
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        // 更新用户信息显示
        var displayUsername = document.getElementById('displayUsername');
        if (displayUsername) displayUsername.textContent = userData.username;

        // 根据关卡显示不同的余额信息
        if (currentLevel === 1) {
            var displayAlipayBalance = document.getElementById('displayAlipayBalance');
            var displayBankBalance = document.getElementById('displayBankBalance');
            if (displayAlipayBalance) displayAlipayBalance.textContent = '¥' + parseFloat(userData.alipayBalance).toFixed(3);
            if (displayBankBalance) displayBankBalance.textContent = '¥' + parseFloat(userData.bankBalance).toFixed(2);
        } else {
            var displayBalance = document.getElementById('displayBalance');
            if (displayBalance) displayBalance.textContent = parseFloat(userData.balance).toFixed(2);
        }

        // 处理通关密码
        updatePasscodeDisplay(userData.passcode);

        // 第二关和第三关：显示商品列表和订单
        if ((currentLevel === 2 || currentLevel === 3) && userData.products) {
            renderProducts(userData.products);
            fetchOrders();
            // 更新元宝数量显示
            if (userData.yuanbaoCount !== undefined) {
                var displayYuanbaoCount = document.getElementById('displayYuanbaoCount');
                if (displayYuanbaoCount) displayYuanbaoCount.textContent = userData.yuanbaoCount + ' 个';
            }
        }

        // 获取交易记录（仅第一关）
        if (currentLevel === 1) {
            fetchTransactions();
        }
    }

    /**
     * 从服务器数据初始化用户信息显示
     * @param {Object} userData
     */
    window.displayUserInfoFromServer = function (userData) {
        displayUserInfo(userData);
    };

    /**
     * 更新通关密码显示
     * @param {string} passcode
     */
    function updatePasscodeDisplay(passcode) {
        var passcodeArea = document.getElementById('passcodeArea');
        var displayPasscode = document.getElementById('displayPasscode');

        if (passcode) {
            if (passcodeArea) passcodeArea.style.display = 'flex';
            if (displayPasscode) displayPasscode.textContent = passcode;
        } else {
            if (passcodeArea) passcodeArea.style.display = 'none';
        }
    }

    /**
     * 渲染商品列表（第三关）
     * @param {Array} products
     */
    function renderProducts(products) {
        // 先保存商品列表供购买时使用
        currentProducts = products || [];

        var container = document.getElementById('productsList');
        if (!container) return;

        container.innerHTML = '';

        products.forEach(function (product) {
            var item = document.createElement('div');
            item.className = 'product-item';
            item.innerHTML = '<div class="product-info">' +
                '<span class="product-name">' + escapeHtml(product.name) + '</span>' +
                '<span class="product-price">¥' + parseFloat(product.price).toFixed(2) + '</span>' +
                '</div>';
            container.appendChild(item);
        });
    }

    /**
     * 绑定提现按钮（第一关）
     */
    function bindWithdrawButton() {
        var btn = document.getElementById('withdrawBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var amountInput = document.getElementById('withdrawAmount');
            var amount = amountInput ? amountInput.value : 0;

            if (!amount || parseFloat(amount) <= 0) {
                showMessageModal('请输入有效的提现金额', 'warning');
                return;
            }

            btn.classList.add('loading');

            fetch('api/level' + currentLevel + '/withdraw.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showMessageModal(data.message || '提现成功', 'success', function () {
                            // 更新余额显示
                            if (data.data) {
                                var displayAlipayBalance = document.getElementById('displayAlipayBalance');
                                var displayBankBalance = document.getElementById('displayBankBalance');
                                if (displayAlipayBalance) displayAlipayBalance.textContent = '¥' + parseFloat(data.data.alipayBalance).toFixed(3);
                                if (displayBankBalance) displayBankBalance.textContent = '¥' + parseFloat(data.data.bankBalance).toFixed(2);

                                // 更新通关密码
                                updatePasscodeDisplay(data.data.passcode);
                            }
                            // 刷新交易记录
                            fetchTransactions();
                            // 清空输入框
                            if (amountInput) amountInput.value = '';
                        });
                    } else {
                        showMessageModal(data.message || '提现失败', 'error');
                    }
                })
                .catch(function (error) {
                    showMessageModal('提现失败，请稍后重试', 'error');
                })
                .finally(function () {
                    btn.classList.remove('loading');
                });
        });
    }

    /**
     * 绑定转账按钮（第二关）
     */
    function bindTransferButton() {
        var btn = document.getElementById('transferBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var amountInput = document.getElementById('transferAmount');
            var amount = amountInput ? amountInput.value : 0;

            if (!amount || parseFloat(amount) <= 0) {
                showMessageModal('请输入有效的转账金额', 'warning');
                return;
            }

            btn.classList.add('loading');

            fetch('api/level' + currentLevel + '/transfer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount, target: 'user' })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showMessageModal(data.message || '转账成功', 'success', function () {
                            // 更新余额显示
                            if (data.data) {
                                var displayBalance = document.getElementById('displayBalance');
                                if (displayBalance) displayBalance.textContent = parseFloat(data.data.balance).toFixed(2);

                                // 更新通关密码
                                updatePasscodeDisplay(data.data.passcode);
                            }
                            // 刷新交易记录
                            fetchTransactions();
                            // 清空输入框
                            if (amountInput) amountInput.value = '';
                        });
                    } else {
                        showMessageModal(data.message || '转账失败', 'error');
                    }
                })
                .catch(function (error) {
                    showMessageModal('转账失败，请稍后重试', 'error');
                })
                .finally(function () {
                    btn.classList.remove('loading');
                });
        });
    }

    /**
     * 绑定购买按钮（第二关和第三关）
     */
    function bindPurchaseButton() {
        var btn = document.getElementById('purchaseBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var quantityInput = document.getElementById('purchaseQuantity');
            var quantity = quantityInput ? parseInt(quantityInput.value) : 1;

            if (!quantity || quantity < 1) {
                showMessageModal('请输入有效的购买数量', 'warning');
                return;
            }

            btn.classList.add('loading');

            // 从商品列表获取第一个商品的ID
            var productId = (currentProducts && currentProducts.length > 0) ? currentProducts[0].id : 1;

            fetch('api/level' + currentLevel + '/purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ productId: productId, quantity: quantity })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showMessageModal(data.message || '购买成功', 'success', function () {
                            // 刷新页面以更新余额显示
                            location.reload();
                        });
                    } else {
                        showMessageModal(data.message || '购买失败', 'error');
                    }
                })
                .catch(function (error) {
                    showMessageModal('购买失败，请稍后重试', 'error');
                })
                .finally(function () {
                    btn.classList.remove('loading');
                });
        });
    }

    /**
     * 获取交易记录
     */
    function fetchTransactions() {
        fetch('api/level' + currentLevel + '/transactions.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderTransactions(data.data.transactions);
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 渲染交易记录
     * @param {Array} transactions
     */
    function renderTransactions(transactions) {
        var container = document.getElementById('transactionsList');
        if (!container) return;

        if (!transactions || transactions.length === 0) {
            container.innerHTML = '<div class="transactions-empty"><i class="fa fa-inbox"></i> 暂无交易记录</div>';
            return;
        }

        var html = '';
        transactions.forEach(function (tx) {
            var amountClass = parseFloat(tx.amount) >= 0 ? 'positive' : 'negative';
            var amountPrefix = parseFloat(tx.amount) >= 0 ? '+' : '';
            html += '<div class="transaction-item">' +
                '<div class="transaction-info">' +
                '<div class="transaction-type">' + escapeHtml(getTransactionTypeText(tx.type)) + '</div>' +
                '<div class="transaction-detail">' + escapeHtml(tx.detail || '') + ' ' + formatTransactionTime(tx.created_at) + '</div>' +
                '</div>' +
                '<div class="transaction-amount ' + amountClass + '">' + amountPrefix + parseFloat(tx.amount).toFixed(2) + '</div>' +
                '</div>';
        });

        container.innerHTML = html;
    }

    /**
     * 获取交易类型文本
     * @param {string} type
     * @returns {string}
     */
    function getTransactionTypeText(type) {
        var types = {
            'withdraw': '提现',
            'transfer': '转账',
            'purchase': '购买'
        };
        return types[type] || type;
    }

    /**
     * 格式化交易时间
     * @param {string} datetime
     * @returns {string}
     */
    function formatTransactionTime(datetime) {
        if (!datetime) return '';
        var date = new Date(datetime);
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        return month + '-' + day + ' ' + hours + ':' + minutes;
    }

    /**
     * 绑定刷新交易记录按钮
     */
    function bindRefreshTransactions() {
        var btn = document.getElementById('refreshTransactionsBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 刷新中...';
            fetchTransactions();
            setTimeout(function () {
                btn.innerHTML = '<i class="fa fa-refresh"></i> 刷新';
            }, 500);
        });
    }

    /**
     * 获取订单列表（第二关和第三关）
     */
    function fetchOrders() {
        fetch('api/level' + currentLevel + '/orders.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderOrders(data.data.orders);
                    // 更新元宝数量（第二关）
                    if (currentLevel === 2 && data.data.yuanbaoCount !== undefined) {
                        var displayYuanbaoCount = document.getElementById('displayYuanbaoCount');
                        if (displayYuanbaoCount) displayYuanbaoCount.textContent = data.data.yuanbaoCount + ' 个';
                    }
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 渲染订单列表
     * @param {Array} orders
     */
    function renderOrders(orders) {
        var container = document.getElementById('ordersList');
        if (!container) return;

        if (!orders || orders.length === 0) {
            container.innerHTML = '<div class="orders-empty"><i class="fa fa-inbox"></i> 暂无订单记录</div>';
            return;
        }

        var html = '';
        orders.forEach(function (order) {
            html += '<div class="order-item">' +
                '<div class="order-header">' +
                '<span class="order-id">订单 #' + order.id + '</span>' +
                '<span class="order-time">' + formatOrderTime(order.created_at) + '</span>' +
                '</div>' +
                '<div class="order-info">' +
                '<span class="order-product-name">' + escapeHtml(order.product_name || '天积元宝') + '</span>' +
                '<span class="order-quantity">x' + order.quantity + '</span>' +
                '<span class="order-total">¥' + parseFloat(order.total_amount).toFixed(2) + '</span>' +
                '</div>';

            // 第三关：显示二维码
            if (currentLevel === 3 && order.has_qrcode) {
                html += '<div class="order-qrcode">' +
                    '<img src="api/level3/qrcode.php?orderId=' + order.id + '" alt="通关密码二维码" style="max-width: 150px; border-radius: 8px;">' +
                    '<p style="font-size: 12px; color: #6c757d; margin-top: 8px;">扫描二维码获取通关密码</p>' +
                    '</div>';
            }

            html += '</div>';
        });

        container.innerHTML = html;
    }

    /**
     * 格式化订单时间
     * @param {string} datetime
     * @returns {string}
     */
    function formatOrderTime(datetime) {
        if (!datetime) return '';
        var date = new Date(datetime);
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
    }

    /**
     * 绑定刷新订单按钮
     */
    function bindRefreshOrders() {
        var btn = document.getElementById('refreshOrdersBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 刷新中...';
            fetchOrders();
            setTimeout(function () {
                btn.innerHTML = '<i class="fa fa-refresh"></i> 刷新';
            }, 500);
        });
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
            // 调用退出登录API
            fetch('api/logout.php', {
                method: 'POST'
            })
                .then(function (res) { return res.json(); })
                .catch(function (error) { /* 静默处理 */ })
                .finally(function () {
                    // 等待API请求完成后再刷新页面，确保会话已清除
                    location.reload();
                });
        });
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
                    if (data.success && (data.data ? data.data.passed : data.passed)) {
                        showVerifyResult(true, data.message);
                        // 显示下一关按钮或恭喜弹窗
                        if (currentLevel === 3) {
                            showCongratsModal();
                        } else {
                            var nextBtn = document.getElementById('nextLevelBtn');
                            if (nextBtn) {
                                nextBtn.style.display = 'inline-flex';
                            }
                        }
                    } else {
                        showVerifyResult(false, data.message || '通关密码错误');
                    }
                })
                .catch(function (error) {
                    showVerifyResult(false, '验证失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.classList.remove('loading');
                    }
                });
        });
    }

    /**
     * 显示验证结果
     * @param {boolean} success
     * @param {string} message
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
                message: '你掌握了异常数据处理攻击的实现方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'anomdata',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了异常数据处理攻击的实现方式！');
        }
    }

    /**
     * HTML转义函数
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
