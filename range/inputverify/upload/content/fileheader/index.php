<?php
/**
 * HeaSec天积安全团队 - 文件上传文件头检测靶场
 * 版本: v1.0.0
 * 创建日期: 2025-12-22
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传文件头检测 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传文件头检测靶场';
$rangeName = '文件上传文件头检测';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('fileheader');

// 验证会话完整性
HeaSec_ValidateSession();

/**
 * 获取或生成会话中的秘密字符串
 * 使用新的会话管理组件
 */
function getSecret()
{
    return HeaSec_GetSecret(20);
}

/**
 * 检查文件头是否为合法图片格式
 * @param string $filePath 文件路径
 * @return bool 是否为合法图片头
 */
function isValidImageHeader($filePath)
{
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }

    // 读取前8个字节
    $header = fread($handle, 8);
    fclose($handle);

    if (strlen($header) < 2) {
        return false;
    }

    // 转换为十六进制字符串用于比较
    $headerHex = bin2hex($header);

    // JPG 文件头检查 (FF D8 FF)
    if (substr($headerHex, 0, 6) === 'ffd8ff') {
        return true;
    }

    // PNG 文件头检查 (89 50 4E 47)
    if (substr($headerHex, 0, 8) === '89504e47') {
        return true;
    }

    // GIF 文件头检查 (47 49 46 38)
    if (substr($headerHex, 0, 8) === '47494638') {
        return true;
    }

    return false;
}

// 创建上传目录
$uploadDir = __DIR__ . '/images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 创建.htaccess文件阻止web访问secret.php（如果不存在）
$htaccessFile = $uploadDir . '.htaccess';
if (!file_exists($htaccessFile)) {
    $htaccessContent = "<Files \"secret.php\">\nRequire all denied\n</Files>";
    file_put_contents($htaccessFile, $htaccessContent);
}

// 获取或生成秘密字符串
$secret = getSecret();

// 创建secret.php文件（放在images目录下）
$secretFile = __DIR__ . '/images/secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问
 */
echo "' . $secret . '";
?>';
file_put_contents($secretFile, $secretContent);

// 处理文件上传
$message = '';
$messageType = '';
$uploadedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadedFile = $_FILES['avatar'];

    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($uploadedFile['name']);

        // 安全的文件名处理
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $fileName = trim($fileName, '._-');

        // 文件名长度检查
        if (empty($fileName) || strlen($fileName) > 255) {
            $message = '文件名不合法！';
            $messageType = 'error';
        } else {
            $targetPath = $uploadDir . $fileName;

            // 文件大小限制（10MB）
            $maxSize = 10 * 1024 * 1024;
            if ($uploadedFile['size'] > $maxSize) {
                $message = '文件大小超过限制（10MB）！';
                $messageType = 'error';
            } else {
                // 检查文件头
                if (isValidImageHeader($uploadedFile['tmp_name'])) {
                    // 文件头检测通过，允许上传
                    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                        // 检查是否是PHP文件（通过文件扩展名和内容）
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $fileContent = file_get_contents($targetPath);
                        if ($fileExtension === 'php' || strpos($fileContent, '<?php') !== false) {
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
                    // 文件头检测失败
                    $message = '文件头不合法，只允许JPG/PNG/GIF格式的图片文件！';
                    $messageType = 'error';
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

// 处理重置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    // 删除上传目录中的所有文件，但保留secret.php和.htaccess
    $files = glob($uploadDir . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'secret.php' && basename($file) !== '.htaccess') {
            unlink($file);
        }
    }
    $message = '已重置所有上传的文件！';
    $messageType = 'info';
}

// 获取已上传的文件列表
$uploadedFiles = [];
if (file_exists($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'secret.php' && $file !== '.htaccess') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($uploadDir . $file)
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

            <form class="upload-form" method="POST" enctype="multipart/form-data" onsubmit="return validateFileType();">
                <!-- 拖拽上传区域 -->
                <div class="upload-dropzone" id="uploadDropzone">
                    <i class="fa fa-cloud-upload fa-3x"
                        style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
                    <p style="margin: 0; color: #666;">点击选择文件或拖拽文件到此处</p>
                    <div class="drag-hint">放开鼠标上传文件</div>
                    <input type="file" name="avatar" id="avatarInputDropzone" accept=".jpg,.jpeg,.png,.gif">
                </div>

                <!-- 传统文件选择区域 -->
                <div style="margin-bottom: 20px; margin-top: 20px;">
                    <label class="file-input-wrapper">
                        <input type="file" class="file-input" id="avatarInput"
                            accept=".jpg,.jpeg,.png,.gif" onchange="validateFileType();">
                        <i class="fa fa-folder-open"></i> 选择图片文件
                    </label>
                    <button type="submit" class="upload-button">
                        <i class="fa fa-cloud-upload"></i> 上传图片
                    </button>
                </div>
                <small style="color: #6c757d;">
                    <i class="fa fa-info-circle"></i>
                    注意：请上传 JPG/PNG/GIF 格式图片文件
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
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功理解了文件头检测绕过漏洞的危害！',
        'rangeCode' => 'fileheader'
    ]);
    ?>
</div>

<!-- JavaScript前端校验 -->
<script src="js/script.js"></script>
<script>

    // 上传PHP脚本成功后显示恭喜消息
    <?php if ($messageType === 'success' && strpos($message, 'php脚本') !== false): ?>
        document.addEventListener('DOMContentLoaded', function () {
            // 使用公共组件显示恭喜消息
            if (window.heasecModalManager) {
                window.heasecModalManager.showSuccess({
                    title: '恭喜你掌握了一个新技能！',
                    message: '你成功理解了文件头检测绕过漏洞的危害！',
                    buttonText: '知道了',
                    callback: function () {
                        // 回调函数，弹窗关闭后执行
                    }
                });
            } else {
                // 备用方案：使用原生alert
                alert('恭喜你掌握了一个新技能！\n你成功理解了文件头检测绕过漏洞的危害！');
            }
        });
    <?php endif; ?>
</script>

<?php
// 引入公共底部
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>