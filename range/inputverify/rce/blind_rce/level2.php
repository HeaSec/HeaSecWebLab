<?php
/**
 * HeaSec天积安全团队 - 无回显命令注入靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '无回显命令注入 - 第二关';
$rangeName = '无回显命令注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入公共函数
require_once 'includes/functions.php';

// 确保第二关的目标字符串和通关密码文件存在
$targetFile = dirname(__FILE__) . '/config/level2/target.txt';
generateTargetFile($targetFile);
$passcodeFile = dirname(__FILE__) . '/config/level2/passcode.php';
generateSecretFile($passcodeFile);

// 读取目标字符串用于页面展示
$targetString = trim(file_get_contents($targetFile));
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 卡片一：天积网络诊断工具 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-network-wired"></i>
                <span>天积网络诊断工具</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的网络诊断工具支持输入IP地址或域名进行连通性检测。出于安全考虑，系统仅返回目标是否可达，不会展示详细的ping命令输出</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用命令注入漏洞在 execution 目录下创建 attack.txt 文件，完成后点击下方的"检测文件"按钮</small>
                </div>
            </div>

            <!-- Ping测试区域 -->
            <div class="submit-section">
                <div class="ip-input-wrapper">
                    <input type="text" id="ipInput" class="tech-input" placeholder="请输入IP地址或域名" autocomplete="off">
                    <button type="button" id="pingBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-bolt"></i> 测试
                    </button>
                </div>
            </div>

            <!-- Ping结果展示区域 -->
            <div id="pingResultArea" class="ping-result-wrapper"></div>
        </div>
    </div>

    <br>

    <!-- 卡片二：任务目标 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-tasks"></i>
                <span>任务目标</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <div class="task-description">
                请在 <code>execution/</code> 目录下创建 <code>attack.txt</code> 文件，文件内容为以下目标字符串：
            </div>

            <!-- 目标字符串展示 -->
            <div class="target-string-display">
                <div class="target-label">目标字符串（请将此内容写入 attack.txt）</div>
                <div class="target-value"><?php echo htmlspecialchars($targetString); ?></div>
            </div>

            <!-- 文件检测区域 -->
            <div class="check-file-section">
                <button type="button" id="checkFileBtn" class="tech-btn tech-btn-info">
                    <i class="fa fa-search"></i> 检测文件
                </button>
            </div>

            <!-- 检测结果区域 -->
            <div id="checkResultArea" class="detection-result" style="display: none;"></div>

            <!-- 通关密码展示区域（检测通过后显示） -->
            <div id="passcodeArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 卡片三：通关验证 -->
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

<!-- 引入交互脚本 -->
<script src="js/blind_rce.js?v=<?php echo $version; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initBlindRce(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
});
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
