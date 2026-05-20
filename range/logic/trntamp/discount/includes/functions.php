<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-21
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成随机通关密码字符串
 * @return string 20位随机字符串
 */
function generateRandomPasscode() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $passcode;
}

/**
 * 初始化用户通关密码（如果不存在则生成）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return string 通关密码
 */
function initUserPasscode($userId, $level, $pdo) {
    // 先检查是否已有通关密码
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_discount_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['passcode'])) {
        return $result['passcode'];
    }

    // 生成新的通关密码
    $passcode = generateRandomPasscode();
    $stmt = $pdo->prepare("UPDATE heasec_discount_users SET passcode = ? WHERE id = ? AND level = ?");
    $stmt->execute([$passcode, $userId, $level]);

    return $passcode;
}

/**
 * 获取用户通关密码
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return string|null 通关密码或null
 */
function getUserPasscode($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_discount_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['passcode'] : null;
}

/**
 * 验证通关密码
 * @param string $passcode 待验证的密码
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否验证通过
 */
function verifyPasscode($passcode, $level, $pdo) {
    $userId = isset($_SESSION['discount_user_id_level' . $level]) ? $_SESSION['discount_user_id_level' . $level] : null;
    if (!$userId) {
        return false;
    }

    // 从用户表验证通关密码
    $storedPasscode = getUserPasscode($userId, $level, $pdo);
    return $storedPasscode && $storedPasscode === $passcode;
}

/**
 * 获取用户信息
 * @param int $level 关卡编号
 * @param string $username 用户名
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUser($level, $username, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取用户信息ByID
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUserById($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户余额
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param float $balance 新余额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateUserBalance($userId, $level, $balance, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_discount_users SET balance = ? WHERE id = ? AND level = ?");
    return $stmt->execute([$balance, $userId, $level]);
}

/**
 * 更新用户积分
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param int $points 新积分
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateUserPoints($userId, $level, $points, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_discount_users SET points = ? WHERE id = ? AND level = ?");
    return $stmt->execute([$points, $userId, $level]);
}

/**
 * 更新用户首购状态
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param int $firstPurchase 首购状态（0或1）
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateFirstPurchase($userId, $level, $firstPurchase, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_discount_users SET first_purchase = ? WHERE id = ? AND level = ?");
    return $stmt->execute([$firstPurchase, $userId, $level]);
}

/**
 * 获取关卡商品列表
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 商品列表
 */
function getProducts($level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, name, price, allow_points FROM heasec_discount_products WHERE level = ? ORDER BY id");
    $stmt->execute([$level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取商品信息
 * @param int $productId 商品ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 商品信息或null
 */
function getProductById($productId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_products WHERE id = ? AND level = ?");
    $stmt->execute([$productId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取优惠券列表（第一关使用）
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 优惠券列表
 */
function getCoupons($level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, name, min_amount, discount FROM heasec_discount_coupons WHERE level = ? ORDER BY id");
    $stmt->execute([$level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取优惠券信息
 * @param int $couponId 优惠券ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 优惠券信息或null
 */
function getCouponById($couponId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_coupons WHERE id = ? AND level = ?");
    $stmt->execute([$couponId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 生成订单号
 * @return string 订单号
 */
function generateOrderNo() {
    return 'ORD' . date('YmdHis') . str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * 创建订单
 * @param array $orderData 订单数据
 * @param PDO $pdo 数据库连接
 * @return int 订单ID
 */
function createOrder($orderData, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_discount_orders
        (user_id, level, order_no, product_id, product_name, quantity, price,
         coupon_ids, discount, payment_type, total_amount, paid_amount, used_points, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->execute([
        $orderData['user_id'],
        $orderData['level'],
        $orderData['order_no'],
        $orderData['product_id'],
        $orderData['product_name'],
        $orderData['quantity'],
        $orderData['price'],
        isset($orderData['coupon_ids']) ? $orderData['coupon_ids'] : null,
        isset($orderData['discount']) ? $orderData['discount'] : 0,
        isset($orderData['payment_type']) ? $orderData['payment_type'] : 'balance',
        $orderData['total_amount'],
        isset($orderData['paid_amount']) ? $orderData['paid_amount'] : 0,
        isset($orderData['used_points']) ? $orderData['used_points'] : 0
    ]);

    return $pdo->lastInsertId();
}

/**
 * 获取订单信息
 * @param string $orderNo 订单号
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 订单信息或null
 */
function getOrderByNo($orderNo, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_orders WHERE order_no = ? AND level = ?");
    $stmt->execute([$orderNo, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取订单信息ByID
 * @param int $orderId 订单ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 订单信息或null
 */
function getOrderById($orderId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_orders WHERE id = ? AND level = ?");
    $stmt->execute([$orderId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取用户待支付订单列表
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 订单列表
 */
function getPendingOrders($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'pending'
                           ORDER BY created_at DESC");
    $stmt->execute([$userId, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取用户所有订单列表
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 订单列表
 */
function getUserOrders($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ?
                           ORDER BY created_at DESC");
    $stmt->execute([$userId, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 更新订单支付状态
 * @param int $orderId 订单ID
 * @param float $paidAmount 实际支付金额
 * @param int $usedPoints 使用的积分
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateOrderPaid($orderId, $paidAmount, $usedPoints, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_discount_orders
                           SET status = 'paid', paid_amount = ?, used_points = ?, paid_at = NOW()
                           WHERE id = ?");
    return $stmt->execute([$paidAmount, $usedPoints, $orderId]);
}

/**
 * 检查第一关通关条件（成功购买1个天积元宝）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否满足通关条件
 */
function checkLevel1PassCondition($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'paid'
                           AND product_name = '天积元宝'");
    $stmt->execute([$userId, $level]);
    $count = $stmt->fetchColumn();
    return $count >= 1;
}

/**
 * 检查第二关通关条件（成功购买2个天积元宝和2个天积小元宝）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否满足通关条件
 */
function checkLevel2PassCondition($userId, $level, $pdo) {
    // 检查天积元宝数量
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'paid'
                           AND product_name = '天积元宝'");
    $stmt->execute([$userId, $level]);
    $yuanbaoCount = (int)$stmt->fetchColumn();

    // 检查天积小元宝数量
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'paid'
                           AND product_name = '天积小元宝'");
    $stmt->execute([$userId, $level]);
    $smallYuanbaoCount = (int)$stmt->fetchColumn();

    return $yuanbaoCount >= 2 && $smallYuanbaoCount >= 2;
}

/**
 * 检查第三关通关条件（成功购买3个天积元宝）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否满足通关条件
 */
function checkLevel3PassCondition($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'paid'
                           AND product_name = '天积元宝'");
    $stmt->execute([$userId, $level]);
    $count = (int)$stmt->fetchColumn();
    return $count >= 3;
}

/**
 * 获取第三关已购买天积元宝数量
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return int 已购买数量
 */
function getPaidYuanbaoCount($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM heasec_discount_orders
                           WHERE user_id = ? AND level = ? AND status = 'paid'
                           AND product_name = '天积元宝'");
    $stmt->execute([$userId, $level]);
    return (int)$stmt->fetchColumn();
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Discount Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取请求输入数据（支持JSON和表单）
 * @return array 请求数据
 */
function getRequestData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        $data = $_POST;
    }
    return $data ?: [];
}
