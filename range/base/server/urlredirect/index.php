<?php
/**
 * HeaSec天积安全团队 - URL任意跳转靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec URL任意跳转 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'URL任意跳转';
$rangeName = 'URL任意跳转';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从urlredirect目录到range/common/需要向上3级）
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 定义访问常量
if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

// 引入会话管理和数据库
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('urlredirect');
HeaSec_ValidateSession();

// 引入公共函数
require_once 'includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_base');

// 检查登录状态
$isLoggedIn = isset($_SESSION['urlredirect_user_id']) && !empty($_SESSION['urlredirect_user_id']);

// 已登录状态处理
if ($isLoggedIn) {
    $url = isset($_GET['url']) ? $_GET['url'] : '';
    handleUrlRedirect($_SESSION['urlredirect_user_id'], $url, $pdo, 'dashboard.php');
}

// 未登录状态 - 获取GET参数
$urlParam = isset($_GET['url']) ? $_GET['url'] : '';
$errorParam = isset($_GET['error']) ? true : false;

// 引入公共头部（包含自动数据库检查）
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户登录卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                用户登录
            </h3>
        </div>
        <div class="tech-card-body">

            <!-- 安全提示 -->
            <div class="alert alert-info">
                <div>
                    <i class="fa fa-info-circle"></i>
                    <strong>请尝试让系统登录后跳转到baidu.com下的任意页面</strong>                   
                </div>
                <small>PS：系统登录后会根据URL参数跳转到指定地址，只允许跳转到heasec.com下的页面</small>     
            </div>
       
            <?php if ($errorParam): ?>
                <div class="alert alert-error">
                    <div>
                        <i class="fa fa-times-circle"></i>
                        <strong>用户名或密码错误</strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 登录表单 -->
            <form id="loginForm" method="POST" action="api/login.php">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fa fa-user"></i> 用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input"
                           placeholder="请输入用户名" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa fa-key"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input"
                           placeholder="请输入密码" autocomplete="off" required>
                </div>
                <?php if (!empty($urlParam)): ?>
                    <input type="hidden" name="url" value="<?php echo htmlspecialchars($urlParam); ?>">
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
            </form>

            <div style="margin-top: 15px; text-align: center; color: #888; font-size: 12px;">
                测试账号：heasec / 123456
            </div>
        </div>
    </div>

    <!-- 任务说明卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-tasks"></i>
                任务说明
            </h3>
        </div>
        <div class="tech-card-body">
            <div class="tech-info-panel">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">任务描述</span>
                        <span class="info-value">这个网站的登录功能支持跳转到指定页面，但跳转检查似乎存在漏洞</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">任务目标</span>
                        <span class="info-value">构造一个URL，使其通过安全检查但实际跳转到 www.baidu.com</span>
                    </div>
                </div>
            </div>



            <div class="tech-info-panel">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">已知条件</span>
                        <span class="info-value">信任域名：<code>heasec.com</code> | 目标域名：<code>www.baidu.com</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
