<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第一关：成绩查看系统
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件越权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件越权访问靶场 - 第一关';
$rangeName = '文件越权访问';
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
$levelTitle = '第一关：成绩查看系统';
$nextPage = 'filebac_level2/index.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

// 验证会话完整性
HeaSec_ValidateSession();

// 自定义重置处理（在header.php之前，以便覆盖默认重置行为）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reset') {
    header('Content-Type: application/json');

    require_once $commonBasePath . 'includes/HeaSec_Database.php';
    require_once __DIR__ . '/includes/image_generator.php';

    $response = ['success' => false, 'message' => ''];

    try {
        $deletedCount = 0;
        $baseDir = __DIR__;

        // 删除第一关图片
        $level1Dir = $baseDir . '/transcript/';
        $deletedCount += deletePngFiles($level1Dir);

        // 删除第二关图片
        $level2Dir = $baseDir . '/filebac_level2/order/';
        $deletedCount += deleteAllPngFiles($level2Dir);

        // 删除第三关图片
        $level3Dir = $baseDir . '/filebac_level3/idcard/';
        $deletedCount += deletePngFiles($level3Dir);

        // 执行数据库初始化脚本
        $pdo = HeaSec_Database::getServerConnection();
        $sqlContent = file_get_contents($initSqlFile);

        // 移除注释并分割SQL语句
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
        $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

        $pdo->beginTransaction();
        foreach ($sqlStatements as $sql) {
            if (!empty($sql)) {
                $pdo->exec($sql);
            }
        }
        $pdo->commit();

        // 清除会话
        $_SESSION = [];

        $response['success'] = true;
        $response['message'] = '重置成功，已删除 ' . $deletedCount . ' 个图片文件';

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = '重置失败: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入数据初始化
require_once __DIR__ . '/includes/user-init.php';

// 获取数据库连接并初始化用户数据
$pdo = HeaSec_Database::getConnection('heasec_logic');
initLevel1Data($pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['filebac_level1_logged_in']) && $_SESSION['filebac_level1_logged_in'] === true;
$currentUser = isset($_SESSION['filebac_level1_user']) ? $_SESSION['filebac_level1_user'] : null;

// 处理退出登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['filebac_level1_logged_in']);
    unset($_SESSION['filebac_level1_user']);
    $isLoggedIn = false;
    $currentUser = null;
}

// 检查通关状态
$isPassed = isset($_SESSION['filebac_level1_passed']) && $_SESSION['filebac_level1_passed'] === true;
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-graduation-cap"></i>
                <?php echo $isLoggedIn ? '学生信息' : '成绩查看系统'; ?>
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
                    <small><?php echo $isLoggedIn ? '想看看同专业其他同学的成绩单吗？' : '登录系统查看成绩'; ?></small>
                </span>
            </div>

            <!-- 登录表单 -->
            <div id="loginSection" style="display: <?php echo !$isLoggedIn ? 'block' : 'none'; ?>;">
                <form id="loginForm" class="tech-form">
                    <div class="form-group">
                        <label for="account" class="form-label">
                            <i class="fa fa-user"></i> 账号
                        </label>
                        <input type="text" id="account" name="account" class="tech-input" placeholder="请输入账号" autocomplete="off">
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
                        <small>测试账号：test / 123456</small>
                    </div>
                </form>
            </div>

            <!-- 已登录状态 -->
            <div id="userInfoSection" style="display: <?php echo $isLoggedIn ? 'block' : 'none'; ?>;">
                <div id="userInfoContainer" data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
                    <div id="userInfoLoading" style="text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin"></i> 加载学生信息...
                    </div>
                    <div id="userInfoDisplay" style="display: none;"></div>
                </div>
                <div class="form-actions">
                    <button type="button" id="viewTranscriptBtn" class="tech-btn tech-btn-primary" style="display: none;">
                        <i class="fa fa-file-image-o"></i> 查看成绩单
                    </button>
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
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: <?php echo $isPassed ? 'inline-flex' : 'none'; ?>;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>

</div>

<!-- 引入交互脚本 -->
<script src="js/filebac.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initFilebac(1, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
