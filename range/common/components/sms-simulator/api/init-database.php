<?php
/**
 * HeaSec天积安全团队 - 数据库初始化API
 * API: Initialize Database
 * 版本: v1.0.0
 * 创建日期: 2026-01-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 执行数据库初始化脚本
 *   - 创建必要的表结构
 *   - 插入默认数据
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SMS Simulator API v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入必要的文件
require_once dirname(dirname(dirname(__DIR__))) . '/includes/HeaSec_Database.php';

try {
    // 读取SQL文件
    $sqlFile = __DIR__ . '/../database/init_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL文件不存在: ' . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);

    // 连接数据库（先连接到mysql系统数据库）
    $pdo = HeaSec_Database::getConnection('mysql');

    // 检查heasec_common数据库是否存在，不存在则创建
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `heasec_common` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 切换到heasec_common数据库
    $pdo->exec("USE `heasec_common`");

    // 执行SQL语句（移除USE语句，因为已经切换了数据库）
    $sql = preg_replace('/^USE\s+`[^`]+`;?\s*/im', '', $sql);

    // 分割SQL语句并执行
    $statements = explode(';', $sql);
    $executedCount = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executedCount++;
            } catch (PDOException $e) {
                // 忽略DROP TABLE IF NOT EXISTS的错误
                if (strpos($e->getMessage(), 'DROP TABLE') === false) {
                    throw $e;
                }
            }
        }
    }

    // 返回成功响应
    echo json_encode(array(
        'success' => true,
        'message' => '数据库初始化成功',
        'database' => 'heasec_common',
        'executed_statements' => $executedCount
    ));

} catch (PDOException $e) {
    // PDO异常
    echo json_encode(array(
        'success' => false,
        'error' => 'pdo_error',
        'message' => '数据库操作失败: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    // 其他异常
    echo json_encode(array(
        'success' => false,
        'error' => 'unknown_error',
        'message' => '初始化失败: ' . $e->getMessage()
    ));
}
?>
