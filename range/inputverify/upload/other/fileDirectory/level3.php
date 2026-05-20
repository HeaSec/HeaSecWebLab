<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-03-10
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 漏洞机制：.htaccess覆盖攻击 - 通过上传恶意.htaccess覆盖配置
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件目录执行权限绕过靶场';
$rangeName = '文件目录执行权限绕过③';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 关卡配置
$currentLevel = 3;
$levelTitle = '第三关：.htaccess覆盖攻击';
$nextPage = 'index.php';
$nextBtnText = '返回第一关';
$devTip = '开发人员ps：已通过.htaccess配置限制只有图片文件可以访问，这下绝对安全！';
$isFinalLevel = true;

// 公共组件路径
$commonBasePath = '../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('filedirectory');
HeaSec_ValidateSession();

// 生成或获取当前关卡的通关密码
if (!isset($_SESSION['filedirectory_level3_secret'])) {
    $_SESSION['filedirectory_level3_secret'] = HeaSec_SessionManager::generateSecret(20);
}
$secret = $_SESSION['filedirectory_level3_secret'];

// exec目录路径
$execDir = __DIR__ . '/exec/';
$imagesDir = $execDir . 'images/';

// 创建exec目录（如果不存在）
if (!file_exists($execDir)) {
    mkdir($execDir, 0755, true);
}

// 创建images目录（如果不存在）
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 创建images/.htaccess文件（只允许图片访问）
// 只在文件不存在时创建，允许攻击者通过上传.htaccess覆盖配置
$htaccessFile = $imagesDir . '.htaccess';
if (!file_exists($htaccessFile)) {
    $htaccessContent = '# 禁止所有文件访问（默认）
Order allow,deny
Deny from all

# 只允许图片文件访问
<FilesMatch "\.(?i)(jpg|jpeg|png|gif)$">
    Allow from all
</FilesMatch>';
    file_put_contents($htaccessFile, $htaccessContent);
}

// 创建或更新exec/secret.php文件（硬编码密码）
$secretFile = $execDir . 'secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问
 */
defined("HEASEC_RANGE_ACCESS") or die("Direct access not allowed");
echo "' . $secret . '";
?>';
file_put_contents($secretFile, $secretContent);

// 获取已上传文件列表（只显示images目录下的用户上传文件）
$uploadedFiles = [];
if (file_exists($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'exec/images/' . $file,
                'size' => filesize($imagesDir . $file)
            ];
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 引入恭喜弹窗组件资源
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<!-- 引入统一样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">


<!-- 引入密码验证卡片组件 -->
<?php
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场内容 -->
<div class="tech-container">
    <!-- 文件上传区域 -->
    <div class="upload-section">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-upload"></i>
                请上传您的文件
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 消息区域 -->
            <div id="messageArea"></div>

            <!-- 上传表单 -->
            <form id="uploadForm" class="upload-form" method="POST" enctype="multipart/form-data">
                <!-- 拖拽上传区域 -->
                <div class="upload-dropzone" id="uploadDropzone">
                    <i class="fa fa-cloud-upload fa-3x" style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
                    <p style="margin: 0; color: #666;">点击选择文件或拖拽文件到此处</p>
                    <input type="file" name="file" id="fileInputDropzone">
                </div>

                <!-- 传统文件选择区域 -->
                <div style="margin-bottom: 20px; margin-top: 20px;">
                    <label class="file-input-wrapper">
                        <input type="file" class="file-input" id="fileInput">
                        <i class="fa fa-folder-open"></i> 选择文件
                    </label>
                    <button type="submit" class="upload-button">
                        <i class="fa fa-cloud-upload"></i> 上传文件
                    </button>
                </div>
                <small style="color: #6c757d;">
                    <i class="fa fa-info-circle"></i>
                    <?php echo $devTip; ?>
                </small>
            </form>

            <!-- 已上传文件列表 -->
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

                <!-- 重置按钮 -->
                <form id="resetForm" style="margin-top: 20px;">
                    <button type="button" id="resetBtn" class="reset-button">
                        <i class="fa fa-trash"></i> 重置文件列表
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="no-files-hint" style="margin-top: 20px;">
                <i class="fa fa-inbox"></i> 暂无已上传的文件
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 通关验证卡片 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '输入你发现的秘密',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你完成了所有关卡！',
        'successHint' => '你已经成功掌握了目录执行权限绕过漏洞的利用方式！',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'enableCongrats' => true,
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功理解了目录执行权限绕过漏洞的原理和危害！',
        'rangeCode' => 'filedirectory'
    ]);
    ?>
</div>

<!-- 引入交互脚本 -->
<script src="js/script.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFileDirectory({
            level: <?php echo $currentLevel; ?>,
            isFinalLevel: true,
            nextPage: '<?php echo $nextPage; ?>'
        });
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
