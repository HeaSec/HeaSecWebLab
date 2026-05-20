<?php
/**
 * HeaSec天积安全团队 - SQL关键字过滤靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 功能: 第三关反馈查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的关键词
$keyword = $_POST['keyword'] ?? '';

if ($keyword === '') {
    sendJsonResponse(false, '请输入关键词');
}

// ===== 多重过滤器 =====
// 1. 空白字符过滤（仅允许普通空格，不允许制表符、换行符、回车符等其他空白字符）
$whitespace_chars = ["\x09", "\x0a", "\x0d", "\x0b", "\x0c"];
foreach ($whitespace_chars as $char) {
    if (strpos($keyword, $char) !== false) {
        sendJsonResponse(false, '输入包含被过滤的空白字符');
    }
}

// 2. 注释符过滤
if (preg_match('/--|#|\/\*|\*\//', $keyword)) {
    sendJsonResponse(false, '输入包含被过滤的注释符');
}

// 3. 关键字过滤（大小写不敏感，严格过滤 where、and、or）
$blocked_keywords = '/\b(where|and|or)\b/i';
if (preg_match($blocked_keywords, $keyword)) {
    sendJsonResponse(false, '输入包含被过滤的SQL关键字');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, customer, content, rating FROM heasec_kwbpsi_feedback WHERE content LIKE \"%" . $keyword . "%\"";

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
