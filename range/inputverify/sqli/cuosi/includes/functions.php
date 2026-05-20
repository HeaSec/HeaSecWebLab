<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 公共函数库
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 附加数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Cuosi Range v1.0.0');
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 生成指定长度的随机字符串
 * @param int $length 字符串长度
 * @return string 随机字符串（包含大小写字母和数字）
 */
function generateRandomString($length = 20) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 确保指定关卡的通关密码已生成
 *
 * 所有关卡的密码均存储在 config/ 目录下的文件中（防止报错注入直接提取）
 * L1: config/secret.php（键名: level1_pass）
 * L2: config/secret2.php（键名: level2_pass）
 * L3: config/secret3.php（键名: level3_pass）
 *
 * @param int $level 关卡编号（1/2/3）
 */
function ensurePasswordExists($level) {
    $fileNameMap = [1 => 'secret.php', 2 => 'secret2.php', 3 => 'secret3.php'];
    $varNameMap = [1 => 'level1_pass', 2 => 'level2_pass', 3 => 'level3_pass'];

    $secretFile = __DIR__ . '/../config/' . ($fileNameMap[$level] ?? '');
    $config = file_exists($secretFile) ? include($secretFile) : [];
    $configKey = $varNameMap[$level] ?? '';

    if (!empty($config[$configKey])) {
        return;
    }

    $password = generateRandomString(20);
    $content = "<?php\nreturn [\n    '" . $configKey . "' => '" . addslashes($password) . "',\n];\n";
    file_put_contents($secretFile, $content, LOCK_EX);
}

/**
 * 获取指定关卡的通关密码
 *
 * 所有关卡的密码均从 config/ 目录下的文件中读取
 *
 * @param int $level 关卡编号
 * @return string|false 密码字符串或失败时返回false
 */
function getPasscode($level) {
    $fileNameMap = [1 => 'secret.php', 2 => 'secret2.php', 3 => 'secret3.php'];
    $varNameMap = [1 => 'level1_pass', 2 => 'level2_pass', 3 => 'level3_pass'];

    $secretFile = __DIR__ . '/../config/' . ($fileNameMap[$level] ?? '');
    $config = file_exists($secretFile) ? include($secretFile) : [];
    $configKey = $varNameMap[$level] ?? '';

    return (!empty($config[$configKey])) ? $config[$configKey] : false;
}
