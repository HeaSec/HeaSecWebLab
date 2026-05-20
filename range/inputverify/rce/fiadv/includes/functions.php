<?php
/**
 * HeaSec天积安全团队 - 文件包含进阶靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-17
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 发送JSON响应
 *
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 附加数据
 */
function sendJsonResponse($success, $message, $data = [])
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Fiadv Range');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取或创建目标字符串（全局共享模式，全站唯一）
 *
 * @param PDO $pdo 数据库连接
 * @return string 34位目标字符串（I_love_heasec_ + 20位随机）
 */
function getOrCreateTargetString($pdo)
{
    $sql = "SELECT target_value FROM heasec_fiadv_targets LIMIT 1";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row['target_value'];
    }

    // 生成新的目标字符串
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomPart = '';
    for ($i = 0; $i < 20; $i++) {
        $randomPart .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    $targetValue = 'I_love_heasec_' . $randomPart;

    // 写入数据库
    $sql = "INSERT INTO heasec_fiadv_targets (target_value, created_at) VALUES (?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$targetValue]);

    // 同时生成 target_def.php 文件
    generateTargetDefFile($targetValue);

    return $targetValue;
}

/**
 * 生成 target_def.php 目标字符串定义文件
 * 目标字符串以拼接形式存储，避免读取文件时直接暴露完整字符串
 *
 * @param string $targetValue 目标字符串
 */
function generateTargetDefFile($targetValue)
{
    $dir = dirname(__DIR__) . '/templates/config';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // 将目标字符串拆分为前缀和随机部分，拼接存储防止正则误匹配
    $prefix = 'I_love_heasec_';
    $suffix = substr($targetValue, strlen($prefix));

    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * 天积安全 - 模板配置常量\n";
    $content .= " * 此文件定义了模板引擎的目标输出字符串\n";
    $content .= " */\n";
    $content .= "define('HEASEC_TARGET', '" . $prefix . "' . '" . $suffix . "');\n";
    $content .= "// 此文件被 include 时仅定义常量，不产生输出\n";

    file_put_contents($dir . '/target_def.php', $content);
}

/**
 * 检测PHP运行时配置是否满足靶场要求
 *
 * @return array 包含 is_ready, message, config_file, severity 等字段
 */
function checkPhpConfig()
{
    $allowUrlInclude = ini_get('allow_url_include');

    $result = [
        'is_ready' => ($allowUrlInclude === '1'),
        'current_value' => $allowUrlInclude,
    ];

    if (!$result['is_ready']) {
        $result['message'] = '警告：当前 PHP 配置中 allow_url_include 未开启（当前值：' . $allowUrlInclude .
            '）。本靶场的部分功能需要此配置项为 On 才能正常运行。' .
            '请在 php.ini 中设置 allow_url_include = On 后重启服务。';
        $result['config_file'] = php_ini_loaded_file();
        $result['severity'] = 'warning';
    } else {
        $result['message'] = 'PHP 配置检测通过：allow_url_include 已开启。';
        $result['severity'] = 'success';
    }

    return $result;
}

/**
 * 检测 include 输出是否包含目标字符串，若匹配则记录成就（全局共享模式）
 * 需要同时匹配目标字符串和验证令牌，确保输出由 PHP 代码执行产生而非纯文本包含
 *
 * @param string $content include() 的输出内容
 * @param PDO $pdo 数据库连接
 * @param string $protocol 协议标识（file/php_input/data/zip/phar）
 * @param string $verifyToken RCE验证令牌，通过 $GLOBALS['heasec_rce_token'] 获取
 */
function checkAndRecordAchievement($content, $pdo, $protocol, $verifyToken = '')
{
    if (empty($verifyToken)) {
        return;
    }

    $pattern = '/I_love_heasec_[a-zA-Z0-9]{20}:' . preg_quote($verifyToken, '/') . '/';
    if (preg_match($pattern, $content, $matches)) {
        $sql = "INSERT INTO heasec_fiadv_achievements (protocol, success_count, first_success_at, last_success_at)
                VALUES (?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$protocol]);
    }
}

/**
 * 获取全局成就状态（全局共享模式）
 *
 * @param PDO $pdo 数据库连接
 * @return array 包含 achieved_count, records[]
 */
function getAchievementStatus($pdo)
{
    $sql = "SELECT protocol, success_count, first_success_at
            FROM heasec_fiadv_achievements
            ORDER BY first_success_at ASC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $records = [];
    foreach ($rows as $row) {
        $protocolName = getProtocolDisplayName($row['protocol']);
        $records[] = [
            'name' => $protocolName,
            'count' => $row['success_count']
        ];
    }

    return [
        'achieved_count' => count($rows),
        'records' => $records
    ];
}

/**
 * 协议标识到显示名称的映射
 *
 * @param string $protocol 协议标识
 * @return string 显示名称
 */
function getProtocolDisplayName($protocol)
{
    $map = [
        'file' => 'file:// 本地文件包含',
        'php_input' => 'php://input 输入流',
        'php' => 'php:// 伪协议',
        'data' => 'data:// Data URI',
        'zip' => 'zip:// 压缩包',
        'phar' => 'phar:// PHAR归档',
    ];
    if (isset($map[$protocol])) {
        return $map[$protocol];
    }
    return strtoupper($protocol) . ':// 协议利用';
}

/**
 * 生成进度提示文本
 *
 * @param int $currentCount 当前已解锁的协议数量
 * @return string 进度提示
 */
function generateProgressHint($currentCount)
{
    $thresholds = [1, 3, 5];
    $titles = ['', '新手成就(1星)', '探索者成就(2星)', '大师成就(3星)'];

    if ($currentCount >= 5) {
        return '恭喜！你已解锁全部成就！';
    }

    // 找到下一个阈值
    $nextThreshold = null;
    $nextStarIndex = 0;
    foreach ($thresholds as $i => $t) {
        if ($currentCount < $t) {
            $nextThreshold = $t;
            $nextStarIndex = $i + 1;
            break;
        }
    }

    if ($nextThreshold !== null) {
        $remaining = $nextThreshold - $currentCount;
        return '还差 ' . $remaining . ' 种新协议解锁 ' . $titles[$nextStarIndex];
    }

    return '';
}

/**
 * 获取已上传文件列表
 *
 * @return array 文件列表
 */
function getUploadedFilesList()
{
    $uploadDir = dirname(__DIR__) . '/uploads/';
    $files = [];

    if (!is_dir($uploadDir)) {
        return $files;
    }

    $items = scandir($uploadDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.htaccess') {
            continue;
        }
        $filePath = $uploadDir . $item;
        if (is_file($filePath)) {
            $files[] = [
                'filename' => $item,
                'filepath' => 'uploads/' . $item
            ];
        }
    }

    return $files;
}
