<?php
/**
 * HeaSec天积安全团队 - 回显型命令注入靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 初始化靶场状态（操作系统检测与关卡状态）
 * 需要在调用前通过 HeaSec_InitRangeSession() 完成会话初始化
 * @return void
 */
function initEchoRceSession() {
    if (!isset($_SESSION['echo_rce_os'])) {
        $_SESSION['echo_rce_os'] = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'windows' : 'linux';
    }

    for ($i = 1; $i <= 3; $i++) {
        if (!isset($_SESSION['echo_rce_level' . $i . '_passed'])) {
            $_SESSION['echo_rce_level' . $i . '_passed'] = false;
        }
    }
}

/**
 * 检测命令执行结果中是否包含目标命令的特征输出
 * @param string $result 命令执行的完整输出
 * @param int $level 关卡编号（1-3）
 * @param bool $isWindows 是否为Windows系统
 * @return array ['detected' => bool, 'passcode' => string|null]
 */
function detectCommandExecution($result, $level, $isWindows) {
    $detected = false;
    $passcode = null;

    switch ($level) {
        case 1:
            // 第一关：检测结果分析
            if ($isWindows) {
                // Windows环境下的用户标识特征检测
                $detected = (bool)preg_match('/^.+\\\\.+$/m', trim($result));
            } else {
                // Linux环境下的用户标识特征检测（排除ping输出干扰）
                $lines = array_filter(array_map('trim', explode("\n", $result)));
                foreach ($lines as $line) {
                    if ($line !== '' && !stripos($line, 'PING') && !stripos($line, 'ping') &&
                        !stripos($line, 'bytes') && !stripos($line, 'packet') &&
                        !stripos($line, 'rtt') && !stripos($line, 'ttl') &&
                        preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $line)) {
                        $detected = true;
                        $passcode = $line;
                        break;
                    }
                }
            }
            break;

        case 2:
            // 第二关：检测结果分析
            if ($isWindows) {
                // Windows环境下的文件列表特征检测
                $detected = (bool)(
                    stripos($result, '<DIR>') !== false ||
                    stripos($result, '目录') !== false ||
                    preg_match('/\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}\s+[\[<]?DIR[\]>]?/i', $result) ||
                    preg_match('/\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}\s+\d+\s+\w+/i', $result)
                );
            } else {
                // Linux环境下的文件列表特征检测
                $detected = (bool)preg_match('/^[-bcdlps][rwxst-]{9}\s+\d+/m', $result);
            }
            break;

        case 3:
            // 第三关：检测结果分析
            if ($isWindows) {
                // Windows环境下的网络信息特征检测
                $detected = (bool)preg_match('/\b(TCP|UDP)\s+\S+\s+\S+\s+\S+\s+\d{1,5}/i', $result);
            } else {
                // Linux环境下的系统信息特征检测
                $detected = (stripos($result, 'Linux') !== false && stripos($result, 'GNU/Linux') !== false);
            }
            break;
    }

    return [
        'detected' => $detected,
        'passcode' => $passcode
    ];
}

/**
 * 将命令执行输出转换为UTF-8编码（Windows系统下exec()返回GBK编码）
 * @param string $output 命令执行原始输出
 * @return string UTF-8编码的输出
 */
function toUtf8($output) {
    if (mb_check_encoding($output, 'UTF-8')) {
        return $output;
    }
    return mb_convert_encoding($output, 'UTF-8', 'GBK, GB2312, CP936, auto');
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec EchoRCE Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        // 确保所有字符串值为UTF-8编码
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = toUtf8($value);
            }
        }
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
