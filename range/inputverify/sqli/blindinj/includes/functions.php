<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 公共函数库
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
    header('X-HeavenlySecret: HeaSec BlindInj Range v1.0.0');
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
        $stmt = $pdo->query("SELECT password FROM heasec_blindinj_flag LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row && $row['password'] !== '') {
            return;
        }
        $password = generateRandomString(20);
        $stmt = $pdo->prepare("UPDATE heasec_blindinj_flag SET password = ? WHERE id = 1");
        $stmt->execute([$password]);
    } else {
        $stmt = $pdo->query("SELECT var_value FROM heasec_blindinj_vars WHERE level = " . intval($level) . " LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row && $row['var_value'] !== '') {
            return;
        }
        $length = ($level === 3) ? 10 : 20;
        $password = generateRandomString($length);
        $varName = ($level === 2) ? 'password' : 'password3';
        $stmt = $pdo->prepare("UPDATE heasec_blindinj_vars SET var_value = ? WHERE level = ? AND var_name = ?");
        $stmt->execute([$password, intval($level), $varName]);
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
        $stmt = $pdo->query("SELECT password FROM heasec_blindinj_flag LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return ($row && $row['password'] !== '') ? $row['password'] : false;
    } else {
        $varName = ($level === 2) ? 'password' : 'password3';
        $stmt = $pdo->prepare("SELECT var_value FROM heasec_blindinj_vars WHERE level = ? AND var_name = ? LIMIT 1");
        $stmt->execute([intval($level), $varName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row && $row['var_value'] !== '') ? $row['var_value'] : false;
    }
}
