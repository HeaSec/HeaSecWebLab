<?php
/**
 * HeaSec天积安全团队 - SSRF漏洞靶场步骤提示接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 获取当前步骤提示信息和已探测端口列表
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SSRF Range v1.0.0');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('ssrf');

require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = HeaSec_Database::getConnection('heasec_base');
    $sessionId = session_id();

    $progress = getOrCreateProgress($pdo, $sessionId);
    $currentStep = (int)$progress['current_step'];

    // 获取步骤提示
    $rangeBasePath = realpath(__DIR__ . '/..');
    $metadataUrl = generateMetadataUrl();
    list($taskText, $hintText) = getStepHints($progress, $rangeBasePath, $metadataUrl);

    // 获取已探测端口列表
    $stmt = $pdo->prepare("SELECT target_host, port, is_open, probed_at FROM heasec_ssrf_ports WHERE session_id = ? ORDER BY probed_at DESC");
    $stmt->execute([$sessionId]);
    $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取数据库连接信息（步骤3完成后显示）
    $dbInfo = null;
    if ($progress['step3_completed'] == 1) {
        $projectRoot = realpath(__DIR__ . '/' . $commonBasePath . '../../');
        $configJson = json_decode(file_get_contents($projectRoot . '/config/config.json'), true);
        $dbInfo = [
            'host' => $configJson['database']['host'] ?? 'localhost',
            'port' => $configJson['database']['port'] ?? 3306,
            'username' => $configJson['database']['username'] ?? 'root',
            'database' => 'heasec_base'
        ];
    }

    echo json_encode([
        'success' => true,
        'current_step' => $currentStep,
        'task_text' => $taskText,
        'hint_text' => $hintText,
        'ports' => $ports,
        'db_info' => $dbInfo
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '获取提示信息失败']);
}
