<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 第三关配置页面
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 垂直越权基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '垂直越权基础靶场 - 第三关配置';
$rangeName = '垂直越权基础';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 当前关卡
$currentLevel = 3;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('vertbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入用户初始化
require_once __DIR__ . '/../includes/user-init.php';

// 获取数据库连接并初始化用户数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
initVertbpLevelUsers($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['vertbp_level3_logged_in']) && $_SESSION['vertbp_level3_logged_in'] === true;

// 如果未登录，重定向到登录页面
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

// 从Session中校验当前账号角色
$isAdmin = isset($_SESSION['vertbp_level3_user']['role']) && $_SESSION['vertbp_level3_user']['role'] === 'admin';

if (!$isAdmin) {
    // 显示权限拒绝页面
    ?>
    <!-- 引入统一样式文件 -->
    <link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
    <!-- 引入站点特定样式文件 -->
    <link rel="stylesheet" href="../css/style.css">

    <div class="tech-container">
        <div class="tech-card">
            <div class="tech-card-body" style="text-align: center; padding: 40px;">
                <i class="fa fa-lock" style="font-size: 48px; color: #dc3545; margin-bottom: 20px;"></i>
                <h2 style="color: #333; margin-bottom: 10px;">权限不足</h2>
                <p style="color: #666; margin-bottom: 20px;">仅管理员可访问此页面</p>
                <button class="tech-btn tech-btn-outline" onclick="window.close()">
                    <i class="fa fa-times"></i> 关闭窗口
                </button>
            </div>
        </div>
    </div>
    <?php
    require_once $commonBasePath . 'includes/footer.php';
    exit;
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="../css/style.css">

<style>
    /* 隐藏公共头部和底部，保留纯净的小窗口卡片风格并居中卡片 */
    .top-header,
    .footer {
        display: none !important;
    }

    html,
    body {
        height: 100%;
        margin: 0;
    }

    .content-wrapper {
        padding: 20px !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main-container {
        width: 100%;
        margin: 0 auto;
    }

    .tech-container {
        padding: 0 !important;
        margin: 0 auto;
    }
</style>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-cog"></i> 高级配置</h3>
            <button class="tech-btn tech-btn-outline" onclick="window.close()"
                style="padding: 4px 12px; font-size: 12px;">
                <i class="fa fa-times"></i> 关闭窗口
            </button>
        </div>
        <div class="tech-card-body" id="configContainer">
            <div id="configLoading" style="text-align: center; padding: 40px;">
                <i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #007bff;"></i>
                <div style="margin-top: 10px; color: #6c757d;">加载配置数据...</div>
            </div>
            <div id="configDisplay" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="../js/vertbp.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initVertbp(3, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>