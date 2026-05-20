<?php
/**
 * HeaSec天积安全团队 - HTML前端校验绕过靶场
 * 版本: v1.0.0
 * 创建日期: 2025-12-13
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec HTML前端校验绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'HTML前端校验绕过靶场';
$rangeName = 'HTML前端校验绕过';
$showVersion = false;
$showResetButton = false;  // 此靶场不使用数据库，无需重置按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 引入星星系统组件资源（包含恭喜弹窗）
echo HeaSec_StarSystem::renderAssets($commonBasePath, [
    'css' => true,
    'js' => true,
    'congrats' => true
]);

?>

<!-- 统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 交互脚本 -->
<script src="js/interactions.js?v=<?php echo $version; ?>"></script>

<!-- 定义全局配置 -->
<script>
    window.heasecConfig = {
        commonBasePath: '<?php echo $commonBasePath; ?>',
        rangeCode: 'htmlbypass'
    };
</script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 申请表单区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user-plus"></i>
                请尝试通过申请：天积安全黑客学院申请表单
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 申请表单 -->
            <form class="tech-form" id="applicationForm" onsubmit="return false;">
                <!-- 姓名输入 -->
                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fa fa-user"></i>
                        姓名
                    </label>
                    <input type="text" id="name" name="name" class="tech-input" placeholder="请输入真实姓名" required
                        aria-label="姓名">
                </div>

                <!-- 昵称输入 -->
                <div class="form-group">
                    <label for="nickname" class="form-label">
                        <i class="fa fa-tag"></i>
                        昵称
                    </label>
                    <input type="text" id="nickname" name="nickname" class="tech-input" value="脚本小子" readonly
                        aria-label="昵称">
                </div>

                <!-- 性别选择 -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa fa-venus-mars"></i>
                        性别
                    </label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="男">
                            <span class="radio-custom"></span>
                            <i class="fa fa-mars"></i> 男
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="女">
                            <span class="radio-custom"></span>
                            <i class="fa fa-venus"></i> 女
                        </label>
                    </div>
                </div>

                <!-- 手机号输入 -->
                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fa fa-phone"></i>
                        手机号
                    </label>
                    <input type="text" id="phone" name="phone" class="tech-input" placeholder="请输入11位手机号"
                        pattern="[0-9]{11}" maxlength="11" required aria-label="手机号">
                </div>

                <!-- 年龄输入 -->
                <div class="form-group">
                    <label for="age" class="form-label">
                        <i class="fa fa-birthday-cake"></i>
                        年龄
                    </label>
                    <input type="number" id="age" name="age" class="tech-input" placeholder="请输入年龄" min="35" max="60"
                        required aria-label="年龄">
                </div>

                <!-- 特长选择 -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa fa-star"></i>
                        特长
                    </label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="skill" value="长得好看">
                            <span class="radio-custom"></span>
                            <i class="fa fa-smile-o"></i> 长得好看
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="skill" value="脑子灵光">
                            <span class="radio-custom"></span>
                            <i class="fa fa-lightbulb-o"></i> 脑子灵光
                        </label>
                        <!-- 真正的特长往往需要深入的挖掘
                        <label class="radio-label">
                            <input type="radio" name="skill" value="热爱网络安全" >
                            <span class="radio-custom" ></span>
                            <i class="fa fa-shield"></i> 热爱网络安全
                        </label>
                        -->
                    </div>
                </div>

                <!-- 干过的坏事 -->
                <div class="form-group">
                    <label for="bad_deed" class="form-label">
                        <i class="fa fa-exclamation-triangle"></i>
                        干过的坏事
                    </label>
                    <textarea id="bad_deed" name="bad_deed" class="tech-input" rows="3" placeholder="请如实填写您干过的坏事"
                        required aria-label="干过的坏事"></textarea>
                </div>

                <!-- 申请理由 -->
                <div class="form-group">
                    <label for="reason" class="form-label">
                        <i class="fa fa-edit"></i>
                        申请理由
                    </label>
                    <textarea id="reason" name="reason" class="tech-input" rows="2" placeholder="请输入申请理由（10个字符）"
                        maxlength="10" aria-label="申请理由"></textarea>
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-paper-plane"></i>
                        提交申请
                    </button>
                </div>
            </form>

            <!-- 提交结果消息容器 -->
            <div id="submitResult" style="margin-top: 20px; display: none;">
                <div class="detection-result">
                    <div class="alert" id="resultAlert">
                        <div id="resultContent">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong id="resultMessage"></strong>
                        </div>
                        <div class="alert-hint" id="resultHint" style="display: none;">
                            <small>提示：尝试修改HTML代码绕过前端限制，提交符合要求的申请内容！</small>
                        </div>
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