<?php
/**
 * HeaSec天积安全团队 - 金额篡改靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-14
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成通关密码
 * @param int $level 关卡编号
 * @return string 20位随机字符串
 */
function generatePasscode($level) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    $_SESSION['amttamp_passcode_level' . $level] = $passcode;

    return $passcode;
}

/**
 * 获取通关密码
 * @param int $level 关卡编号
 * @return string|null 通关密码或null
 */
function getPasscode($level) {
    return isset($_SESSION['amttamp_passcode_level' . $level]) ? $_SESSION['amttamp_passcode_level' . $level] : null;
}

/**
 * 验证通关密码
 * @param string $passcode 待验证的密码
 * @param int $level 关卡编号
 * @return bool 是否验证通过
 */
function verifyPasscode($passcode, $level) {
    $storedPasscode = getPasscode($level);
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_amttamp_users WHERE level = ? AND username = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_amttamp_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户余额
 * @param int $userId 用户ID
 * @param float $amount 新余额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_amttamp_users SET balance = ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

/**
 * 获取关卡商品列表
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 商品列表
 */
function getProducts($level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, name, price FROM heasec_amttamp_products WHERE level = ? ORDER BY id");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_amttamp_products WHERE id = ? AND level = ?");
    $stmt->execute([$productId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取用户优惠券
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 优惠券信息或null
 */
function getUserCoupon($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_amttamp_coupons WHERE user_id = ? AND level = ? AND status = 0 LIMIT 1");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 使用优惠券
 * @param int $couponId 优惠券ID
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function useCoupon($couponId, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_amttamp_coupons SET status = 1 WHERE id = ?");
    return $stmt->execute([$couponId]);
}

/**
 * 创建订单
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param float $totalAmount 总金额
 * @param float $discountAmount 优惠金额
 * @param PDO $pdo 数据库连接
 * @return int 订单ID
 */
function createOrder($userId, $level, $totalAmount, $discountAmount, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_amttamp_orders (user_id, level, total_amount, discount_amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $level, $totalAmount, $discountAmount]);
    return $pdo->lastInsertId();
}

/**
 * 添加订单详情
 * @param int $orderId 订单ID
 * @param int $productId 商品ID
 * @param int $quantity 数量
 * @param float $price 单价
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function addOrderItem($orderId, $productId, $quantity, $price, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_amttamp_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$orderId, $productId, $quantity, $price]);
}

/**
 * 获取用户购买的天积元宝数量
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return int 购买数量
 */
function getPurchasedYuanbaoCount($userId, $level, $pdo) {
    $stmt = $pdo->prepare("
        SELECT SUM(oi.quantity) as total
        FROM heasec_amttamp_order_items oi
        JOIN heasec_amttamp_orders o ON oi.order_id = o.id
        JOIN heasec_amttamp_products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND o.level = ? AND p.name = '天积元宝'
    ");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['total']);
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Amount Tampering Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

/**
 * 获取用户订单列表
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 订单列表
 */
function getUserOrders($userId, $level, $pdo) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, o.discount_amount, o.status, o.passcode, o.created_at
        FROM heasec_amttamp_orders o
        WHERE o.user_id = ? AND o.level = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId, $level]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取每个订单的商品详情
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT oi.product_id, oi.quantity, oi.price, p.name as product_name
            FROM heasec_amttamp_order_items oi
            LEFT JOIN heasec_amttamp_products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $orders;
}

/**
 * 更新订单通关密码
 * @param int $orderId 订单ID
 * @param string $passcode 通关密码
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateOrderPasscode($orderId, $passcode, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_amttamp_orders SET passcode = ? WHERE id = ?");
    return $stmt->execute([$passcode, $orderId]);
}
