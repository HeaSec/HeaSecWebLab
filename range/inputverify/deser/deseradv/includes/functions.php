<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成秘密文件（支持PHP格式和纯文本格式）
 * @param string $filePath 秘密文件路径
 * @param bool $isTextFile 是否为纯文本格式（true=纯文本，false=PHP格式）
 * @return void
 */
function generateSecretFile($filePath, $isTextFile = false) {
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

    if ($isTextFile) {
        // 纯文本格式（用于第三关 SplFileObject 直接读取）
        file_put_contents($filePath, $passcode . "\n");
    } else {
        // PHP格式（用于第一关、第二关 file_get_contents 读取后正则提取）
        $content  = "<?php\n";
        $content .= "/**\n";
        $content .= " * <<系统配置文件>>\n";
        $content .= " * 天积安全 - HeaSec Security\n";
        $content .= " * @version 1.0.0\n";
        $content .= " */\n";
        $content .= "\$secret_passcode = '" . $passcode . "';\n";

        file_put_contents($filePath, $content);
    }
}

/**
 * 根据关卡获取秘密文件路径
 * @param int $level 关卡编号
 * @return string 文件路径
 */
function getSecretFilePath($level) {
    $baseDir = dirname(__DIR__) . '/config';
    switch ($level) {
        case 1:
            return $baseDir . '/secret.php';
        case 2:
            return $baseDir . '/level2/secret.php';
        case 3:
            return $baseDir . '/.level3_secret';
        default:
            return $baseDir . '/secret.php';
    }
}

/**
 * 从secret.php中提取通关密码（PHP格式文件）
 * @param string $filePath secret.php文件路径
 * @return string|null 密码字符串或失败时返回null
 */
function extractPasscode($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    if (preg_match('/\$secret_passcode\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * 从纯文本秘密文件中获取通关密码（第三关专用）
 * @param string $filePath 纯文本秘密文件路径
 * @return string|null 密码字符串或失败时返回null
 */
function extractTextPasscode($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = trim(file_get_contents($filePath));
    if (preg_match('/^[a-zA-Z0-9]{20}$/', $content)) {
        return $content;
    }
    return null;
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');

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
