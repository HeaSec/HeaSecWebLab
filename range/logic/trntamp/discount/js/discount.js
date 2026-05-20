/**
 * HeaSec天积安全团队 - 优惠滥用靶场交互脚本
 * 版本: v1.0.1
 * 创建日期: 2026-03-21
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    // 关卡配置
    var levelConfigs = {
        1: {
            hasCoupons: true,
            hasPoints: false,
            hasFirstPurchase: false,
            taskHint: '购买1个天积元宝即可通关'
        },
        2: {
            hasCoupons: false,
            hasPoints: true,
            hasFirstPurchase: false,
            taskHint: '购买2个天积元宝和2个天积小元宝即可通关'
        },
        3: {
            hasCoupons: false,
            hasPoints: false,
            hasFirstPurchase: true,
            taskHint: '购买3个天积元宝即可通关'
        }
    };

    var levelConfig = levelConfigs[1];

    // 购物车数据
    var cart = [];

    // 商品数据
    var productsData = [];

    // 优惠券数据（第一关）
    var couponsData = [];
    var selectedCouponId = null;

    // 首购优惠状态（第三关）
    var firstPurchaseStatus = true;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initDiscount = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        levelConfig = levelConfigs[level] || levelConfigs[1];

        bindLoginForm();
        bindLogoutButton();
        bindVerifyForm();
        bindRefreshOrders();
        bindSubmitOrder();

        // 第二关：绑定支付方式切换及自定义下拉框
        if (currentLevel === 2) {
            bindPaymentMethod();
            initCustomSelect();
        }
    };

    /**
     * 从服务器数据初始化页面
     * 支持两种数据格式：
     * 1. 从PHP直接传递：{ username, balance, products, coupon, passcode }
     * 2. 从API返回：{ loggedIn, user: {username, balance}, products, coupons, orders, passcode }
     */
    window.displayUserInfoFromServer = function (userData) {
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

        // 兼容两种数据格式
        var username = userData.username || (userData.user && userData.user.username);
        var balance = userData.balance !== undefined ? userData.balance : (userData.user && userData.user.balance);
        var points = userData.points !== undefined ? userData.points : (userData.user && userData.user.points);
        var firstPurchase = userData.firstPurchase !== undefined ? userData.firstPurchase : (userData.user && userData.user.firstPurchase);

        // 显示用户名
        var displayUsername = document.getElementById('displayUsername');
        if (displayUsername && username) {
            displayUsername.textContent = username;
        }

        // 显示余额
        var displayBalance = document.getElementById('displayBalance');
        if (displayBalance && balance !== undefined) {
            displayBalance.textContent = parseFloat(balance).toFixed(2);
        }

        // 显示积分（第二关）
        if (currentLevel === 2 && points !== undefined) {
            var displayPoints = document.getElementById('displayPoints');
            if (displayPoints) displayPoints.textContent = points;
        }

        // 显示首购状态（第三关）
        if (currentLevel === 3 && firstPurchase !== undefined) {
            firstPurchaseStatus = firstPurchase;
        }

        // 显示已购买数量（第三关）
        if (currentLevel === 3 && userData.paidCount !== undefined) {
            var displayPaidCount = document.getElementById('displayPaidCount');
            if (displayPaidCount) displayPaidCount.textContent = userData.paidCount;
        }

        // 显示通关密码
        if (userData.passcode) {
            var passcodeArea = document.getElementById('passcodeArea');
            var displayPasscode = document.getElementById('displayPasscode');
            if (passcodeArea) passcodeArea.style.display = 'flex';
            if (displayPasscode) displayPasscode.textContent = userData.passcode;
        }

        // 渲染商品列表
        if (userData.products && userData.products.length > 0) {
            productsData = userData.products;
            renderProducts(userData.products);
        }

        // 清空购物车
        cart = [];
        renderCart();

        // 渲染优惠券列表（第一关）
        var coupons = userData.coupon || userData.coupons;
        if (levelConfig.hasCoupons && coupons && coupons.length > 0) {
            couponsData = coupons;
            selectedCouponId = null;
            renderCouponSection(coupons);
        } else {
            removeCouponSection();
        }

        // 加载订单列表
        fetchOrders();
    };

    /**
     * 显示消息模态框
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
     * 渲染商品列表
     * @param {Array} products 商品数据
     */
    function renderProducts(products) {
        var container = document.getElementById('productsList');
        if (!container || !products || products.length === 0) return;

        container.innerHTML = '';

        products.forEach(function (product) {
            var item = document.createElement('div');
            item.className = 'product-item';

            var pointsTag = product.allow_points ? '<small class="product-points">(支持积分)</small>' : '';

            item.innerHTML = '<div class="product-info">' +
                '<span class="product-name">' + escapeHtml(product.name) + '</span>' +
                '<span class="product-price">¥' + parseFloat(product.price).toFixed(2) + '</span>' +
                pointsTag +
                '</div>' +
                '<button type="button" class="tech-btn tech-btn-info" onclick="window.discountAddToCart(' + product.id + ', \'' + escapeHtml(product.name) + '\', ' + product.price + ', ' + (product.allow_points ? 1 : 0) + ')">' +
                '<i class="fa fa-plus"></i> 添加' +
                '</button>';

            container.appendChild(item);
        });
    }

    /**
     * 添加商品到购物车
     */
    window.discountAddToCart = function (productId, productName, price, allowPoints) {
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
                quantity: 1,
                allowPoints: allowPoints === 1
            });
        }

        renderCart();
    };

    /**
     * 修改购物车商品数量
     */
    window.discountUpdateQuantity = function (productId, change) {
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
    window.discountRemoveFromCart = function (productId) {
        cart = cart.filter(function (item) {
            return item.id !== productId;
        });
        renderCart();
    };

    /**
     * 选择优惠券
     */
    window.discountSelectCoupon = function (couponId) {
        selectedCouponId = couponId;
        renderCart();
    };

    /**
     * 渲染购物车
     */
    function renderCart() {
        var container = document.getElementById('cartItems');
        var cartSummary = document.getElementById('cartSummary');
        var cartEmpty = document.getElementById('cartEmpty');
        var cartTotal = document.getElementById('cartTotal');
        var totalBalanceEl = document.getElementById('totalBalance');
        var totalPointsEl = document.getElementById('totalPoints');

        if (!container) return;

        container.innerHTML = '';

        if (cart.length === 0) {
            if (cartEmpty) cartEmpty.style.display = 'block';
            if (cartSummary) cartSummary.style.display = 'none';
            return;
        }

        if (cartEmpty) cartEmpty.style.display = 'none';
        if (cartSummary) cartSummary.style.display = 'block';

        var total = 0;

        // 第三关：首购优惠价格显示
        if (currentLevel === 3) {
            var firstPurchasePrice = 10; // 首购优惠价10元/个
            var originalPrice = 50; // 原价50元/个
            var firstPurchaseDiscount = 0;

            // 计算总数量
            var totalQty = cart.reduce(function(sum, item) {
                return sum + item.quantity;
            }, 0);

            // 计算总价：首购优惠只对第一件商品有效
            if (firstPurchaseStatus === false && totalQty > 0) {
                // 第一件享受首购优惠价10元，其余按原价50元
                total = firstPurchasePrice + (totalQty - 1) * originalPrice;
                firstPurchaseDiscount = originalPrice - firstPurchasePrice; // 固定优惠40元
            } else {
                // 已使用首购优惠，全部按原价
                cart.forEach(function (item) {
                    total += item.price * item.quantity;
                });
            }

            cart.forEach(function (item, index) {
                var itemHtml;
                // 只有第一个商品显示首购优惠价（如果未使用首购优惠）
                if (firstPurchaseStatus === false && index === 0) {
                    // 第一个商品：显示首购优惠价
                    // 计算该商品的小计（第一个商品的首件优惠，其余原价）
                    var itemSubtotal;
                    if (item.quantity === 1) {
                        itemSubtotal = firstPurchasePrice;
                    } else {
                        itemSubtotal = firstPurchasePrice + (item.quantity - 1) * originalPrice;
                    }

                    var priceDisplay = '<span class="cart-item-price"><del style="color:#999;font-size:12px;">¥' + item.price.toFixed(2) + '</del> <span style="color:#ffd700;font-weight:600;">¥' + firstPurchasePrice.toFixed(2) + '</span> 起</span>';
                    var subtotalHtml = '<span class="cart-item-subtotal">¥' + itemSubtotal.toFixed(2) + '</span>';

                    itemHtml = '<div class="cart-item">' +
                        '<span class="cart-item-name">' + escapeHtml(item.name) + '</span>' +
                        priceDisplay +
                        '<div class="cart-item-quantity">' +
                        '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', -1)">-</button>' +
                        '<span>' + item.quantity + '</span>' +
                        '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', 1)">+</button>' +
                        '</div>' +
                        subtotalHtml +
                        '<button type="button" class="tech-btn tech-btn-secondary cart-remove-btn" onclick="window.discountRemoveFromCart(' + item.id + ')">' +
                        '<i class="fa fa-trash"></i>' +
                        '</button>' +
                        '</div>';
                } else {
                    // 其他商品或已使用首购优惠：显示原价
                    var subtotal = item.price * item.quantity;
                    var priceDisplay = '<span class="cart-item-price">¥' + parseFloat(item.price).toFixed(2) + '</span>';
                    var subtotalHtml = '<span class="cart-item-subtotal">¥' + subtotal.toFixed(2) + '</span>';

                    itemHtml = '<div class="cart-item">' +
                        '<span class="cart-item-name">' + escapeHtml(item.name) + '</span>' +
                        priceDisplay +
                        '<div class="cart-item-quantity">' +
                        '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', -1)">-</button>' +
                        '<span>' + item.quantity + '</span>' +
                        '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', 1)">+</button>' +
                        '</div>' +
                        subtotalHtml +
                        '<button type="button" class="tech-btn tech-btn-secondary cart-remove-btn" onclick="window.discountRemoveFromCart(' + item.id + ')">' +
                        '<i class="fa fa-trash"></i>' +
                        '</button>' +
                        '</div>';
                }

                container.insertAdjacentHTML('beforeend', itemHtml);
            });

            if (cartTotal) {
                if (firstPurchaseDiscount > 0) {
                    cartTotal.innerHTML = '¥' + total.toFixed(2) +
                        ' <small style="color: #ffd700; font-size: 12px;">(首购优惠 -¥' + firstPurchaseDiscount.toFixed(2) + ')</small>';
                } else {
                    cartTotal.textContent = '¥' + total.toFixed(2);
                }
            }
            return;
        }

        // 第一关和第二关：通用购物车渲染
        cart.forEach(function (item) {
            var subtotal = item.price * item.quantity;
            total += subtotal;

            var subtotalHtml = '<span class="cart-item-subtotal">¥' + subtotal.toFixed(2) + '</span>';

            var itemHtml = '<div class="cart-item">' +
                '<span class="cart-item-name">' + escapeHtml(item.name) + '</span>' +
                '<span class="cart-item-price">¥' + parseFloat(item.price).toFixed(2) + '</span>' +
                '<div class="cart-item-quantity">' +
                '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', -1)">-</button>' +
                '<span>' + item.quantity + '</span>' +
                '<button type="button" class="qty-btn" onclick="window.discountUpdateQuantity(' + item.id + ', 1)">+</button>' +
                '</div>' +
                subtotalHtml +
                '<button type="button" class="tech-btn tech-btn-secondary cart-remove-btn" onclick="window.discountRemoveFromCart(' + item.id + ')">' +
                '<i class="fa fa-trash"></i>' +
                '</button>' +
                '</div>';

            container.insertAdjacentHTML('beforeend', itemHtml);
        });

        // 计算优惠券折扣（第一关）
        var discount = 0;
        if (levelConfig.hasCoupons && selectedCouponId) {
            var selectedCoupon = couponsData.find(function (c) {
                return c.id == selectedCouponId;
            });
            if (selectedCoupon && total >= parseFloat(selectedCoupon.min_amount)) {
                discount = parseFloat(selectedCoupon.discount);
            }
        }

        var finalTotal = total - discount;

        // 第一关：显示总价（含优惠券优惠）
        if (currentLevel === 1 && cartTotal) {
            if (discount > 0) {
                cartTotal.innerHTML = '¥' + finalTotal.toFixed(2) +
                    ' <small style="color: #28a745; font-size: 12px;">(已优惠 ¥' + discount.toFixed(2) + ')</small>';
            } else {
                cartTotal.textContent = '¥' + finalTotal.toFixed(2);
            }
        }

        // 第二关：显示余额和积分，并根据商品类型限制支付方式
        if (currentLevel === 2) {
            var globalPaymentSelect = document.getElementById('globalPaymentType');
            var pointsOption = globalPaymentSelect ? globalPaymentSelect.querySelector('option[value="points"]') : null;
            var customPointsOption = document.getElementById('customOptionPoints');
            var customTriggerText = document.querySelector('#customPaymentTrigger .custom-select-text');
            var customOptionsDivs = document.querySelectorAll('.custom-option');

            // 检查购物车中是否有不支持积分支付的商品（allowPoints=false）
            var hasNonPointsItem = cart.some(function(item) {
                return !item.allowPoints;
            });

            // 根据购物车商品更新支付方式选项
            if (pointsOption) {
                if (hasNonPointsItem) {
                    // 购物车中有不支持积分的商品，隐藏积分支付选项
                    pointsOption.style.display = 'none';
                    pointsOption.disabled = true;
                    if (customPointsOption) {
                        customPointsOption.style.display = 'none';
                        customPointsOption.classList.add('disabled');
                    }
                    
                    // 如果当前选择的是积分支付，自动切换到余额支付
                    if (globalPaymentSelect.value === 'points') {
                        globalPaymentSelect.value = 'balance';
                        if (customTriggerText) customTriggerText.textContent = '余额支付';
                        if (customOptionsDivs) {
                            customOptionsDivs.forEach(function(opt) {
                                opt.classList.remove('selected');
                                if (opt.getAttribute('data-value') === 'balance') opt.classList.add('selected');
                            });
                        }
                    }
                } else {
                    // 所有商品都支持积分支付，显示积分支付选项
                    pointsOption.style.display = '';
                    pointsOption.disabled = false;
                    pointsOption.textContent = '积分支付';
                    if (customPointsOption) {
                        customPointsOption.style.display = '';
                        customPointsOption.classList.remove('disabled');
                    }
                }
            }

            var paymentType = globalPaymentSelect ? globalPaymentSelect.value : 'balance';

            if (paymentType === 'points') {
                if (totalBalanceEl) totalBalanceEl.textContent = '¥0.00';
                if (totalPointsEl) totalPointsEl.textContent = (total * 100) + '积分';
            } else {
                if (totalBalanceEl) totalBalanceEl.textContent = '¥' + total.toFixed(2);
                if (totalPointsEl) totalPointsEl.textContent = '0积分';
            }
        }
    }

    /**
     * 渲染优惠券区域
     */
    function renderCouponSection(coupons) {
        var cartSection = document.querySelector('.cart-section');
        if (!cartSection) return;

        // 移除已有的优惠券区域
        removeCouponSection();

        if (!coupons || coupons.length === 0) return;

        var couponItemsHtml = '';
        coupons.forEach(function (coupon) {
            var checked = selectedCouponId == coupon.id ? ' checked' : '';
            couponItemsHtml += '<div class="coupon-item">' +
                '<input type="radio" name="cart_coupon_id" id="cart_coupon_' + coupon.id + '" value="' + coupon.id + '"' + checked + ' onchange="window.discountSelectCoupon(' + coupon.id + ')">' +
                '<label for="cart_coupon_' + coupon.id + '">' +
                '<div class="coupon-info">' +
                '<span class="coupon-name">' + escapeHtml(coupon.name) + '</span>' +
                '<span class="coupon-condition">满' + parseFloat(coupon.min_amount).toFixed(0) + '元可用</span>' +
                '</div>' +
                '<div class="coupon-discount">-¥' + parseFloat(coupon.discount).toFixed(2) + '</div>' +
                '</label>' +
                '</div>';
        });

        var couponHtml = '<div id="couponSection" class="coupon-section">' +
            '<h4><i class="fa fa-tags"></i> 可用优惠券</h4>' +
            '<div class="coupon-list">' + couponItemsHtml + '</div>' +
            '<div class="coupon-hint"><i class="fa fa-info-circle"></i> 每单限用一张优惠券</div>' +
            '</div>';

        var cartItems = document.getElementById('cartItems');
        if (cartItems) {
            cartItems.insertAdjacentHTML('afterend', couponHtml);
        }
    }

    /**
     * 移除优惠券区域
     */
    function removeCouponSection() {
        var existingCoupon = document.getElementById('couponSection');
        if (existingCoupon) {
            existingCoupon.remove();
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

            var orderData;

            if (currentLevel === 1) {
                // 第一关：发送优惠券信息（漏洞点）
                var items = cart.map(function (item) {
                    return {
                        product_id: item.id,
                        quantity: item.quantity
                    };
                });
                orderData = {
                    items: items,
                    coupon_id: selectedCouponId
                };
            } else if (currentLevel === 2) {
                // 第二关：支持积分支付，提交后直接结算
                var items2 = cart.map(function (item) {
                    return {
                        product_id: item.id,
                        quantity: item.quantity
                    };
                });
                var globalPaymentSelect = document.getElementById('globalPaymentType');
                var paymentType = globalPaymentSelect ? globalPaymentSelect.value : 'balance';
                orderData = {
                    items: items2,
                    payment_type: paymentType
                };
            } else if (currentLevel === 3) {
                // 第三关：首购优惠
                var items3 = cart.map(function (item) {
                    return {
                        product_id: item.id,
                        quantity: item.quantity
                    };
                });
                orderData = { items: items3 };
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
                            if (displayBalance && data.data && data.data.balance !== undefined) {
                                displayBalance.textContent = parseFloat(data.data.balance).toFixed(2);
                            }

                            // 更新积分显示（第二关）
                            if (currentLevel === 2) {
                                var displayPoints = document.getElementById('displayPoints');
                                if (displayPoints && data.data && data.data.points !== undefined) {
                                    displayPoints.textContent = data.data.points;
                                }
                            }

                            // 显示通关密码
                            if (data.data && data.data.passcode) {
                                var passcodeArea = document.getElementById('passcodeArea');
                                var displayPasscode = document.getElementById('displayPasscode');
                                if (passcodeArea) passcodeArea.style.display = 'flex';
                                if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                            }

                            // 清空购物车
                            cart = [];
                            renderCart();

                            // 刷新数据
                            refreshData();
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
                        // 登录成功后刷新页面数据
                        refreshData();
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
     * 刷新页面数据
     */
    function refreshData() {
        fetch('api/level' + currentLevel + '/data.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    var userData = data.data;
                    if (userData.loggedIn) {
                        window.displayUserInfoFromServer(userData);
                    }
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 获取订单列表
     */
    function fetchOrders() {
        fetch('api/level' + currentLevel + '/orders.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    renderOrders(data.data.orders || []);

                    // 更新已购买数量（第三关）
                    if (currentLevel === 3 && data.data.paidCount !== undefined) {
                        var displayPaidCount = document.getElementById('displayPaidCount');
                        if (displayPaidCount) displayPaidCount.textContent = data.data.paidCount;
                    }

                    // 显示通关密码
                    if (data.data.passcode) {
                        var passcodeArea = document.getElementById('passcodeArea');
                        var displayPasscode = document.getElementById('displayPasscode');
                        if (passcodeArea) passcodeArea.style.display = 'flex';
                        if (displayPasscode) displayPasscode.textContent = data.data.passcode;
                    }
                }
            })
            .catch(function (error) {
                // 静默失败
            });
    }

    /**
     * 渲染订单列表
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

            var actionHtml = '';
            if (order.status === 'pending') {
                actionHtml = '<button type="button" class="tech-btn tech-btn-primary btn-sm pay-order-btn" data-order-id="' + order.id + '">' +
                    '<i class="fa fa-credit-card"></i> 去支付' +
                    '</button>';
            }

            var discountHtml = '';
            if (order.discount && parseFloat(order.discount) > 0) {
                discountHtml = '<div class="order-discount"><i class="fa fa-tag"></i> 优惠：-¥' + parseFloat(order.discount).toFixed(2) + '</div>';
            }

            var paymentHtml = '';
            var amountHtml = '';
            // 仅在第二关显示支付方式和对应的金额/积分
            if (currentLevel === 2 && order.payment_type) {
                if (order.payment_type === 'points') {
                    // 积分支付：显示消耗的积分数
                    var usedPoints = order.used_points || (parseFloat(order.total_amount || 0) * 100);
                    paymentHtml = '<div class="order-payment-info points"><i class="fa fa-star"></i> 积分支付</div>';
                    amountHtml = '<div class="order-amount points">' + parseInt(usedPoints) + '积分</div>';
                } else {
                    // 余额支付
                    paymentHtml = '<div class="order-payment-info balance"><i class="fa fa-money"></i> 余额支付</div>';
                    amountHtml = '<div class="order-amount">¥' + parseFloat(order.total_amount || order.amount || 0).toFixed(2) + '</div>';
                }
            } else {
                // 其他关卡：只显示金额
                amountHtml = '<div class="order-amount">¥' + parseFloat(order.total_amount || order.amount || 0).toFixed(2) + '</div>';
            }

            // 使用 order_no 或 order.id 作为订单号显示
            var orderIdDisplay = order.order_no ? ('订单 ' + escapeHtml(order.order_no)) : ('订单 #' + order.id);

            html += '<div class="order-item" data-order-id="' + order.id + '">' +
                '<div class="order-header">' +
                '<span class="order-id">' + orderIdDisplay + '</span>' +
                '<span class="order-status ' + statusInfo.className + '">' + statusInfo.text + '</span>' +
                '</div>' +
                '<div class="order-body">' +
                '<div class="order-product">' +
                '<span class="product-name">' + escapeHtml(order.product_name || '商品') + '</span>' +
                '<span class="product-qty">x' + (order.quantity || 1) + '</span>' +
                '</div>' +
                amountHtml +
                discountHtml +
                paymentHtml +
                '</div>' +
                '<div class="order-footer">' +
                '<span class="order-time">' + formatOrderTime(order.created_at) + '</span>' +
                actionHtml +
                '</div>' +
                '</div>';
        });

        container.innerHTML = html;

        // 绑定支付按钮事件
        container.querySelectorAll('.pay-order-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var orderId = this.getAttribute('data-order-id');
                payOrder(orderId);
            });
        });
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
            'refunded': { text: '已退款', className: 'status-refund' }
        };
        return statusMap[status] || { text: status, className: '' };
    }

    /**
     * 支付订单
     */
    function payOrder(orderId) {
        fetch('api/level' + currentLevel + '/pay.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showMessageModal('支付成功！', 'success', function () {
                        refreshData();
                    });
                } else {
                    showMessageModal(data.message || '支付失败', 'error');
                }
            })
            .catch(function (error) {
                showMessageModal('支付失败，请稍后重试', 'error');
            });
    }

    /**
     * 绑定刷新订单按钮
     */
    /**
     * 绑定支付方式切换（第二关）
     */
    function bindPaymentMethod() {
        var globalPaymentSelect = document.getElementById('globalPaymentType');
        if (globalPaymentSelect) {
            globalPaymentSelect.addEventListener('change', function () {
                renderCart();
            });
        }
    }

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

    /**
     * 绑定退出登录按钮
     */
    function bindLogoutButton() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (!logoutBtn) return;

        logoutBtn.addEventListener('click', function () {
            fetch('api/logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ level: currentLevel })
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

            var topLogoutBtn = document.getElementById('logoutBtn');
            if (topLogoutBtn) topLogoutBtn.style.display = 'none';

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
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了优惠系统漏洞的利用方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'discount',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了优惠系统漏洞的利用方式！');
        }
    }

    /**
     * HTML转义函数
     */
    function escapeHtml(text) {
        if (!text) return '';
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

    /**
     * 初始化自定义支付下拉框
     */
    function initCustomSelect() {
        var trigger = document.getElementById('customPaymentTrigger');
        var options = document.getElementById('customPaymentOptions');
        var customSelect = document.getElementById('customPaymentSelect');
        var selectElement = document.getElementById('globalPaymentType');
        
        if (!trigger || !options || !customSelect || !selectElement) return;

        // 切换下拉框显示隐藏
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            customSelect.classList.toggle('active');
        });

        // 点击选项
        var customOptions = options.querySelectorAll('.custom-option');
        customOptions.forEach(function(option) {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                if (this.classList.contains('disabled') || this.style.display === 'none') {
                    customSelect.classList.remove('active');
                    return;
                }

                var value = this.getAttribute('data-value');
                var text = this.textContent;

                // 更新UI显示
                trigger.querySelector('.custom-select-text').textContent = text;
                customOptions.forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');

                // 更新隐藏的原生select并触发change事件
                if (selectElement.value !== value) {
                    selectElement.value = value;
                    var event = new Event('change');
                    selectElement.dispatchEvent(event);
                }

                customSelect.classList.remove('active');
            });
        });

        // 点击外部关闭下拉框
        document.addEventListener('click', function(e) {
            if (customSelect && !customSelect.contains(e.target)) {
                customSelect.classList.remove('active');
            }
        });
    }
})();
