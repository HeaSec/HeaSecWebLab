<?php
/**
 * HeaSec天积安全团队 - 无回显命令注入靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 确保第一关的database.php存在（动态生成含20位随机密码的配置）
 * @param string $filePath 文件路径
 */
function ensureDatabaseConfig($filePath) {
    if (file_exists($filePath)) {
        return;
    }
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $dbPass = '';
    for ($i = 0; $i < 20; $i++) {
        $dbPass .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * 数据库配置文件\n";
    $content .= " * 天积安全 - HeaSec Security\n";
    $content .= " * @version 1.0.0\n */\n";
    $content .= "\$db_host = '192.168.1.100';\n";
    $content .= "\$db_user = 'heasec_admin';\n";
    $content .= "\$db_pass = '" . $dbPass . "';\n";
    $content .= "\$db_name = 'heasec_blind_rce';\n";
    $content .= "\$db_port = '3306';\n";

    file_put_contents($filePath, $content);
}

/**
 * 生成目标字符串文件（第二关专用，10位随机字符串）
 * @param string $filePath 目标字符串文件路径
 */
function generateTargetFile($filePath) {
    if (file_exists($filePath)) {
        return;
    }
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $target = '';
    for ($i = 0; $i < 10; $i++) {
        $target .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    file_put_contents($filePath, $target);
}

/**
 * 生成秘密文件（第二关/第三关通关密码，20位随机字符串，PHP格式）
 * @param string $filePath 秘密文件路径
 */
function generateSecretFile($filePath) {
    if (file_exists($filePath)) {
        return;
    }
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * <<系统配置文件>>\n";
    $content .= " * 天积安全 - HeaSec Security\n";
    $content .= " * @version 1.0.0\n */\n";
    $content .= "\$secret_passcode = '" . $passcode . "';\n";
    file_put_contents($filePath, $content);
}

/**
 * 从database.php中提取数据库密码（第一关专用）
 * @param string $filePath database.php文件路径
 * @return string|false 密码字符串或失败时返回false
 */
function extractDbPassword($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    $content = file_get_contents($filePath);
    if (preg_match('/\$db_pass\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * 从passcode.php中提取通关密码（第二关/第三关专用）
 * @param string $filePath passcode.php文件路径
 * @return string|false 密码字符串或失败时返回false
 */
function extractPasscode($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    $content = file_get_contents($filePath);
    if (preg_match('/\$secret_passcode\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
