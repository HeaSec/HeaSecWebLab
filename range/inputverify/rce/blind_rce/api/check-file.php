<?php
/**
 * HeaSec天积安全团队 - 盲注型命令注入靶场 - 文件检测接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$level = isset($_POST['level']) ? intval($_POST['level']) : 0;

if (!in_array($level, [2, 3])) {
    sendJsonResponse(false, '无效的关卡编号');
}

// 靶场根目录
$baseDir = dirname(__DIR__);

if ($level === 2) {
    // 第二关：检测attack.txt文件
    $attackFile = $baseDir . '/execution/attack.txt';
    $targetFile = $baseDir . '/config/level2/target.txt';

    if (!file_exists($attackFile)) {
        sendJsonResponse(false, '未检测到 execution 目录下的 attack.txt 文件，请检查是否已创建该目录并正确写入文件');
    }

    $attackContent = trim(file_get_contents($attackFile));
    $targetContent = trim(file_get_contents($targetFile));

    if ($attackContent === $targetContent) {
        $passcode = extractPasscode($baseDir . '/config/level2/passcode.php');
        sendJsonResponse(true, '文件检测通过', ['passcode' => $passcode]);
    } else {
        sendJsonResponse(false, 'attack.txt 文件内容不匹配，请检查后重试');
    }

} elseif ($level === 3) {
    // 第三关：检测webshell.php文件
    $webshellFile = $baseDir . '/webshell.php';

    if (!file_exists($webshellFile)) {
        sendJsonResponse(false, '未检测到 webshell.php 文件');
    }

    $content = file_get_contents($webshellFile);

    // 精确检测文件内容是否为指定的一句话木马
    $targetContent = '<?php @eval($_POST[\'heasec\']); ?>';
    $fileContent = trim($content);

    if ($fileContent === $targetContent) {
        $passcode = extractPasscode($baseDir . '/config/level3/passcode.php');
        sendJsonResponse(true, '文件检测通过', ['passcode' => $passcode]);
    } else {
        sendJsonResponse(false, 'webshell.php 文件内容不符合要求，请确保文件内容为指定的一句话木马');
    }
}
