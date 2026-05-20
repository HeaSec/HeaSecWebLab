<?php
/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 第三关
 * 版本: v1.0.0
 * 功能: 第三关 — 安全告警查询
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec Symbol Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL特殊字符过滤靶场 - 第三关';
$rangeName = 'SQL特殊字符过滤';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 3;
$nextPage = '';
$nextBtnText = '';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('symbol');

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
    <!-- 卡片一：安全告警查询 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-exclamation-triangle"></i>
                <span>天积运维 — 安全告警查询</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积分司运维管理平台 — 安全告警查询功能。请输入告警ID查询告警信息，系统将返回查询结果</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用SQL注入漏洞获取通关密码。提示：系统过滤了单引号和双引号，通关密码存储在 heasec_symbol_alerts 表中</small>
                </div>
            </div>

            <!-- 查询表单 -->
            <div class="submit-section">
                <form id="queryForm" class="query-form">
                    <div class="form-group">
                        <label for="alertId" class="form-label">
                            <i class="fa fa-bell"></i> 告警ID
                        </label>
                        <input type="text" id="alertId" name="id" class="tech-input" placeholder="请输入告警ID" autocomplete="off">
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
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script src="js/symbol.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initSymbol(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
