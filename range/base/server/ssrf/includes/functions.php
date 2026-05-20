<?php
/**
 * HeaSec天积安全团队 - SSRF漏洞靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 包含步骤进度管理、秘密生成、端口记录等公共函数
 */

/**
 * 获取或初始化当前会话的步骤进度
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @return array 当前进度记录
 */
function getOrCreateProgress($pdo, $sessionId) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_ssrf_progress WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$progress) {
        $stmt = $pdo->prepare(
            "INSERT INTO heasec_ssrf_progress (session_id, current_step, step1_completed, step2_completed, step3_completed, step4_completed)
             VALUES (?, 1, 0, 0, 0, 0)"
        );
        $stmt->execute([$sessionId]);

        $stmt = $pdo->prepare("SELECT * FROM heasec_ssrf_progress WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $progress;
}

/**
 * 更新步骤完成状态
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @param int $step 完成的步骤号（1-4）
 * @return bool 是否更新成功
 */
function completeStep($pdo, $sessionId, $step) {
    $validSteps = [1, 2, 3, 4];
    if (!in_array($step, $validSteps)) {
        return false;
    }

    // 检查前置步骤是否完成
    if ($step > 1) {
        $prevStep = $step - 1;
        $stmt = $pdo->prepare("SELECT step{$prevStep}_completed FROM heasec_ssrf_progress WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $prev = $stmt->fetchColumn();
        if (!$prev) {
            return false;
        }
    }

    $nextStep = $step + 1;
    $sql = "UPDATE heasec_ssrf_progress SET step{$step}_completed = 1, current_step = ? WHERE session_id = ? AND step{$step}_completed = 0";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$nextStep, $sessionId]);
}

/**
 * 获取或生成SSRF秘密字符串（从数据库中读取）
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @return string 秘密字符串
 */
function getOrCreateSecret($pdo, $sessionId) {
    $stmt = $pdo->prepare("SELECT secret_value FROM heasec_ssrf_secrets WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $secret = $stmt->fetchColumn();

    if (!$secret) {
        // 生成20位随机秘密字符串
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $secret = '';
        for ($i = 0; $i < 20; $i++) {
            $secret .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        $stmt = $pdo->prepare("INSERT INTO heasec_ssrf_secrets (session_id, secret_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE secret_value = VALUES(secret_value)");
        $stmt->execute([$sessionId, $secret]);
    }

    return $secret;
}

/**
 * 记录开放端口到数据库
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @param string $host 目标主机
 * @param int $port 端口号
 * @param bool $isOpen 是否开放
 * @return bool 是否记录成功
 */
function recordOpenPort($pdo, $sessionId, $host, $port, $isOpen = true) {
    // 检查是否已记录过该端口
    $stmt = $pdo->prepare("SELECT id FROM heasec_ssrf_ports WHERE session_id = ? AND target_host = ? AND port = ?");
    $stmt->execute([$sessionId, $host, $port]);
    if ($stmt->fetchColumn()) {
        return true;
    }

    $stmt = $pdo->prepare("INSERT INTO heasec_ssrf_ports (session_id, target_host, port, is_open) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$sessionId, $host, $port, $isOpen ? 1 : 0]);
}

/**
 * 记录请求日志
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @param string $protocol 使用的协议
 * @param string $targetUrl 目标URL
 * @param string|null $responsePreview 响应摘要
 * @param bool $isSuccess 是否成功
 * @param int|null $stepCompleted 触发的步骤完成
 * @return bool 是否记录成功
 */
function logRequest($pdo, $sessionId, $protocol, $targetUrl, $responsePreview = null, $isSuccess = false, $stepCompleted = null) {
    $stmt = $pdo->prepare(
        "INSERT INTO heasec_ssrf_logs (session_id, protocol, target_url, response_preview, is_success, step_completed)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([$sessionId, $protocol, $targetUrl, $responsePreview, $isSuccess ? 1 : 0, $stepCompleted]);
}

/**
 * 生成SSRF Token（基于会话，每次请求相同）
 *
 * @return string SSRF Token
 */
function generateSSRFToken() {
    if (!isset($_SESSION['ssrf_token'])) {
        $_SESSION['ssrf_token'] = hash('sha256', session_id() . 'heasec_ssrf_internal');
    }
    return $_SESSION['ssrf_token'];
}

/**
 * 验证SSRF Token
 *
 * @param string $token 待验证的token
 * @return bool 是否验证通过
 */
function validateSSRFToken($token) {
    return hash_equals(generateSSRFToken(), $token);
}

/**
 * 获取当前步骤的提示信息
 *
 * @param array $progress 当前进度记录
 * @param string $rangeBasePath 靶场根目录的绝对路径
 * @param string $metadataUrl 元数据接口的完整URL
 * @return array [task_text, hint_text] 任务文本和提示文本
 */
function getStepHints($progress, $rangeBasePath, $metadataUrl) {
    $currentStep = (int)$progress['current_step'];

    switch ($currentStep) {
        case 1:
            $task = '探索系统的SSRF漏洞，完成一系列挑战获取秘密。听说内网有一个元数据接口，也许能发现一些有趣的信息？试试用识图功能访问它：<code>' . htmlspecialchars($metadataUrl) . '</code>';
            $hint = '';
            break;
        case 2:
            $task = '进度：1/4 - 已发现内网元数据接口。config/hit.php 文件中似乎隐藏着什么信息，但直接访问看不到内容，尝试利用SSRF漏洞读取文件内容。完整路径：<code>' . htmlspecialchars(str_replace('\\', '/', $rangeBasePath)) . '/config/hit.php</code>';
            $hint = '';
            break;
        case 3:
            $task = '进度：2/4 - 已读取本地配置文件，根据获得的提示。请直接在浏览器中访问 <a href="portscan.php">portscan.php</a> 页面获取下一步任务指引';
            $hint = '';
            break;
        case 4:
            $task = '进度：3/4 - 已完成端口探测。最后一步！利用SSRF漏洞攻击内网 Redis 服务（10.66.66.66:56379），提取秘密字符串';
            $hint = '';
            break;
        default:
            // current_step >= 5 表示全部步骤已完成（含秘密验证成功）
            $task = '进度：4/4 - 已完成全部挑战';
            $hint = '恭喜你完成了所有挑战！请在下方秘密验证区域输入你发现的秘密。';
            break;
    }

    return [$task, $hint];
}

/**
 * 处理特殊端口的 gopher 请求
 *
 * @param string $url gopher URL
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @return string 模拟的服务响应
 */
function handleSimulatedRedis($url, $pdo, $sessionId) {
    // 1. 提取 _ 后的 URL 编码数据
    $data = '';
    if (preg_match('#gopher://[^/]+/_(.+)#', $url, $m)) {
        $data = urldecode($m[1]);
    }

    // 2. 解析 Redis 命令
    $commands = parseRedisCommand($data);
    $command = strtoupper($commands[0] ?? '');

    // 3. 根据命令返回模拟响应
    switch ($command) {
        case 'PING':
            return "+PONG\r\n";
        case 'INFO':
            return getSimulatedRedisInfo();
        case 'KEYS':
            return "*1\r\n$19\r\nheasec:ssrf:secret\r\n";
        case 'GET':
            $key = $commands[1] ?? '';
            if ($key === 'heasec:ssrf:secret') {
                $secret = getOrCreateSecret($pdo, $sessionId);
                return '$' . strlen($secret) . "\r\n" . $secret . "\r\n";
            }
            return "$-1\r\n";
        default:
            return "-ERR unknown command '" . $command . "'\r\n";
    }
}

/**
 * 解析 RESP 协议命令数据
 *
 * @param string $data 原始命令数据
 * @return array 命令及参数数组
 */
function parseRedisCommand($data) {
    $data = trim($data);
    if (strpos($data, '*') === 0) {
        return parseRespCommands($data);
    }
    // 内联格式解析
    $lines = preg_split('/\r?\n/', $data);
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line) {
            $parts = preg_split('/\s+/', $line, 2);
            $result[] = $parts[0];
            if (isset($parts[1])) {
                $result[] = $parts[1];
            }
        }
    }
    return $result;
}

/**
 * 解析 RESP 格式的命令数据
 *
 * @param string $data RESP 格式数据
 * @return array 命令及参数数组
 */
function parseRespCommands($data) {
    $lines = explode("\r\n", $data);
    $commands = [];
    $i = 0;
    $count = 0;

    if (isset($lines[$i]) && $lines[$i][0] === '*') {
        $count = (int)substr($lines[$i], 1);
        $i++;
    }

    for ($j = 0; $j < $count && $i < count($lines); $j++) {
        if (isset($lines[$i]) && $lines[$i][0] === '$') {
            $len = (int)substr($lines[$i], 1);
            $i++;
            if (isset($lines[$i])) {
                $commands[] = $lines[$i];
            }
            $i++;
        }
    }

    return $commands;
}

/**
 * 获取模拟的 INFO 响应
 *
 * @return string 模拟的服务器信息
 */
function getSimulatedRedisInfo() {
    $info = "# Server\r\n"
          . "redis_version:6.2.6\r\n"
          . "redis_git_sha1:00000000\r\n"
          . "redis_git_dirty:0\r\n"
          . "redis_build_id:abc123def456\r\n"
          . "redis_mode:standalone\r\n"
          . "os:Linux 5.4.0-100-generic x86_64\r\n"
          . "arch_bits:64\r\n"
          . "multiplexing_api:epoll\r\n"
          . "atomicvar_api:atomic-builtin\r\n"
          . "gcc_version:9.4.0\r\n"
          . "process_id:1234\r\n"
          . "process_supervised:no\r\n"
          . "run_id:abcdef1234567890abcdef1234567890abcdef12\r\n"
          . "tcp_port:6379\r\n"
          . "server_time_usec:" . (time() * 1000000) . "\r\n"
          . "uptime_in_seconds:86400\r\n"
          . "uptime_in_days:1\r\n"
          . "hz:10\r\n"
          . "configured_hz:10\r\n"
          . "lru_clock:" . time() . "\r\n"
          . "executable:/usr/local/bin/redis-server\r\n"
          . "config_file:/etc/redis/redis.conf\r\n"
          . "# Clients\r\n"
          . "connected_clients:1\r\n"
          . "cluster_enabled:0\r\n"
          . "# Keyspace\r\n"
          . "db0:keys=1,expires=0,avg_ttl=0\r\n";

    return '$' . strlen($info) . "\r\n" . $info . "\r\n";
}

/**
 * 重置靶场数据
 *
 * @param PDO $pdo 数据库连接
 * @param string $sessionId 会话ID
 * @return bool 是否重置成功
 */
function resetRangeData($pdo, $sessionId) {
    try {
        $pdo->beginTransaction();

        // 清除秘密记录
        $stmt = $pdo->prepare("DELETE FROM heasec_ssrf_secrets WHERE session_id = ?");
        $stmt->execute([$sessionId]);

        // 清除端口探测记录
        $stmt = $pdo->prepare("DELETE FROM heasec_ssrf_ports WHERE session_id = ?");
        $stmt->execute([$sessionId]);

        // 清除请求日志
        $stmt = $pdo->prepare("DELETE FROM heasec_ssrf_logs WHERE session_id = ?");
        $stmt->execute([$sessionId]);

        // 重置步骤进度
        $stmt = $pdo->prepare(
            "UPDATE heasec_ssrf_progress SET current_step = 1, step1_completed = 0, step2_completed = 0, step3_completed = 0, step4_completed = 0 WHERE session_id = ?"
        );
        $stmt->execute([$sessionId]);

        // 如果没有进度记录则创建
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_ssrf_progress WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO heasec_ssrf_progress (session_id, current_step, step1_completed, step2_completed, step3_completed, step4_completed)
                 VALUES (?, 1, 0, 0, 0, 0)"
            );
            $stmt->execute([$sessionId]);
        }

        $pdo->commit();

        // 生成新秘密
        getOrCreateSecret($pdo, $sessionId);

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * 动态生成元数据接口的完整URL
 *
 * @return string 元数据接口的完整URL
 */
function generateMetadataUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // 从当前脚本路径推导靶场根目录
    $scriptDir = dirname(__DIR__); // 指向 ssrf/ 目录
    $docRoot = $_SERVER['DOCUMENT_ROOT'];

    // 计算相对路径
    $relativePath = str_replace('\\', '/', substr($scriptDir, strlen($docRoot)));
    $relativePath = ltrim($relativePath, '/');

    return $protocol . '://' . $host . '/' . $relativePath . '/lcapi/metadata.php';
}
