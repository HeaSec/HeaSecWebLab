<?php
/**
 * HeaSec天积安全团队 - JavaScript 绕过靶场（第一关）
 * 版本: v1.0.0
 * 创建日期: 2025-12-24
 * 团队: 天积安全 (HeavenlySecret)
 * 关卡: String.fromCharCode 混淆 + 开发者工具阻止
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JavaScript 绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JavaScript 绕过靶场';
$rangeName = 'JavaScript 绕过';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 引入会话管理（必须在输出任何HTML内容之前）
require_once $commonBasePath . 'includes/session_manager.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 初始化靶场会话（必须在引入header.php之前完成）
HeaSec_InitRangeSession('jsbypass');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';


// 获取或生成密码
$password = HeaSec_GetSecret(20);

// 将密码转换为 String.fromCharCode 形式
function stringToFromCharCode($str)
{
    $result = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $result .= ord($str[$i]);
        if ($i < strlen($str) - 1) {
            $result .= ',';
        }
    }
    return $result;
}

$fromCharCodeStr = stringToFromCharCode($password);

// 处理表单提交
$loginResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $inputPassword = trim($_POST['password']);

    if ($inputPassword === $password) {
        // 密码正确，更新学习状态：从"待学习"更新为"学习中"
        HeaSec_UpdateLearningStatusIfNeeded('jsbypass');

        $loginResult = [
            'type' => 'success',
            'message' => '密码正确，点击按钮进入下一关'
        ];
    } else {
        $loginResult = [
            'type' => 'error',
            'message' => '密码错误，请重新输入'
        ];
    }
}
?>

<!-- 统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 交互脚本 -->
<script src="js/common.js?v=<?php echo $version; ?>"></script>
<script src="js/custom_utils.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-lock"></i>
                第一关 请输入正确通关密码
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 密码输入表单 -->
            <form class="tech-form" method="post">
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-key"></i>
                        密码
                    </label>
                    <input type="text" id="password" name="password" class="tech-input" placeholder="请输入20位密码"
                        autocomplete="off" onmousemove="showModal('密码是一个20位随机字符串，仔细找找在哪里呢')" aria-label="密码">
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-unlock"></i>
                        提交
                    </button>
                    <?php if ($loginResult && $loginResult['type'] === 'success'): ?>
                        <a href="jsbypass2.php" class="tech-btn tech-btn-success">
                            <i class="fa fa-arrow-right"></i>
                            下一关
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- 登录结果消息 -->
            <?php if ($loginResult): ?>
                <div class="detection-result" style="margin-top: 20px;">
                    <div class="alert alert-<?php echo $loginResult['type']; ?>">
                        <div>
                            <i
                                class="fa fa-<?php echo $loginResult['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <strong><?php echo htmlspecialchars($loginResult['message']); ?></strong>
                        </div>
                        <?php if ($loginResult['type'] === 'error'): ?>
                            <p class="alert-hint">
                                <small>密码是一个20位随机字符串，仔细找找在哪里呢</small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    var password = String.fromCharCode(<?php echo $fromCharCodeStr; ?>);
    console.log('[HeaSec Level 1] : ' + password);


    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        showToast('右键菜单已被禁用！');
    });

    document.addEventListener('keydown', function (e) {
        // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        if (e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'J') ||
            (e.ctrlKey && e.key === 'U')) {
            e.preventDefault();
            e.stopPropagation();
            showToast('开发者工具已被禁用！');
            return false;
        }
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>