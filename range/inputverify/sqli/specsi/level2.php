<?php
/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场 - 第二关
 * 版本: v1.0.0
 * 功能: 宽字节注入 — 用户登录/商品搜索 + 通关验证
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec Specsi Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL特殊注入场景靶场 - 第二关';
$rangeName = 'SQL特殊注入场景';
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

// 会话管理（必须在header.php之前初始化）
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('specsi');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);

// 检查登录状态
$isLoggedIn = isset($_SESSION['specsi_user_id']) && ($_SESSION['specsi_level'] ?? 0) === 2;
$currentUsername = $_SESSION['specsi_username'] ?? '';
$currentRole = $_SESSION['specsi_role'] ?? '';
$isAdmin = ($currentRole === 'admin');

// 如果已登录且是管理员，获取通关密码
$adminPasscode = '';
if ($isAdmin) {
    $passcode = getPasscode($currentLevel);
    $adminPasscode = ($passcode !== false) ? $passcode : '';
}
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：商品搜索 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-search"></i>
                <span>天积社区 — 商品搜索</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积社区平台 — 商品搜索功能。输入商品名称搜索商品信息，系统将返回匹配结果</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>获取admin用户的密码并以admin身份登录，查看通关密码。提示：有什么东西太窄了进不去，admin密码存储在 heasec_specsi_accounts 表中</small>
                </div>
            </div>

            <!-- 用户信息区（已登录时显示） -->
            <?php if ($isLoggedIn): ?>
            <div id="userInfoArea" class="alert-info" style="margin-bottom: 15px;">
                <i class="fa fa-user"></i>
                <span>当前用户：<strong><?php echo htmlspecialchars($currentUsername); ?></strong>（角色：<?php echo $isAdmin ? '管理员' : '普通用户'; ?>）</span>
                <button type="button" id="logoutBtn" class="tech-btn tech-btn-secondary" style="margin-left: auto; padding: 4px 12px; font-size: 13px;">
                    <i class="fa fa-sign-out"></i> 退出
                </button>
            </div>
            <?php endif; ?>

            <!-- 管理员面板（仅admin可见） -->
            <?php if ($isAdmin && $adminPasscode): ?>
            <div id="adminPanel" class="alert-success" style="margin-bottom: 15px;">
                <i class="fa fa-star"></i>
                <span>管理员面板 — 通关密码：<strong style="font-family: monospace; letter-spacing: 1px;"><?php echo htmlspecialchars($adminPasscode); ?></strong></span>
            </div>
            <?php endif; ?>

            <!-- 登录表单（未登录时显示） -->
            <?php if (!$isLoggedIn): ?>
            <div class="submit-section">
                <form id="loginForm" class="query-form">
                    <div class="form-group">
                        <label for="loginUsername" class="form-label">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="loginUsername" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="loginPassword" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="text" id="loginPassword" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="loginBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- 商品搜索表单（已登录时显示） -->
            <?php if ($isLoggedIn): ?>
            <div class="submit-section" style="margin-top: 15px;">
                <form id="searchForm" class="query-form">
                    <div class="form-group">
                        <label for="searchKeyword" class="form-label">
                            <i class="fa fa-search"></i> 商品名称
                        </label>
                        <input type="text" id="searchKeyword" name="keyword" class="tech-input" placeholder="请输入商品名称搜索" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="searchBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-search"></i> 搜索
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

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

<script src="js/specsi.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initSpecsi(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
