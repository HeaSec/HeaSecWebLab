<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 执行数据备份接口
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

$tableName = $data['table'] ?? '';
$backupFileName = $data['filename'] ?? '';

if (empty($tableName) || empty($backupFileName)) {
    sendJsonResponse(false, '请指定备份表名和文件名');
}

// 白名单限制可备份的表
$allowedTables = ['heasec_code_inj_user'];
if (!in_array($tableName, $allowedTables)) {
    sendJsonResponse(false, '不允许备份该表');
}

// 仅做基本字符清理，不限制文件扩展名
$safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $backupFileName);
if (empty($safeFileName)) {
    sendJsonResponse(false, '文件名无效');
}

$backupDir = dirname(__DIR__) . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$backupFile = $backupDir . $safeFileName;

// 查询数据
$sql = "SELECT * FROM {$tableName}";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 导出为 SQL INSERT 语句格式
$backupContent = "-- HeaSec 数据备份文件\n";
$backupContent .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n";
$backupContent .= "-- 来源表: {$tableName}\n\n";

foreach ($rows as $row) {
    $fields = array_keys($row);
    $values = array_values($row);
    $rawValues = array_map(function ($v) {
        return "'" . $v . "'";
    }, $values);
    $backupContent .= "INSERT INTO {$tableName} (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $rawValues) . ");\n";
}

file_put_contents($backupFile, $backupContent);

sendJsonResponse(true, '备份成功', [
    'filename' => $safeFileName,
    'filepath' => 'backups/' . $safeFileName
]);
