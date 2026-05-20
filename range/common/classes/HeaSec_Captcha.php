<?php
/**
 * HeaSec天积安全团队 - 验证码组件
 * HeaSec Captcha Component
 * 版本: v1.0.0
 * 创建日期: 2026-01-15
 * 团队: 天积安全 (HeavenlySecret)
 */

class HeaSec_Captcha
{
    private $width;
    private $height;
    private $length;
    private $fontSize;
    private $charset;
    private $code;
    private $image;
    private $sessionKey;
    private $commonBasePath;

    /**
     * 构造函数
     * @param int $width 图片宽度
     * @param int $height 图片高度
     * @param int $length 验证码长度
     * @param int $fontSize 字体大小
     * @param string $sessionKey Session存储键名
     * @param string $commonBasePath common目录的基础路径（从靶场目录到common目录的相对路径）
     */
    public function __construct($width = 120, $height = 40, $length = 4, $fontSize = 20, $sessionKey = 'heasec_captcha', $commonBasePath = '../common/')
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->fontSize = $fontSize;
        $this->sessionKey = $sessionKey;
        $this->commonBasePath = $commonBasePath;
        // 去除易混淆字符 (0, o, O, 1, l, I)
        $this->charset = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    }

    /**
     * 生成验证码并输出图片
     */
    public function generate()
    {
        // 1. 生成随机码
        $this->code = $this->generateCode();

        // 2. 保存到Session
        $this->saveToSession();

        // 3. 创建图片资源
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // 4. 设置背景色 (浅色)
        $backgroundColor = imagecolorallocate($this->image, mt_rand(240, 255), mt_rand(240, 255), mt_rand(240, 255));
        imagefill($this->image, 0, 0, $backgroundColor);

        // 5. 添加干扰点 (增大尺寸，加深颜色)
        for ($i = 0; $i < 80; $i++) {
            // 颜色更深：0-200
            $color = imagecolorallocate($this->image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            // 使用小圆点代替单像素，使其更大更明显
            imagefilledellipse($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(2, 4), mt_rand(2, 4), $color);
        }

        // 6. 添加干扰线 (加粗，加深颜色)
        // 设置线条宽度
        imagesetthickness($this->image, mt_rand(2, 3));
        for ($i = 0; $i < 6; $i++) {
            // 颜色更深
            $color = imagecolorallocate($this->image, mt_rand(80, 220), mt_rand(80, 220), mt_rand(80, 220));
            imageline($this->image, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        // 6.5 添加干扰曲线 (加粗)
        imagesetthickness($this->image, 2);
        for ($i = 0; $i < 3; $i++) {
            $color = imagecolorallocate($this->image, mt_rand(80, 220), mt_rand(80, 220), mt_rand(80, 220));
            $cx = mt_rand(0, $this->width);
            $cy = mt_rand(0, $this->height);
            $w = mt_rand($this->width / 2, $this->width);
            $h = mt_rand($this->height / 2, $this->height);
            imagearc($this->image, $cx, $cy, $w, $h, mt_rand(0, 360), mt_rand(0, 360), $color);
        }

        // 恢复默认线条宽度，以免影响文字（如果文字使用线条绘制的话，但这里用的是 TTF/String）
        imagesetthickness($this->image, 1);

        // 7. 写入文字
        // __DIR__ is classes, dirname(__DIR__) is common
        $fontFile = dirname(__DIR__) . '/assets/fonts/arial.ttf';
        $useTtf = file_exists($fontFile) && function_exists('imagettftext');

        $span = floor($this->width / ($this->length + 1));
        for ($i = 0; $i < $this->length; $i++) {
            $char = $this->code[$i];

            // 随机颜色
            // 如果使用TTF，颜色可以深一些
            $color = imagecolorallocate($this->image, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));

            if ($useTtf) {
                // 使用 TTF 字体
                $angle = mt_rand(-20, 20); // 增加旋转角度
                $size = $this->fontSize > 0 ? $this->fontSize : 20;

                // 计算坐标 (TTF 的坐标是左下角基线)
                // x: 分布在区间内
                // y: 垂直居中附近
                $x = $span * ($i + 1) - ($size / 2);
                $y = ($this->height / 2) + ($size / 2) + mt_rand(-10, 10); // 增加垂直抖动

                imagettftext($this->image, $size, $angle, $x, $y, $color, $fontFile, $char);
            } else {
                // 回退到内置字体
                $x = $span * ($i + 1) - 10;
                // imagestring 坐标是左上角
                $y = mt_rand($this->height / 2 - 10, $this->height / 2);
                imagestring($this->image, 5, $x, $y, $char, $color);
            }
        }

        // 8. 输出图片
        header('Content-Type: image/png');
        // 添加防缓存头
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        imagepng($this->image);
        imagedestroy($this->image);

        // 9. 调试模式下记录验证码到日志
        $this->logCaptcha();
    }

    /**
     * 验证输入的验证码
     * @param string $input 用户输入的验证码
     * @param bool $caseSensitive 是否区分大小写
     * @return bool
     */
    public function verify($input, $caseSensitive = false)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }

        $sessionCode = $_SESSION[$this->sessionKey];

        // 验证一次后立即销毁，防止重放攻击
        unset($_SESSION[$this->sessionKey]);

        if ($caseSensitive) {
            return $input === $sessionCode;
        } else {
            return strtolower($input) === strtolower($sessionCode);
        }
    }

    /**
     * 获取当前验证码（仅用于调试）
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 生成随机码
     */
    private function generateCode()
    {
        $len = strlen($this->charset);
        $code = '';
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $this->charset[mt_rand(0, $len - 1)];
        }
        return $code;
    }

    /**
     * 保存到 Session
     */
    private function saveToSession()
    {
        // 确保 Session 已开启
        if (session_status() == PHP_SESSION_NONE) {
            // 注意：具体项目可能已经通过 HeaSec_SessionManager 开启了 Session
            // 这里做个保险检查
        }

        $_SESSION[$this->sessionKey] = $this->code;
    }

    /**
     * 读取测试配置
     * @return array 配置数组，默认返回 ['captcha_debug' => false]
     */
    private function getTestConfig()
    {
        $configPath = $this->commonBasePath . 'config/test_config.json';

        // 配置文件不存在时返回默认配置
        if (!file_exists($configPath)) {
            return ['captcha_debug' => false];
        }

        $content = file_get_contents($configPath);
        $config = json_decode($content, true);

        // JSON解析失败时返回默认配置
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['captcha_debug' => false];
        }

        // 确保captcha_debug键存在
        if (!isset($config['captcha_debug'])) {
            $config['captcha_debug'] = false;
        }

        return $config;
    }

    /**
     * 记录验证码到日志（用于自动化测试调试）
     */
    private function logCaptcha()
    {
        $config = $this->getTestConfig();

        // 调试模式未开启则跳过
        if (!$config['captcha_debug']) {
            return;
        }

        $logDir = $this->commonBasePath . 'log';
        $logFile = $logDir . '/captcha_debug.json';

        // 自动创建log目录
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 获取当前session_id
        $sessionId = session_id();

        // 构建日志记录
        $logEntry = [
            'session_id' => $sessionId,
            'code' => $this->code,
            'timestamp' => time()
        ];

        // 追加写入日志文件（每行一条JSON记录）
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
