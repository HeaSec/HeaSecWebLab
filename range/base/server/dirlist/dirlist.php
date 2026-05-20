<?php
/**
 * HeaSec天积安全团队 - 目录浏览靶场
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec 目录浏览 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// 设置页面变量
$pageTitle = '目录浏览靶场';
$rangeName = '目录浏览';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('dirlist');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共函数
require_once __DIR__ . '/includes/functions.php';

/**
 * 获取或生成会话中的秘密字符串
 */
function getSecret()
{
    return HeaSec_GetSecret(20);
}

// 获取或生成秘密字符串
$secret = getSecret();

// 处理重置请求（在引入header.php之前处理，避免被数据库重置逻辑拦截）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'reset') {
    header('Content-Type: application/json');
    try {
        // 清理已生成的文件
        cleanupFiles();
        // 清除会话中的秘密和生成标记
        unset($_SESSION['heasec_secret']);
        unset($_SESSION['dirlist_files_generated']);
        echo json_encode(['success' => true, 'message' => '重置成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '重置失败: ' . $e->getMessage()]);
    }
    exit;
}

// 生成随机文件（首次访问时）
generateRandomFiles($secret);

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 自定义重置按钮行为（覆盖非数据库模式的默认行为） -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var resetBtn = document.getElementById('resetDatabaseBtn');
    if (!resetBtn) return;

    // 克隆按钮以移除原有事件监听器
    var newBtn = resetBtn.cloneNode(true);
    resetBtn.parentNode.replaceChild(newBtn, resetBtn);

    newBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (window.heasecModalManager) {
            window.heasecModalManager.showResetConfirm({
                action: 'reset',
                url: 'dirlist.php?action=reset',
                onSuccess: function() {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            });
        }
    });
});
</script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 漏洞提示卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-folder-open"></i>
                目录浏览漏洞
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 漏洞说明区域 -->
            <div class="alert alert-info">
                <div>
                    <i class="fa fa-info-circle"></i>
                    <strong>当Web服务器配置不当时，可能会将目录中的文件列表暴露给用户，导致敏感信息泄露</strong>
                </div>
            </div>

            <!-- 提示区域 -->
            <div class="alert alert-warning" style="margin-top: 15px;">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>秘密字符串在当前网站某个目录下的某个文件中，你可以像浏览电脑文件夹一样流量浏览目录中的文件。</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 秘密验证区域 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => '目录浏览漏洞是由于Web服务器配置不当（如Apache开启了Options +Indexes），当目录下没有默认索引文件时，服务器会列出目录中的所有文件和子目录。',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你成功利用目录浏览漏洞发现了隐藏的敏感信息',
        'rangeCode' => 'dirlist'
    ]);
    ?>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
