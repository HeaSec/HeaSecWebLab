<?php
/**
 * HeaSec天积安全团队 - 文件上传WAF对抗绕过靶场（第三关）
 * 版本: v3.1.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第三关：严格关键字校验
 * - WAF 对上传文件内容执行全文危险关键字检测
 * - 对原始内容及多种编码（URL、Base64）解码后的内容进行递归检测
 * - eval 采用精准检测（仅拦截直接执行用户输入的模式），避免对间接调用误判
 * - 仅当危险关键字未以可识别形式出现时才放行
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传WAF对抗绕过 Range v3.1.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传WAF对抗绕过靶场（第三关）';
$rangeName = '文件上传WAF对抗绕过③';
$showVersion = false;
$showResetButton = false; // 不使用数据库，隐藏顶部导航栏的数据库重置按钮
$version = 'v3.1.0';

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

// 进入新关卡时自动清理images目录（仅GET请求且本次会话首次访问时执行，避免干扰上传和重置操作）
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['heasec_antiwaf3_images_cleaned'])) {
    $files = glob($imagesDir . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'secret.php') {
                @unlink($file);
            }
        }
    }
    $_SESSION['heasec_antiwaf3_images_cleaned'] = true;
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

        error_log('[HeaSec antiwaf3] 找到文件数量: ' . ($files !== false ? count($files) : 0));

        if ($files !== false) {
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
        }

        // 清除首次访问标记，以便下次访问时重新清理
        unset($_SESSION['heasec_antiwaf3_images_cleaned']);

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
    // 精准检测 eval 直接执行用户输入的危险模式（如 eval($_POST['x'])）
    // 采用精准匹配而非通用 eval( 关键字，避免对间接 eval 调用产生误判
    if (preg_match('/\beval\s*\(\s*\$_/i', $content)) {
        $detectedFunctions[] = 'eval_direct_user_input';
    }
    // 检测反引号执行运算符（等价 shell_exec，是绕过函数名关键字检测的常见手法）
    // 仅在 PHP 代码上下文中检测，避免对二进制文件（如图片）产生误报
    if (preg_match('/<\?/', $content) && preg_match('/`[^`]+`/', $content)) {
        $detectedFunctions[] = 'backtick_operator';
    }
    return $detectedFunctions;
}

/**
 * 判断内容是否为合法 Base64 编码
 *
 * @param string $content 待检测内容
 * @return bool 是否为 Base64 编码
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

    // 解码后应该包含可打印字符（排除二进制数据误判）
    if (preg_match('/[^\x20-\x7E\n\r\t]/', $decoded)) {
        return false;
    }

    return true;
}

/**
 * 收集原始内容及各层解码后的内容候选
 * 对 URL 编码、Base64 编码递归解码，最多 $maxDepth 层，用于多层关键字检测
 *
 * @param string $content 原始内容
 * @param int $maxDepth 最大递归深度
 * @return array 待检测的内容候选数组
 */
function collectDecodedCandidates($content, $maxDepth = 3)
{
    $seen = [];      // 已处理内容指纹去重
    $result = [];    // 收集到的所有候选内容
    $queue = [$content];
    $depth = 0;

    while ($queue && $depth <= $maxDepth) {
        $nextQueue = [];
        foreach ($queue as $item) {
            $hash = md5($item);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $result[] = $item;

            // 派生：URL 解码
            $urlDecoded = urldecode($item);
            if ($urlDecoded !== $item) {
                $nextQueue[] = $urlDecoded;
            }

            // 派生：Base64 解码（仅当整体符合 Base64 特征，避免对二进制数据误解码）
            if (isBase64Encoded($item)) {
                $b64Decoded = base64_decode($item, true);
                if ($b64Decoded !== false && $b64Decoded !== $item) {
                    $nextQueue[] = $b64Decoded;
                }
            }
        }
        $queue = $nextQueue;
        $depth++;
    }

    return $result;
}

/**
 * WAF内容检测函数（第三关：严格关键字校验）
 *
 * 检测逻辑：
 * 1. 读取整个文件内容（全文检测，避免恶意代码隐藏在文件尾部）
 * 2. 对原始内容及 URL、Base64 各层解码结果逐一进行危险关键字检测
 * 3. 任一层命中即判定为恶意内容并拦截
 *
 * @param string $filePath 待检测文件路径
 * @return array 检测结果 ['detected' => bool, 'functions' => array]
 */
function heasecWAFContentCheck($filePath)
{
    // 常用危险函数列表
    $dangerousFunctions = [
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

    // 读取整个文件内容进行完整检测
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return ['detected' => false, 'functions' => []];
    }

    // 收集原始内容及各层解码结果，逐一检测危险关键字
    $candidates = collectDecodedCandidates($content, 3);
    foreach ($candidates as $candidate) {
        $detectedFunctions = detectDangerousFunctions($candidate, $dangerousFunctions);
        if (count($detectedFunctions) > 0) {
            return [
                'detected' => true,
                'functions' => $detectedFunctions
            ];
        }
    }

    return ['detected' => false, 'functions' => []];
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
                // WAF 严格关键字检测
                $wafResult = heasecWAFContentCheck($uploadedFile['tmp_name']);

                if ($wafResult['detected']) {
                    // 检测到恶意代码 - 阻止上传
                    $message = '检测到存在恶意代码特征，请遵纪守法';
                    $messageType = 'waf_warning';
                } else {
                    // 通过检测，原样保存文件
                    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
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
                    运维小李：升级了WAF内容检测引擎，现在会对文件内容做<br>严格的安全关键字校验，恶意脚本再也混不进来了...
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
        'congratsMessage' => '你成功理解了通过强混淆绕过 WAF 关键字检测的原理！',
        'rangeCode' => 'antiwaf'
    ]);
    ?>
</div>

<!-- JavaScript基础功能 -->
<script src="js/script.js"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
