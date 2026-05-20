<?php
/**
 * HeaSec天积安全团队 - 暴力破解靶场（第三关）
 * 版本: v1.0.0
 * 创建日期: 2025-12-10
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 暴力破解 Range v1.0.0 - Level 3');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '暴力破解基础靶场 - 第三关';
$rangeName = '暴力破解基础';
$showVersion = false;
$showResetButton = false; // 第三关不显示重置按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入星星系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 启动输出缓冲
ob_start();

// 获取数据库连接
try {
    $pdo = HeaSec_Database::getConnection('heasec_base');

    /**
     * 创建第三关的随机账号
     * 如果数据库中没有第三关的账号，则创建一个
     *
     * @param PDO $pdo 数据库连接
     * @return array 创建的用户名和密码数组
     */
    function ensureLevel3User($pdo)
    {
        // 检查是否有第三关的账号
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM heasec_brute_users WHERE level = 3");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            // 已有账号，返回空
            return [];
        }

        // 可选的用户名和密码列表
        $usernames = ['test', 'user', 'guest'];
        $passwords = ['test', 'abc123', 'password', 'admin123', '123123', 'test123'];

        $selectedUsername = $usernames[array_rand($usernames)];
        $selectedPassword = $passwords[array_rand($passwords)];
        $passwordHash = md5($selectedPassword);

        // 插入新账号
        $stmt = $pdo->prepare("INSERT INTO heasec_brute_users (username, password, level, created_at) VALUES (?, ?, 3, NOW())");
        $stmt->execute([$selectedUsername, $passwordHash]);

        return [
            'username' => $selectedUsername,
            'password' => $selectedPassword
        ];
    }

    // 确保第三关有账号
    $level3User = ensureLevel3User($pdo);

    // 处理登录表单提交
    $loginResult = null; // 登录结果：null表示未提交，array表示结果

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // 验证第三关的账号密码
        $stmt = $pdo->prepare("SELECT username, password FROM heasec_brute_users WHERE username = ? AND level = 3 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 验证密码
            $inputPasswordHash = md5($password);

            if ($inputPasswordHash === $user['password']) {
                // 登录成功
                $loginResult = [
                    'type' => 'success',
                    'message' => '登录成功，你找到了我的秘密'
                ];
            } else {
                // 登录失败
                $loginResult = [
                    'type' => 'error',
                    'message' => '登录失败，账号或密码错误'
                ];
            }
        } else {
            $loginResult = [
                'type' => 'error',
                'message' => '登录失败，账号或密码错误'
            ];
        }
    }
} catch (Exception $e) {
    error_log('[HeaSec] 数据库连接失败: ' . $e->getMessage());
    die('数据库连接失败，请检查数据库配置。');
}

// 引入星星系统组件资源（包含恭喜弹窗）
echo HeaSec_StarSystem::renderAssets($commonBasePath, [
    'css' => true,
    'js' => true,
    'congrats' => true
]);

// 如果需要显示恭喜弹窗，则准备JavaScript代码
$showCongrats = false;
$congratsConfig = [];

if ($loginResult && $loginResult['type'] === 'success') {
    $showCongrats = true;
    $congratsConfig = [
        'title' => '🎉 恭喜你掌握了一个新技能',
        'message' => '你掌握了http暴力破解攻击的基础实现方式',
        'buttonText' => '继续学习',
        'enableNextRangeButton' => true,
        'rangeCode' => 'brute',
        'nextRangeApiUrl' => $commonBasePath . 'api/next-range.php',
        'updateLearningStatus' => true,
        'updateStatusApiUrl' => $commonBasePath . 'api/update-learning-status.php',
        'learningStatus' => '已掌握',
        'showParticles' => true,
        'particleCount' => 8,
        'animationDuration' => 2000
    ];
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件（用于覆盖和扩展） -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入交互脚本 -->
<script src="js/interactions.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 登录表单区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-sign-in"></i>
                第三关 请尝试登录
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 登录表单 -->
            <form class="tech-form" method="post">
                <!-- 用户名输入 -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名"
                        aria-label="用户名">
                </div>

                <!-- 密码输入 -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i>
                        密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off" aria-label="密码">
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i>
                        登录
                    </button>
                    <?php if ($loginResult && $loginResult['type'] === 'success'): ?>
                        <a href="index.php" class="tech-btn tech-btn-success">
                            <i class="fa fa-arrow-left"></i>
                            返回第一关
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
                                <small>这个系统有个测试账号，尝试登录一下</small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($showCongrats): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 等待页面加载完成后显示恭喜弹窗
            setTimeout(function () {
                if (typeof HeaSecCongratsModal !== 'undefined') {
                    HeaSecCongratsModal.show(<?php echo json_encode($congratsConfig); ?>);
                } else {
                    console.error('[HeaSec] 恭喜弹窗组件未加载');
                    // 降级处理：显示简单提示
                    alert('恭喜你掌握了http暴力破解攻击的实现方式！');
                }
            }, 500); // 延迟500ms确保组件已加载
        });
    </script>
<?php endif; ?>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>