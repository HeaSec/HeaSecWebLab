<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 获取用户信息接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/../../../common/includes/HeaSec_Database.php';

$pdo = HeaSec_Database::getConnection('heasec_inputverify');

$sql = "SELECT username, email, bio FROM heasec_code_inj_user WHERE id = 1";
$stmt = $pdo->query($sql);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    sendJsonResponse(true, '', ['data' => $user]);
} else {
    sendJsonResponse(false, '用户信息不存在');
}
