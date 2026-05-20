/**
 * HeaSec天积安全团队 - 金额篡改靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-14
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var requiredYuanbao = 5;
    var hasCoupon = false;

    // 购物车数据
    var cart = [];
    // 优惠券数据
    var couponData = null;
    var useCouponSelected = false;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     * @param {number} yuanbao - 需要购买的元宝数量
     * @param {boolean} coupon - 是否有优惠券功能
     */
    window.initAmttamp = function (level, basePath, yuanbao, coupon) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        requiredYuanbao = yuanbao || 5;
        hasCoupon = coupon || false;

        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();
        bindCustomReset();
        bindSubmitOrder();
        bindRefreshOrders();
    };

    /**
     * 显示消息模态框（替代原生alert）
     * @param {string} message - 消息内容
     * @param {string} type - 消息类型：'success', 'error', 'warning', 'info'
     * @param {Function} onClose - 关闭回调（可选）
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

        // 显示退出登录按钮
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        // 更新用户信息显示
        var displayUsername = document.getElementById('displayUsername');
        var displayBalance = document.getElementById('displayBalance');
        if (displayUsername) displayUsername.textContent = userData.username;
        if (displayBalance) displayBalance.textContent = parseFloat(userData.balance).toFixed(2);

        // 显示商品列表
        renderProducts(userData.products);

        // 处理优惠券
        couponData = userData.coupon;
        useCouponSelected = false;
        renderCouponSection();

        // 清空购物车
        cart = [];
        renderCart();

        // 加载订单列表
        fetchOrders();
    }

    /**
     * 从服务器数据初始化用户信息显示（页面刷新后使用）
     * @param {Object} userData
     */
    window.displayUserInfoFromServer = function (userData) {
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

        // 显示退出登录按钮
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.style.display = 'inline-flex';

        // 更新用户信息显示
        var displayUsername = document.getElementById('displayUsername');
        var displayBalance = document.getElementById('displayBalance');
        if (displayUsername) displayUsername.textContent = userData.username;
        if (displayBalance) displayBalance.textContent = parseFloat(userData.balance).toFixed(2);

        // 显示商品列表
        renderProducts(userData.products);

        // 处理优惠券
        couponData = userData.coupon;
        useCouponSelected = false;
        renderCouponSection();

        // 清空购物车
        cart = [];
        renderCart();

        // 加载订单列表
        fetchOrders();
    }

    /**
     * 渲染商品列表
     * @param {Array} products
     */
    function renderProducts(products) {
        var container = document.getElementById('productsList');
        if (!container) return;

        container.innerHTML = '';

        products.forEach(function (product) {
            var item = document.createElement('div');
            item.className = 'product-item';
            item.innerHTML = '<div class="product-info">' +
                '<span class="product-name">' + escapeHtml(product.name) + '</span>' +
                '<span class="product-price">¥' + parseFloat(product.price).toFixed(2) + '</span>' +
                '</div>' +
                '<button type="button" class="tech-btn tech-btn-info" onclick="window.amttampAddToCart(' + product.id + ', \'' + escapeHtml(product.name) + '\', ' + product.price + ')">' +
                '<i class="fa fa-plus"></i> 添加' +
                '</button>';
            container.appendChild(item);
        });
    }

    /**
     * 渲染优惠券区域
     */
    function renderCouponSection() {
        var cartSection = document.querySelector('.cart-section');
        if (!cartSection) return;

        // 移除已有的优惠券区域
        var existingCoupon = document.getElementById('couponSection');
        if (existingCoupon) {
            existingCoupon.remove();
        }

        if (!hasCoupon || !couponData) return;

        var couponHtml = '<div id="couponSection" class="coupon-section">' +
            '<h4><i class="fa fa-ticket"></i> 优惠券</h4>' +
            '<div class="coupon-item">' +
            '<input type="checkbox" id="useCoupon" onchange="window.amttampToggleCoupon(this.checked)">' +
            '<label for="useCoupon">' + escapeHtml(couponData.code) + ' 满100减' + parseFloat(couponData.original_value).toFixed(2) + '元</label>' +
            '</div>' +
            '</div>';

        var cartItems = document.getElementById('cartItems');
        if (cartItems) {
            cartItems.insertAdjacentHTML('afterend', couponHtml);
        }
    }

    /**
     * 添加商品到购物车
     */
    window.amttampAddToCart = function (productId, productName, price) {
        var existingItem = cart.find(function (item) {
            return item.id === productId;
        });

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: price,
                quantity: 1
            });
        }

        renderCart();
    };

    /**
     * 修改购物车商品数量
     */
    window.amttampUpdateQuantity = function (productId, change) {
        var item = cart.find(function (i) {
            return i.id === productId;
        });

        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                cart = cart.filter(function (i) {
                    return i.id !== productId;
                });
            }
            renderCart();
        }
    };

    /**
     * 从购物车移除商品
     */
    window.amttampRemoveFromCart = function (productId) {
        cart = cart.filter(function (item) {
            return item.id !== productId;
        });
        renderCart();
    };

    /**
     * 切换优惠券使用状态
     */
    window.amttampToggleCoupon = function (checked) {
        useCouponSelected = checked;
        renderCart();
    };

    /**
     * 渲染购物车
     */
    function renderCart() {
        var container = document.getElementById('cartItems');
        var totalElement = document.getElementById('cartTotal');
        if (!container) return;

        container.innerHTML = '';

        var total = 0;

        cart.forEach(function (item) {
            var subtotal = item.price * item.quantity;
            total += subtotal;

            // 第三关不显示商品小计
            var subtotalHtml = currentLevel === 3 ? '' : '<span class="cart-item-subtotal">¥' + subtotal.toFixed(2) + '</span>';

            var itemHtml = '<div class="cart-item">' +
                '<span class="cart-item-name">' + escapeHtml(item.name) + '</span>' +
                '<span class="cart-item-price">¥' + parseFloat(item.price).toFixed(2) + '</span>' +
                '<div class="cart-item-quantity">' +
                '<button type="button" class="qty-btn" onclick="window.amttampUpdateQuantity(' + item.id + ', -1)">-</button>' +
                '<span>' + item.quantity + '</span>' +
                '<button type="button" class="qty-btn" onclick="window.amttampUpdateQuantity(' + item.id + ', 1)">+</button>' +
                '</div>' +
                subtotalHtml +
                '<button type="button" class="tech-btn tech-btn-secondary cart-remove-btn" onclick="window.amttampRemoveFromCart(' + item.id + ')">' +
                '<i class="fa fa-trash"></i>' +
                '</button>' +
                '</div>';

            container.insertAdjacentHTML('beforeend', itemHtml);
        });

        // 计算优惠券折扣
        var discount = 0;
        if (hasCoupon && useCouponSelected && couponData && total >= 100) {
            discount = parseFloat(couponData.original_value);
        }

        var finalTotal = total - discount;

        if (totalElement) {
            if (discount > 0) {
                totalElement.innerHTML = '¥' + finalTotal.toFixed(2) +
                    ' <small style="color: #28a745; font-size: 12px;">(已优惠 ¥' + discount.toFixed(2) + ')</small>';
            } else {
                totalElement.textContent = '¥' + finalTotal.toFixed(2);
            }
        }
    }

    /**
     * 绑定提交订单按钮
     */
    function bindSubmitOrder() {
        var btn = document.getElementById('submitOrderBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (cart.length === 0) {
                showMessageModal('购物车为空', 'warning');
                return;
            }

            btn.classList.add('loading');

            var orderData = {
                items: cart.map(function (item) {
                    // 第一关：发送price字段（漏洞点）
                    if (currentLevel === 1) {
                        return {
                            id: item.id,
                            price: item.price,
                            quantity: item.quantity
                        };
                    }
                    // 第二关和第三关：不发送price字段
                    return {
                        id: item.id,
                        quantity: item.quantity
                    };
                })
            };

            // 第三关：添加优惠券信息（漏洞点）
            if (currentLevel === 3 && useCouponSelected && couponData) {
                orderData.couponId = couponData.id;
                orderData.discount = parseFloat(couponData.original_value);
            }

            fetch('api/level' + currentLevel + '/order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showMessageModal('购买成功！', 'success', function () {
                            // 更新余额显示
                            var displayBalance = document.getElementById('displayBalance');
                            if (displayBalance && data.data) {
                                displayBalance.textContent = parseFloat(data.data.balance).toFixed(2);
                            }

                            // 清空购物车
                            cart = [];
                            renderCart();

                            // 刷新订单列表
                            fetchOrders();
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
     * 更新通关密码显示
     * @param {Object} data
     */
    function updatePasscodeDisplay(data) {
        var passcodeArea = document.getElementById('passcodeArea');
        var passcodeHint = document.getElementById('passcodeHint');
        var displayPasscode = document.getElementById('displayPasscode');

        if (data && data.passcode) {
            if (passcodeArea) passcodeArea.style.display = 'flex';
            if (passcodeHint) passcodeHint.style.display = 'none';
            if (displayPasscode) displayPasscode.textContent = data.passcode;
        } else {
            if (passcodeArea) passcodeArea.style.display = 'none';
            if (passcodeHint) passcodeHint.style.display = 'flex';
        }
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
            // 调用退出登录API清除服务器端会话
            fetch('api/logout.php', {
                method: 'POST'
            })
                .then(function (res) { return res.json(); })
                .catch(function (error) { /* 静默处理错误 */ });

            // 重置UI
            var loginForm = document.getElementById('loginForm');
            var loginErrorArea = document.getElementById('loginErrorArea');
            var userInfoArea = document.getElementById('userInfoArea');
            var verifyResultArea = document.getElementById('verifyResultArea');
            var nextLevelBtn = document.getElementById('nextLevelBtn');
            var mainCardTitle = document.getElementById('mainCardTitle');
            var ordersList = document.getElementById('ordersList');

            if (loginForm) {
                loginForm.style.display = 'block';
                loginForm.reset();
                // 移除登录按钮的loading状态
                var submitBtn = loginForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                }
            }
            if (loginErrorArea) loginErrorArea.style.display = 'none';
            if (userInfoArea) userInfoArea.style.display = 'none';
            if (verifyResultArea) verifyResultArea.style.display = 'none';
            if (nextLevelBtn) nextLevelBtn.style.display = 'none';
            if (mainCardTitle) mainCardTitle.textContent = '用户登录';
            if (ordersList) ordersList.innerHTML = '';

            // 隐藏退出登录按钮
            var logoutBtnHeader = document.getElementById('logoutBtn');
            if (logoutBtnHeader) logoutBtnHeader.style.display = 'none';

            // 清空购物车
            cart = [];
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
                body: JSON.stringify({
                    passcode: passcode
                })
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
     * 绑定自定义重置功能
     * 使用公共模态框管理器替代原生confirm/alert
     */
    function bindCustomReset() {
        // 不再需要自定义重置逻辑，由heasec_range_common.js中的HeaSecRangeInitializer统一处理
        // 该函数保留为空函数以保持向后兼容
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了金额篡改攻击的实现方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'amttamp',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了金额篡改攻击的实现方式！');
        }
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

    /**
     * 获取订单列表
     */
    function fetchOrders() {
        fetch('api/level' + currentLevel + '/orders.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderOrders(data.data.orders);
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 渲染订单列表
     * @param {Array} orders 订单列表
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
            var orderItemsHtml = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(function (item) {
                    orderItemsHtml += '<div class="order-item-row">' +
                        '<span class="order-item-name">' + escapeHtml(item.product_name || '未知商品') + '</span>' +
                        '<span class="order-item-qty">x' + item.quantity + '</span>' +
                        '<span class="order-item-price">¥' + parseFloat(item.price).toFixed(2) + '</span>' +
                        '</div>';
                });
            }

            var passcodeHtml = '';
            if (order.passcode) {
                passcodeHtml = '<div class="order-passcode">' +
                    '<i class="fa fa-trophy"></i>' +
                    '<span class="order-passcode-label">通关密码：</span>' +
                    '<span class="order-passcode-value">' + escapeHtml(order.passcode) + '</span>' +
                    '</div>';
            }

            html += '<div class="order-item">' +
                '<div class="order-header">' +
                '<span class="order-id">订单 #' + order.id + '</span>' +
                '<span class="order-time">' + formatOrderTime(order.created_at) + '</span>' +
                '</div>' +
                '<div class="order-items">' + orderItemsHtml + '</div>' +
                '<div class="order-footer">' +
                '<span class="order-total">合计：<span class="order-total-amount">¥' + parseFloat(order.total_amount).toFixed(2) + '</span></span>' +
                '</div>' +
                passcodeHtml +
                '</div>';
        });

        container.innerHTML = html;
    }

    /**
     * 格式化订单时间
     * @param {string} datetime 时间字符串
     * @returns {string} 格式化后的时间
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
        var refreshBtn = document.getElementById('refreshOrdersBtn');
        if (!refreshBtn) return;

        refreshBtn.addEventListener('click', function () {
            refreshBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 刷新中...';
            fetchOrders();
            setTimeout(function () {
                refreshBtn.innerHTML = '<i class="fa fa-refresh"></i> 刷新';
            }, 500);
        });
    }
})();
