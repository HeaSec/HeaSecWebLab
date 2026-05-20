<?php
/**
 * HeaSec天积安全团队 - 时间盲注靶场公共函数
 * 版本: v2.0.0
 * 创建日期: 2026-04-20
 * 更新日期: 2026-04-26 - 双维度成就系统升级
 * 团队: 天积安全 (HeavenlySecret)
 */

if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

/**
 * 发送JSON响应
 *
 * @param bool   $success 是否成功
 * @param string $message 消息
 * @param array  $data    附加数据
 */
function sendJsonResponse($success, $message, $data = [])
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Timesi Range v1.0.0');

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
 * 检查用户输入是否包含 database() 函数引用
 *
 * @param string $input 用户输入
 * @return bool
 */
function containsDatabaseCheck($input)
{
    return preg_match('/\bdatabase\s*\(/i', $input) === 1;
}

/**
 * 识别用户使用的延迟技术
 *
 * @param string $input 用户输入
 * @return string|null 延迟技术标识名，无法识别时返回null
 */
function detectDelayFunction($input)
{
    $rules = [
        '/\bsleep\s*\(/i'                                                    => 'sleep',
        '/\bbenchmark\s*\(/i'                                                => 'benchmark',
        '/\bget_lock\s*\(/i'                                                 => 'get_lock',
        '/FROM(?:\/\*\*\/|\s+)[\w`.]+(?:[\/\s\*]*[\w`.]+)*,[\s\/\*]*[\w`.]+/i' => 'cartesian',
        '/\bCROSS(?:\/\*\*\/|\s+)JOIN\b/i'                                   => 'cartesian',
    ];

    foreach ($rules as $pattern => $funcName) {
        if (preg_match($pattern, $input)) {
            return $funcName;
        }
    }

    return null;
}

/**
 * 检查用户输入是否在判断数据库名的第一位是否为'h'
 * 确保用户确实在猜解数据库名首字符（数据库名 heasec_sqli 首字符为 'h'）
 *
 * @param string $input 用户输入
 * @return bool
 */
function containsFirstCharCheck($input)
{
    // 必须包含 database()
    if (!preg_match('/\bdatabase\s*\(/i', $input)) {
        return false;
    }

    // 必须包含首字符提取模式：mid/substr(x,1,1) 或 left(x,1) 或 ord/ascii 包裹等
    $hasFirstChar = preg_match('/,\s*1\s*,\s*1\s*\)/i', $input)  // mid/substr(x, 1, 1)
        || preg_match('/,\s*1\s*\)/i', $input);                    // left/right(x, 1)

    if (!$hasFirstChar) {
        return false;
    }

    // 必须与 'h' 比较（字符串 'h'、ASCII 104 或十六进制 0x68）
    $hasH = preg_match('/[\'"]h[\'"]/i', $input)   // 'h' 或 "h"
        || preg_match('/\b104\b/', $input)           // ASCII(h) = 104
        || preg_match('/0x68/i', $input);            // HEX(h) = 0x68

    return $hasH;
}

/**
 * 识别用户使用的字符串处理函数
 * 用于从 database() 中提取字符进行盲注猜解
 *
 * @param string $input 用户输入
 * @return string|null 字符串函数标识名，无法识别时返回null
 */
function detectStringFunction($input)
{
    $rules = [
        // 截取类
        '/\bmid\s*\(/i'              => 'mid',
        '/\bleft\s*\(/i'             => 'left',
        '/\bright\s*\(/i'            => 'right',
        '/\bsubstring_index\s*\(/i'  => 'substring_index',
        // 编码类
        '/\bascii\s*\(/i'            => 'ascii',
        '/\bord\s*\(/i'              => 'ord',
        '/\bchar\s*\(/i'             => 'char',
        '/\bhex\s*\(/i'              => 'hex',
        '/\bbin\s*\(/i'              => 'bin',
        '/\boct\s*\(/i'              => 'oct',
        '/\bconv\s*\(/i'             => 'conv',
        // 搜索类
        '/\binstr\s*\(/i'            => 'instr',
        '/\blocate\s*\(/i'           => 'locate',
        '/\bposition\s*\(/i'         => 'position',
        // 填充/替换类
        '/\blpad\s*\(/i'             => 'lpad',
        '/\brpad\s*\(/i'             => 'rpad',
        '/\breplace\s*\(/i'          => 'replace',
        '/\binsert\s*\(/i'           => 'insert',
        // 修剪类
        '/\btrim\s*\(/i'             => 'trim',
        '/\bltrim\s*\(/i'            => 'ltrim',
        '/\brtrim\s*\(/i'            => 'rtrim',
        // 其他
        '/\breverse\s*\(/i'          => 'reverse',
        '/\blength\s*\(/i'           => 'length',
        '/\bconcat\s*\(/i'           => 'concat',
        '/\bgroup_concat\s*\(/i'     => 'group_concat',
        '/\bmake_set\s*\(/i'         => 'make_set',
        '/\bexport_set\s*\(/i'       => 'export_set',
        '/\bregexp_like\s*\(/i'      => 'regexp_like',
    ];

    foreach ($rules as $pattern => $funcName) {
        if (preg_match($pattern, $input)) {
            return $funcName;
        }
    }

    return null;
}

/**
 * 记录字符串函数成就（全局共享模式）
 *
 * @param PDO    $pdo          数据库连接
 * @param string $functionName 字符串函数标识名
 */
function recordStringFunction($pdo, $functionName)
{
    $sql = "INSERT INTO heasec_timesi_string_functions (function_name, success_count, first_success_at, last_success_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            success_count = success_count + 1,
            last_success_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$functionName]);
}

/**
 * 字符串函数标识到显示名称的映射
 *
 * @param string $functionName 字符串函数标识名
 * @return string 显示名称
 */
function getStringFunctionDisplayName($functionName)
{
    $map = [
        'mid'              => 'mid() 截取',
        'left'             => 'left() 截取',
        'right'            => 'right() 截取',
        'substring_index'  => 'substring_index() 截取',
        'ascii'            => 'ascii() 编码',
        'ord'              => 'ord() 编码',
        'char'             => 'char() 解码',
        'hex'              => 'hex() 编码',
        'bin'              => 'bin() 二进制',
        'oct'              => 'oct() 八进制',
        'conv'             => 'conv() 进制转换',
        'instr'            => 'instr() 搜索',
        'locate'           => 'locate() 搜索',
        'position'         => 'position() 搜索',
        'lpad'             => 'lpad() 填充',
        'rpad'             => 'rpad() 填充',
        'replace'          => 'replace() 替换',
        'insert'           => 'insert() 替换',
        'trim'             => 'trim() 修剪',
        'ltrim'            => 'ltrim() 修剪',
        'rtrim'            => 'rtrim() 修剪',
        'reverse'          => 'reverse() 反转',
        'length'           => 'length() 长度',
        'concat'           => 'concat() 拼接',
        'group_concat'     => 'group_concat() 拼接',
        'make_set'         => 'make_set() 位集',
        'export_set'       => 'export_set() 位集',
        'regexp_like'      => 'regexp_like() 匹配',
        'other'            => '其他字符串函数',
    ];
    return isset($map[$functionName]) ? $map[$functionName] : $functionName . ' 函数';
}

/**
 * 记录延迟技术成就（全局共享模式）
 *
 * @param PDO    $pdo          数据库连接
 * @param string $functionName 延迟技术标识名
 */
function recordAchievement($pdo, $functionName)
{
    $sql = "INSERT INTO heasec_timesi_achievements (function_name, success_count, first_success_at, last_success_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            success_count = success_count + 1,
            last_success_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$functionName]);
}

/**
 * 获取全局成就状态（全局共享模式，双维度）
 *
 * @param PDO $pdo 数据库连接
 * @return array 包含 star_count, delay_count, string_count, 各维度 records 和 hints
 */
function getAchievementStatus($pdo)
{
    // 查询延迟技术维度
    $sql1 = "SELECT function_name, success_count, first_success_at
             FROM heasec_timesi_achievements
             ORDER BY first_success_at ASC";
    $stmt1 = $pdo->query($sql1);
    $delayRows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    $delayRecords = [];
    foreach ($delayRows as $row) {
        $displayName = getFunctionDisplayName($row['function_name']);
        $delayRecords[] = [
            'name'  => $displayName,
            'count' => $row['success_count']
        ];
    }
    $delayCount = count($delayRows);

    // 查询字符串函数维度
    $sql2 = "SELECT function_name, success_count, first_success_at
             FROM heasec_timesi_string_functions
             ORDER BY first_success_at ASC";
    $stmt2 = $pdo->query($sql2);
    $stringRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $stringRecords = [];
    foreach ($stringRows as $row) {
        $displayName = getStringFunctionDisplayName($row['function_name']);
        $stringRecords[] = [
            'name'  => $displayName,
            'count' => $row['success_count']
        ];
    }
    $stringCount = count($stringRows);

    // 跨维度计算星星数量（从高到低判断，两个维度都要满足）
    $starCount = 0;
    if ($delayCount >= 3 && $stringCount >= 5) {
        $starCount = 3;
    } elseif ($delayCount >= 2 && $stringCount >= 3) {
        $starCount = 2;
    } elseif ($delayCount >= 1 && $stringCount >= 1) {
        $starCount = 1;
    }

    // 各维度进度提示（目标为3星最高条件：3种延迟 + 5种函数）
    $delayTarget = 3;
    $stringTarget = 5;

    if ($delayCount >= $delayTarget) {
        $delayHint = '恭喜！延迟技术已全部掌握';
    } else {
        $delayHint = '还差 ' . ($delayTarget - $delayCount) . ' 种延迟技术获得下一颗星';
    }

    if ($stringCount >= $stringTarget) {
        $stringHint = '恭喜！字符串函数已全部掌握';
    } else {
        $stringHint = '还差 ' . ($stringTarget - $stringCount) . ' 种函数获得下一颗星';
    }

    return [
        'star_count'      => $starCount,
        'delay_count'     => $delayCount,
        'string_count'    => $stringCount,
        'delay_records'   => $delayRecords,
        'string_records'  => $stringRecords,
        'delay_hint'      => $delayHint,
        'string_hint'     => $stringHint,
        'achieved_count'  => $starCount,  // 向后兼容
        'records'         => $delayRecords // 向后兼容
    ];
}

/**
 * 延迟技术标识到显示名称的映射
 *
 * @param string $functionName 延迟技术标识名
 * @return string 显示名称
 */
function getFunctionDisplayName($functionName)
{
    $map = [
        'sleep'     => 'sleep() 延迟',
        'benchmark' => 'benchmark() 延迟',
        'get_lock'  => 'get_lock() 延迟',
        'cartesian' => '笛卡尔积延迟',
        'other'     => '其他延迟方式',
    ];
    return isset($map[$functionName]) ? $map[$functionName] : $functionName . ' 延迟';
}

/**
 * 获取服务列表数据（用于页面展示）
 *
 * @param PDO $pdo 数据库连接
 * @return array 服务列表
 */
function getServiceList($pdo)
{
    $sql = "SELECT id, service_name, service_type, status, port
            FROM heasec_timesi_services
            ORDER BY id ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
