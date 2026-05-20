<?php
/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 功能: 第一关服务器查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的服务器ID
$id = $_POST['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入服务器ID');
}

// 空格过滤器：检测常见空白字符
$blocked_chars = ["\x20", "\x2b", "\x0a", "\x0d", "\x09"];
foreach ($blocked_chars as $char) {
    if (strpos($id, $char) !== false) {
        sendJsonResponse(false, '输入包含被过滤的字符（空格、+、换行符、回车符、制表符）');
    }
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, hostname, status FROM heasec_symbol_servers WHERE id = " . $id;

try {
    $stmt = $pdo->query($sql);
    $results = [];
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
    }
    sendJsonResponse(true, '查询完成', ['results' => $results]);
} catch (PDOException $e) {
    sendJsonResponse(false, '查询失败，请检查输入参数');
}
