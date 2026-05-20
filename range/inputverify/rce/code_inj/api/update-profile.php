<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 更新个人简介接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/../../../common/includes/HeaSec_Database.php';

$pdo = HeaSec_Database::getConnection('heasec_inputverify');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$bio = $data['bio'] ?? '';

if ($bio === '') {
    sendJsonResponse(false, '简介内容不能为空');
}

$sql = "UPDATE heasec_code_inj_user SET bio = :bio WHERE id = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':bio' => $bio]);

sendJsonResponse(true, '个人简介更新成功');
