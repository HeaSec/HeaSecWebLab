<?php
/**
 * HeaSec天积安全团队 - 报错注入靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-18
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
    header('X-HeavenlySecret: HeaSec Errsi Range v1.0.0');

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
 * 识别报错注入使用的函数
 * 通过错误信息特征+用户输入函数名双重匹配确定具体函数
 *
 * @param string $errorMessage PDO错误信息
 * @param string $userInput    用户输入的参数值
 * @return string|null 函数标识名（小写），无法识别时返回null
 */
function detectErrorFunction($errorMessage, $userInput)
{
    $detectionRules = [
        '/XPATH syntax error/i' => [
            '/\bextractvalue\s*\(/i' => 'extractvalue',
            '/\bupdatexml\s*\(/i'    => 'updatexml',
        ],
        '/Duplicate entry/i' => [
            '/\bfloor\s*\(/i' => 'floor',
        ],
        '/Malformed GTID/i' => [
            '/\bGTID_SUBSET\s*\(/i'   => 'GTID_SUBSET',
            '/\bGTID_SUBTRACT\s*\(/i' => 'GTID_SUBTRACT',
        ],
        '/Incorrect geohash/i' => [
            '/\bST_LatFromGeoHash\s*\(/i'  => 'ST_LatFromGeoHash',
            '/\bST_LongFromGeoHash\s*\(/i' => 'ST_LongFromGeoHash',
            '/\bST_PointFromGeoHash\s*\(/i' => 'ST_PointFromGeoHash',
        ],
    ];

    foreach ($detectionRules as $errorPattern => $funcRules) {
        if (preg_match($errorPattern, $errorMessage)) {
            foreach ($funcRules as $funcPattern => $funcName) {
                if (preg_match($funcPattern, $userInput)) {
                    return $funcName;
                }
            }
        }
    }

    return null;
}

/**
 * 记录报错注入函数成就（全局共享模式）
 *
 * @param PDO    $pdo          数据库连接
 * @param string $functionName 函数标识名
 */
function recordAchievement($pdo, $functionName)
{
    $sql = "INSERT INTO heasec_errsi_achievements (function_name, success_count, first_success_at, last_success_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            success_count = success_count + 1,
            last_success_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$functionName]);
}

/**
 * 获取全局成就状态（全局共享模式）
 *
 * @param PDO $pdo 数据库连接
 * @return array 包含 achieved_count, records[]
 */
function getAchievementStatus($pdo)
{
    $sql = "SELECT function_name, success_count, first_success_at
            FROM heasec_errsi_achievements
            ORDER BY first_success_at ASC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $records = [];
    foreach ($rows as $row) {
        $displayName = getFunctionDisplayName($row['function_name']);
        $records[] = [
            'name'  => $displayName,
            'count' => $row['success_count']
        ];
    }

    return [
        'achieved_count' => count($rows),
        'records'       => $records
    ];
}

/**
 * 函数标识到显示名称的映射
 *
 * @param string $functionName 函数标识名
 * @return string 显示名称
 */
function getFunctionDisplayName($functionName)
{
    $map = [
        'extractvalue'        => 'extractvalue() XPath报错',
        'updatexml'           => 'updatexml() XPath报错',
        'floor'               => 'floor() 主键冲突报错',
        'GTID_SUBSET'         => 'GTID_SUBSET() GTID报错',
        'GTID_SUBTRACT'       => 'GTID_SUBTRACT() GTID报错',
        'ST_LatFromGeoHash'   => 'ST_LatFromGeoHash() 空间函数报错',
        'ST_LongFromGeoHash'  => 'ST_LongFromGeoHash() 空间函数报错',
        'ST_PointFromGeoHash' => 'ST_PointFromGeoHash() 空间函数报错',
        'other'               => '其他函数报错注入',
    ];
    return isset($map[$functionName]) ? $map[$functionName] : $functionName . '() 报错注入';
}

/**
 * 生成进度提示文本
 *
 * @param int $currentCount 当前已解锁的函数数量
 * @return string 进度提示
 */
function generateProgressHint($currentCount)
{
    $thresholds = [1, 3, 5];
    $titles = ['', '报错新手(1星)', '报错探索者(2星)', '报错大师(3星)'];

    if ($currentCount >= 5) {
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
        return '还差 ' . $remaining . ' 个新函数解锁 ' . $titles[$nextStarIndex];
    }

    return '';
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
            FROM heasec_errsi_services
            ORDER BY id ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
