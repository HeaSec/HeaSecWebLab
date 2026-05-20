<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
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
    header('X-HeavenlySecret: HeaSec RceAdv Range');

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
 * 根据操作系统构建ping命令
 *
 * @param string $host 目标地址
 * @return string 完整的ping命令
 */
function buildBaseCommand($host)
{
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    $pingCmd = $isWindows ? 'ping -n 4 ' : 'ping -c 4 ';
    return $pingCmd . $host;
}

/**
 * 检查输入是否包含破坏性命令
 * 仅拦截极少数可能导致系统不可用的命令
 *
 * @param string $input 用户输入
 * @return bool 是否包含破坏性命令
 */
function containsDestructiveCommand($input)
{
    $destructivePatterns = [
        '/\brm\s+-rf\s+\//i',
        '/\brd\s+\/[sq]\b/i',
        '/\bdel\s+\/[sq]\s+[Cc]:/i',
        '/\bformat\s+[Cc]:/i',
        '/\bshutdown\b/i',
        '/\breboot\b/i',
        '/\bhalt\b/i',
        '/\bpoweroff\b/i',
        '/\biptables\s+-F\b/i',
        '/\bnetsh\s+advfirewall\s+set\s+allprofiles\s+state\s+off\b/i',
        '/\btaskkill\s+\/[fF]\b/i',
        '/\bsc\s+(stop|delete)\b/i',
        '/\breg\s+delete\b/i',
    ];
    foreach ($destructivePatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

/**
 * 检测反弹shell连接（检查靶场服务器出站连接状态）
 *
 * @param array $post POST数据（含ip和port）
 * @param string &$detail 详情信息
 * @return bool 是否验证通过
 */
function checkReverseShell($post, &$detail)
{
    $ip = $post['ip'] ?? '';
    $port = intval($post['port'] ?? 0);
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $detail = 'IP地址格式无效';
        return false;
    }
    if ($port < 1 || $port > 65535) {
        $detail = '端口号无效（1-65535）';
        return false;
    }

    $target = $ip . ':' . $port;

    $output = [];
    if ($isWindows) {
        @exec('netstat -an 2>&1', $output);
    } else {
        @exec('ss -tn state established 2>/dev/null || netstat -tn 2>/dev/null', $output);
    }

    $connections = implode("\n", $output);

    // 逐行检查，确保同一行中同时包含目标地址和ESTABLISHED状态
    foreach ($output as $line) {
        if (stripos($line, $target) !== false && stripos($line, 'ESTAB') !== false) {
            $detail = $target;
            return true;
        }
    }

    $detail = '未检测到到 ' . $target . ' 的反弹shell连接，请确认反弹shell已建立且连接仍然活跃';
    return false;
}

/**
 * 检测系统用户是否已创建且具有管理员权限
 *
 * @param bool $isWindows 是否为Windows系统
 * @param string &$detail 详情信息
 * @return bool 是否验证通过
 */
function checkCreateUser($isWindows, &$detail)
{
    if ($isWindows) {
        $output1 = [];
        exec('net user heasec 2>&1', $output1);
        $userCheck = implode("\n", $output1);

        if (stripos($userCheck, 'heasec') === false || stripos($userCheck, '找不到') !== false) {
            $detail = '用户 heasec 不存在';
            return false;
        }

        $output2 = [];
        exec('net localgroup administrators 2>&1', $output2);
        $groupCheck = implode("\n", $output2);

        if (stripos($groupCheck, 'heasec') === false) {
            $detail = '用户 heasec 存在但不在管理员组中';
            return false;
        }

        $detail = 'Windows 管理员用户 heasec';
        return true;
    } else {
        $output1 = [];
        exec('id heasec 2>&1', $output1);
        $userCheck = implode("\n", $output1);

        if (strpos($userCheck, 'uid=') === false) {
            $detail = '用户 heasec 不存在';
            return false;
        }

        $output2 = [];
        exec('groups heasec 2>&1', $output2);
        $groupCheck = implode("\n", $output2);

        if (stripos($groupCheck, 'sudo') === false && stripos($groupCheck, 'wheel') === false) {
            $detail = '用户 heasec 存在但不在 sudo/wheel 组中';
            return false;
        }

        $detail = 'Linux sudo 用户 heasec';
        return true;
    }
}

/**
 * 检测是否存在开启RDP服务的计划任务
 *
 * @param string &$detail 详情信息
 * @return bool 是否验证通过
 */
function checkOpenPort(&$detail)
{
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if ($isWindows) {
        $output = [];
        @exec('schtasks /query /tn "HeaSecRDP" 2>&1', $output, $returnVar);
        $result = implode("\n", $output);

        if ($returnVar === 0 && stripos($result, 'HeaSecRDP') !== false) {
            $detail = '计划任务 HeaSecRDP 已存在（用于开启RDP服务）';
            return true;
        }

        $detail = '未检测到计划任务 HeaSecRDP，请确认已通过命令注入创建该计划任务';
        return false;
    } else {
        $output = [];
        @exec('crontab -l 2>/dev/null', $output);
        $crontab = implode("\n", $output);

        if (stripos($crontab, 'HeaSecRDP') !== false) {
            $detail = '定时任务 HeaSecRDP 已存在（用于开启远程服务）';
            return true;
        }

        $detail = '未检测到定时任务 HeaSecRDP，请确认已通过命令注入创建该定时任务';
        return false;
    }
}

/**
 * 记录成就到数据库（全局共享模式，INSERT ON DUPLICATE KEY UPDATE）
 *
 * @param PDO $pdo 数据库连接
 * @param string $type 成就类型（reverse_shell/create_user/open_port）
 * @param string $detail 成就详情
 */
function recordAchievement($pdo, $type, $detail)
{
    $sql = "INSERT INTO heasec_rceadv_achievements (achievement_type, detail, success_count, first_success_at, last_success_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                detail = VALUES(detail),
                success_count = success_count + 1,
                last_success_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$type, $detail]);
}

/**
 * 获取全局成就状态（全局共享模式）
 *
 * @param PDO $pdo 数据库连接
 * @return array 包含 achieved_count, records[], 各成就完成状态
 */
function getAchievementStatus($pdo)
{
    $sql = "SELECT achievement_type, success_count, first_success_at
            FROM heasec_rceadv_achievements
            ORDER BY first_success_at ASC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $records = [];
    $completedTypes = [];
    foreach ($rows as $row) {
        $records[] = [
            'name'  => getAchievementDisplayName($row['achievement_type']),
            'count' => $row['success_count']
        ];
        $completedTypes[] = $row['achievement_type'];
    }

    return [
        'achieved_count'   => count($rows),
        'records'          => $records,
        'reverse_shell'    => in_array('reverse_shell', $completedTypes),
        'create_user'      => in_array('create_user', $completedTypes),
        'open_port'        => in_array('open_port', $completedTypes)
    ];
}

/**
 * 成就标识到显示名称的映射
 *
 * @param string $type 成就类型
 * @return string 显示名称
 */
function getAchievementDisplayName($type)
{
    $map = [
        'reverse_shell' => '反弹shell',
        'create_user'   => '系统渗透',
        'open_port'     => '计划任务',
    ];
    return $map[$type] ?? $type;
}

/**
 * 生成进度提示文本
 *
 * @param int $currentCount 当前已解锁的成就数量
 * @return string 进度提示
 */
function generateProgressHint($currentCount)
{
    $thresholds = [1, 2, 3];
    $titles = ['', '渗透新手(1星)', '渗透能手(2星)', '渗透专家(3星)'];

    if ($currentCount >= 3) {
        return '恭喜！你已解锁全部成就！';
    }

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
        return '还差 ' . $remaining . ' 个成就解锁 ' . $titles[$nextStarIndex];
    }

    return '';
}
