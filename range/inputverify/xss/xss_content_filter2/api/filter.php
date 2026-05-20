<?php
/**
 * HeaSec天积安全团队 - XSS标签与事件组合学习靶场 - 内容过滤API
 * 版本: v1.0.0
 * 创建日期: 2026-02-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: AJAX方式处理XSS内容过滤，返回JSON结果
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS Content Filter2 Filter API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '[HeaSec] 只允许POST请求']);
    exit;
}

// 获取输入
$inputCode = isset($_POST['xss_code']) ? trim($_POST['xss_code']) : '';

if (empty($inputCode)) {
    echo json_encode([
        'success' => true,
        'has_input' => false,
        'is_blocked' => false,
        'blocked_reason' => '',
        'filtered_code' => ''
    ]);
    exit;
}

// 定义禁用规则
$bannedTags = ['script', 'input', 'button'];
$bannedEvents = ['onclick', 'onmouseover', 'onload'];

$isBlocked = false;
$blockedReason = '';

// 检测禁用标签
foreach ($bannedTags as $tag) {
    if (preg_match('/<' . $tag . '/i', $inputCode)) {
        $isBlocked = true;
        $blockedReason = "检测到禁用标签: &lt;{$tag}&gt;";
        break;
    }
}

// 检测禁用事件
if (!$isBlocked) {
    foreach ($bannedEvents as $event) {
        if (preg_match('/' . $event . '/i', $inputCode)) {
            $isBlocked = true;
            $blockedReason = "检测到禁用事件: {$event}";
            break;
        }
    }
}

// 返回结果
echo json_encode([
    'success' => true,
    'has_input' => true,
    'is_blocked' => $isBlocked,
    'blocked_reason' => $blockedReason,
    'filtered_code' => $isBlocked ? '' : $inputCode
]);
?>