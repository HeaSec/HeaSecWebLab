<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第三关：实名登记系统
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件越权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件越权访问靶场 - 第三关';
$rangeName = '文件越权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = dirname(__DIR__) . '/database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$resetUrl = '../api/reset.php';

// 当前关卡
$currentLevel = 3;
$levelTitle = '第三关：实名登记系统';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入数据初始化
require_once dirname(__DIR__) . '/includes/user-init.php';

// 获取数据库连接并初始化用户数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
initLevel3Data($pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['filebac_level3_logged_in']) && $_SESSION['filebac_level3_logged_in'] === true;

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['filebac_level3_logged_in']);
    unset($_SESSION['filebac_level3_user']);
    $isLoggedIn = false;
}

// 检查通关状态
$isPassed = isset($_SESSION['filebac_level3_passed']) && $_SESSION['filebac_level3_passed'] === true;
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="../css/style.css">
<!-- 引入恭喜弹窗样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-id-card"></i>
                <?php echo $isLoggedIn ? '实名信息' : '实名登记系统'; ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small><?php echo $isLoggedIn ? '这个文件名看起来像是某种不可逆的……' : '使用天积移动的福州人才知道秘密'; ?></small>
                </span>
            </div>

            <!-- 登录表单 -->
            <div id="loginSection" style="display: <?php echo !$isLoggedIn ? 'block' : 'none'; ?>;">
                <form id="loginForm" class="tech-form">
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fa fa-mobile"></i> 手机号
                        </label>
                        <input type="text" id="phone" name="phone" class="tech-input" placeholder="请输入手机号" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                    <div id="loginResultArea" class="detection-result" style="display: none;"></div>
                    <!-- 测试账号提示 -->
                    <div class="test-account-hint" style="text-align: center; margin-top: 15px; color: #888; font-size: 13px;">
                        <small>测试账号：13805916688 / 123456</small>
                    </div>
                </form>
            </div>

            <!-- 已登录状态 -->
            <div id="userInfoSection" style="display: <?php echo $isLoggedIn ? 'block' : 'none'; ?>;">
                <div id="userInfoContainer" data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
                    <div id="userInfoLoading" style="text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin"></i> 加载用户信息...
                    </div>
                    <div id="userInfoDisplay" style="display: none;"></div>
                </div>

                <!-- 身份证上传区域 -->
                <div id="uploadSection" style="margin-top: 20px; padding: 15px; border: 2px dashed #ddd; border-radius: 8px; text-align: center; display: none;">
                    <h4 style="margin: 0 0 10px 0; color: #666;"><i class="fa fa-cloud-upload"></i> 上传身份证照片</h4>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="file" id="idcard_image" name="idcard_image" accept="image/png,image/jpeg,image/jpg" style="display: none;">
                        <button type="button" class="tech-btn tech-btn-primary" onclick="document.getElementById('idcard_image').click();">
                            <i class="fa fa-upload"></i> 选择图片
                        </button>
                        <span id="fileName" style="margin-left: 10px; color: #888;"></span>
                    </form>
                    <div id="uploadResult" style="margin-top: 10px;"></div>

                    <!-- 当前身份证预览 -->
                    <div id="idcardPreview" style="margin-top: 20px; display: none;">
                        <h4 style="margin: 0 0 10px 0; color: #666;"><i class="fa fa-id-card"></i> 当前身份证照片</h4>
                        <div id="idcardImageContainer" style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;"></div>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 15px;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="tech-btn tech-btn-danger">
                            <i class="fa fa-sign-out"></i> 退出登录
                        </button>
                    </form>
                </div>
            </div>
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

<!-- 引入交互脚本 -->
<script src="../js/filebac.js?v=<?php echo $version; ?>"></script>
<!-- 引入恭喜弹窗脚本 -->
<script src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initFilebac(3, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
