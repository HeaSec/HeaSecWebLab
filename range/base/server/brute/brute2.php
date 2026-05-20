<?php
/**
 * HeaSec天积安全团队 - 暴力破解靶场（第二关）
 * 版本: v1.0.0
 * 创建日期: 2025-12-10
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 暴力破解 Range v1.0.0 - Level 2');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '暴力破解基础靶场 - 第二关';
$rangeName = '暴力破解基础';
$showVersion = false;
$showResetButton = false; // 第二关不显示重置按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 获取数据库连接
try {
    $pdo = HeaSec_Database::getConnection('heasec_base');

    /**
     * 创建第二关的随机账号
     * 如果数据库中没有第二关的账号，则创建一个
     *
     * @param PDO $pdo 数据库连接
     * @return string 创建的用户名
     */
    function ensureLevel2User($pdo)
    {
        // 检查是否有第二关的账号
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM heasec_brute_users WHERE level = 2");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            // 已有账号，返回空
            return '';
        }

        // 可选的用户名列表
        $usernames = ['zhangjing', 'chenbin', 'wangwei', 'linjie', 'liting', 'zhangwei'];
        $selectedUsername = $usernames[array_rand($usernames)];
        $password = '123456';
        $passwordHash = md5($password);

        // 插入新账号
        $stmt = $pdo->prepare("INSERT INTO heasec_brute_users (username, password, level, created_at) VALUES (?, ?, 2, NOW())");
        $stmt->execute([$selectedUsername, $passwordHash]);

        return $selectedUsername;
    }

    // 确保第二关有账号
    ensureLevel2User($pdo);

    // 处理登录表单提交
    $loginResult = null; // 登录结果：null表示未提交，array表示结果

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // 验证第二关的账号密码
        $stmt = $pdo->prepare("SELECT username, password FROM heasec_brute_users WHERE username = ? AND level = 2 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 验证密码
            $inputPasswordHash = md5($password);

            if ($inputPasswordHash === $user['password']) {
                // 登录成功，更新学习状态：从"待学习"更新为"学习中"
                HeaSec_UpdateLearningStatusIfNeeded('brute');

                $loginResult = [
                    'type' => 'success',
                    'message' => '登录成功，点击按钮进入下一关'
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
                第二关 请尝试登录
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
                        <a href="brute3.php" class="tech-btn tech-btn-success">
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
                                <small>默认用户名为姓名全拼，密码为123456，请及时修改默认密码</small>
                            </p>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>