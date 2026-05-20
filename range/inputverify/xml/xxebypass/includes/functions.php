<?php
/**
 * HeaSec天积安全团队 - XXE绕过靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成secret.php文件（如不存在则创建）
 * 包含特殊字符的配置文件
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
    $content .= " * &config: data-source='internal' && status='active'\n";
    $content .= " */\n";
    $content .= "\$secret_passcode = '" . $passcode . "'; /* 系统密钥 */\n";

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
 * 获取指定关卡的数据文件路径
 * @param int $level 关卡编号
 * @return string 数据文件绝对路径
 */
function getDataFilePath($level) {
    $basePath = dirname(__DIR__);
    return $basePath . '/data/level' . $level . '.json';
}

/**
 * 确保导入数据文件存在
 * @param string $filePath 数据文件路径
 * @return void
 */
function ensureDataFile($filePath) {
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

/**
 * 追加导入数据
 * @param string $filePath 数据文件路径
 * @param array $product 商品数据
 * @return void
 */
function appendImportedData($filePath, $product) {
    $data = json_decode(file_get_contents($filePath), true);
    if (!is_array($data)) {
        $data = [];
    }
    $product['import_time'] = date('Y-m-d H:i:s');
    $data[] = $product;
    file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 获取导入数据
 * @param string $filePath 数据文件路径
 * @return array 商品数据数组
 */
function getImportedData($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $data = json_decode(file_get_contents($filePath), true);
    return is_array($data) ? $data : [];
}

/**
 * 清空导入数据
 * @param string $filePath 数据文件路径
 * @return void
 */
function clearImportedData($filePath) {
    file_put_contents($filePath, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec XXEBypass Range v1.0.0');

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
