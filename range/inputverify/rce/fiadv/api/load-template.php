<?php
/**
 * HeaSec天积安全团队 - 文件包含进阶靶场模板加载接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-17
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = HeaSec_Database::getConnection('heasec_inputverify');

$template = isset($_GET['template']) ? $_GET['template'] : '';

if ($template === '') {
    sendJsonResponse(false, '请指定要加载的模板');
}

// 解析协议类型
$parsedUrl = parse_url($template);
$protocol = strtolower(isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : '');

// 如果没有 scheme，视为本地相对路径
if ($protocol === '') {
    $protocol = 'local';
}

// 协议分发处理
switch ($protocol) {
    case 'local':
        handleLocalInclude($template, $pdo);
        break;
    case 'file':
        handleFileInclude($template, $pdo);
        break;
    case 'php':
        handlePhpWrapper($template, $pdo);
        break;
    case 'data':
        handleDataInclude($template, $pdo);
        break;
    case 'zip':
        handleZipInclude($template, $pdo);
        break;
    case 'phar':
        handlePharInclude($template, $pdo);
        break;
    default:
        handleOtherProtocol($template, $pdo, $protocol);
        break;
}

/**
 * 处理本地文件包含
 */
function handleLocalInclude($template, $pdo)
{
    $basePath = dirname(__DIR__);
    $targetFile = $basePath . '/' . $template;

    // 路径安全检查
    $realPath = realpath($targetFile);
    if ($realPath === false || strpos($realPath, $basePath) !== 0) {
        sendJsonResponse(false, '不允许访问该路径');
    }

    ob_start();
    include($targetFile);
    $content = ob_get_clean();

    // 本地包含不计入成就（正常模板加载）
    sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
}

/**
 * 处理 file:// 协议
 */
function handleFileInclude($template, $pdo)
{
    $filePath = preg_replace('#^file://#i', '', $template);

    // 安全检查：realpath + 靶场目录范围限制
    $realPath = realpath($filePath);
    $basePath = realpath(dirname(__DIR__));
    if ($realPath === false || strpos($realPath, $basePath) !== 0) {
        sendJsonResponse(false, '不允许通过 file:// 协议访问该路径');
    }

    $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

    ob_start();
    include($template);
    $content = ob_get_clean();

    checkAndRecordAchievement($content, $pdo, 'file', $GLOBALS['heasec_rce_token']);
    sendJsonResponse(true, '文件包含成功', ['content' => $content, 'template' => $template]);
}

/**
 * 处理 php:// 伪协议族
 */
function handlePhpWrapper($template, $pdo)
{
    if (stripos($template, 'php://input') !== false) {
        // php://input: 需要检查是否有 POST body
        $postData = file_get_contents('php://input');
        if (empty($postData)) {
            sendJsonResponse(false, '该协议需要携带请求数据才能正常工作');
        }

        $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

        ob_start();
        include($template);
        $content = ob_get_clean();

        checkAndRecordAchievement($content, $pdo, 'php_input', $GLOBALS['heasec_rce_token']);
        sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);

    } elseif (stripos($template, 'php://filter') !== false) {
        // php://filter: 允许正常工作（侦察工具），但不计入成就
        // 修正 resource 路径：相对于靶场根目录解析
        $basePath = dirname(__DIR__);
        $resolvedTemplate = preg_replace_callback(
            '/resource=([^&]+)/',
            function ($matches) use ($basePath) {
                $resource = $matches[1];
                // 如果不是绝对路径，则相对于靶场根目录解析
                if ($resource[0] !== '/' && !preg_match('/^[A-Za-z]:/', $resource)) {
                    $resource = $basePath . '/' . $resource;
                }
                return 'resource=' . $resource;
            },
            $template
        );

        ob_start();
        include($resolvedTemplate);
        $content = ob_get_clean();

        sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);

    } else {
        // 其他 php:// 变体
        $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

        ob_start();
        @include($template);
        $content = ob_get_clean();

        checkAndRecordAchievement($content, $pdo, 'php', $GLOBALS['heasec_rce_token']);
        sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
    }
}

/**
 * 处理 data:// 协议
 */
function handleDataInclude($template, $pdo)
{
    if (!ini_get('allow_url_include')) {
        sendJsonResponse(false, '当前 PHP 配置不支持此操作，请确认 allow_url_include 已设置为 On');
    }

    $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

    ob_start();
    include($template);
    $content = ob_get_clean();

    checkAndRecordAchievement($content, $pdo, 'data', $GLOBALS['heasec_rce_token']);
    sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
}

/**
 * 处理 zip:// 协议
 */
function handleZipInclude($template, $pdo)
{
    if (!ini_get('allow_url_include')) {
        sendJsonResponse(false, '当前 PHP 配置不支持此操作，请确认 allow_url_include 已设置为 On');
    }

    $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

    ob_start();
    include($template);
    $content = ob_get_clean();

    checkAndRecordAchievement($content, $pdo, 'zip', $GLOBALS['heasec_rce_token']);
    sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
}

/**
 * 处理 phar:// 协议
 */
function handlePharInclude($template, $pdo)
{
    if (!ini_get('allow_url_include')) {
        sendJsonResponse(false, '当前 PHP 配置不支持此操作，请确认 allow_url_include 已设置为 On');
    }

    if (!extension_loaded('phar')) {
        sendJsonResponse(false, '系统缺少 PHAR 扩展支持');
    }

    $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

    ob_start();
    include($template);
    $content = ob_get_clean();

    checkAndRecordAchievement($content, $pdo, 'phar', $GLOBALS['heasec_rce_token']);
    sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
}

/**
 * 处理其他协议（非预定义协议，动态识别协议类型并记录成就）
 */
function handleOtherProtocol($template, $pdo, $protocol)
{
    if (!ini_get('allow_url_include')) {
        sendJsonResponse(false, '当前 PHP 配置不支持此操作，请确认 allow_url_include 已设置为 On');
    }

    $GLOBALS['heasec_rce_token'] = bin2hex(random_bytes(8));

    ob_start();
    @include($template);
    $content = ob_get_clean();

    checkAndRecordAchievement($content, $pdo, $protocol, $GLOBALS['heasec_rce_token']);
    sendJsonResponse(true, '模板加载成功', ['content' => $content, 'template' => $template]);
}
