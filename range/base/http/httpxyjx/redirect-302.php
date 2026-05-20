<?php
// HTTP 302重定向测试处理文件
// 立即执行302重定向，由浏览器处理跳转

// 添加调试信息到响应头
header('X-HeavenlySecret: HeaSec HTTP 302 Redirect Test', true);
header('X-Test-Timestamp: ' . time(), true);

// 重定向回主页面
$redirectUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/index.php';
header('X-Redirect-Target: ' . $redirectUrl, true);

// 立即执行302重定向
header('HTTP/1.1 302 Found', true, 302);
header('Location: ' . $redirectUrl, true, 302);

// 立即终止脚本执行，确保重定向被发送
exit;
?>