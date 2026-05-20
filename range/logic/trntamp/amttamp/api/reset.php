<?php
/**
 * HeaSec天积安全团队 - 金额篡改靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 金额篡改 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('amttamp');

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getServerConnection();

    // 读取并执行初始化SQL文件
    $initSqlFile = __DIR__ . '/../database/init_database.sql';

    if (file_exists($initSqlFile)) {
        $sqlContent = file_get_contents($initSqlFile);

        // 移除注释
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

        // 分割SQL语句
        $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

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

    // 清除会话中的通关密码和用户信息
    for ($i = 1; $i <= 3; $i++) {
        unset($_SESSION['amttamp_passcode_level' . $i]);
        unset($_SESSION['amttamp_user_id_level' . $i]);
        unset($_SESSION['amttamp_username_level' . $i]);
    }

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
