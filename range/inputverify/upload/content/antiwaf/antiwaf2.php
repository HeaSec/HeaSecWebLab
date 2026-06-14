<?php
/**
 * HeaSec天积安全团队 - 文件上传WAF对抗绕过靶场（第二关）
 * 版本: v2.0.0
 * 创建日期: 2026-01-27
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第二关：递归解码检测
 * - 先URL解码，再base64解码，循环直到不是编码格式
 * - 漏洞点：URL解码后检测到base64编码则跳过检测
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传WAF对抗绕过 Range v2.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传WAF对抗绕过靶场（第二关）';
$rangeName = '文件上传WAF对抗绕过②';
$showVersion = false;
$showResetButton = false; // 不使用数据库，隐藏顶部导航栏的数据库重置按钮
$version = 'v2.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('antiwaf2');

// 验证会话完整性
HeaSec_ValidateSession();

// 创建images目录（用于存储上传文件和secret.php文件）
$imagesDir = __DIR__ . '/images/';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 进入新关卡时自动清理images目录（仅GET请求且本次会话首次访问时执行，避免干扰上传和重置操作）
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['heasec_antiwaf2_images_cleaned'])) {
    $files = glob($imagesDir . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'secret.php') {
                @unlink($file);
            }
        }
    }
    $_SESSION['heasec_antiwaf2_images_cleaned'] = true;
}

// 处理AJAX重置请求（返回JSON）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reset') {
    header('Content-Type: application/json');

    try {
        // 记录调试日志
        error_log('[HeaSec antiwaf2] 开始重置文件列表');
        error_log('[HeaSec antiwaf2] images目录路径: ' . $imagesDir);

        // 检查目录是否存在
        if (!file_exists($imagesDir)) {
            error_log('[HeaSec antiwaf2] images目录不存在');
            echo json_encode([
                'success' => false,
                'message' => '目录不存在'
            ]);
            exit;
        }

        // 检查目录是否可写
        if (!is_writable($imagesDir)) {
            error_log('[HeaSec antiwaf2] images目录不可写');
            echo json_encode([
                'success' => false,
                'message' => '目录无写入权限'
            ]);
            exit;
        }

        // 删除images目录中的所有文件（除了secret.php）
        $files = glob($imagesDir . '*');
        $deletedCount = 0;
        $failedFiles = [];

        error_log('[HeaSec antiwaf2] 找到文件数量: ' . ($files !== false ? count($files) : 0));

        if ($files !== false) {
            foreach ($files as $file) {
                $fileName = basename($file);
                if (is_file($file) && $fileName !== 'secret.php') {
                    if (unlink($file)) {
                        $deletedCount++;
                        error_log('[HeaSec antiwaf2] 删除成功: ' . $fileName);
                    } else {
                        $failedFiles[] = $fileName;
                        error_log('[HeaSec antiwaf2] 删除失败: ' . $fileName);
                    }
                }
            }
        }

        // 清除首次访问标记，以便下次访问时重新清理
        unset($_SESSION['heasec_antiwaf2_images_cleaned']);

        error_log('[HeaSec antiwaf2] 删除完成: 成功' . $deletedCount . '个, 失败' . count($failedFiles) . '个');

        echo json_encode([
            'success' => true,
            'message' => '已重置所有上传的文件！',
            'deletedCount' => $deletedCount,
            'failedCount' => count($failedFiles),
            'failedFiles' => $failedFiles
        ]);
    } catch (Exception $e) {
        error_log('[HeaSec antiwaf2] 重置异常: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '重置失败：' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 获取或生成会话中的秘密字符串
 * 使用新的会话管理组件
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
 * 判断内容是否为base64编码格式
 *
 * @param string $content 待检测内容
 * @return bool 是否为base64编码
 */
function isBase64Encoded($content)
{
    // 去除首尾空白
    $content = trim($content);

    // base64编码特征：只包含A-Za-z0-9+/=，且长度是4的倍数
    if (empty($content) || strlen($content) % 4 !== 0) {
        return false;
    }

    // 检查是否只包含base64字符
    if (!preg_match('/^[A-Za-z0-9\/\r\n+]*={0,2}$/', $content)) {
        return false;
    }

    // 尝试解码验证
    $decoded = @base64_decode($content, true);
    if ($decoded === false) {
        return false;
    }

    // 解码后应该包含可打印字符
    if (preg_match('/[^\x20-\x7E\n\r\t]/', $decoded)) {
        return false;
    }

    return true;
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
        // 使用词边界和函数调用模式匹配
        if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $content)) {
            $detectedFunctions[] = $func;
        }
    }
    return $detectedFunctions;
}

/**
 * WAF内容检测函数（第二关：编码检测绕过）
 *
 * 检测逻辑：
 * 1. 对原生无编码内容，直接检测危险函数
 * 2. 检测到URL编码，URL解码后检测危险函数
 * 3. 若检测到base64编码，直接跳过检测不解码（漏洞点）
 *
 * 漏洞原理：WAF检测到base64编码时跳过检测，但服务器保存时会自动解码base64内容，
 * 导致WAF检测与服务器解析逻辑不一致，攻击者可以上传base64编码的恶意文件。
 *
 * @param string $filePath 文件路径
 * @return array 检测结果
 */
function heasecWAFContentCheck($filePath)
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

    // 读取文件前500个字符
    $handle = @fopen($filePath, 'r');
    if (!$handle) {
        return ['detected' => false, 'functions' => [], 'decoding_path' => '', 'needs_decode' => false];
    }
    $content = fread($handle, 500);
    fclose($handle);

    $decodingPath = 'original';
    $needsDecode = false; // 标记是否需要解码保存

    // 步骤1：先检测原始内容
    $detectedFunctions = detectDangerousFunctions($content, $dangerousFunctions);
    if (count($detectedFunctions) > 0) {
        return [
            'detected' => true,
            'functions' => $detectedFunctions,
            'decoding_path' => $decodingPath,
            'needs_decode' => false
        ];
    }

    // 步骤2：检测URL编码
    if (preg_match('/%[0-9A-Fa-f]{2}/', $content)) {
        $decodedContent = urldecode($content);
        $decodingPath .= ' -> urldecode';

        // 检测URL解码后是否为base64编码（漏洞点）
        if (isBase64Encoded($decodedContent)) {
            // URL解码后检测到base64，跳过检测
            // 但需要标记解码，因为服务器会自动解码保存
            return [
                'detected' => false,
                'functions' => [],
                'decoding_path' => $decodingPath . ' -> base64(detected, skipped)',
                'bypassed' => true,
                'bypass_reason' => 'URL解码后检测到base64编码，跳过检测',
                'needs_decode' => true,
                'decode_type' => 'url_base64'  // 先URL解码，再base64解码
            ];
        }

        // 检测URL解码后的内容
        $detectedFunctions = detectDangerousFunctions($decodedContent, $dangerousFunctions);
        if (count($detectedFunctions) > 0) {
            return [
                'detected' => true,
                'functions' => $detectedFunctions,
                'decoding_path' => $decodingPath,
                'needs_decode' => false
            ];
        }
    }

    // 步骤3：检测base64编码
    if (isBase64Encoded($content)) {
        // 漏洞点：检测到base64编码，直接跳过检测不解码
        return [
            'detected' => false,
            'functions' => [],
            'decoding_path' => $decodingPath . ' -> base64(detected, skipped)',
            'bypassed' => true,
            'bypass_reason' => '检测到base64编码，跳过检测',
            'needs_decode' => true,
            'decode_type' => 'base64'  // 直接base64解码
        ];
    }

    return [
        'detected' => false,
        'functions' => [],
        'decoding_path' => $decodingPath,
        'needs_decode' => false
    ];
}

// 处理文件上传
$message = '';
$messageType = '';
$uploadedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadedFile = $_FILES['avatar'];

    // 服务器端进行文件内容校验
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($uploadedFile['name']);

        // 安全的文件名处理
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $fileName = trim($fileName, '._-'); // 防止以特殊字符开头或结尾

        // 文件名长度检查
        if (empty($fileName) || strlen($fileName) > 255) {
            $message = '文件名不合法！';
            $messageType = 'error';
        } elseif (strlen($fileName) < 1) {
            $message = '文件名过短！';
            $messageType = 'error';
        } else {
            $targetPath = $imagesDir . $fileName;

            // 文件大小限制（10MB）
            $maxSize = 10 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxSize) {
                $message = '文件大小超过限制（10MB）！';
                $messageType = 'error';
            } else {
                // WAF内容检测（第二关：编码检测绕过）
                $wafResult = heasecWAFContentCheck($uploadedFile['tmp_name']);

                if ($wafResult['detected']) {
                    // 检测到恶意代码 - 阻止上传
                    $message = '检测到存在恶意代码特征，请遵纪守法';
                    $messageType = 'waf_warning';
                } else {
                    // 绕过检测 - 处理文件保存
                    $fileSaved = false;

                    // 检查是否需要解码保存（WAF与服务器解析不一致）
                    if (isset($wafResult['needs_decode']) && $wafResult['needs_decode']) {
                        // 读取上传文件内容
                        $fileContent = file_get_contents($uploadedFile['tmp_name']);
                        $decodedContent = $fileContent;

                        // 根据解码类型进行解码
                        if (isset($wafResult['decode_type'])) {
                            if ($wafResult['decode_type'] === 'base64') {
                                // 直接base64解码
                                $decodedContent = base64_decode($fileContent);
                            } elseif ($wafResult['decode_type'] === 'url_base64') {
                                // 先URL解码，再base64解码
                                $urlDecoded = urldecode($fileContent);
                                $decodedContent = base64_decode($urlDecoded);
                            }
                        }

                        // 保存解码后的内容
                        $fileSaved = file_put_contents($targetPath, $decodedContent) !== false;
                    } else {
                        // 不需要解码，直接移动上传文件
                        $fileSaved = move_uploaded_file($uploadedFile['tmp_name'], $targetPath);
                    }

                    if ($fileSaved) {
                        // 检查是否是PHP文件
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
        }
    } else {
        // 详细的错误处理
        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $message = '文件大小超过php.ini中的限制！';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = '文件大小超过表单中的限制！';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = '文件只有部分被上传！';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = '没有文件被上传！';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = '缺少临时目录！';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = '文件写入失败！';
                break;
            default:
                $message = '未知上传错误！';
                break;
        }
        $messageType = 'error';
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
// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入密码验证卡片组件 -->
<?php
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
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
                    开发小王：根据需求终于实现网站对URL和Base64<br>编码格式文件的解析识别了，这回可以放心了...
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
        // 禁用恭喜弹窗，改为显示下一关按钮
        'enableCongrats' => false
    ]);
    ?>

    <!-- 下一关按钮（验证成功后显示） -->
    <div id="nextLevelContainer" style="display: none; margin-top: 20px; text-align: center;">
        <a href="antiwaf3.php" id="nextLevelBtn" class="next-level-button">
            <i class="fa fa-arrow-right"></i> 下一关
        </a>
    </div>
</div>

<!-- JavaScript基础功能 -->
<script src="js/script.js"></script>
<script>
/**
 * 监听秘密验证成功后显示"下一关"按钮
 */
document.addEventListener('DOMContentLoaded', function() {
    // 使用MutationObserver监听验证结果区域的变化
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            // 检查是否有验证成功的消息
            var resultContainers = document.querySelectorAll('[id^="validation-result-"]');
            resultContainers.forEach(function(container) {
                if (container.innerHTML.indexOf('alert-success') !== -1) {
                    // 验证成功，显示下一关按钮
                    var nextLevelContainer = document.getElementById('nextLevelContainer');
                    if (nextLevelContainer) {
                        nextLevelContainer.style.display = 'block';
                    }
                }
            });
        });
    });

    // 监听整个文档的变化
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>

<?php
// 引入公共底部
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>