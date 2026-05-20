<?php
/**
 * HeaSec天积安全团队 - 文件上传综合对抗靶场
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明：
 * 本靶场包含多层安全校验，用于教学演示文件上传漏洞的各种绕过技巧
 * - 前端JS校验：只允许jpg/png格式
 * - 服务端后缀名黑名单校验：阻止.php/.PHP/.pHp/.phP，但.Php会被保存为.php
 * - 服务端文件头校验：只允许jpg/png/gif图片头
 * - 服务端内容校验：前1000字符检测危险函数
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传综合对抗靶场 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传综合对抗靶场';
$rangeName = '文件上传综合对抗';
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
HeaSec_InitRangeSession('upload_Comprehensive');

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

// 创建images目录（用于存储上传文件和secret.php文件）
$imagesDir = __DIR__ . '/images/';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 获取或生成秘密字符串
$secret = getSecret();

// 创建secret.php文件（放在images目录下，添加访问控制）
$secretFile = __DIR__ . '/images/secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问，不能通过web直接访问
 */
defined("HEASEC_RANGE_ACCESS") or die("Direct access not allowed");
echo "' . $secret . '";
?>';
file_put_contents($secretFile, $secretContent);

// 处理文件上传
$message = '';
$messageType = '';
$uploadedFiles = [];

// 定义PHP危险函数黑名单（用于内容校验）
$dangerousFunctions = array(
    'eval',
    'assert',
    'system',
    'exec',
    'shell_exec',
    'passthru',
    'popen',
    'proc_open',
    'pcntl_exec',
    'create_function',
    'call_user_func',
    'call_user_func_array',
    'preg_replace'
);

// 定义允许的图片文件头
$allowedHeaders = array(
    'jpg' => array("\xFF\xD8\xFF"),
    'jpeg' => array("\xFF\xD8\xFF"),
    'png' => array("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"),
    'gif' => array("GIF87a", "GIF89a")
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadedFile = $_FILES['avatar'];

    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($uploadedFile['name']);

        // 安全的文件名处理（只过滤特殊字符，保留原始扩展名大小写）
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $fileName = trim($fileName, '._-');

        // 获取文件扩展名（保留原始大小写）
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileExtensionLower = strtolower($fileExtension);

        // 文件名长度检查
        if (empty($fileName) || strlen($fileName) > 255) {
            $message = '文件名不合法！';
            $messageType = 'error';
        } elseif (strlen($fileName) < 1) {
            $message = '文件名过短！';
            $messageType = 'error';
        } else {
            // ========== 第一层：服务端后缀名黑名单校验 ==========
            // 只检查纯小写的 .php，以及特定的大小写组合 .PHP/.pHp/.phP
            $blockedExtensions = array('php', 'PHP', 'pHp', 'phP');
            $isBlocked = false;

            foreach ($blockedExtensions as $blocked) {
                if ($fileExtension === $blocked) {
                    $isBlocked = true;
                    break;
                }
            }

            if ($isBlocked) {
                $message = '疑似上传恶意文件，请遵纪守法';
                $messageType = 'error';
            } else {
                // 检查是否是 .Php 扩展名（会被保存为 .php）
                $finalFileName = $fileName;
                if ($fileExtension === 'Php') {
                    // 将 .Php 转换为 .php
                    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                    $finalFileName = $baseName . '.php';
                }

                // ========== 第二层：文件头校验 ==========
                $fileContent = file_get_contents($uploadedFile['tmp_name']);
                $isValidHeader = false;

                foreach ($allowedHeaders as $type => $headers) {
                    foreach ($headers as $header) {
                        if (strpos($fileContent, $header) === 0) {
                            $isValidHeader = true;
                            break 2;
                        }
                    }
                }

                if (!$isValidHeader) {
                    $message = '文件头校验失败，仅允许上传jpg/png/gif格式的图片！';
                    $messageType = 'error';
                } else {
                    // ========== 第三层：文件内容校验（前1000字符） ==========
                    $contentToCheck = substr($fileContent, 0, 1000);
                    $hasDangerousContent = false;

                    foreach ($dangerousFunctions as $func) {
                        if (stripos($contentToCheck, $func) !== false) {
                            $hasDangerousContent = true;
                            break;
                        }
                    }

                    if ($hasDangerousContent) {
                        $message = '检测到存在恶意代码特征，请遵纪守法';
                        $messageType = 'error';
                    } else {
                        // 所有校验通过，保存文件
                        $targetPath = $imagesDir . $finalFileName;

                        // 文件大小限制（10MB）
                        $maxSize = 10 * 1024 * 1024;
                        if ($uploadedFile['size'] > $maxSize) {
                            $message = '文件大小超过限制（10MB）！';
                            $messageType = 'error';
                        } else {
                            // 尝试移动上传的文件
                            if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                                // 检查是否是PHP文件（最终文件名）
                                $finalExtension = strtolower(pathinfo($finalFileName, PATHINFO_EXTENSION));
                                if ($finalExtension === 'php') {
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
    // 删除images目录中的所有文件（除了secret.php）
    $files = glob($imagesDir . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'secret.php') {
            unlink($file);
        }
    }
    $message = '已重置所有上传的文件！';
    $messageType = 'info';
}

// 获取已上传的文件列表（排除secret.php）
$uploadedFiles = array();
if (file_exists($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'secret.php') {
            $uploadedFiles[] = array(
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($imagesDir . $file)
            );
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

            <form class="upload-form" method="POST" enctype="multipart/form-data" id="uploadForm">
                <!-- 拖拽上传区域 -->
                <div class="upload-dropzone" id="uploadDropzone">
                    <i class="fa fa-cloud-upload fa-3x"
                        style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
                    <p style="margin: 0; color: #666;">点击选择文件或拖拽文件到此处</p>
                    <input type="file" name="avatar" id="avatarInputDropzone" accept=".jpg,.jpeg,.png">
                </div>

                <!-- 传统文件选择区域 -->
                <div style="margin-bottom: 20px; margin-top: 20px;">
                    <label class="file-input-wrapper">
                        <input type="file" class="file-input" id="avatarInput" accept=".jpg,.jpeg,.png">
                        <i class="fa fa-folder-open"></i> 选择图片文件
                    </label>
                    <button type="submit" class="upload-button" id="uploadBtn">
                        <i class="fa fa-cloud-upload"></i> 上传图片
                    </button>
                </div>
                <small style="color: #6c757d;">
                    <i class="fa fa-info-circle"></i>
                    注意：请上传 JPG/PNG 格式图片文件
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
    echo renderSecretCard(array(
        'cardTitle' => '输入你发现的秘密',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => '你已经成功找到了服务器端存储的秘密字符串！',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功理解了文件上传综合校验绕过漏洞的危害！',
        'rangeCode' => 'upload_Comprehensive'
    ));
    ?>
</div>

<!-- JavaScript功能 -->
<script src="js/script.js"></script>
<script>

    // 上传PHP脚本成功后显示恭喜消息
    <?php if ($messageType === 'success' && strpos($message, 'php脚本') !== false): ?>
        document.addEventListener('DOMContentLoaded', function () {
            // 使用公共组件显示恭喜消息
            if (window.heasecModalManager) {
                window.heasecModalManager.showSuccess({
                    title: '恭喜你掌握了一个新技能！',
                    message: '你成功理解了文件上传综合校验绕过漏洞的危害！',
                    buttonText: '知道了',
                    callback: function () {
                        console.log('恭喜消息已关闭');
                    }
                });
            } else {
                // 备用方案：使用原生alert
                alert('恭喜你掌握了一个新技能！\n你成功理解了文件上传综合校验绕过漏洞的危害！');
            }
        });
    <?php endif; ?>
</script>

<?php
// 引入公共底部
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>