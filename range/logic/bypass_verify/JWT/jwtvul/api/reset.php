<?php
/**
 * HeaSec天积安全团队 - JWT基础漏洞靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明：重置数据库和JWT签名密钥
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT基础漏洞 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
require_once '../../../../../common/includes/HeaSec_Database.php';
require_once '../../../../../common/includes/session_manager.php';

// 引入JWT类
require_once '../includes/jwt_level1.php';
require_once '../includes/jwt_level2.php';
require_once '../includes/jwt_level3.php';

try {
    // 1. 重置密钥文件
    JWT_Level1::resetSecret();
    JWT_Level2::resetSecret();
    JWT_Level3::resetSecret();

    // 2. 执行数据库初始化脚本
    $initSqlFile = '../database/init_database.sql';

    if (file_exists($initSqlFile)) {
        $sqlContent = file_get_contents($initSqlFile);

        // 移除注释
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

        // 分割SQL语句
        $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

        $pdo = HeaSec_Database::getServerConnection();
        $pdo->beginTransaction();
        $hasSqlError = false;

        foreach ($sqlStatements as $sql) {
            if (!empty($sql)) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {
                    $hasSqlError = true;
                    error_log('[HeaSec] SQL error: ' . $e->getMessage());
                    break;
                }
            }
        }

        if ($hasSqlError) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => '重置失败，请稍后重试'
            ]);
            exit;
        }

        $pdo->commit();
    }

    HeaSec_SessionManager::destroySession();

    echo json_encode([
        'success' => true,
        'message' => '重置成功'
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Reset error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '重置失败，请稍后重试'
    ]);
}
