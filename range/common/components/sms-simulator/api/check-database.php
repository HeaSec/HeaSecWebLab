<?php
/**
 * HeaSec天积安全团队 - 数据库检查API
 * API: Check Database Status
 * 版本: v1.0.0
 * 创建日期: 2026-01-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 检查heasec_common数据库是否存在
 *   - 检查相关表是否存在
 *   - 返回数据库状态信息
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SMS Simulator API v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入必要的文件
require_once '../../../includes/HeaSec_Database.php';

try {
    // 检查数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 检查表是否存在
    $tables = array(
        'heasec_sms_simulator' => false,
        'heasec_sms_log' => false,
        'heasec_sms_message' => false
    );

    // 查询所有表
    $sql = "SHOW TABLES LIKE 'heasec_sms_%'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 标记存在的表
    foreach ($existingTables as $table) {
        if (isset($tables[$table])) {
            $tables[$table] = true;
        }
    }

    // 判断所有表是否都存在
    $allTablesExist = !in_array(false, $tables, true);

    // 如果表存在，检查是否有数据
    $hasData = false;
    if ($allTablesExist) {
        $sql = "SELECT COUNT(*) as count FROM heasec_sms_simulator";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasData = intval($result['count']) > 0;
    }

    // 返回成功响应
    echo json_encode(array(
        'success' => true,
        'database' => 'heasec_common',
        'tables' => $tables,
        'all_tables_exist' => $allTablesExist,
        'has_data' => $hasData,
        'message' => $allTablesExist ? '数据库已初始化' : '数据库未初始化'
    ));

} catch (PDOException $e) {
    // 数据库连接失败
    if ($e->getCode() == '1049') {
        // 数据库不存在
        echo json_encode(array(
            'success' => false,
            'database' => 'heasec_common',
            'error' => 'database_not_exists',
            'message' => '数据库 heasec_common 不存在'
        ));
    } else {
        // 其他错误
        echo json_encode(array(
            'success' => false,
            'database' => 'heasec_common',
            'error' => 'connection_failed',
            'message' => '数据库连接失败: ' . $e->getMessage()
        ));
    }
} catch (Exception $e) {
    // 其他异常
    echo json_encode(array(
        'success' => false,
        'database' => 'heasec_common',
        'error' => 'unknown_error',
        'message' => '未知错误: ' . $e->getMessage()
    ));
}
?>
