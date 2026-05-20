<?php
/**
 * HeaSec天积安全团队 - 假验证码图片生成类
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 生成显示"不可见"的假验证码图片，真实验证码存储在session中
 * 注意: 此文件从imgcodebp1复制，供imgcodebp2独立使用
 */

class FakeCaptchaGenerator
{
    private $width;
    private $height;
    private $codeLength;
    private $code;
    private $image;
    private $commonBasePath;

    /**
     * 构造函数
     * @param int $width 图片宽度
     * @param int $height 图片高度
     * @param int $codeLength 验证码长度
     * @param string $commonBasePath 公共组件路径（可选）
     */
    public function __construct($width = 120, $height = 40, $codeLength = 4, $commonBasePath = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->codeLength = $codeLength;
        // 默认使用从当前includes目录到common的相对路径
        $this->commonBasePath = $commonBasePath ;
    }

    /**
     * 生成假验证码图片和真实验证码
     * @param string $sessionKey session中存储真实验证码的key
     * @return array ['image' => base64图片, 'code' => 真实验证码]
     */
    public function generate($sessionKey)
    {
        // 生成真实验证码（4位随机字符串：数字和小写字母）
        $this->code = $this->generateRealCode();

        // 存储到session（需要外部已启动会话）
        $_SESSION[$sessionKey] = $this->code;

        // 创建图片
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // 设置随机浅色背景
        $bgR = mt_rand(230, 255);
        $bgG = mt_rand(230, 255);
        $bgB = mt_rand(230, 255);
        $backgroundColor = imagecolorallocate($this->image, $bgR, $bgG, $bgB);
        imagefill($this->image, 0, 0, $backgroundColor);

        // 添加干扰线（3-5条）
        $lineCount = mt_rand(3, 5);
        for ($i = 0; $i < $lineCount; $i++) {
            $lineColor = imagecolorallocate(
                $this->image,
                mt_rand(100, 200),
                mt_rand(100, 200),
                mt_rand(100, 200)
            );
            imageline(
                $this->image,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $lineColor
            );
        }

        // 添加干扰点（20-30个）
        $dotCount = mt_rand(20, 30);
        for ($i = 0; $i < $dotCount; $i++) {
            $dotColor = imagecolorallocate(
                $this->image,
                mt_rand(50, 200),
                mt_rand(50, 200),
                mt_rand(50, 200)
            );
            imagesetpixel(
                $this->image,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                $dotColor
            );
        }

        // 写入"不可见"三个中文字
        $this->drawFakeText();

        // 输出为base64
        ob_start();
        imagepng($this->image);
        $imageData = ob_get_clean();
        imagedestroy($this->image);

        $base64Image = 'data:image/png;base64,' . base64_encode($imageData);

        return array(
            'image' => $base64Image,
            'code' => $this->code
        );
    }

    /**
     * 仅生成图片（不返回验证码明文）
     * @param string $sessionKey session中存储真实验证码的key
     * @return string base64图片
     */
    public function generateImageOnly($sessionKey)
    {
        $result = $this->generate($sessionKey);
        return $result['image'];
    }

    /**
     * 验证验证码
     * @param string $sessionKey session中存储验证码的key
     * @param string $inputCode 用户输入的验证码
     * @param bool $destroyAfterVerify 验证后是否销毁验证码
     * @return bool
     */
    public function verify($sessionKey, $inputCode, $destroyAfterVerify = true)
    {
        // 需要外部已启动会话
        if (!isset($_SESSION[$sessionKey])) {
            return false;
        }

        $sessionCode = $_SESSION[$sessionKey];

        // 根据参数决定是否销毁验证码
        if ($destroyAfterVerify) {
            unset($_SESSION[$sessionKey]);
        }

        // 不区分大小写比较
        return strtolower($inputCode) === strtolower($sessionCode);
    }

    /**
     * 生成真实验证码（4位随机字符串：数字和小写字母）
     * @return string
     */
    private function generateRealCode()
    {
        $charset = '0123456789abcdefghijklmnopqrstuvwxyz';
        $len = strlen($charset);
        $code = '';
        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $charset[mt_rand(0, $len - 1)];
        }
        return $code;
    }

    /**
     * 绘制假文字"不可见"
     */
    private function drawFakeText()
    {
        $text = '不可见';
        $chars = array('不', '可', '见');

        // 尝试使用TTF字体 - 使用$commonBasePath
        $fontFile = $this->commonBasePath . 'assets/fonts/simhei.ttf';
        $useTtf = file_exists($fontFile) && function_exists('imagettftext');

        // 备用字体路径
        if (!$useTtf) {
            $fontFile = 'C:/Windows/Fonts/simhei.ttf';
            $useTtf = file_exists($fontFile) && function_exists('imagettftext');
        }

        // 计算每个字符的位置
        $charWidth = $this->width / 3;

        for ($i = 0; $i < 3; $i++) {
            // 随机深色
            $textColor = imagecolorallocate(
                $this->image,
                mt_rand(0, 100),
                mt_rand(0, 100),
                mt_rand(0, 100)
            );

            // 随机字体大小（14-18px）
            $fontSize = mt_rand(14, 18);

            // 随机倾斜角度（-15到15度）
            $angle = mt_rand(-15, 15);

            // 计算位置
            $x = $charWidth * $i + ($charWidth / 2) - ($fontSize / 2);
            $y = ($this->height / 2) + ($fontSize / 2) + mt_rand(-3, 3);

            if ($useTtf) {
                imagettftext($this->image, $fontSize, $angle, (int) $x, (int) $y, $textColor, $fontFile, $chars[$i]);
            } else {
                // 降级：使用内置字体显示问号（无法显示中文）
                imagestring($this->image, 5, (int) $x, (int) ($this->height / 2 - 8), '?', $textColor);
            }
        }
    }
}
