<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '代码注入 - 第一关';
$rangeName = '代码注入';
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

// 初始化靶场会话
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

// 引入公共函数
require_once 'includes/functions.php';

// 确保当前关卡的secret.php存在
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

// 确保 templates 目录存在
$templateDir = __DIR__ . '/templates/';
if (!is_dir($templateDir)) {
    mkdir($templateDir, 0755, true);
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 主题模板编辑器卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-paint-brush"></i>
                <span>天积主题模板编辑器</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的主题模板编辑器支持创建和编辑自定义页面主题。您可以编写HTML内容作为模板，系统会将模板保存为文件并通过渲染引擎预览展示效果</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用模板功能获取通关密码。提示：仔细观察模板的保存和预览过程——模板内容会被如何处理？如果模板中不仅仅是HTML呢？思考一下"渲染引擎"在服务端是如何工作的。秘密文件位于 config 目录下</small>
                </div>
            </div>

            <!-- 模板创建/编辑表单 -->
            <div class="form-group">
                <label for="templateName" class="form-label">
                    <i class="fa fa-tag"></i> 模板名称
                </label>
                <input type="text" id="templateName" class="tech-input" placeholder="请输入模板名称（仅支持字母数字下划线）" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="templateContent" class="form-label">
                    <i class="fa fa-code"></i> 模板内容
                </label>
                <textarea id="templateContent" class="tech-input" placeholder="请输入模板内容（支持HTML）" rows="6"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" id="saveTemplateBtn" class="tech-btn tech-btn-primary">
                    <i class="fa fa-save"></i> 保存模板
                </button>
                <button type="button" id="previewTemplateBtn" class="tech-btn tech-btn-info" disabled>
                    <i class="fa fa-eye"></i> 预览渲染
                </button>
            </div>

            <!-- 已保存模板列表 -->
            <div class="template-list">
                <h4><i class="fa fa-list"></i> 已保存模板</h4>
                <div id="templateListArea">
                    <p style="color: #6c757d; font-size: 13px;">加载中...</p>
                </div>
            </div>

            <hr class="section-divider">

            <!-- 预览渲染区域 -->
            <div id="previewArea" class="preview-area" style="display: none;">
                <h4><i class="fa fa-eye"></i> 预览渲染结果</h4>
                <div id="previewContent"></div>
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
<script src="js/code_inj.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCodeInj(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
