<?php
/**
 * HeaSec天积安全团队 - XXE基础靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-04-09
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XXEBase Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XXE基础靶场 - 第二关';
$rangeName = 'XXE基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入公共函数
require_once 'includes/functions.php';

// 确保当前关卡的secret.php存在
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

// Windows环境下计算靶场目录绝对路径（用于页面提示）
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$rangeBasePath = '';
if ($isWindows) {
    $rangeBasePath = str_replace('\\', '/', __DIR__) . '/';
}

// 确保导入数据文件存在
$dataPath = getDataFilePath($currentLevel);
ensureDataFile($dataPath);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 商品数据导入卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-database"></i>
                <span>天积数据平台 - 商品数据导入 - 第二关</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统支持通过XML文件批量导入商品数据</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用XXE漏洞读取config/level2目录下的secret.ini文件，获取通关密码。</small>
                    <?php if ($isWindows): ?>
                    <small class="text-muted" style="display:block;margin-top:8px;">
                        提示：Windows 环境下 file:// 协议不支持相对路径，靶场目录为：<code><?php echo $rangeBasePath; ?></code>
                    </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 文件上传区域 - 拖拽上传 -->
            <div class="upload-section">
                <div class="dropzone" id="dropZone">
                    <input type="file" id="xmlFileInput" accept=".xml">
                    <div class="dropzone-content">
                        <i class="fa fa-cloud-upload dropzone-icon"></i>
                        <p class="dropzone-text">将XML文件拖拽到此处，或<span class="dropzone-link">点击选择文件</span></p>
                        <p class="dropzone-hint">仅支持 .xml 格式文件</p>
                    </div>
                    <div class="dropzone-file-info" id="fileInfoArea" style="display: none;">
                        <i class="fa fa-file-code-o"></i>
                        <span id="fileNameDisplay"></span>
                        <button type="button" id="clearFileBtn" class="clear-file-btn" title="移除文件">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="upload-actions">
                    <button type="button" id="downloadTemplateBtn" class="tech-btn tech-btn-secondary">
                        <i class="fa fa-download"></i> 下载XML模板
                    </button>
                    <button type="button" id="importBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-upload"></i> 导入数据
                    </button>
                </div>
            </div>

            <!-- 导入结果区域 -->
            <div id="resultArea" style="display: none;"></div>

            <!-- 已导入数据表格 -->
            <div class="data-table-section">
                <h4><i class="fa fa-table"></i> 已导入数据</h4>
                <div id="importedData"></div>
            </div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                <span>通关验证</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <form id="verifyForm" class="tech-form">
                <div class="form-group">
                    <label for="passcode" class="form-label">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/xxebase.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initXxeBase(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
