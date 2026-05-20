<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 第一关
 * 版本: v1.0.0
 * 功能: 报错注入 — 商品信息查询
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec BlindInj Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL盲注靶场 - 第一关';
$rangeName = 'SQL盲注';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 1;
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('blindinj');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：商品信息查询 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-search"></i>
                <span>天积商城 — 商品信息查询</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积商城后台管理系统 — 商品信息查询功能。请输入商品ID查询商品是否存在，系统将返回查询结果</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用SQL注入漏洞获取通关密码。提示：观察报错信息，尝试利用报错函数携带数据，通关密码存储在 heasec_blindinj_flag 表中</small>
                </div>
            </div>

            <!-- 查询表单 -->
            <div class="submit-section">
                <form id="queryForm" class="query-form">
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
            <div id="queryResultArea" style="display: none;"></div>
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

<script src="js/blindinj.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initBlindInj(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
