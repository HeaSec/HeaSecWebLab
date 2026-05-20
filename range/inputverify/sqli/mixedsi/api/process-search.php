<?php
/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场 - 第一关新闻搜索接口
 * 版本: v1.0.0
 * 功能: 新闻搜索处理（UNION注入点）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

$keyword = $_POST['keyword'] ?? '';

if ($keyword === '') {
    sendJsonResponse(false, '请输入搜索关键词');
}

// 安全过滤器
if (!filterLevel1_spaces($keyword)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel1_comments($keyword)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel1_keywords($keyword)) {
    sendJsonResponse(false, '输入包含非法字符');
}

// SQL查询构造
$pdo = HeaSec_Database::getConnection('heasec_sqli');
$sql = "SELECT id, title, content FROM heasec_mixedsi_news WHERE title = ('" . $keyword . "')";

try {
    $stmt = $pdo->query($sql);
    $news = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    // 对输出进行HTML转义
    foreach ($news as &$item) {
        $item['title'] = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
        $item['content'] = htmlspecialchars($item['content'], ENT_QUOTES, 'UTF-8');
    }
    unset($item);
    sendJsonResponse(true, '查询成功', ['news' => $news, 'count' => count($news)]);
} catch (PDOException $e) {
    sendJsonResponse(false, '查询出错');
}
