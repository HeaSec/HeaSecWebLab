<?php
/**
 * HeaSec天积安全团队 - JWT基础漏洞靶场 - 公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成随机密码
 * @param int $length
 * @return string
 */
function generateRandomPassword($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 生成通关密码
 * @return string
 */
function generatePasscode()
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $passcode;
}

/**
 * 检查并创建用户账号
 * @param int $level
 * @param PDO $pdo
 */
function ensureUsersExist($level, $pdo)
{
    // 检查test账号是否存在
    $stmt = $pdo->prepare("SELECT * FROM heasec_jwtvul_users WHERE level = ? AND username = 'test'");
    $stmt->execute([$level]);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        // 创建test账号
        $stmt = $pdo->prepare("INSERT INTO heasec_jwtvul_users (level, username, password, role) VALUES (?, 'test', '123456', 'user')");
        $stmt->execute([$level]);
    }

    // 检查admin账号是否存在
    $stmt = $pdo->prepare("SELECT * FROM heasec_jwtvul_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminUser) {
        // 创建admin账号
        $password = generateRandomPassword(10);
        $passcode = generatePasscode();
        $stmt = $pdo->prepare("INSERT INTO heasec_jwtvul_users (level, username, password, role, passcode) VALUES (?, 'admin', ?, 'admin', ?)");
        $stmt->execute([$level, $password, $passcode]);
    }
}
