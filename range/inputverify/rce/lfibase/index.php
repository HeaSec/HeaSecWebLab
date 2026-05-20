<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件包含基础 - 第一关';
$rangeName = '文件包含基础';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 1;
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入公共函数
require_once 'includes/functions.php';

// 确保当前关卡的secret文件存在（第一关使用纯文本格式）
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath, true);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 文档查看器卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-file-text-o"></i>
                <span>天积文档查看器</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的文档查看器支持通过URL参数选择要查看的页面，服务端会将您选择的页面文件加载并展示出来</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用文件包含漏洞获取通关密码，秘密文件路径为 <code>config/secret.txt</code></small>
                </div>
            </div>

            <!-- 导航链接区域 -->
            <div class="doc-nav">
                <a href="#" class="doc-nav-link" data-page="pages/home.php"><i class="fa fa-home"></i> home</a>
                <a href="#" class="doc-nav-link" data-page="pages/about.php"><i class="fa fa-info-circle"></i> about</a>
                <a href="#" class="doc-nav-link" data-page="pages/contact.php"><i class="fa fa-envelope"></i> contact</a>
            </div>

            <!-- 路径输入区域 -->
            <div class="submit-section">
                <input type="text" id="pageInput" placeholder="或直接输入要查看的页面路径...">
                <div class="submit-actions">
                    <button type="button" id="viewBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-eye"></i> 查看
                    </button>
                </div>
            </div>

            <!-- 文档内容展示区域 -->
            <div id="contentArea" class="doc-content-wrapper" style="display: none;"></div>
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
<script src="js/lfibase.js?v=<?php echo $version; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initLfiBase(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
});
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
