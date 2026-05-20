<?php
/**
 * HeaSec天积安全团队 - 手机短信模拟器状态检查接口
 * SMS Simulator Status Check API
 * 版本: v1.0.0
 * 创建日期: 2026-01-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明:
 *   - 检查数据库初始化状态
 *   - 获取默认手机号信息
 *   - 返回系统状态数据供前端使用
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('HeavenlySecret: HeaSec-API-v1.0.0');

// 定义访问常量并引入数据库组件
define('HEASEC_RANGE_ACCESS', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/HeaSec_Database.php';

// 引入辅助类
require_once dirname(__DIR__) . '/includes/HeaSec_SmsSimulator.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => array(
        'db_initialized' => false,
        'default_phone' => null
    ),
    'timestamp' => time()
);

try {
    // 检查数据库状态
    $dbInitialized = false;
    $pdo = HeaSec_Database::getConnection(HeaSec_SmsSimulator::DB_NAME);
    $sql = "SHOW TABLES LIKE '" . HeaSec_SmsSimulator::TABLE_PREFIX . "%'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $dbInitialized = count($tables) >= 3;

    $response['data']['db_initialized'] = $dbInitialized;

    // 获取默认手机号
    $defaultPhone = null;
    if ($dbInitialized) {
        $defaultPhone = HeaSec_SmsSimulator::getDefaultPhone();
    }
    $response['data']['default_phone'] = $defaultPhone;

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '状态检查成功';

} catch (Exception $e) {
    // 捕获异常
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
    $response['data']['db_initialized'] = false;
    $response['data']['default_phone'] = null;
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
