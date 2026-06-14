<?php
/**
 * HeaSec天积安全团队 - 文件上传WAF对抗绕过靶场
 * 版本: v1.0.0
 * 创建日期: 2026-01-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传WAF对抗绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传WAF对抗绕过靶场';
$rangeName = '文件上传WAF对抗绕过①';
$showVersion = false;
$showResetButton = false; // 不使用数据库，隐藏顶部导航栏的数据库重置按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('antiwaf');

// 验证会话完整性
HeaSec_ValidateSession();

// 创建images目录（用于存储上传文件和secret.php文件）
$imagesDir = __DIR__ . '/images/';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 进入新关卡时自动清理images目录（仅GET请求且本次会话首次访问时执行，避免干扰上传和重置操作）
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['heasec_antiwaf_images_cleaned'])) {
    $files = glob($imagesDir . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'secret.php') {
                @unlink($file);
            }
        }
    }
    $_SESSION['heasec_antiwaf_images_cleaned'] = true;
}

// 处理重置请求（表单POST提交）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    // 删除images目录中的所有文件（除了secret.php）
    $files = glob($imagesDir . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'secret.php') {
                @unlink($file);
            }
        }
    }
    // 清除首次访问标记，以便重定向后重新清理
    unset($_SESSION['heasec_antiwaf_images_cleaned']);
    // 重定向回当前页面，避免刷新时重复提交
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/**
 * 获取或生成会话中的秘密字符串
 * 使用新的会话管理组件
 */
function getSecret() {
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
 * WAF内容检测函数
 * 检测文件前500字符中的危险PHP函数
 *
 * @param string $filePath 文件路径
 * @return array 检测结果 ['detected' => bool, 'functions' => array]
 */
function heasecWAFContentCheck($filePath) {
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
        return ['detected' => false, 'functions' => []];
    }
    $content = fread($handle, 500);
    fclose($handle);

    // 检测危险函数（使用词边界匹配避免误报）
    $detectedFunctions = [];
    foreach ($dangerousFunctions as $func) {
        // 使用词边界和函数调用模式匹配
        if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $content)) {
            $detectedFunctions[] = $func;
        }
    }

    return [
        'detected' => count($detectedFunctions) > 0,
        'functions' => $detectedFunctions
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
                // WAF内容检测
                $wafResult = heasecWAFContentCheck($uploadedFile['tmp_name']);

                if ($wafResult['detected']) {
                    // 检测到恶意代码 - 阻止上传
                    $message = '检测到存在恶意代码特征，请遵纪守法';
                    $messageType = 'waf_warning';
                } else {
                    // 绕过检测 - 允许上传
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
                    <i class="fa fa-cloud-upload fa-3x" style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
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
                    运维小王：数据中心接入防护后，很多大型文件的</br>传输明显受影响，得做些策略调整才行....
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
                                        <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank" class="file-link">
                                            <i class="fa fa-eye"></i> 预览
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <form method="POST" style="margin-top: 20px;" onsubmit="return confirmReset();">
                        <input type="hidden" name="action" value="reset">
                        <button type="submit" class="reset-button">
                            <i class="fa fa-trash"></i> 重置文件列表
                        </button>
                    </form>
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
        // 禁用恭喜弹窗
        'enableCongrats' => false
    ]);
    ?>

    <!-- 下一关按钮（验证成功后显示） -->
    <div id="nextLevelContainer" style="display: none; margin-top: 20px; text-align: center;">
        <a href="antiwaf2.php" id="nextLevelBtn" class="next-level-button">
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
