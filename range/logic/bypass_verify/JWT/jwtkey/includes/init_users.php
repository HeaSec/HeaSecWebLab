<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - 用户初始化逻辑
 * JWT Key Injection Range - User Initialization
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS')) {
    exit('Access denied');
}

/**
 * 初始化用户账号
 * 创建test测试账号和admin目标账号
 *
 * @return array 初始化结果 ['success' => bool, 'admin_password' => string]
 */
function initializeUsers()
{
    try {
        $db = heasec_db('heasec_logic');

        // 检查用户表是否存在
        $stmt = $db->query("SHOW TABLES LIKE 'heasec_jwtkey_users'");
        if ($stmt->fetch() === false) {
            return ['success' => false, 'message' => '用户表不存在'];
        }

        // 检查test账号是否存在
        $stmt = $db->prepare("SELECT id FROM heasec_jwtkey_users WHERE username = 'test'");
        $stmt->execute();
        if ($stmt->fetch() === false) {
            // 创建test账号
            $stmt = $db->prepare("INSERT INTO heasec_jwtkey_users (username, password, role) VALUES ('test', '123456', 'user')");
            $stmt->execute();
        }

        // 检查admin账号是否存在
        $stmt = $db->prepare("SELECT password FROM heasec_jwtkey_users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin === false) {
            // 创建admin账号，密码为随机生成
            $adminPassword = generateRandomPassword(10);
            $stmt = $db->prepare("INSERT INTO heasec_jwtkey_users (username, password, role) VALUES ('admin', ?, 'admin')");
            $stmt->execute([$adminPassword]);
            return ['success' => true, 'admin_password' => $adminPassword];
        }

        return ['success' => true, 'admin_password' => $admin['password']];

    } catch (Exception $e) {
        error_log('[HeaSec] initializeUsers error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 获取用户信息
 *
 * @param string $username 用户名
 * @return array|null 用户信息或null
 */
function getUserByUsername($username)
{
    try {
        $db = heasec_db('heasec_logic');
        $stmt = $db->prepare("SELECT * FROM heasec_jwtkey_users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('[HeaSec] getUserByUsername error: ' . $e->getMessage());
        return null;
    }
}
?>
