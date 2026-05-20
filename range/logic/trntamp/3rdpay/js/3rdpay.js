/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    // ==================== 状态管理 ====================
    var currentLevel = 1;
    var commonBasePath = '';
    var payPage = '';
    var requiredYuanbao = 0;

    // ==================== 初始化 ====================

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     * @param {string} pay - 支付页面
     * @param {number} yuanbao - 需要的元宝数量（第三关）
     */
    window.init3rdpay = function (level, basePath, pay, yuanbao) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        payPage = pay || 'pay1.php';
        requiredYuanbao = yuanbao || 0;

        // 只绑定静态元素的事件
        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();
        bindRefreshOrders();

        // 动态元素的事件在渲染时绑定
    };

    // ==================== UI渲染 ====================

    /**
     * 渲染商品卡片
     * @param {object} product - 商品数据
     */
    function renderProduct(product) {
        var productArea = document.getElementById('productArea');
        if (!productArea || !product) return;

        var html = '<div class="product-card">' +
            '<div class="product-image">' +
            '<i class="fa fa-diamond"></i>' +
            '</div>' +
            '<div class="product-info">' +
            '<h5 class="product-name">' + escapeHtml(product.name) + '</h5>' +
            '<p class="product-price">¥' + parseFloat(product.price).toFixed(2) + '</p>' +
            '</div>' +
            '<div class="product-action">';

        // 第三关需要数量选择器
        if (currentLevel === 3) {
            html += '<div class="quantity-input">' +
                '<button type="button" class="qty-btn" id="qtyMinus">-</button>' +
                '<input type="text" id="quantity" value="1">' +
                '<button type="button" class="qty-btn" id="qtyPlus">+</button>' +
                '</div>';
        }

        html += '<button type="button" class="tech-btn tech-btn-primary" id="buyBtn">' +
            '<i class="fa fa-shopping-cart"></i> 购买' +
            '</button>' +
            '</div>' +
            '</div>';

        productArea.innerHTML = html;

        // 绑定动态元素的事件
        if (currentLevel === 3) {
            bindQuantityButtons();
        }
        bindBuyButton();
    }

    /**
     * 渲染订单列表
     * @param {array} orders - 订单数据
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
            var statusInfo = getOrderStatusInfo(order.status);
            var actionHtml = getOrderActionHtml(order);

            var discountHtml = '';
            if (order.discount && parseFloat(order.discount) > 0) {
                discountHtml = '<div class="order-discount"><i class="fa fa-tag"></i> 优惠：-¥' + parseFloat(order.discount).toFixed(2) + '</div>';
            }

            // 已退款信息（仅部分退款状态时显示，完全退款不显示）
            var refundedHtml = '';
            var refundedQty = parseInt(order.refunded_quantity) || 0;
            var refundedAmt = parseFloat(order.refunded_amount) || 0;
            if (order.status === 'partial_refund' && (refundedQty > 0 || refundedAmt > 0)) {
                refundedHtml = '<div class="order-discount" style="color: #856404; background: #fff3cd; padding: 5px 10px; border-radius: 4px; margin-top: 5px;"><i class="fa fa-undo"></i> 已退款：' + refundedQty + '个，共¥' + refundedAmt.toFixed(2) + '</div>';
            }

            // 使用原始购买数量显示（不因退款而改变）
            var displayQuantity = order.original_quantity || order.quantity;

            html += '<div class="order-item" data-order-id="' + order.id + '">' +
                '<div class="order-header">' +
                '<span class="order-id">订单 ' + escapeHtml(order.order_no) + '</span>' +
                '<span class="order-status ' + statusInfo.className + '">' + statusInfo.text + '</span>' +
                '</div>' +
                '<div class="order-body">' +
                '<div class="order-product">' +
                '<span class="product-name">' + escapeHtml(order.product_name || '天积元宝') + '</span>' +
                '<span class="product-qty">x' + displayQuantity + '</span>' +
                '</div>' +
                '<div class="order-amount">¥' + parseFloat(order.amount).toFixed(2) + '</div>' +
                discountHtml +
                refundedHtml +
                '</div>' +
                '<div class="order-footer">' +
                '<span class="order-time">' + formatOrderTime(order.created_at) + '</span>' +
                actionHtml +
                '</div>' +
                '</div>';
        });

        container.innerHTML = html;

        // 绑定动态按钮事件
        bindPayOrderButtons();
        bindRefundButtons();
    }

    /**
     * 获取订单状态信息
     * @param {string} status - 订单状态
     * @returns {object} 状态文本和样式类
     */
    function getOrderStatusInfo(status) {
        var statusMap = {
            'pending': { text: '待支付', className: 'status-pending' },
            'paid': { text: '已支付', className: 'status-paid' },
            'partial_refund': { text: '部分退款', className: 'status-refund' },
            'refunded': { text: '已退款', className: 'status-refund' }
        };
        return statusMap[status] || { text: status, className: '' };
    }

    /**
     * 获取订单操作按钮HTML
     * @param {object} order - 订单数据
     * @returns {string} 按钮HTML
     */
    function getOrderActionHtml(order) {
        if (order.status === 'pending') {
            return '<button type="button" class="tech-btn tech-btn-primary btn-sm pay-order-btn" ' +
                'data-order="' + order.order_no + '" ' +
                'data-amount="' + order.amount + '" ' +
                'data-quantity="' + (order.quantity || 1) + '">' +
                '<i class="fa fa-credit-card"></i> 去支付' +
                '</button>';
        } else if ((order.status === 'paid' || order.status === 'partial_refund') && currentLevel === 3) {
            // 使用剩余可退数量（不超过剩余可退金额对应的数量）
            var maxRefundQty = order.max_refund_quantity || order.quantity;
            return '<button type="button" class="tech-btn tech-btn-warning btn-sm refund-btn" ' +
                'data-order-id="' + order.id + '" ' +
                'data-order-no="' + order.order_no + '" ' +
                'data-product-name="' + escapeHtml(order.product_name || '天积元宝') + '" ' +
                'data-amount="' + order.amount + '" ' +
                'data-paid-amount="' + (order.paid_amount || order.amount) + '" ' +
                'data-quantity="' + maxRefundQty + '" ' +
                'data-original-quantity="' + (order.original_quantity || order.quantity) + '" ' +
                'data-price="' + (order.price || (order.amount / (order.quantity || 1))) + '" ' +
                'data-refunded-quantity="' + (order.refunded_quantity || 0) + '" ' +
                'data-refunded-amount="' + (order.refunded_amount || 0) + '">' +
                '<i class="fa fa-undo"></i> 申请退款' +
                '</button>';
        }
        return '';
    }

    /**
     * 显示用户信息（统一入口）
     * @param {object} userData - 用户数据
     */
    function displayUserInfo(userData) {
        var loginForm = document.getElementById('loginForm');
        var loginErrorArea = document.getElementById('loginErrorArea');
        if (loginForm) loginForm.style.display = 'none';
        if (loginErrorArea) loginErrorArea.style.display = 'none';

        var userInfoArea = document.getElementById('userInfoArea');
        if (userInfoArea) userInfoArea.style.display = 'block';

        var mainCardTitle = document.getElementById('mainCardTitle');
        if (mainCardTitle) mainCardTitle.textContent = '天积商城';

        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        var displayUsername = document.getElementById('displayUsername');
        if (displayUsername) displayUsername.textContent = userData.username;

        // 渲染商品列表
        if (userData.product) {
            renderProduct(userData.product);
        }

        // 显示通关密码
        if (userData.passcode) {
            var passcodeArea = document.getElementById('passcodeArea');
            var displayPasscode = document.getElementById('displayPasscode');
            if (passcodeArea) passcodeArea.style.display = 'flex';
            if (displayPasscode) displayPasscode.textContent = userData.passcode;
        }

        // 显示元宝数量（第三关）
        if (currentLevel === 3 && userData.yuanbaoCount !== undefined) {
            var displayYuanbao = document.getElementById('displayYuanbao');
            if (displayYuanbao) displayYuanbao.textContent = userData.yuanbaoCount;
        }

        // 更新购买状态（第一关和第二关）
        if (currentLevel !== 3 && userData.hasPaidOrder !== undefined) {
            updatePurchaseStatus(userData.hasPaidOrder);
        }

        // 加载订单列表
        fetchOrders();
    }

    // 别名，保持向后兼容
    window.displayUserInfoFromServer = displayUserInfo;

    /**
     * 更新购买状态显示
     * @param {boolean} hasPaid - 是否已购买
     */
    function updatePurchaseStatus(hasPaid) {
        var statusElement = document.getElementById('displayPurchaseStatus');
        if (!statusElement) return;

        if (hasPaid) {
            statusElement.innerHTML = '<span class="status-purchased"><i class="fa fa-check-circle"></i> 已购买</span>';
        } else {
            statusElement.innerHTML = '<span class="status-not-purchased"><i class="fa fa-clock-o"></i> 未购买</span>';
        }
    }

    // ==================== 事件绑定（使用单例模式） ====================

    /**
     * 绑定购买按钮
     */
    function bindBuyButton() {
        var buyBtn = document.getElementById('buyBtn');
        if (!buyBtn) return;

        // 使用事件委托或克隆节点避免重复绑定
        var parent = buyBtn.parentNode;
        var newBtn = buyBtn.cloneNode(true);
        parent.replaceChild(newBtn, buyBtn);

        newBtn.addEventListener('click', handleBuyClick);
    }

    /**
     * 绑定数量按钮（第三关）
     */
    function bindQuantityButtons() {
        var qtyMinus = document.getElementById('qtyMinus');
        var qtyPlus = document.getElementById('qtyPlus');
        var quantityInput = document.getElementById('quantity');

        if (qtyMinus && quantityInput) {
            qtyMinus.onclick = function () {
                var val = parseInt(quantityInput.value) || 1;
                if (val > 1) {
                    quantityInput.value = val - 1;
                }
            };
        }

        if (qtyPlus && quantityInput) {
            qtyPlus.onclick = function () {
                var val = parseInt(quantityInput.value) || 1;
                if (val < 10) {
                    quantityInput.value = val + 1;
                }
            };
        }
    }

    /**
     * 绑定订单支付按钮
     */
    function bindPayOrderButtons() {
        document.querySelectorAll('.pay-order-btn').forEach(function (btn) {
            btn.onclick = function () {
                var orderNo = this.getAttribute('data-order');
                var amount = this.getAttribute('data-amount');
                var quantity = this.getAttribute('data-quantity');
                var payUrl = payPage + '?order_no=' + encodeURIComponent(orderNo) +
                    '&amount=' + amount +
                    '&quantity=' + quantity;
                var payWindow = window.open(payUrl, '_blank', 'width=500,height=700');

                // 监听支付窗口关闭，自动刷新订单列表
                if (payWindow) {
                    var checkClosed = setInterval(function () {
                        if (payWindow.closed) {
                            clearInterval(checkClosed);
                            // 延迟刷新，等待支付回调处理完成
                            setTimeout(function () {
                                fetchOrders();
                            }, 500);
                        }
                    }, 500);
                }
            };
        });
    }

    /**
     * 绑定退款按钮
     */
    function bindRefundButtons() {
        document.querySelectorAll('.refund-btn').forEach(function (btn) {
            btn.onclick = function () {
                // 直接从按钮的data属性获取订单信息，避免依赖旧的orders数组
                var orderId = parseInt(this.getAttribute('data-order-id'));
                var orderNo = this.getAttribute('data-order-no');
                var productName = this.getAttribute('data-product-name') || '天积元宝';
                var amount = parseFloat(this.getAttribute('data-amount')) || 0;
                var paidAmount = parseFloat(this.getAttribute('data-paid-amount')) || amount;
                var maxQuantity = parseInt(this.getAttribute('data-quantity')) || 1;
                var originalQuantity = parseInt(this.getAttribute('data-original-quantity')) || maxQuantity;
                var price = parseFloat(this.getAttribute('data-price')) || 0;
                var refundedQuantity = parseInt(this.getAttribute('data-refunded-quantity')) || 0;
                var refundedAmount = parseFloat(this.getAttribute('data-refunded-amount')) || 0;

                // 构造订单对象
                var order = {
                    id: orderId,
                    order_no: orderNo,
                    product_name: productName,
                    amount: amount,
                    paid_amount: paidAmount,
                    quantity: maxQuantity, // 剩余可退数量
                    original_quantity: originalQuantity, // 原始购买数量
                    price: price,
                    refunded_quantity: refundedQuantity,
                    refunded_amount: refundedAmount
                };

                showRefundModal(order, maxQuantity);
            };
        });
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
     * 绑定刷新订单按钮
     */
    function bindRefreshOrders() {
        var refreshBtn = document.getElementById('refreshOrdersBtn');
        if (!refreshBtn) return;

        refreshBtn.addEventListener('click', function () {
            refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 刷新中...';
            refreshBtn.disabled = true;

            fetch('api/level' + currentLevel + '/orders.php')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        renderOrders(data.data.orders);

                        // 更新元宝数量（第三关）
                        if (currentLevel === 3 && data.data.yuanbaoCount !== undefined) {
                            var displayYuanbao = document.getElementById('displayYuanbao');
                            if (displayYuanbao) displayYuanbao.textContent = data.data.yuanbaoCount;

                            // 检查通关条件
                            if (data.data.yuanbaoCount >= requiredYuanbao && data.data.passcode) {
                                var passcodeArea = document.getElementById('passcodeArea');
                                var displayPasscode = document.getElementById('displayPasscode');
                                if (passcodeArea) passcodeArea.style.display = 'flex';
                                if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                            }
                        }

                        // 第一关和第二关：检查是否有已支付订单，更新购买状态和通关密码
                        if (currentLevel !== 3) {
                            // 检查是否有已支付订单
                            var hasPaidOrder = false;
                            if (data.data.orders && data.data.orders.length > 0) {
                                data.data.orders.forEach(function (order) {
                                    if (order.status === 'paid') {
                                        hasPaidOrder = true;
                                    }
                                });
                            }

                            // 更新购买状态
                            updatePurchaseStatus(hasPaidOrder);

                            // 如果有通关密码，显示通关密码
                            if (hasPaidOrder && data.data.passcode) {
                                var passcodeArea = document.getElementById('passcodeArea');
                                var displayPasscode = document.getElementById('displayPasscode');
                                if (passcodeArea) passcodeArea.style.display = 'flex';
                                if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                            }
                        }
                    }
                })
                .catch(function (error) {
                    // 静默失败
                })
                .finally(function () {
                    refreshBtn.innerHTML = '<i class="fa fa-refresh"></i> 刷新';
                    refreshBtn.disabled = false;
                });
        });
    }

    /**
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            fetch('api/logout.php', {
                method: 'POST'
            })
                .then(function (res) { return res.json(); })
                .catch(function (error) { /* 静默处理 */ });

            // 重置UI
            var loginForm = document.getElementById('loginForm');
            var loginErrorArea = document.getElementById('loginErrorArea');
            var userInfoArea = document.getElementById('userInfoArea');
            var verifyResultArea = document.getElementById('verifyResultArea');
            var nextLevelBtn = document.getElementById('nextLevelBtn');
            var mainCardTitle = document.getElementById('mainCardTitle');
            var ordersList = document.getElementById('ordersList');
            var passcodeArea = document.getElementById('passcodeArea');
            var productArea = document.getElementById('productArea');

            if (loginForm) {
                loginForm.style.display = 'block';
                loginForm.reset();
                var submitBtn = loginForm.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.classList.remove('loading');
            }
            if (loginErrorArea) loginErrorArea.style.display = 'none';
            if (userInfoArea) userInfoArea.style.display = 'none';
            if (verifyResultArea) verifyResultArea.style.display = 'none';
            if (nextLevelBtn) nextLevelBtn.style.display = 'none';
            if (mainCardTitle) mainCardTitle.textContent = '天积商城 - 用户登录';
            if (ordersList) ordersList.innerHTML = '';
            if (passcodeArea) passcodeArea.style.display = 'none';
            if (productArea) productArea.innerHTML = '';

            var topLogoutBtn = document.getElementById('logoutBtn');
            if (topLogoutBtn) topLogoutBtn.style.display = 'none';
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
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/level' + currentLevel + '/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    passcode: passcode
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && (data.data ? data.data.passed : data.passed)) {
                        showVerifyResult(true, data.message);
                        if (currentLevel === 3) {
                            showCongratsModal();
                        } else {
                            var nextBtn = document.getElementById('nextLevelBtn');
                            if (nextBtn) nextBtn.style.display = 'inline-flex';
                        }
                    } else {
                        showVerifyResult(false, data.message || '通关密码错误');
                    }
                })
                .catch(function (error) {
                    showVerifyResult(false, '验证失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    // ==================== API调用 ====================

    /**
     * 获取订单列表
     */
    function fetchOrders() {
        fetch('api/level' + currentLevel + '/orders.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderOrders(data.data.orders);

                    // 更新元宝数量（第三关）
                    if (currentLevel === 3 && data.data.yuanbaoCount !== undefined) {
                        var displayYuanbao = document.getElementById('displayYuanbao');
                        if (displayYuanbao) displayYuanbao.textContent = data.data.yuanbaoCount;

                        // 检查通关条件
                        if (data.data.yuanbaoCount >= requiredYuanbao && data.data.passcode) {
                            var passcodeArea = document.getElementById('passcodeArea');
                            var displayPasscode = document.getElementById('displayPasscode');
                            if (passcodeArea) passcodeArea.style.display = 'flex';
                            if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                        }
                    }

                    // 第一关和第二关：检查是否有已支付订单，更新购买状态和通关密码
                    if (currentLevel !== 3) {
                        // 检查是否有已支付订单
                        var hasPaidOrder = false;
                        if (data.data.orders && data.data.orders.length > 0) {
                            data.data.orders.forEach(function (order) {
                                if (order.status === 'paid') {
                                    hasPaidOrder = true;
                                }
                            });
                        }

                        // 更新购买状态
                        updatePurchaseStatus(hasPaidOrder);

                        // 如果有通关密码，显示通关密码
                        if (hasPaidOrder && data.data.passcode) {
                            var passcodeArea = document.getElementById('passcodeArea');
                            var displayPasscode = document.getElementById('displayPasscode');
                            if (passcodeArea) passcodeArea.style.display = 'flex';
                            if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                        }
                    }
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 处理购买点击
     */
    function handleBuyClick() {
        var buyBtn = document.getElementById('buyBtn');
        var productId = currentLevel;
        var quantity = 1;

        var quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantity = parseInt(quantityInput.value) || 1;
        }

        buyBtn.classList.add('loading');

        fetch('api/level' + currentLevel + '/order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                productId: productId,
                quantity: quantity
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showMessageModal('订单创建成功！', 'success', function () {
                        var payUrl = payPage + '?order_no=' + encodeURIComponent(data.data.orderNo) +
                            '&amount=' + data.data.amount +
                            '&quantity=' + (data.data.quantity || 1);
                        var payWindow = window.open(payUrl, '_blank', 'width=500,height=700');

                        // 监听支付窗口关闭，自动刷新订单列表
                        if (payWindow) {
                            var checkClosed = setInterval(function () {
                                if (payWindow.closed) {
                                    clearInterval(checkClosed);
                                    // 延迟刷新，等待支付回调处理完成
                                    setTimeout(function () {
                                        fetchOrders();
                                    }, 500);
                                }
                            }, 500);
                        }

                        fetchOrders();
                    });
                } else {
                    showMessageModal(data.message || '订单创建失败', 'error');
                }
            })
            .catch(function (error) {
                showMessageModal('订单创建失败，请稍后重试', 'error');
            })
            .finally(function () {
                buyBtn.classList.remove('loading');
            });
    }

    /**
     * 处理退款请求
     * @param {number} orderId - 订单ID
     * @param {number} quantity - 退款数量
     * @param {HTMLElement} btn - 退款按钮
     */
    function handleRefund(orderId, quantity, btn) {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
        }

        fetch('api/level3/refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                orderId: orderId,
                quantity: quantity
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    // 关闭当前模态框
                    if (window.heasecModalManager) {
                        window.heasecModalManager.hideModal();
                    }
                    // 显示成功消息并刷新订单
                    showMessageModal('退款成功！退款金额：¥' + data.data.refundAmount.toFixed(2), 'success', function () {
                        fetchOrders();
                    });
                } else {
                    showMessageModal(data.message || '退款失败', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa fa-undo"></i> 申请退款';
                    }
                }
            })
            .catch(function (error) {
                showMessageModal('退款失败，请稍后重试', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-undo"></i> 申请退款';
                }
            });
    }

    // ==================== 模态框 ====================

    /**
     * 显示消息模态框
     * @param {string} message - 消息内容
     * @param {string} type - 类型：success/error/warning/info
     * @param {Function} onClose - 关闭回调
     */
    function showMessageModal(message, type, onClose) {
        if (window.heasecModalManager) {
            if (typeof window.heasecModalManager.destroyModal === 'function') {
                window.heasecModalManager.destroyModal('success_message');
            }
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
     * 显示退款模态框
     * @param {object} order - 订单信息
     * @param {number} maxQuantity - 最大可退数量
     */
    function showRefundModal(order, maxQuantity) {
        // 存储当前退款信息供确认时使用
        var currentRefundOrder = order;
        var currentMaxQuantity = maxQuantity;

        // 获取商品原价（退款按原价计算，这是漏洞点）
        var unitPrice = parseFloat(order.price) || 20;

        // 获取实际支付金额
        var paidAmount = parseFloat(order.paid_amount) || parseFloat(order.amount);

        // 已退款信息
        var refundedQuantity = order.refunded_quantity || 0;
        var refundedAmount = order.refunded_amount || 0;

        // 剩余可退金额（基于实际支付金额）
        var remainingRefundable = paidAmount - refundedAmount;

        // 构建已退款信息HTML
        var refundedInfoHtml = '';
        if (refundedQuantity > 0 || refundedAmount > 0) {
            refundedInfoHtml = '<div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px; background: #fff3cd; border-radius: 4px;">' +
                '<span style="color: #856404;"><i class="fa fa-info-circle"></i> 已退款：</span>' +
                '<span style="font-weight: 600; color: #856404;">' + refundedQuantity + ' 个，共 ¥' + refundedAmount.toFixed(2) + '</span>' +
                '</div>';
        }

        var content = '<div class="refund-modal-content" style="text-align: left;">' +
            '<div class="refund-order-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">订单号：</span>' +
            '<span style="font-weight: 600;">' + escapeHtml(order.order_no || ('#' + order.id)) + '</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">商品：</span>' +
            '<span style="font-weight: 600;">' + escapeHtml(order.product_name || '天积元宝') + '</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">商品单价：</span>' +
            '<span style="font-weight: 600;">¥' + unitPrice.toFixed(2) + '</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">订单金额：</span>' +
            '<span style="font-weight: 600;">¥' + parseFloat(order.amount).toFixed(2) + '</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">实际支付：</span>' +
            '<span style="font-weight: 600; color: #e74c3c;">¥' + paidAmount.toFixed(2) + '</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">可退数量：</span>' +
            '<span style="font-weight: 600;">' + maxQuantity + ' 个</span>' +
            '</div>' +
            '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">' +
            '<span style="color: #666;">剩余可退：</span>' +
            '<span style="font-weight: 600; color: #28a745;">¥' + remainingRefundable.toFixed(2) + '</span>' +
            '</div>' +
            refundedInfoHtml +
            '</div>' +
            '<div style="margin-bottom: 15px;">' +
            '<label style="display: block; margin-bottom: 10px; font-weight: 600; color: #333;">退款数量：</label>' +
            '<div style="display: flex; align-items: center; gap: 10px;">' +
            '<button type="button" id="refundQtyMinus" style="width: 36px; height: 36px; border: 1px solid #ddd; background: #fff; border-radius: 4px; cursor: pointer; font-size: 18px;">-</button>' +
            '<input type="text" id="refundQuantity" value="1" min="1" max="' + maxQuantity + '" style="width: 80px; height: 36px; text-align: center; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">' +
            '<button type="button" id="refundQtyPlus" style="width: 36px; height: 36px; border: 1px solid #ddd; background: #fff; border-radius: 4px; cursor: pointer; font-size: 18px;">+</button>' +
            '</div>' +
            '</div>' +
            '<div id="refundEstimate" style="padding: 12px; background: #e8f5e9; border-radius: 8px; text-align: center;">' +
            '<span style="color: #2e7d32;"><i class="fa fa-calculator"></i> 本次预计退款：</span>' +
            '<span id="refundEstimateAmount" style="font-weight: 700; font-size: 18px; color: #2e7d32;">¥' + Math.min(unitPrice, remainingRefundable).toFixed(2) + '</span>' +
            '</div>' +
            '</div>';

        if (window.heasecModalManager) {
            if (typeof window.heasecModalManager.destroyModal === 'function') {
                window.heasecModalManager.destroyModal('success_message');
            }
            // 使用 success_message 类型，但自定义内容
            window.heasecModalManager.showModal('success_message', {
                content: content,
                confirmText: '确认退款',
                confirmClass: 'tech-btn-warning',
                onConfirm: function () {
                    var qtyInput = document.getElementById('refundQuantity');
                    var refundQty = qtyInput ? parseInt(qtyInput.value) : 1;

                    if (isNaN(refundQty) || refundQty <= 0 || refundQty > currentMaxQuantity) {
                        showMessageModal('请输入有效的退款数量', 'warning');
                        return false; // 阻止关闭模态框
                    }

                    // 找到对应的退款按钮
                    var refundBtn = document.querySelector('.refund-btn[data-order-id="' + currentRefundOrder.id + '"]');
                    handleRefund(currentRefundOrder.id, refundQty, refundBtn);
                }
            });

            // 绑定数量按钮事件（使用 requestAnimationFrame 确保 DOM 已更新）
            function bindRefundQtyButtons() {
                var qtyMinus = document.getElementById('refundQtyMinus');
                var qtyPlus = document.getElementById('refundQtyPlus');
                var qtyInput = document.getElementById('refundQuantity');
                var estimateAmount = document.getElementById('refundEstimateAmount');

                if (!qtyMinus || !qtyPlus || !qtyInput) {
                    // DOM 尚未准备好，稍后重试
                    requestAnimationFrame(bindRefundQtyButtons);
                    return;
                }

                // 更新预计退款金额的函数（按原价计算，与后台逻辑一致）
                function updateEstimate() {
                    var qty = parseInt(qtyInput.value) || 1;
                    // 按原价计算退款金额（与后端一致）
                    var estimatedRefund = unitPrice * qty;
                    // 如果超过剩余可退金额，则显示剩余可退金额（与后端逻辑一致）
                    if (estimatedRefund > remainingRefundable) {
                        estimatedRefund = remainingRefundable;
                    }
                    if (estimateAmount) {
                        estimateAmount.textContent = '¥' + estimatedRefund.toFixed(2);
                    }
                }

                qtyMinus.onclick = function () {
                    var val = parseInt(qtyInput.value) || 1;
                    if (val > 1) {
                        qtyInput.value = val - 1;
                        updateEstimate();
                    }
                };
                qtyPlus.onclick = function () {
                    var val = parseInt(qtyInput.value) || 1;
                    if (val < currentMaxQuantity) {
                        qtyInput.value = val + 1;
                        updateEstimate();
                    }
                };
                // 监听输入框变化
                qtyInput.oninput = updateEstimate;
            }
            requestAnimationFrame(bindRefundQtyButtons);
        } else {
            // 降级为原生prompt
            var refundQty = prompt('请输入退款数量（最多' + maxQuantity + '个）：', '1');
            if (refundQty === null) return;

            refundQty = parseInt(refundQty);
            if (isNaN(refundQty) || refundQty <= 0 || refundQty > maxQuantity) {
                showMessageModal('请输入有效的退款数量', 'warning');
                return;
            }

            var refundBtn = document.querySelector('.refund-btn[data-order-id="' + order.id + '"]');
            handleRefund(order.id, refundQty, refundBtn);
        }
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了三方支付漏洞的利用方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: '3rdpay',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了三方支付漏洞的利用方式！');
        }
    }

    // ==================== 工具函数 ====================

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
     * 显示登录错误
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
     * HTML转义函数
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 格式化订单时间
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
})();
