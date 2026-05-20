<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 重置API
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/image_generator.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

$response = ['success' => false, 'message' => ''];

try {
    // 1. 删除所有图片文件
    $baseDir = dirname(__DIR__);
    $deletedCount = 0;

    // 删除第一关图片
    $level1Dir = $baseDir . '/transcript/';
    $deletedCount += deletePngFiles($level1Dir);

    // 删除第二关图片
    $level2Dir = $baseDir . '/filebac_level2/order/';
    $deletedCount += deleteAllPngFiles($level2Dir);

    // 删除第三关图片
    $level3Dir = $baseDir . '/filebac_level3/idcard/';
    $deletedCount += deletePngFiles($level3Dir);

    // 2. 执行数据库初始化脚本
    $initSqlFile = $baseDir . '/database/init_database.sql';
    if (!file_exists($initSqlFile)) {
        throw new Exception('数据库初始化文件不存在');
    }

    $pdo = HeaSec_Database::getServerConnection();
    $sqlContent = file_get_contents($initSqlFile);

    // 移除注释并分割SQL语句
    $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
    $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

    $pdo->beginTransaction();
    foreach ($sqlStatements as $sql) {
        if (!empty($sql)) {
            $pdo->exec($sql);
        }
    }
    $pdo->commit();

    // 3. 清除会话
    $_SESSION = [];

    $response['success'] = true;
    $response['message'] = '重置成功，已删除 ' . $deletedCount . ' 个图片文件';

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = '重置失败: ' . $e->getMessage();
    error_log('[HeaSec] filebac reset error: ' . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
