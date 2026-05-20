<?php
/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 功能: 成员验证处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的成员ID
$memberId = $_POST['member_id'] ?? '';

if ($memberId === '') {
    sendJsonResponse(false, '请输入成员ID');
}

// WAF过滤：拦截逗号
if (!waf_level2($memberId)) {
    sendJsonResponse(false, '请求被WAF拦截：检测到逗号');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 字符型SQL查询 — 直接拼接用户输入
$sql = "SELECT id, name, role FROM heasec_bsiadv_members WHERE member_id = '" . $memberId . "'";

try {
    $stmt = $pdo->query($sql);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($row) {
        sendJsonResponse(true, '验证成功：该成员存在', ['found' => true]);
    } else {
        sendJsonResponse(true, '验证完成：该成员不存在', ['found' => false]);
    }
} catch (PDOException $e) {
    sendJsonResponse(false, '验证出错');
}
