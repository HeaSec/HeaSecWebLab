<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-03-10
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 漏洞机制：路径穿越上传 - 直接使用用户提交的文件名，未做任何过滤
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件目录执行权限绕过靶场';
$rangeName = '文件目录执行权限绕过①';
$showVersion = false;
$showResetButton = true;
$version = 'v1.1.0';

// 关卡配置
$currentLevel = 1;
$levelTitle = '第一关：路径穿越上传';
$nextPage = 'level2.php';
$nextBtnText = '下一关';
$cardTitle = '请上传您的头像图片';
$devTip = '开发人员ps：上传目录已禁止PHP代码执行，这下安全了！';

// 公共组件路径
$commonBasePath = '../../../../common/';

// 全局变量供组件使用
$GLOBALS['commonBasePath'] = $commonBasePath;

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('filedirectory');
HeaSec_ValidateSession();

// 生成或获取当前关卡的通关密码
if (!isset($_SESSION['filedirectory_level1_secret'])) {
    $_SESSION['filedirectory_level1_secret'] = HeaSec_SessionManager::generateSecret(20);
}
$secret = $_SESSION['filedirectory_level1_secret'];

// 创建exec目录
$execDir = __DIR__ . '/exec/';
$imagesDir = $execDir . 'images/';

if (!file_exists($execDir)) {
    mkdir($execDir, 0755, true);
}
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 创建或更新 exec/images/.htaccess 文件（禁止PHP执行）
$htaccessFile = $imagesDir . '.htaccess';
$htaccessContent = '# 禁止PHP执行 - phpStudy兼容配置
<FilesMatch "\.php$">
    php_flag engine off
</FilesMatch>

# 禁止访问敏感文件
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>';
file_put_contents($htaccessFile, $htaccessContent);

// 创建或更新 exec/secret.php 文件
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

// 获取已上传文件列表（exec/images目录）
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
                <?php echo $cardTitle; ?>
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

                <!-- 已选择的文件列表 -->
                <div id="selectedFileContainer" style="display: none; margin-top: 15px;">
                    <h5>已选择的文件：</h5>
                    <ul id="selectedFileList" class="selected-file-list"></ul>
                </div>

                <small style="color: #6c757d;">
                    <i class="fa fa-info-circle"></i>
                    <?php echo $devTip; ?>
                </small>
            </form>

            <!-- 已上传文件列表 -->
            <?php if (!empty($uploadedFiles)): ?>
            <div style="margin-top: 30px;">
                <h4>已上传的文件:</h4>
                <table class="files-table">
                    <thead>
                        <tr>
                            <th>文件名</th>
                            <th>文件大小</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadedFiles as $file): ?>
                        <tr data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                            <td class="file-name-cell"><?php echo htmlspecialchars($file['name']); ?></td>
                            <td class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                            <td>
                                <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank" class="file-link">
                                    <i class="fa fa-eye"></i> 预览
                                </a>
                                <button type="button" class="rename-btn" data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                                    <i class="fa fa-edit"></i> 重命名
                                </button>
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
        'successMessage' => '验证成功，恭喜你发现了秘密!',
        'successHint' => '你已经成功绕过目录执行权限限制获取了通关密码!',
        'errorMessage' => '验证失败，这不是我的秘密!',
        'emptyMessage' => '请输入秘密',
        'enableCongrats' => false,
        'rangeCode' => 'filedirectory'
    ]);
    ?>

    <!-- 下一关按钮 -->
    <div id="nextLevelSection" style="display: none; margin-top: 20px; text-align: center;">
        <a href="<?php echo $nextPage; ?>" id="nextLevelBtn" class="next-level-button">
            <i class="fa fa-arrow-right"></i> <?php echo $nextBtnText; ?>
        </a>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/script.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initFileDirectory({
            level: <?php echo $currentLevel; ?>,
            isFinalLevel: false,
            nextPage: '<?php echo $nextPage; ?>'
        });
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
