<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
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
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_3rdpay_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['passcode'])) {
        return $result['passcode'];
    }

    // 生成新的通关密码
    $passcode = generateRandomPasscode();
    $stmt = $pdo->prepare("UPDATE heasec_3rdpay_users SET passcode = ? WHERE id = ? AND level = ?");
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
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_3rdpay_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['passcode'] : null;
}

/**
 * 验证用户通关密码
 * @param int $userId 用户ID
 * @param string $passcode 待验证的密码
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否验证通过
 */
function verifyUserPasscode($userId, $passcode, $level, $pdo) {
    $storedPasscode = getUserPasscode($userId, $level, $pdo);
    return $storedPasscode && $storedPasscode === $passcode;
}

/**
 * 获取电商用户信息
 * @param int $level 关卡编号
 * @param string $username 用户名
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUser($level, $username, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取电商用户信息ByID
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUserById($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取关卡商品列表
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 商品列表
 */
function getProducts($level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, name, price FROM heasec_3rdpay_products WHERE level = ? ORDER BY id");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_products WHERE id = ? AND level = ?");
    $stmt->execute([$productId, $level]);
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
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param string $orderNo 订单号
 * @param string $productName 商品名称
 * @param int $quantity 购买数量
 * @param float $price 商品单价
 * @param float $amount 订单金额
 * @param float $discount 优惠金额
 * @param PDO $pdo 数据库连接
 * @return int 订单ID
 */
function createOrder($userId, $level, $orderNo, $productName, $quantity, $price, $amount, $discount, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_3rdpay_orders (user_id, level, order_no, product_name, quantity, price, amount, discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $level, $orderNo, $productName, $quantity, $price, $amount, $discount]);
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_orders WHERE order_no = ? AND level = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_orders WHERE id = ? AND level = ?");
    $stmt->execute([$orderId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新订单支付状态
 * @param string $orderNo 订单号
 * @param float $paidAmount 实际支付金额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateOrderPaid($orderNo, $paidAmount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_3rdpay_orders SET status = 'paid', paid_amount = ?, paid_at = NOW() WHERE order_no = ?");
    return $stmt->execute([$paidAmount, $orderNo]);
}

/**
 * 更新订单退款状态
 * @param int $orderId 订单ID
 * @param string $status 订单状态
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateOrderStatus($orderId, $status, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_3rdpay_orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $orderId]);
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
        SELECT id, order_no, product_name, quantity, price, amount, discount, paid_amount, status, created_at, paid_at
        FROM heasec_3rdpay_orders
        WHERE user_id = ? AND level = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取订单已退款信息
 * @param string $orderNo 订单号
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 包含已退款数量和金额的数组
 */
function getOrderRefundInfo($orderNo, $level, $pdo) {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(amount), 0) as refunded_amount,
            COUNT(*) as refunded_count
        FROM heasec_3rdpay_transactions
        WHERE order_no = ? AND level = ? AND type = 'refund'
    ");
    $stmt->execute([$orderNo, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'refunded_amount' => floatval($result['refunded_amount']),
        'refunded_count' => intval($result['refunded_count'])
    ];
}

/**
 * 获取用户拥有的天积元宝数量（第三关使用）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return int 元宝数量
 */
function getUserYuanbaoCount($userId, $level, $pdo) {
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as total
        FROM heasec_3rdpay_orders
        WHERE user_id = ? AND level = ? AND status IN ('paid', 'partial_refund')
    ");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['total']);
}

// ========== 天积宝相关函数 ==========

/**
 * 获取天积宝用户信息
 * @param int $level 关卡编号
 * @param string $username 用户名
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getPayUser($level, $username, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_pay_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取天积宝用户信息ByID
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getPayUserById($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_3rdpay_pay_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新天积宝用户余额
 * @param int $userId 用户ID
 * @param float $balance 新余额
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updatePayUserBalance($userId, $balance, $level, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_3rdpay_pay_users SET balance = ? WHERE id = ? AND level = ?");
    return $stmt->execute([$balance, $userId, $level]);
}

/**
 * 创建天积宝交易记录
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param string $orderNo 订单号
 * @param float $amount 交易金额
 * @param string $type 交易类型
 * @param PDO $pdo 数据库连接
 * @return int 记录ID
 */
function createTransaction($userId, $level, $orderNo, $amount, $type, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_3rdpay_transactions (user_id, level, order_no, amount, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $level, $orderNo, $amount, $type]);
    return $pdo->lastInsertId();
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec 3rdPay Range v1.0.0');

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
 * 生成签名密钥（用于第二关回调接口）
 * @return string 签名密钥
 */
function getSecretKey() {
    return 'HeaSec_Pay_Secret_2026';
}

/**
 * 生成回调签名（第一关使用MD5）
 * @param string $orderId 订单号
 * @param string $status 支付状态
 * @return string 签名
 */
function generateCallbackSign($orderId, $status) {
    return md5($orderId . $status . getSecretKey());
}

/**
 * 生成回调签名（第二、三关使用SHA256）
 * @param string $orderId 订单号
 * @param string $status 支付状态
 * @param string $amount 金额
 * @param string $timestamp 时间戳
 * @param string $discount 优惠金额（可选）
 * @return string 签名
 */
function generateCallbackSignV2($orderId, $status, $amount, $timestamp, $discount = '') {
    return hash('sha256', $orderId . $status . $amount . $timestamp . $discount . getSecretKey());
}

/**
 * 验证回调签名（第一关）
 * @param string $orderId 订单号
 * @param string $status 支付状态
 * @param string $sign 签名
 * @return bool 是否验证通过
 */
function verifyCallbackSign($orderId, $status, $sign) {
    $expectedSign = generateCallbackSign($orderId, $status);
    return $sign === $expectedSign;
}

/**
 * 验证回调签名（第二、三关）
 * @param string $orderId 订单号
 * @param string $status 支付状态
 * @param string $amount 金额
 * @param string $timestamp 时间戳
 * @param string $sign 签名
 * @param string $discount 优惠金额（可选）
 * @return bool 是否验证通过
 */
function verifyCallbackSignV2($orderId, $status, $amount, $timestamp, $sign, $discount = '') {
    $expectedSign = generateCallbackSignV2($orderId, $status, $amount, $timestamp, $discount);
    return $sign === $expectedSign;
}

/**
 * 发送HTTP请求（用于回调通知）
 * @param string $url 回调URL
 * @param array $data POST数据
 * @return bool 是否成功
 */
function sendCallbackRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}
