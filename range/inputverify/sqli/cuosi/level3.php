<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 第三关
 * 版本: v1.0.0
 * 功能: ORDER BY注入+布尔盲注 — 商品商城
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec Cuosi Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL不同语句注入靶场 - 第三关';
$rangeName = 'SQL不同语句注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 自定义重置处理：重置时删除所有密码配置文件
if (isset($_GET['action']) && in_array($_GET['action'], ['reset', 'init']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $passFiles = ['secret.php', 'secret2.php', 'secret3.php'];
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
HeaSec_InitRangeSession('cuosi');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 当前关卡配置
$currentLevel = 3;

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：商品商城 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-shopping-cart"></i>
                <span>天积社区 — 商品商城</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积社区平台 — 商品商城。浏览商品列表，支持通过URL参数控制排序方式</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用SQL注入漏洞获取通关密码。提示：商品列表"正常显示"和"查询失败"之间存在差异，尝试通过URL参数逐字符猜解密码，密码存储在MySQL变量@password3中</small>
                </div>
            </div>


            <!-- 商品列表区域 -->
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
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script src="js/cuosi.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCuosi(<?php echo $currentLevel; ?>, <?php echo json_encode($commonBasePath); ?>);
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
