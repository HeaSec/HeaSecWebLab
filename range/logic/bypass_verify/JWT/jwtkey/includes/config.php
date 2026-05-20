<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场配置文件
 * JWT Key Injection Range Configuration
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义常量
define('JWTKEY_TOKEN_EXPIRE', 86400);  // Token有效期（24小时）

/**
 * 获取攻击类型名称
 *
 * @param string $type 攻击类型
 * @return string 攻击方式名称
 */
function getAttackTypeName($type) {
    $names = [
        'kid_injection' => 'kid注入RSA公钥',
        'jku_injection' => 'jku注入',
        'kid_traversal' => 'kid路径遍历'
    ];
    return isset($names[$type]) ? $names[$type] : $type;
}

/**
 * 生成随机密码
 * 生成10位包含大小写字母、数字和特殊字符的随机字符串
 *
 * @param int $length 密码长度，默认10位
 * @return string 随机密码
 */
function generateRandomPassword($length = 10) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    $allChars = $lowercase . $uppercase . $numbers . $special;
    $password = '';

    // 确保包含各类字符
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];

    // 填充剩余字符
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }

    // 打乱顺序
    return str_shuffle($password);
}

/**
 * 检查成就是否已存在
 *
 * @param string $attackType 攻击类型
 * @return bool 成就是否已存在
 */
function isAchievementExists($attackType) {
    try {
        $db = heasec_db('heasec_logic');
        $stmt = $db->prepare("SELECT id FROM heasec_jwtkey_records WHERE attack_type = ?");
        $stmt->execute([$attackType]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log('[HeaSec] isAchievementExists error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 记录成就
 *
 * @param string $attackType 攻击类型
 * @return bool 是否成功
 */
function recordAchievement($attackType) {
    try {
        $db = heasec_db('heasec_logic');
        $sql = "INSERT INTO heasec_jwtkey_records (attack_type, success_count, last_success_at)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute([$attackType]);
        return true;
    } catch (Exception $e) {
        error_log('[HeaSec] recordAchievement error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 识别攻击类型
 * 通过分析JWT Header识别漏洞利用方式
 *
 * @param array $header JWT Header
 * @return string|null 攻击类型或null
 */
function identifyAttackType($header) {
    if (!is_array($header)) {
        return null;
    }

    $kid = isset($header['kid']) ? $header['kid'] : '';
    $jku = isset($header['jku']) ? $header['jku'] : '';

    // 检查jku注入
    // 正常Token不应该有jku字段，只要存在jku字段就说明是攻击
    // 不限制jku的URL地址，只要通过jku注入密钥且验证成功就算攻击
    if (!empty($jku)) {
        return 'jku_injection';
    }

    // 检查kid
    if (!empty($kid)) {
        // RSA公钥特征
        $rsaPatterns = [
            'BEGIN PUBLIC KEY',
            'BEGIN RSA PUBLIC KEY',
            'MIIBIjANBg',  // RSA公钥Base64编码常见前缀
            'MFkwE',       // RSA公钥Base64编码另一种前缀
            'MIIBojANBg'   // RSA公钥Base64编码
        ];

        $isRsaPublicKey = false;
        foreach ($rsaPatterns as $pattern) {
            if (stripos($kid, $pattern) !== false) {
                $isRsaPublicKey = true;
                break;
            }
        }

        // 如果包含RSA公钥特征且不含路径遍历字符，则是kid注入攻击（优先检测）
        if ($isRsaPublicKey) {
            return 'kid_injection';
        }

        // 路径遍历字符（仅检测真正的目录穿越模式）
        $pathTraversalChars = ['../', '..\\'];
        $hasPathTraversal = false;
        foreach ($pathTraversalChars as $char) {
            if (strpos($kid, $char) !== false) {
                $hasPathTraversal = true;
                break;
            }
        }

        // 如果包含路径遍历字符，则是路径遍历攻击
        if ($hasPathTraversal) {
            return 'kid_traversal';
        }
    }

    return null;
}
?>
