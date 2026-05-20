<?php
/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 公共函数库
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
    header('X-HeavenlySecret: HeaSec Symbol Range v1.0.0');
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
 * @param int $level 关卡编号（1/2/3）
 */
function ensurePasswordExists($level) {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    if ($level === 1) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM heasec_symbol_servers WHERE secret_key != ''");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['cnt'] > 0) return;

        $ids = $pdo->query("SELECT id FROM heasec_symbol_servers ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $randomId = $ids[array_rand($ids)];
        $password = generateRandomString(20);
        $pdo->prepare("UPDATE heasec_symbol_servers SET secret_key = ? WHERE id = ?")->execute([$password, $randomId]);

    } elseif ($level === 2) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM heasec_symbol_employees WHERE access_token != ''");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['cnt'] > 0) return;

        $ids = $pdo->query("SELECT id FROM heasec_symbol_employees ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $randomId = $ids[array_rand($ids)];
        $password = generateRandomString(20);
        $pdo->prepare("UPDATE heasec_symbol_employees SET access_token = ? WHERE id = ?")->execute([$password, $randomId]);

    } elseif ($level === 3) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM heasec_symbol_alerts WHERE auth_code != ''");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['cnt'] > 0) return;

        $ids = $pdo->query("SELECT id FROM heasec_symbol_alerts ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            $code = generateRandomString(20);
            $pdo->prepare("UPDATE heasec_symbol_alerts SET auth_code = ? WHERE id = ?")->execute([$code, $id]);
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

    if ($level === 1) {
        $stmt = $pdo->query("SELECT secret_key FROM heasec_symbol_servers WHERE secret_key != '' LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return ($row && $row['secret_key'] !== '') ? $row['secret_key'] : false;
    } elseif ($level === 2) {
        $stmt = $pdo->query("SELECT access_token FROM heasec_symbol_employees WHERE access_token != '' LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return ($row && $row['access_token'] !== '') ? $row['access_token'] : false;
    } elseif ($level === 3) {
        $stmt = $pdo->query("SELECT auth_code FROM heasec_symbol_alerts WHERE alert_type = 'secret' LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return ($row && $row['auth_code'] !== '') ? $row['auth_code'] : false;
    }
    return false;
}
