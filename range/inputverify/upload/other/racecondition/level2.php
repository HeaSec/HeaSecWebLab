<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 漏洞机制：临时目录+延迟验证 - 先存tmp目录，延迟验证后移动/删除
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 条件竞争上传 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '条件竞争上传靶场';
$rangeName = '条件竞争上传②';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 关卡配置
$currentLevel = 2;
$levelTitle = '第二关：临时目录验证';
$nextPage = 'level3.php';
$nextBtnText = '下一关';
$devTip = '开发人员ps：只有通过验证的图片文件才会被保存到正式目录！';

// 公共组件路径
$commonBasePath = '../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('racecondition');
HeaSec_ValidateSession();

// 生成或获取当前关卡的通关密码
if (!isset($_SESSION['racecondition_level2_secret'])) {
    $_SESSION['racecondition_level2_secret'] = HeaSec_SessionManager::generateSecret(20);
}
$secret = $_SESSION['racecondition_level2_secret'];

// 创建images目录
$imagesDir = __DIR__ . '/images/';
if (!file_exists($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// 创建images/tmp临时目录
$tmpDir = $imagesDir . 'tmp/';
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

// 创建或更新secret.php文件
$secretFile = $imagesDir . 'secret.php';
$secretContent = '<?php
/**
 * HeaSec天积安全团队 - 秘密文件
 * 此文件只能在服务器端访问
 */
defined("HEASEC_RANGE_ACCESS") or die("Direct access not allowed");
echo "' . $secret . '";
?>';
file_put_contents($secretFile, $secretContent);

// 删除 images/.htaccess 文件（如果存在）
// phpStudy 默认已配置 PHP 处理，无需额外的 .htaccess 配置
if (file_exists($imagesDir . '.htaccess')) {
    @unlink($imagesDir . '.htaccess');
}

// 获取已上传文件列表
$uploadedFiles = [];

// 获取images目录下的文件
if (file_exists($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'secret.php' && $file !== '.htaccess' && $file !== 'tmp') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($imagesDir . $file)
            ];
        }
    }
}

// 获取当前用户的头像状态
$avatarStatus = 'none'; // none, pending, approved, rejected
$avatarInfo = null;
if (isset($_SESSION['racecondition_level2_upload'])) {
    $uploadInfo = $_SESSION['racecondition_level2_upload'];
    $uploadTime = $uploadInfo['upload_time'];
    $elapsedTime = time() - $uploadTime;

    $tmpPath = $tmpDir . $uploadInfo['tmp_filename'];
    $finalPath = $imagesDir . $uploadInfo['tmp_filename'];

    if (file_exists($finalPath)) {
        $avatarStatus = 'approved';
        $avatarInfo = [
            'url' => 'images/' . $uploadInfo['tmp_filename'],
            'original_name' => $uploadInfo['original_name']
        ];
    } elseif ($elapsedTime >= 30 && !file_exists($tmpPath)) {
        $avatarStatus = 'rejected';
        $avatarInfo = [
            'original_name' => $uploadInfo['original_name']
        ];
    } elseif (file_exists($tmpPath)) {
        $avatarStatus = 'pending';
        $remainingTime = max(0, 30 - $elapsedTime);
        $avatarInfo = [
            'tmp_url' => 'images/tmp/' . $uploadInfo['tmp_filename'],
            'original_name' => $uploadInfo['original_name'],
            'remaining_time' => $remainingTime
        ];
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
    <!-- 头像显示区域 -->
    <div class="avatar-section">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user-circle"></i>
                我的头像
            </h3>
        </div>
        <div class="tech-card-body avatar-display">
            <div id="avatarContainer" class="avatar-container">
                <?php if ($avatarStatus === 'approved' && $avatarInfo): ?>
                    <div class="avatar-approved">
                        <img src="<?php echo htmlspecialchars($avatarInfo['url']); ?>" alt="头像" class="avatar-image">
                        <p class="avatar-filename"><?php echo htmlspecialchars($avatarInfo['original_name']); ?></p>
                        <span class="avatar-status approved"><i class="fa fa-check-circle"></i> 审核通过</span>
                    </div>
                <?php elseif ($avatarStatus === 'pending' && $avatarInfo): ?>
                    <div class="avatar-pending">
                        <div class="avatar-placeholder">
                            <i class="fa fa-clock-o fa-3x"></i>
                            <p>待审核中...</p>
                            <p class="remaining-time">剩余 <span id="remainingTime"><?php echo $avatarInfo['remaining_time']; ?></span> 秒</p>
                        </div>
                        <p class="avatar-filename"><?php echo htmlspecialchars($avatarInfo['original_name']); ?></p>
                        <span class="avatar-status pending"><i class="fa fa-hourglass-half"></i> 审核中</span>
                    </div>
                <?php elseif ($avatarStatus === 'rejected' && $avatarInfo): ?>
                    <div class="avatar-rejected">
                        <div class="avatar-placeholder">
                            <i class="fa fa-times-circle fa-3x"></i>
                            <p>审核未通过</p>
                        </div>
                        <p class="avatar-filename"><?php echo htmlspecialchars($avatarInfo['original_name']); ?></p>
                        <span class="avatar-status rejected"><i class="fa fa-times-circle"></i> 非图片格式，已拒绝</span>
                    </div>
                <?php else: ?>
                    <div class="avatar-none">
                        <div class="avatar-placeholder">
                            <i class="fa fa-user fa-3x"></i>
                            <p>暂无头像</p>
                        </div>
                        <span class="avatar-status none">请上传头像图片</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 文件上传区域 -->
    <div class="upload-section">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-upload"></i>
                请上传您的头像图片
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
                    <?php echo $devTip; ?></br>
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
                        <?php
    endforeach; ?>
                    </tbody>
                </table>

                <!-- 重置按钮 -->
                <form id="resetForm" style="margin-top: 20px;">
                    <button type="button" id="resetBtn" class="reset-button">
                        <i class="fa fa-trash"></i> 重置文件列表
                    </button>
                </form>
            </div>
            <?php
else: ?>
            <div class="no-files-hint" style="margin-top: 20px;">
                <i class="fa fa-inbox"></i> 暂无已上传的文件
            </div>
            <?php
endif; ?>
        </div>
    </div>

    <!-- 通关验证卡片 -->
    <?php
echo renderSecretCard([
    'cardTitle' => '输入你发现的秘密',
    'cardIcon' => 'fa fa-key',
    'secretValue' => $secret,
    'successMessage' => '验证成功，恭喜你发现了秘密！',
    'successHint' => '你已经成功利用条件竞争漏洞获取了通关密码！',
    'errorMessage' => '验证失败，这不是我的秘密！',
    'emptyMessage' => '请输入秘密',
    'enableCongrats' => false,
    'rangeCode' => 'racecondition'
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
        initRaceCondition({
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
