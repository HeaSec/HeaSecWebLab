<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战初始化与重置辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 确保基础数据存在。
 *
 * @param PDO $pdo 数据库连接
 */
function privesc_ensure_seed_data(PDO $pdo)
{
    $lockName = 'heasec_privesc_seed_lock';
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 10)');
    $lockStmt->execute([$lockName]);

    try {
        if (privesc_get_user_count($pdo) > 0) {
            privesc_get_seed_admin_data($pdo);
            return;
        }

        $config = privesc_get_config();
        $adminConfig = $config['default_admin'];

        privesc_ensure_avatar_directory();
        $adminPassword = privesc_generate_admin_password(10);
        $avatarFilename = privesc_seed_default_avatar();

        $adminId = privesc_create_user($pdo, [
            'username' => $adminConfig['username'],
            'password' => $adminPassword,
            'name' => $adminConfig['name'],
            'phone' => $adminConfig['phone'],
            'role' => 2,
            'status' => 1,
            'avatar' => $avatarFilename,
        ]);

        privesc_create_address($pdo, $adminId, $adminConfig['address']);

        $_SESSION['privesc_seed_admin'] = [
            'username' => $adminConfig['username'],
            'password' => $adminPassword,
        ];
    } finally {
        $unlockStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $unlockStmt->execute([$lockName]);
    }
}

/**
 * 获取初始管理员展示信息。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function privesc_get_seed_admin_data(PDO $pdo)
{
    $config = privesc_get_config();
    $defaultAdmin = isset($config['default_admin']) && is_array($config['default_admin']) ? $config['default_admin'] : [];
    $defaultUsername = isset($defaultAdmin['username']) ? (string) $defaultAdmin['username'] : 'admin';

    $adminUser = privesc_fetch_user_by_username($pdo, $defaultUsername);
    if ($adminUser) {
        $seedAdmin = [
            'username' => (string) $adminUser['username'],
            'password' => (string) $adminUser['password'],
        ];
        $_SESSION['privesc_seed_admin'] = $seedAdmin;
        return $seedAdmin;
    }

    if (isset($_SESSION['privesc_seed_admin']) && is_array($_SESSION['privesc_seed_admin'])) {
        return $_SESSION['privesc_seed_admin'];
    }

    return [
        'username' => $defaultUsername,
        'password' => '',
    ];
}

/**
 * 生成默认头像文件。
 *
 * @return string
 */
function privesc_seed_default_avatar()
{
    $filename = privesc_generate_avatar_filename('png');
    $path = privesc_get_avatar_directory() . $filename;

    // 图片尺寸
    $size = 100;
    $image = imagecreatetruecolor($size, $size);

    // 背景色：科技蓝渐变效果
    $bgColor = imagecolorallocate($image, 30, 60, 114);
    imagefill($image, 0, 0, $bgColor);

    // 添加渐变效果
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = (int)(30 + (42 - 30) * $ratio);
        $g = (int)(60 + (82 - 60) * $ratio);
        $b = (int)(114 + (152 - 114) * $ratio);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $size, $y, $color);
    }

    // 加载字体
    $fontPath = dirname(__DIR__, 4) . '/common/assets/fonts/simhei.ttf';
    if (!is_file($fontPath)) {
        // 字体不存在时回退到纯色背景
        $bgColor = imagecolorallocate($image, 30, 60, 114);
        imagefill($image, 0, 0, $bgColor);
    }

    // 文字颜色：白色
    $textColor = imagecolorallocate($image, 255, 255, 255);

    // 绘制"关"字，居中
    $text = '关';
    $fontSize = 48;

    // 计算文字位置使其居中
    $textBox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = $textBox[2] - $textBox[0];
    $textHeight = $textBox[1] - $textBox[7];
    $x = ($size - $textWidth) / 2 - $textBox[0];
    $y = ($size - $textHeight) / 2 + $textHeight;

    imagettftext($image, $fontSize, 0, (int)$x, (int)$y, $textColor, $fontPath, $text);

    // 保存图片
    imagepng($image, $path);
    imagedestroy($image);

    return $filename;
}

/**
 * 执行重置。
 */
function privesc_execute_reset()
{
    $initSqlFile = dirname(__DIR__) . '/database/init_database.sql';
    if (!is_file($initSqlFile)) {
        throw new Exception('初始化脚本不存在');
    }

    $avatarDirectory = privesc_get_avatar_directory();
    if (is_dir($avatarDirectory)) {
        foreach (glob($avatarDirectory . '*') as $file) {
            if (!is_file($file)) {
                continue;
            }

            if (basename($file) === 'index.html') {
                continue;
            }

            unlink($file);
        }
    }

    $sqlContent = file_get_contents($initSqlFile);
    $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

    $serverPdo = HeaSec_Database::getServerConnection();
    $serverPdo->beginTransaction();

    try {
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            $serverPdo->exec($statement);
        }
        $serverPdo->commit();
    } catch (Exception $exception) {
        if ($serverPdo->inTransaction()) {
            $serverPdo->rollBack();
        }
        throw $exception;
    }

    privesc_set_type_cookie(null);
    HeaSec_SessionManager::destroySession();
    HeaSec_InitRangeSession('privesc');
    HeaSec_ValidateSession();
    privesc_set_type_cookie(null);

    $freshPdo = privesc_get_pdo();
    privesc_ensure_seed_data($freshPdo);
}
