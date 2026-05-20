<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT密钥注入 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径（从api目录到common目录的相对路径）
$commonBasePath = '../../../../../common/';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场配置和功能文件
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/jwt.php';

try {
    // 1. 重置数据库
    $initSqlPath = dirname(__DIR__) . '/database/init_database.sql';
    if (file_exists($initSqlPath)) {
        $sql = file_get_contents($initSqlPath);

        // 分割SQL语句并执行
        $db = heasec_db('heasec_logic');

        // 使用多查询执行
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
            }
        }
    }

    // 2. 重置RSA密钥对
    JWT_KeyInjection::resetKeys();
    JWT_KeyInjection::generateKeyPair();

    // 3. 清除上传的文件
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => '靶场重置成功'
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Reset error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '重置失败：' . $e->getMessage()
    ]);
}
