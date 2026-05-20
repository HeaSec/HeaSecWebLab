<?php
/**
 * HeaSec天积安全团队 - 路径穿越靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成secret.php文件（如不存在则创建）
 * @param string $filePath secret.php文件路径
 * @param int    $level    关卡编号，用于在文件中标注关卡信息
 * @return void
 */
function generateSecretFile($filePath, $level = 1) {
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

    // 关卡名称映射
    $levelNames = [
        1 => '第一关',
        2 => '第二关',
        3 => '第三关',
    ];
    $levelName = isset($levelNames[$level]) ? $levelNames[$level] : '未知关卡';

    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * 系统配置文件 - {$levelName}\n";
    $content .= " * 天积安全 - HeaSec Security\n";
    $content .= " * @version 1.0.0\n";
    $content .= " */\n";
    $content .= "\$secret_passcode = '" . $passcode . "'; /* {$levelName}通关密码：" . $passcode . " */\n";

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
    if (preg_match("/\\\$secret_passcode\s*=\s*'([^']+)'/", $content, $matches)) {
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
 * 获取指定关卡的下载目录路径
 * @param int $level 关卡编号
 * @return string 下载目录绝对路径
 */
function getDownloadsDir($level) {
    $basePath = dirname(__DIR__);
    switch ($level) {
        case 2:
            return $basePath . '/downloads/level2';
        case 3:
            return $basePath . '/downloads/level3';
        default:
            return $basePath . '/downloads';
    }
}

/**
 * 获取指定关卡的可下载文件列表
 * @param int $level 关卡编号
 * @return array 文件列表
 */
function getFileList($level) {
    $dir = getDownloadsDir($level);
    $files = [];

    $fileNames = ['readme.md', 'guide.txt', 'changelog.txt'];
    foreach ($fileNames as $name) {
        $fullPath = $dir . '/' . $name;
        if (file_exists($fullPath)) {
            $files[] = [
                'name' => $name,
                'size' => filesize($fullPath)
            ];
        }
    }

    return $files;
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec PathTrvl Range v1.0.0');

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

/**
 * 格式化文件大小
 * @param int $bytes 字节数
 * @return string 格式化后的大小
 */
function formatFileSize($bytes) {
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}
