<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '反序列化练习 - 第三关';
$rangeName = '反序列化练习';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 3;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入公共函数
require_once 'includes/functions.php';

// 确保当前关卡的secret.php存在
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 数据处理卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user-circle"></i>
                <span>天积用户管理平台 - 数据处理</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统提供用户资料更新接口，已启用安全过滤机制，会自动过滤危险关键字</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>构造序列化字符串获取通关密码。提示：过滤会把东西变没的，想想长度会发生什么变化</small>
                </div>
            </div>

            <!-- 序列化数据输入区域 -->
            <div class="submit-section">
                <textarea id="serializedData" placeholder="请阅读关键类定义，输入序列化字符串，提交后系统会自动进行反序列化操作"></textarea>
                <div class="submit-actions">
                    <button type="button" id="submitBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-paper-plane"></i> 提交
                    </button>
                    <button type="button" id="sourceCodeBtn" class="tech-btn tech-btn-info">
                        <i class="fa fa-code"></i> 查看源代码
                    </button>
                </div>
            </div>

            <!-- 源码展示区域（默认隐藏，内容由JS动态渲染） -->
            <div id="sourceArea" class="source-display" style="display: none;"></div>

            <!-- 反序列化结果区域 -->
            <div id="resultArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
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

<!-- 引入交互脚本 -->
<script src="js/deserbase.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDeserBase(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
