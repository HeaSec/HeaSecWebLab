<?php
/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场 - 公共函数库
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
    header('X-HeavenlySecret: HeaSec BSIAdv Range v1.0.0');
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
function generateRandomString($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 确保指定关卡的通关密码已生成
 * 密码存储在对应业务表中的随机一条记录中
 * @param int $level 关卡编号（1/2/3）
 */
function ensurePasswordExists($level) {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    $tableMap = [
        1 => ['table' => 'heasec_bsiadv_orders', 'field' => 'order_secret'],
        2 => ['table' => 'heasec_bsiadv_members', 'field' => 'member_key'],
        3 => ['table' => 'heasec_bsiadv_tokens',  'field' => 'token_value'],
    ];

    $table = $tableMap[$level]['table'];
    $field = $tableMap[$level]['field'];

    try {
        $pdo->beginTransaction();

        // 检查是否已有密码存在
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$table} WHERE {$field} != ''");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row && $row['cnt'] > 0) {
            $pdo->commit();
            return;
        }

        // 先清空所有记录的密码字段
        $pdo->exec("UPDATE {$table} SET {$field} = ''");

        // 生成8位随机密码
        $password = generateRandomString(8);

        // 获取总记录数，随机选中一条记录写入密码
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM {$table}");
        $countRow = $countStmt ? $countStmt->fetch(PDO::FETCH_ASSOC) : null;
        $totalRecords = $countRow ? $countRow['total'] : 5;

        // 随机选择一条记录
        $randomIndex = mt_rand(1, $totalRecords);
        $updateSql = "UPDATE {$table} SET {$field} = '{$password}' WHERE id = {$randomIndex}";
        $pdo->exec($updateSql);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

/**
 * 获取指定关卡的通关密码
 * @param int $level 关卡编号
 * @return string|false 密码字符串或失败时返回false
 */
function getPasscode($level) {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    $tableMap = [
        1 => ['table' => 'heasec_bsiadv_orders', 'field' => 'order_secret'],
        2 => ['table' => 'heasec_bsiadv_members', 'field' => 'member_key'],
        3 => ['table' => 'heasec_bsiadv_tokens',  'field' => 'token_value'],
    ];

    $table = $tableMap[$level]['table'];
    $field = $tableMap[$level]['field'];

    $stmt = $pdo->query("SELECT {$field} as pwd FROM {$table} WHERE {$field} != '' LIMIT 1");
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    return ($row && $row['pwd'] !== '') ? $row['pwd'] : false;
}

/**
 * 第一关WAF过滤：拦截比较符号
 * 拦截：= != > < >= <= <>
 * 复合符号先检查，避免子串误判
 * @param string $input 用户输入
 * @return bool true通过 false拦截
 */
function waf_level1($input) {
    $blocked = ['>=', '<=', '<>', '!=', '=', '>', '<'];
    foreach ($blocked as $pattern) {
        if (strpos($input, $pattern) !== false) {
            return false;
        }
    }
    return true;
}

/**
 * 第二关WAF过滤：拦截逗号
 * @param string $input 用户输入
 * @return bool true通过 false拦截
 */
function waf_level2($input) {
    if (strpos($input, ',') !== false) {
        return false;
    }
    return true;
}

/**
 * 第三关WAF过滤：拦截判断语句关键字
 * 拦截：IF( IF ( CASE WHEN THEN ELSE END（不区分大小写）
 * @param string $input 用户输入
 * @return bool true通过 false拦截
 */
function waf_level3($input) {
    // 拦截IF语句变体（不区分大小写，兼容IF(和IF (中间有空格）
    if (preg_match('/\bIF\s*\(/i', $input)) {
        return false;
    }
    // 拦截CASE、WHEN、THEN、ELSE、END（不区分大小写）
    $blocked = ['case', 'when', 'then', 'else', 'end'];
    foreach ($blocked as $pattern) {
        if (stripos($input, $pattern) !== false) {
            return false;
        }
    }
    return true;
}
