<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '反序列化实战 - 第三关';
$rangeName = '反序列化实战';
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

// 确保当前关卡的secret文件存在（第三关使用纯文本格式）
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath, true);
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
                <i class="fa fa-puzzle-piece"></i>
                <span>天积插件管理系统 - 插件配置</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的异常报告接口采用严格的内置类白名单策略，仅允许反序列化 PHP 原生内置类（Exception、Error、ArrayObject 等），已完全禁用所有用户自定义类。系统会将异常对象的消息内容提取出来，对指定的目标进行日志式预览</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用 PHP 内置类构造序列化 payload 读取秘密文件获取通关密码。提示：虽然系统禁止了所有自定义类，但 PHP 的内置类同样具有属性和方法。仔细思考 Exception 和 Error 这类异常处理类的内部结构 —— 它们有哪些属性？这些属性的访问修饰符是什么？它们在序列化字符串中是如何编码的？秘密文件是纯文本格式，位于 config/ 目录下，文件名以点号开头（隐藏文件），后缀为 _secret。你已经在第二关中学习了 private 和 protected 属性的序列化格式差异，这个知识在这里同样重要</small>
                </div>
            </div>

            <!-- 序列化数据输入区域 -->
            <div class="submit-section">
                <textarea id="serializedData" placeholder="请阅读关键源码，分析内置类的属性结构，输入序列化字符串"></textarea>
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
<script src="js/deseradv.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDeserAdv(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
