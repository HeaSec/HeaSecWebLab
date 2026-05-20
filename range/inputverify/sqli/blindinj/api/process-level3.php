<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 功能: 系统状态检查（时间盲注）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 从数据库读取密码并设置为MySQL会话变量
$varStmt = $pdo->query("SELECT var_value FROM heasec_blindinj_vars WHERE level = 3 AND var_name = 'password3' LIMIT 1");
$varRow = $varStmt ? $varStmt->fetch(PDO::FETCH_ASSOC) : null;
if ($varRow && $varRow['var_value'] !== '') {
    $pdo->exec("SET @password3 = '" . addslashes($varRow['var_value']) . "'");
}

// 接收用户提交的检查参数
$key = $_POST['key'] ?? '';

if ($key === '') {
    sendJsonResponse(false, '请输入检查参数');
}

// 字符型SQL查询 — 直接拼接用户输入
$sql = "SELECT id, check_key, status FROM heasec_blindinj_checks WHERE check_key = '" . $key . "'";

try {
    $stmt = $pdo->query($sql);
    // 无论查询结果如何，始终返回完全相同的响应
    sendJsonResponse(true, '系统检查完成，一切正常', ['status' => 'ok']);
} catch (PDOException $e) {
    // 错误也返回完全相同的响应
    sendJsonResponse(true, '系统检查完成，一切正常', ['status' => 'ok']);
}
