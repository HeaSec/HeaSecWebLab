<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 第二关留言发布接口
 * 版本: v1.0.0
 * 功能: 留言发布处理（INSERT语句注入+布尔盲注）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';
require_once __DIR__ . '/../includes/functions.php';

HeaSec_InitRangeSession('cuosi');

$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 从配置文件读取密码并设置为MySQL会话变量
$l2pass = getPasscode(2);
if ($l2pass !== false) {
    $pdo->exec("SET @password = '" . addslashes($l2pass) . "'");
}

$content = $_POST['content'] ?? '';

if ($content === '') {
    sendJsonResponse(false, '请输入留言内容');
}

// INSERT语句字符型注入 — VALUES子句中拼接用户输入
$sql = "INSERT INTO heasec_cuosi_messages (user_id, content, created_at) VALUES (1, '" . $content . "', NOW())";

try {
    $stmt = $pdo->query($sql);
    $rowCount = $stmt ? $stmt->rowCount() : 0;
    if ($rowCount > 0) {
        // 发布成功
        sendJsonResponse(true, '留言发布成功', ['success' => true]);
    } else {
        // 发布失败（0行被影响）
        sendJsonResponse(true, '留言发布失败', ['success' => false]);
    }
} catch (PDOException $e) {
    // 不输出SQL错误信息，统一返回发布失败
    sendJsonResponse(true, '留言发布失败', ['success' => false]);
}
