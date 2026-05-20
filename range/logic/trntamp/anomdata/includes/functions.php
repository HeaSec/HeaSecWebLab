<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
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

    $_SESSION['anomdata_passcode_level' . $level] = $passcode;

    return $passcode;
}

/**
 * 获取通关密码
 * @param int $level 关卡编号
 * @return string|null 通关密码或null
 */
function getPasscode($level) {
    return isset($_SESSION['anomdata_passcode_level' . $level]) ? $_SESSION['anomdata_passcode_level' . $level] : null;
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_anomdata_users WHERE level = ? AND username = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_anomdata_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户余额（普通余额）
 * @param int $userId 用户ID
 * @param float $amount 新余额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_anomdata_users SET balance = ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

/**
 * 更新用户支付宝余额
 * @param int $userId 用户ID
 * @param float $amount 新余额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateAlipayBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_anomdata_users SET alipay_balance = ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

/**
 * 更新用户银行卡余额
 * @param int $userId 用户ID
 * @param float $amount 新余额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateBankBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_anomdata_users SET bank_balance = ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

/**
 * 添加交易记录
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param string $type 交易类型
 * @param float $amount 交易金额
 * @param string|null $targetAccount 目标账户
 * @param string|null $detail 交易详情
 * @param PDO $pdo 数据库连接
 * @return int 记录ID
 */
function addTransaction($userId, $level, $type, $amount, $targetAccount = null, $detail = null, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_anomdata_transactions (user_id, level, type, amount, target_account, detail) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $level, $type, $amount, $targetAccount, $detail]);
    return $pdo->lastInsertId();
}

/**
 * 获取用户交易记录
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 交易记录列表
 */
function getUserTransactions($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, type, amount, target_account, detail, created_at FROM heasec_anomdata_transactions WHERE user_id = ? AND level = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userId, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取关卡商品列表
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array 商品列表
 */
function getProducts($level, $pdo) {
    $stmt = $pdo->prepare("SELECT id, name, price FROM heasec_anomdata_products WHERE level = ? ORDER BY id");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_anomdata_products WHERE id = ? AND level = ?");
    $stmt->execute([$productId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 创建订单
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param int $productId 商品ID
 * @param int $quantity 购买数量
 * @param float $totalAmount 总金额
 * @param string|null $passcode 通关密码
 * @param PDO $pdo 数据库连接
 * @return int 订单ID
 */
function createOrder($userId, $level, $productId, $quantity, $totalAmount, $passcode, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_anomdata_orders (user_id, level, product_id, quantity, total_amount, passcode) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $level, $productId, $quantity, $totalAmount, $passcode]);
    return $pdo->lastInsertId();
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
        SELECT o.id, o.product_id, o.quantity, o.total_amount, o.passcode, o.status, o.created_at, p.name as product_name
        FROM heasec_anomdata_orders o
        LEFT JOIN heasec_anomdata_products p ON o.product_id = p.id
        WHERE o.user_id = ? AND o.level = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId, $level]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 检查用户是否购买过商品（第三关使用）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否购买过
 */
function hasPurchasedProduct($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM heasec_anomdata_orders WHERE user_id = ? AND level = ? AND status = 1");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['cnt']) > 0;
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Anomaly Data Range v1.0.0');

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
 * 模拟32位有符号整数溢出（第二关使用）
 * @param mixed $value 输入值（整数或浮点数）
 * @return int 模拟溢出后的32位有符号整数
 */
function int32_overflow($value) {
    // 确保输入转换为整数
    $value = (int)$value;

    // 使用模运算确保值在32位无符号范围内（0 到 2^32-1）
    $value = $value % 4294967296;

    // 如果结果为负（PHP的模运算可能返回负数），调整为正数
    if ($value < 0) {
        $value = $value + 4294967296;
    }

    // 若最高位为1（即大于0x7FFFFFFF），转换为负数（补码）
    if ($value > 0x7FFFFFFF) {
        $value = $value - 0x100000000;
    }

    return (int)$value;
}

/**
 * 获取用户已购买的元宝数量（第二关使用）
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return int 元宝数量
 */
function getPurchasedYuanbaoCount($userId, $level, $pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0) as total
        FROM heasec_anomdata_orders
        WHERE user_id = ? AND level = ? AND status = 1
    ");
    $stmt->execute([$userId, $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($result['total']);
}
