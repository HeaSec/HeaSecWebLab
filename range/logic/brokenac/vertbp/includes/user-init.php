<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 用户数据初始化
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成随机字符串
 * @param int $length 字符串长度
 * @return string 随机字符串
 */
function generateVertbpRandomString($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 初始化关卡用户数据
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 */
function initVertbpLevelUsers($level, $pdo) {
    // 检查是否已初始化
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_vertbp_users WHERE level = ?");
    $stmt->execute([$level]);
    if ($stmt->fetchColumn() > 0) {
        return; // 已初始化，跳过
    }

    // 生成admin的通关密码（20位随机字符串）
    $adminPasscode = generateVertbpRandomString(20);

    // 生成admin的密码（10位随机字符串）
    $adminPassword = generateVertbpRandomString(10);

    // 插入测试用户（test）
    $stmt = $pdo->prepare("INSERT INTO heasec_vertbp_users
        (level, account, password, role, passcode)
        VALUES (?, 'test', '123456', 'user', NULL)");
    $stmt->execute([$level]);

    // 插入管理员用户（admin）
    $stmt = $pdo->prepare("INSERT INTO heasec_vertbp_users
        (level, account, password, role, passcode)
        VALUES (?, 'admin', ?, 'admin', ?)");
    $stmt->execute([$level, $adminPassword, $adminPasscode]);
}
