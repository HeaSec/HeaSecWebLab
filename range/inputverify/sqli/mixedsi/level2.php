<?php
/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场 - 第二关
 * 版本: v1.0.0
 * 功能: 报错注入+多过滤器绕过 — 商品详情查询
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec MixedSI Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL注入综合实战靶场 - 第二关';
$rangeName = 'SQL注入综合实战';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 自定义重置处理
if (isset($_GET['action']) && in_array($_GET['action'], ['reset', 'init']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $passFiles = ['secret.php', 'secret3.php'];
    foreach ($passFiles as $file) {
        $f = __DIR__ . '/config/' . $file;
        if (file_exists($f)) {
            @unlink($f);
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('mixedsi');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 确保密码已生成
ensurePasswordExists($currentLevel);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：天积企业信息平台 — 商品管理 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-cubes"></i>
                <span>天积企业信息平台 — 商品管理</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积企业信息平台 — 商品管理。查询企业商品详细信息</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用漏洞从隐藏的配置表中获取通关密码。提示：通关密码是一个20位随机字符串，直接存储在数据库中某个隐藏的配置表里，表前缀为heasec_mixedsi</small>
                </div>
            </div>

            <!-- 商品查询表单 -->
            <div class="submit-section">
                <form id="productForm" class="query-form">
                    <div class="form-group">
                        <label for="productId" class="form-label">
                            <i class="fa fa-barcode"></i> 商品ID
                        </label>
                        <input type="text" id="productId" name="id" class="tech-input" placeholder="请输入商品ID" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="queryBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-search"></i> 查询
                        </button>
                    </div>
                </form>
            </div>

            <!-- 查询结果区域 -->
            <div id="productResultArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 卡片二：通关验证 -->
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

<script src="js/mixedsi.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initMixedsi(<?php echo $currentLevel; ?>, <?php echo json_encode($commonBasePath); ?>);
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
