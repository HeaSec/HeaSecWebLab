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
        // 注意：ss 使用 state established 过滤时会省略 State 列，导致输出无 ESTAB 字样，
        // 因此这里查询全量连接（输出含 State 列），由下方统一按 ESTAB 状态匹配
        @exec('ss -tn 2>/dev/null || netstat -tn 2>/dev/null', $output);
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

        if (stripos($crontab, 'HeaSecWeb') !== false) {
            $detail = '定时任务 HeaSecWeb 已存在（用于启动WEB服务）';
            return true;
        }

        $detail = '未检测到包含 HeaSecWeb 标识的定时任务，请确认已通过命令注入创建该定时任务';
        return false;
    }
}

/**
 * 检测系统命令是否存在
 *
 * @param string $cmd 命令名
 * @return bool 命令是否存在于 PATH 中
 */
function toolExists($cmd)
{
    exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null', $output, $returnVar);
    return $returnVar === 0;
}

/**
 * Linux 环境前置自检（Windows 环境直接返回无需检测）
 *
 * 检测完成各成就所需的系统工具与 root 权限，用于页面加载时提示
 * 用户当前环境是否具备完成全部成就的条件
 *
 * @return array {
 *   needed: bool,            是否为需要检测的Linux环境
 *   is_root: bool|null,      Web进程是否以root运行（无法检测时为null）
 *   missing_tools: string[], 缺失的命令名列表
 *   issues: array{           各成就的环境问题（null表示不受影响）
 *     reverse_shell: string|null,
 *     create_user: string|null,
 *     open_port: string|null
 *   }
 * }
 */
function getEnvironmentCheck()
{
    $result = [
        'needed'        => false,
        'is_root'       => null,
        'missing_tools' => [],
        'issues'        => [
            'reverse_shell' => null,
            'create_user'   => null,
            'open_port'     => null
        ]
    ];

    // Windows 环境按默认部署方式运行，无需自检
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return $result;
    }

    $result['needed'] = true;

    // 权限检测：Web进程是否以root运行（影响创建用户等系统级操作）
    if (function_exists('posix_getuid')) {
        $result['is_root'] = (posix_getuid() === 0);
    }

    // 工具存在性检测
    $hasPython      = toolExists('python3') || toolExists('python');
    $hasCrontab     = toolExists('crontab');
    $hasUserTools   = toolExists('useradd') && toolExists('usermod') && toolExists('chpasswd');
    $hasNetTools    = toolExists('ss') || toolExists('netstat');

    // 汇总缺失命令清单（用于页面展示）
    if (!$hasPython) {
        $result['missing_tools'][] = 'python';
    }
    if (!$hasCrontab) {
        $result['missing_tools'][] = 'crontab';
    }
    if (!$hasUserTools) {
        $result['missing_tools'][] = 'useradd/usermod/chpasswd';
    }
    if (!$hasNetTools) {
        $result['missing_tools'][] = 'ss/netstat';
    }

    // 归纳各成就的环境问题
    if (!$hasNetTools) {
        $result['issues']['reverse_shell'] = '缺少 ss/netstat 命令，无法验证反弹shell连接';
    }
    if (!$hasUserTools || $result['is_root'] === false) {
        $reasons = [];
        if (!$hasUserTools) {
            $reasons[] = '缺少用户管理命令（useradd/usermod/chpasswd）';
        }
        if ($result['is_root'] === false) {
            $reasons[] = 'Web进程非root权限';
        }
        $result['issues']['create_user'] = implode('；', $reasons) . '，无法完成创建系统用户操作';
    }
    if (!$hasCrontab || !$hasPython) {
        $reasons = [];
        if (!$hasCrontab) {
            $reasons[] = '缺少 crontab 命令，无法创建定时任务';
        }
        if (!$hasPython) {
            $reasons[] = '缺少 python 命令，定时任务内容无法执行';
        }
        $result['issues']['open_port'] = implode('；', $reasons);
    }

    return $result;
}

/**
 * 获取指定成就验证所需的验证工具环境问题（仅Linux环境）
 *
 * 只拦截"验证逻辑本身无法执行"的情况（如 crontab 命令缺失导致
 * 无法读取定时任务），与"任务未完成"的验证失败区分开
 *
 * @param string $type 成就类型（reverse_shell/create_user/open_port）
 * @return string|null 环境问题文案，null表示验证工具可用
 */
function getVerifyEnvironmentIssue($type)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return null;
    }

    switch ($type) {
        case 'reverse_shell':
            if (!toolExists('ss') && !toolExists('netstat')) {
                return '当前环境缺少 ss/netstat 命令，无法检测反弹shell连接';
            }
            break;
        case 'open_port':
            if (!toolExists('crontab')) {
                return '当前环境缺少 crontab 命令，无法检测定时任务';
            }
            break;
    }

    return null;
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
