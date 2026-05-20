<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成secret.php文件（如不存在则创建）
 * @param string $filePath secret.php文件路径
 * @return void
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

    $content  = "<?php\n";
    $content .= "/**\n";
    $content .= " * <<系统配置文件>>\n";
    $content .= " * 天积安全 - HeaSec Security\n";
    $content .= " * @version 1.0.0\n";
    $content .= " */\n";
    $content .= "\$secret_passcode = '" . $passcode . "';\n";

    file_put_contents($filePath, $content);
}

/**
 * 从secret.php文件中提取通关密码
 * @param string $filePath secret.php文件路径
 * @return string|null 通关密码，文件不存在或格式错误返回null
 */
function extractPasscode($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    if (preg_match('/\$secret_passcode\s*=\s*\'([^\']+)\'/', $content, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * 获取指定关卡的秘密文件路径
 * @param int $level 关卡编号
 * @return string secret.php文件绝对路径
 */
function getSecretFilePath($level) {
    $basePath = dirname(__DIR__);
    switch ($level) {
        case 2:
            return $basePath . '/config/level2/secret.php';
        case 3:
            return $basePath . '/config/level3/secret.php';
        default:
            return $basePath . '/config/secret.php';
    }
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

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
