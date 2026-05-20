<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场初始化与重置辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 执行靶场重置。
 */
function sqlibase_execute_reset()
{
    $initSqlFile = dirname(__DIR__) . '/database/init_database.sql';
    if (!is_file($initSqlFile)) {
        throw new Exception('初始化脚本不存在');
    }

    // 清空截图目录
    $screenshotDir = sqlibase_get_screenshot_directory();
    if (is_dir($screenshotDir)) {
        foreach (glob($screenshotDir . '*') as $file) {
            if (is_file($file) && basename($file) !== 'index.html') {
                unlink($file);
            }
        }
    }

    // 执行SQL初始化脚本
    $sqlContent = file_get_contents($initSqlFile);
    $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

    $serverPdo = HeaSec_Database::getServerConnection();
    $serverPdo->beginTransaction();

    try {
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            $serverPdo->exec($statement);
        }
        $serverPdo->commit();
    } catch (Exception $exception) {
        if ($serverPdo->inTransaction()) {
            $serverPdo->rollBack();
        }
        throw $exception;
    }

    // 销毁并重建会话
    HeaSec_SessionManager::destroySession();
    HeaSec_InitRangeSession('sqlibase');
    HeaSec_ValidateSession();
}
