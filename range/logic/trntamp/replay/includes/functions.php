<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
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
    
    $_SESSION['replay_passcode_level' . $level] = $passcode;
    
    return $passcode;
}

/**
 * 获取通关密码
 * @param int $level 关卡编号
 * @return string|null 通关密码或null
 */
function getPasscode($level) {
    return isset($_SESSION['replay_passcode_level' . $level]) ? $_SESSION['replay_passcode_level' . $level] : null;
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_replay_users WHERE level = ? AND username = ?");
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
    $stmt = $pdo->prepare("SELECT * FROM heasec_replay_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户余额
 * @param int $userId 用户ID
 * @param float $amount 增加的金额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function updateBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE heasec_replay_users SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

/**
 * 记录签到
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param string $signinDate 签到日期
 * @param float $amount 获得金额
 * @param PDO $pdo 数据库连接
 * @return bool 是否成功
 */
function recordSignin($userId, $level, $signinDate, $amount, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_replay_signins (user_id, level, signin_date, amount) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $level, $signinDate, $amount]);
}

/**
 * 检查用户在指定日期是否已签到
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param string $signinDate 签到日期
 * @param PDO $pdo 数据库连接
 * @return bool 是否已签到
 */
function hasSignedIn($userId, $level, $signinDate, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_replay_signins WHERE user_id = ? AND level = ? AND signin_date = ?");
    $stmt->execute([$userId, $level, $signinDate]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Replay Attack Range v1.0.0');
    
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
