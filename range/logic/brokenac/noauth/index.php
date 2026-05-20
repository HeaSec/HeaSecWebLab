<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第一关登录页面
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 未授权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '未授权访问靶场 - 第一关';
$rangeName = '未授权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 1;
$nextPage = 'noauth_level2/index.php';
$nextBtnText = '第二关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('noauth');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入配置初始化
require_once __DIR__ . '/includes/config-init.php';

// 获取数据库连接并初始化配置数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
$config = initNoauthLevelConfig($currentLevel, $pdo);

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['noauth_level1_logged_in']);
    unset($_SESSION['noauth_level1_user']);
    header('Location: index.php');
    exit;
}

// 检查登录状态
$isLoggedIn = isset($_SESSION['noauth_level1_logged_in']) && $_SESSION['noauth_level1_logged_in'] === true;

// 如果已登录，重定向到管理页面
if ($isLoggedIn) {
    header('Location: ' . htmlspecialchars($config['random_path']));
    exit;
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">

    <!-- 登录卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-wifi"></i> HeaSec Router - 第一关
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>系统提示</strong>
                </div>
                <span class="alert-hint">
                    <small>本系统仅限管理员访问</small>
                </span>
            </div>

            <form id="loginForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="account">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="account" name="account" class="tech-input" placeholder="请输入账号"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa fa-key"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
                <div id="loginResultArea" class="result-area"></div>
            </form>
        </div>
    </div>

    <br>

    <!-- 通关密码验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i> 通关验证
            </h3>
        </div>
        <div class="tech-card-body">
            <form id="passcodeForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="passcode">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn"
                        class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="passcodeResultArea" class="result-area"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/noauth.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initNoauth(1, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>