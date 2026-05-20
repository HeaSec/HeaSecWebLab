<?php
/**
 * HeaSec天积安全团队 - SSRF漏洞靶场核心请求接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 处理用户提交的URL请求，根据协议类型分发处理
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SSRF Range v1.0.0');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('ssrf');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => '请输入URL']);
    exit;
}

$sessionId = session_id();

try {
    $pdo = HeaSec_Database::getConnection('heasec_base');

    // 初始化表结构（如果不存在）
    initTablesIfNeeded($pdo);

    $progress = getOrCreateProgress($pdo, $sessionId);
    $currentStep = (int)$progress['current_step'];

    // 解析URL获取协议和目标信息
    $parsed = parse_url($url);
    $protocol = strtolower($parsed['scheme'] ?? '');
    $host = $parsed['host'] ?? '';
    $port = $parsed['port'] ?? 0;

    $response = '';
    $type = 'text';
    $stepCompleted = null;
    $stepHint = null;

    // === 特殊协议处理 ===
    if ($protocol === 'gopher' && $host === '10.66.66.66' && $port === 56379) {
        $type = 'gopher';
            $response = handleSimulatedRedis($url, $pdo, $sessionId);

            // 记录日志
            $preview = substr($response, 0, 200);
            logRequest($pdo, $sessionId, 'gopher', $url, $preview, true);

            // 检查是否获取了秘密字符串（第四步）
            $secret = getOrCreateSecret($pdo, $sessionId);
            if (strpos($response, $secret) !== false && $progress['step4_completed'] == 0) {
                completeStep($pdo, $sessionId, 4);
                $stepCompleted = 4;
        }
    } else {
        // === 正常 cURL 请求流程 ===

        // file:// 协议安全限制
        if ($protocol === 'file') {
            $filePath = $parsed['path'] ?? '';
            $realPath = realpath($filePath);
            $rangeDir = realpath(__DIR__ . '/..');

            //if ($realPath === false || strpos($realPath, $rangeDir) !== 0) {
            //    echo json_encode(['success' => false, 'message' => '访问被拒绝：路径不在允许范围内']);
            //    exit;
            // }
        }

        // 在发起cURL请求前释放session锁，避免对localhost的请求造成session死锁
        session_write_close();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 设置请求标识头
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-SSRF-Token: ' . generateSSRFToken()]);
        // 传递会话信息
        $sessionName = session_name();
        $sessionId = session_id();
        if ($sessionName && $sessionId) {
            curl_setopt($ch, CURLOPT_COOKIE, $sessionName . '=' . $sessionId);
        }
        // 配置协议支持
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_ALL);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 重新打开session以便后续数据库操作使用session_id
        session_start();

        if ($response === false) {
            // 请求失败
            $errMsg = !empty($error) ? $error : '请求失败';
            logRequest($pdo, $sessionId, $protocol, $url, $errMsg, false);

            echo json_encode(['success' => false, 'message' => $errMsg]);
            exit;
        }

        // 根据协议类型进行不同的处理
        switch ($protocol) {
            case 'http':
            case 'https':
                $result = handleHttpResponse($pdo, $sessionId, $url, $response, $progress);
                $type = $result['type'];
                $stepCompleted = $result['step_completed'] ?? null;
                break;

            case 'file':
                $result = handleFileResponse($pdo, $sessionId, $url, $response, $progress);
                $type = 'file';
                $stepCompleted = $result['step_completed'] ?? null;
                break;

            case 'dict':
                $result = handleDictResponse($pdo, $sessionId, $url, $host, $port, $response, $httpCode, $progress);
                $type = 'dict';
                $stepCompleted = $result['step_completed'] ?? null;
                $response = $result['message'];
                break;

            case 'gopher':
                $type = 'gopher';
                $preview = substr($response, 0, 200);
                logRequest($pdo, $sessionId, 'gopher', $url, $preview, true);
                break;

            default:
                $type = 'text';
                logRequest($pdo, $sessionId, $protocol, $url, substr($response, 0, 200), true);
                break;
        }
    }

    // 获取最新进度
    $progress = getOrCreateProgress($pdo, $sessionId);
    $stepHint = getStepHints($progress, realpath(__DIR__ . '/..'), generateMetadataUrl());

    // 对内容进行处理
    if ($type === 'image') {
        // 图片类型：返回成功结果（模拟AI识图正常工作）
        $content = base64_encode($response);
        echo json_encode([
            'success' => true,
            'type' => $type,
            'content' => $content,
            'step' => $stepCompleted,
            'current_step' => (int)$progress['current_step'],
            'step_hint' => $stepHint
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 非图片类型：返回"AI识别失败"（模拟真实识图功能），附带原始响应用于调试
        $rawResponse = mb_substr($response, 0, 5000);

        // 根据协议类型生成不同的错误提示
        switch ($type) {
            case 'file':
                $errorMsg = 'AI识别失败：无法识别该资源为有效图片格式，目标似乎不是图片文件';
                break;
            case 'dict':
                $errorMsg = 'AI识别失败：连接超时或目标服务无响应，请检查URL是否正确';
                break;
            case 'gopher':
                $errorMsg = 'AI识别失败：不支持的协议类型，系统仅支持HTTP/HTTPS图片链接';
                break;
            default:
                $errorMsg = 'AI识别失败：无法从该URL获取有效的图片数据，请确认链接指向的是图片资源';
                break;
        }

        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'type' => $type,
            'raw_response' => $rawResponse,
            'step' => $stepCompleted,
            'current_step' => (int)$progress['current_step'],
            'step_hint' => $stepHint
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '服务器内部错误']);
}

/**
 * 处理HTTP/HTTPS响应
 */
function handleHttpResponse($pdo, $sessionId, $url, $response, $progress) {
    $stepCompleted = null;
    $type = 'text';

    // 判断是否为图片
    $contentType = '';
    if (preg_match('/^\x89PNG|\xFF\xD8\xFF|GIF8|RIF.{1}WEBP|BM/is', $response)) {
        $type = 'image';
    }

    // 检查响应内容中是否包含特定标记
    if (strpos($url, 'metadata.php') !== false && $progress['step1_completed'] == 0) {
        $jsonResp = json_decode($response, true);
        if ($jsonResp && isset($jsonResp['hint'])) {
            completeStep($pdo, $sessionId, 1);
            $stepCompleted = 1;
        }
    }

    // 二进制内容（如图片）用base64编码后记录日志，避免MySQL写入失败
    if ($type === 'image') {
        $preview = '[Binary Image Data] ' . base64_encode(substr($response, 0, 100));
    } else {
        $preview = substr($response, 0, 500);
    }
    logRequest($pdo, $sessionId, 'http', $url, $preview, true, $stepCompleted);

    return ['type' => $type, 'step_completed' => $stepCompleted];
}

/**
 * 处理file://协议响应
 */
function handleFileResponse($pdo, $sessionId, $url, $response, $progress) {
    $stepCompleted = null;

    // 检查响应内容中是否包含特定标记
    if (strpos($url, 'hit.php') !== false && $progress['step2_completed'] == 0) {
        if (strpos($response, 'nextHint') !== false || strpos($response, 'portscan.php') !== false) {
            completeStep($pdo, $sessionId, 2);
            $stepCompleted = 2;
        }
    }

    $preview = substr($response, 0, 500);
    logRequest($pdo, $sessionId, 'file', $url, $preview, true, $stepCompleted);

    return ['step_completed' => $stepCompleted];
}

/**
 * 处理dict://协议响应
 */
function handleDictResponse($pdo, $sessionId, $url, $host, $port, $response, $httpCode, $progress) {
    $stepCompleted = null;

    // 判断端口是否开放
    $isOpen = !empty($response) || ($httpCode >= 200 && $httpCode < 400);

    if ($port > 0 && $port <= 65535) {
        recordOpenPort($pdo, $sessionId, $host, $port, $isOpen);

        // 检查是否探测到了目标端口
        $projectRoot = realpath(__DIR__ . '/../../../../..');
        $configJson = json_decode(file_get_contents($projectRoot . '/config/config.json'), true);
        $dbPort = $configJson['database']['port'] ?? 3306;
        if ($port == $dbPort && $isOpen && $progress['step3_completed'] == 0) {
            completeStep($pdo, $sessionId, 3);
            $stepCompleted = 3;
        }
    }

    $preview = $isOpen ? 'Port ' . $port . ' is open' : 'Port ' . $port . ' is closed';
    logRequest($pdo, $sessionId, 'dict', $url, $preview, $isOpen, $stepCompleted);

    $message = $isOpen
        ? "端口 {$port} 开放\n主机: {$host}\n服务已响应"
        : "端口 {$port} 关闭\n主机: {$host}";

    return ['step_completed' => $stepCompleted, 'message' => $message];
}

/**
 * 初始化数据库表结构（如果不存在）
 */
function initTablesIfNeeded($pdo) {
    $tables = ['heasec_ssrf_secrets', 'heasec_ssrf_progress', 'heasec_ssrf_ports', 'heasec_ssrf_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() == 0) {
            $sqlFile = __DIR__ . '/../database/init_database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
            break;
        }
    }
}
