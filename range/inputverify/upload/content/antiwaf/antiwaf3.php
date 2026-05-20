<?php
/**
 * HeaSec天积安全团队 - 文件上传WAF对抗绕过靶场（第三关）
 * 版本: v3.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第三关：双重上传数据包绕过
 * - WAF 只检测第一个 multipart 上传部分的内容
 * - 漏洞点：攻击者构造两个上传部分，第一个是正常文件，第二个是恶意文件
 * - WAF 检测第一个通过后放行，但服务端保存的是第二个文件
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传WAF对抗绕过 Range v3.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传WAF对抗绕过靶场（第三关）';
$rangeName = '文件上传WAF对抗绕过③';
$showVersion = false;
$showResetButton = false; // 不使用数据库，隐藏顶部导航栏的数据库重置按钮
$version = 'v3.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('antiwaf3');

// 验证会话完整性
HeaSec_ValidateSession();

// 创建images目录（用于存储上传文件和secret.php文件）
$imagesDir = __DIR__ . '/images/';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 处理AJAX重置请求（返回JSON）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reset') {
    header('Content-Type: application/json');

    try {
        error_log('[HeaSec antiwaf3] 开始重置文件列表');
        error_log('[HeaSec antiwaf3] images目录路径: ' . $imagesDir);

        if (!file_exists($imagesDir)) {
            error_log('[HeaSec antiwaf3] images目录不存在');
            echo json_encode([
                'success' => false,
                'message' => '目录不存在'
            ]);
            exit;
        }

        if (!is_writable($imagesDir)) {
            error_log('[HeaSec antiwaf3] images目录不可写');
            echo json_encode([
                'success' => false,
                'message' => '目录无写入权限'
            ]);
            exit;
        }

        $files = glob($imagesDir . '*');
        $deletedCount = 0;
        $failedFiles = [];

        error_log('[HeaSec antiwaf3] 找到文件数量: ' . count($files));

        foreach ($files as $file) {
            $fileName = basename($file);
            if (is_file($file) && $fileName !== 'secret.php') {
                if (unlink($file)) {
                    $deletedCount++;
                    error_log('[HeaSec antiwaf3] 删除成功: ' . $fileName);
                } else {
                    $failedFiles[] = $fileName;
                    error_log('[HeaSec antiwaf3] 删除失败: ' . $fileName);
                }
            }
        }

        error_log('[HeaSec antiwaf3] 删除完成: 成功' . $deletedCount . '个, 失败' . count($failedFiles) . '个');

        echo json_encode([
            'success' => true,
            'message' => '已重置所有上传的文件！',
            'deletedCount' => $deletedCount,
            'failedCount' => count($failedFiles),
            'failedFiles' => $failedFiles
        ]);
    } catch (Exception $e) {
        error_log('[HeaSec antiwaf3] 重置异常: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '重置失败：' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 获取或生成会话中的秘密字符串
 */
function getSecret()
{
    return HeaSec_GetSecret(20);
}

// 获取或生成秘密字符串
$secret = getSecret();

// 创建secret.php文件（放在images目录下，添加访问控制）
$secretFile = __DIR__ . '/images/secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问
 */
defined("HEASEC_RANGE_ACCESS") or die("Direct access not allowed");
echo "' . $secret . '";
?>';
file_put_contents($secretFile, $secretContent);

/**
 * 获取请求的Content-Length
 *
 * @return int|null Content-Length值，不存在则返回null
 */
function getRequestContentLength()
{
    if (isset($_SERVER['CONTENT_LENGTH'])) {
        return (int)$_SERVER['CONTENT_LENGTH'];
    }
    if (isset($_SERVER['HTTP_CONTENT_LENGTH'])) {
        return (int)$_SERVER['HTTP_CONTENT_LENGTH'];
    }
    return null;
}

/**
 * 检测内容中的危险函数
 *
 * @param string $content 待检测内容
 * @param array $dangerousFunctions 危险函数列表
 * @return array 检测到的危险函数列表
 */
function detectDangerousFunctions($content, $dangerousFunctions)
{
    $detectedFunctions = [];
    foreach ($dangerousFunctions as $func) {
        if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $content)) {
            $detectedFunctions[] = $func;
        }
    }
    return $detectedFunctions;
}

/**
 * WAF内容检测函数（第三关：Content-Type混淆绕过）
 *
 * 检测逻辑：
 * 1. 尝试从原始请求体解析 multipart 数据
 * 2. 如果成功解析，只检测第一个上传部分的内容
 * 3. 如果原始请求体为空（PHP的php://input无法读取multipart），从$_FILES读取
 * 4. 漏洞点：根据请求中声明的Content-Type判断是否检测文件内容
 *
 * 漏洞原理（Content-Type混淆绕过）：
 * - WAF根据请求中声明的Content-Type判断文件类型
 * - 如果Content-Type是图片类型（image/jpeg等），WAF跳过内容检测
 * - 攻击者可以修改请求中的Content-Type来绕过WAF
 *
 * 利用方式：
 * - 上传shell.php，但将Content-Type改为image/jpeg
 * - WAF认为是图片，跳过检测
 * - 服务端根据文件扩展名保存为shell.php
 *
 * @param string $rawInput 原始请求体内容
 * @param string $contentType Content-Type 头
 * @return array 检测结果
 */
function heasecWAFContentCheck($rawInput, $contentType)
{
    // 常用危险函数列表
    $dangerousFunctions = [
        'eval',
        'system',
        'exec',
        'passthru',
        'shell_exec',
        'assert',
        'preg_replace',
        'create_function',
        'call_user_func',
        'file_put_contents',
        'fwrite',
        'fopen',
        'include',
        'require',
        'include_once',
        'require_once',
        'file_get_contents',
        'file'
    ];

    // 检查是否是 multipart 请求
    $isMultipart = (strpos($contentType, 'multipart/form-data') !== false);

    // 如果原始请求体不为空，尝试解析multipart数据并检测第一个上传部分
    if (!empty($rawInput) && $isMultipart) {
        return heasecWAFCheckFromRawInput($rawInput, $contentType, $dangerousFunctions);
    }

    // 如果原始请求体为空（PHP的php://input无法读取multipart数据）
    // 尝试从 $_FILES 读取并检测
    if (empty($rawInput) && $isMultipart && isset($_FILES['avatar'])) {
        // 漏洞点说明：
        // 当php://input无法读取原始请求时，WAF只能检测$_FILES中的文件
        // 但$_FILES只保存最后一个同名上传文件
        // 如果攻击者能让php://input可读，并构造双重上传请求
        // WAF会检测第一个文件（正常），而服务端保存最后一个（恶意）
        return heasecWAFCheckFromFiles($dangerousFunctions);
    }

    // 非multipart请求，尝试从原始请求体检测
    if (!empty($rawInput)) {
        $detectedFunctions = detectDangerousFunctions($rawInput, $dangerousFunctions);
        if (count($detectedFunctions) > 0) {
            return [
                'detected' => true,
                'functions' => $detectedFunctions,
                'waf_note' => 'WAF说明：检测到请求体包含恶意代码'
            ];
        }
    }

    return [
        'detected' => false,
        'functions' => [],
        'waf_note' => 'WAF说明：未检测到上传文件或请求格式异常，跳过检测'
    ];
}

/**
 * 从原始请求体解析multipart数据并检测第一个上传部分
 *
 * 漏洞点：解析filename时只获取第一个，如果是图片扩展名则跳过内容检测
 * 这模拟了WAF解析参数时的"第一个参数优先"策略
 *
 * @param string $rawInput 原始请求体内容
 * @param string $contentType Content-Type 头
 * @param array $dangerousFunctions 危险函数列表
 * @return array 检测结果
 */
function heasecWAFCheckFromRawInput($rawInput, $contentType, $dangerousFunctions)
{
    // 提取 boundary
    if (!preg_match('/boundary=([^;]+)/i', $contentType, $matches)) {
        return [
            'detected' => false,
            'functions' => [],
            'waf_note' => 'WAF说明：无法解析 Content-Type，跳过检测'
        ];
    }
    $boundary = trim($matches[1]);

    // 分割各个部分
    $parts = explode('--' . $boundary, $rawInput);
    $uploadParts = [];

    foreach ($parts as $part) {
        // 跳过空部分和结束标记
        if (trim($part) === '' || trim($part) === '--') {
            continue;
        }

        // 查找 header 和 body 的分隔符
        $headerBodySep = "\r\n\r\n";
        $sepPos = strpos($part, $headerBodySep);
        if ($sepPos === false) {
            continue;
        }

        $headers = substr($part, 0, $sepPos);
        $body = substr($part, $sepPos + strlen($headerBodySep));
        $body = rtrim($body, "\r\n");

        // 检查是否是文件上传部分
        // 漏洞点：WAF只解析第一个filename参数
        if (preg_match('/filename="([^"]+)"/i', $headers, $filenameMatches)) {
            $uploadParts[] = [
                'filename' => $filenameMatches[1],  // 只获取第一个filename
                'content' => $body,
                'headers' => $headers
            ];
        }
    }

    // 如果没有上传部分，跳过检测
    if (empty($uploadParts)) {
        return [
            'detected' => false,
            'functions' => [],
            'waf_note' => 'WAF说明：未检测到上传文件，跳过检测'
        ];
    }

    // 漏洞点：只检测第一个上传部分，且只看第一个filename
    $firstPart = $uploadParts[0];
    $firstContent = $firstPart['content'];
    $firstFilename = $firstPart['filename'];

    // 漏洞点：如果第一个filename是图片扩展名，跳过内容检测
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $fileExtension = strtolower(pathinfo($firstFilename, PATHINFO_EXTENSION));

    if (in_array($fileExtension, $imageExtensions)) {
        // WAF认为是图片文件，跳过内容检测
        return [
            'detected' => false,
            'functions' => [],
            'waf_note' => 'WAF说明：检测到图片文件("' . $firstFilename . '")，跳过内容检测'
        ];
    }

    // 非图片文件，检测危险函数
    $detectedFunctions = detectDangerousFunctions($firstContent, $dangerousFunctions);

    if (count($detectedFunctions) > 0) {
        return [
            'detected' => true,
            'functions' => $detectedFunctions,
            'waf_note' => 'WAF说明：检测到第1个上传文件("' . $firstFilename . '")包含恶意代码'
        ];
    }

    // 记录检测到的上传部分数量（用于调试）
    $totalParts = count($uploadParts);
    $additionalNote = $totalParts > 1 ? "（共检测到{$totalParts}个上传部分，仅检测了第1个）" : "";

    return [
        'detected' => false,
        'functions' => [],
        'waf_note' => 'WAF说明：检测第1个上传文件("' . $firstFilename . '")，未发现恶意代码' . $additionalNote,
        'total_parts' => $totalParts
    ];
}

/**
 * 从 $_FILES 读取上传文件内容进行WAF检测
 * 当 php://input 无法读取 multipart 数据时使用此方法
 *
 * 漏洞点：WAF根据请求中声明的Content-Type判断是否检测文件内容
 * 如果Content-Type是图片类型，WAF跳过内容检测
 * 攻击者可以修改请求中的Content-Type来绕过WAF
 *
 * 利用方式：
 * 上传shell.php，但将Content-Type改为image/jpeg，WAF会跳过检测
 *
 * @param array $dangerousFunctions 危险函数列表
 * @return array 检测结果
 */
function heasecWAFCheckFromFiles($dangerousFunctions)
{
    // 检查是否有上传文件
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        return [
            'detected' => false,
            'functions' => [],
            'waf_note' => 'WAF说明：未检测到有效上传文件，跳过检测'
        ];
    }

    $uploadedFile = $_FILES['avatar'];
    $filename = $uploadedFile['name'];
    $tmpName = $uploadedFile['tmp_name'];
    $declaredType = $uploadedFile['type']; // 请求中声明的Content-Type

    // 漏洞点：如果声明的Content-Type是图片类型，跳过内容检测
    $allowedImageTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml'
    ];

    if (in_array(strtolower($declaredType), $allowedImageTypes)) {
        // WAF认为是图片文件，跳过内容检测（漏洞点！）
        return [
            'detected' => false,
            'functions' => []
        ];
    }

    // 非图片类型，读取文件内容进行检测
    $content = file_get_contents($tmpName);
    if ($content === false) {
        return [
            'detected' => false,
            'functions' => []
        ];
    }

    // 检测危险函数
    $detectedFunctions = detectDangerousFunctions($content, $dangerousFunctions);

    if (count($detectedFunctions) > 0) {
        return [
            'detected' => true,
            'functions' => $detectedFunctions
        ];
    }

    return [
        'detected' => false,
        'functions' => [],
        'total_parts' => 1
    ];
}

// 处理文件上传
$message = '';
$messageType = '';
$uploadedFiles = [];

/**
 * 从原始 multipart 请求体中解析所有上传文件
 * 服务端保存最后一个上传的文件
 *
 * 漏洞点：解析filename时获取最后一个（与WAF解析第一个形成差异）
 * 攻击者可利用：filename="1.jpg";filename="shell.php"
 * WAF看到1.jpg跳过检测，服务端保存shell.php
 *
 * @param string $rawInput 原始请求体
 * @param string $contentType Content-Type 头
 * @return array|null 解析出的文件信息，失败返回 null
 */
function parseMultipartFile($rawInput, $contentType)
{
    // 提取 boundary
    if (!preg_match('/boundary=([^;]+)/i', $contentType, $matches)) {
        return null;
    }
    $boundary = trim($matches[1]);

    // 分割各个部分
    $parts = explode('--' . $boundary, $rawInput);

    $lastUploadedFile = null;

    foreach ($parts as $part) {
        // 跳过空部分和结束标记
        if (trim($part) === '' || trim($part) === '--') {
            continue;
        }

        // 查找 header 和 body 的分隔符
        $headerBodySep = "\r\n\r\n";
        $sepPos = strpos($part, $headerBodySep);
        if ($sepPos === false) {
            continue;
        }

        $headers = substr($part, 0, $sepPos);
        $body = substr($part, $sepPos + strlen($headerBodySep));

        // 移除末尾的 \r\n
        $body = rtrim($body, "\r\n");

        // 解析 Content-Disposition 头
        if (preg_match('/name="([^"]+)"/i', $headers, $nameMatches)) {
            $fieldName = $nameMatches[1];

            // 只处理 avatar 字段
            if ($fieldName === 'avatar') {
                // 漏洞点：服务端解析最后一个filename参数
                // 使用preg_match_all获取所有filename，取最后一个
                $fileName = 'unknown';
                if (preg_match_all('/filename="([^"]+)"/i', $headers, $filenameMatches)) {
                    // 获取最后一个filename
                    $fileName = end($filenameMatches[1]);
                }

                // 保存最后一个上传的文件
                $lastUploadedFile = [
                    'name' => $fileName,
                    'content' => $body,
                    'size' => strlen($body)
                ];
            }
        }
    }

    return $lastUploadedFile;
}

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 读取原始请求体
    $rawInput = file_get_contents('php://input');

    // 获取 Content-Type
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    // WAF 内容检测（传入 Content-Type 用于解析 multipart）
    $wafResult = heasecWAFContentCheck($rawInput, $contentType);

    if ($wafResult['detected']) {
        // WAF 检测到恶意代码
        $message = '检测到存在恶意代码特征，请遵纪守法';
        $messageType = 'waf_warning';
    } else {
        // 绕过检测或正常文件，解析 multipart 文件
        $parsedFile = parseMultipartFile($rawInput, $contentType);

        if ($parsedFile !== null && !empty($parsedFile['name'])) {
            $fileName = basename($parsedFile['name']);

            // 安全的文件名处理
            $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $fileName = trim($fileName, '._-');

            if (empty($fileName) || strlen($fileName) > 255) {
                $message = '文件名不合法！';
                $messageType = 'error';
            } else {
                $targetPath = $imagesDir . $fileName;

                // 文件大小限制（10MB）
                $maxSize = 10 * 1024 * 1024;
                if ($parsedFile['size'] > $maxSize) {
                    $message = '文件大小超过限制（10MB）！';
                    $messageType = 'error';
                } else {
                    // 保存文件
                    if (file_put_contents($targetPath, $parsedFile['content']) !== false) {
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if ($fileExtension === 'php') {
                            $message = '恭喜你成功上传php脚本！请寻找秘密并输出';
                            $messageType = 'success';
                        } else {
                            $message = '文件上传成功！';
                            $messageType = 'info';
                        }
                    } else {
                        $message = '文件上传失败，请检查目录权限！';
                        $messageType = 'error';
                    }
                }
            }
        } else {
            // 尝试使用传统的 $_FILES 方式（兼容正常浏览器上传）
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['avatar'];
                $fileName = basename($uploadedFile['name']);

                // 安全的文件名处理
                $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $fileName = trim($fileName, '._-');

                if (!empty($fileName) && strlen($fileName) <= 255) {
                    $targetPath = $imagesDir . $fileName;
                    $maxSize = 10 * 1024 * 1024;

                    if ($uploadedFile['size'] <= $maxSize) {
                        if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            if ($fileExtension === 'php') {
                                $message = '恭喜你成功上传php脚本！请寻找秘密并输出';
                                $messageType = 'success';
                            } else {
                                $message = '文件上传成功！';
                                $messageType = 'info';
                            }
                        } else {
                            $message = '文件上传失败，请检查目录权限！';
                            $messageType = 'error';
                        }
                    } else {
                        $message = '文件大小超过限制（10MB）！';
                        $messageType = 'error';
                    }
                } else {
                    $message = '文件名不合法！';
                    $messageType = 'error';
                }
            } else {
                $message = '未能解析上传的文件';
                $messageType = 'error';
            }
        }
    }
}

// 获取已上传的文件列表（排除secret.php）
$uploadedFiles = [];
if (file_exists($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'secret.php') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($imagesDir . $file)
            ];
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入密码验证卡片组件 -->
<?php
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入自定义样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 文件上传区域 -->
    <div class="upload-section">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-upload"></i>
                请上传一张图片
            </h3>
        </div>
        <div class="tech-card-body">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <!-- 拖拽上传区域 -->
                <div class="upload-dropzone" id="uploadDropzone">
                    <i class="fa fa-cloud-upload fa-3x"
                        style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
                    <p style="margin: 0; color: #666;">点击选择文件或拖拽文件到此处</p>
                    <input type="file" name="avatar" id="avatarInputDropzone">
                </div>

                <!-- 传统文件选择区域 -->
                <div style="margin-bottom: 20px; margin-top: 20px;">
                    <label class="file-input-wrapper">
                        <input type="file" class="file-input" id="avatarInput">
                        <i class="fa fa-folder-open"></i> 选择文件
                    </label>
                    <button type="submit" class="upload-button">
                        <i class="fa fa-cloud-upload"></i> 上传文件
                    </button>
                </div>
                <small style="color: #6c757d;">
                    <i class="fa fa-info-circle"></i>
                    运维小李：为提高业务流畅度，设置了文件类型检测策略，<br>重点检测危险的文件类型...
                </small>
            </form>

            <?php if (!empty($uploadedFiles)): ?>
                <div style="margin-top: 30px;">
                    <h4>已上传的文件：</h4>
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>文件大小</th>
                                <th>预览</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploadedFiles as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['name']); ?></td>
                                    <td class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank"
                                            class="file-link">
                                            <i class="fa fa-eye"></i> 预览
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="button" class="reset-button" onclick="showResetConfirm()">
                        <i class="fa fa-trash"></i> 重置文件列表
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 秘密验证区域 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '输入你发现的秘密',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => '你已经成功找到了服务器端存储的秘密字符串！',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功理解了双重上传数据包绕过 WAF 的原理！',
        'rangeCode' => 'antiwaf3'
    ]);
    ?>
</div>

<!-- JavaScript基础功能 -->
<script src="js/script.js"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
