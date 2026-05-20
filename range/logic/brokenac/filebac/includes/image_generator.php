<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 图片生成函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：使用PHP GD库生成包含敏感信息的图片文件
 */

/**
 * 生成成绩单图片
 *
 * @param string $name 学生姓名
 * @param int $score 成绩
 * @param string|null $passcode 通关密码（可选）
 * @param string $outputPath 输出文件路径
 * @return bool 是否生成成功
 */
function generateTranscriptImage($name, $score, $passcode = null, $outputPath) {
    // 创建画布 600x400
    $image = imagecreatetruecolor(600, 400);

    // 设置背景色（浅灰色）
    $bgColor = imagecolorallocate($image, 245, 245, 245);
    imagefill($image, 0, 0, $bgColor);

    // 字体路径（相对于includes目录）
    $fontPath = dirname(__DIR__, 4) . '/common/assets/fonts/simhei.ttf';

    // 检查字体文件是否存在
    if (!file_exists($fontPath)) {
        // 使用内置字体作为后备
        $useTtf = false;
    } else {
        $useTtf = true;
    }

    // 标题颜色（深蓝色）
    $titleColor = imagecolorallocate($image, 15, 52, 96);
    // 内容颜色（黑色）
    $textColor = imagecolorallocate($image, 0, 0, 0);

    // 添加标题
    if ($useTtf) {
        imagettftext($image, 28, 0, 230, 50, $titleColor, $fontPath, '成绩单');
    } else {
        imagestring($image, 5, 250, 20, '成绩单', $titleColor);
    }

    // 添加分隔线
    $lineColor = imagecolorallocate($image, 200, 200, 200);
    imageline($image, 50, 70, 550, 70, $lineColor);

    // 添加学生信息
    $yPos = 120;
    $infoItems = [
        '学校：天积安全学院',
        '姓名：' . $name,
        '成绩：' . $score . ' 分'
    ];

    foreach ($infoItems as $item) {
        if ($useTtf) {
            imagettftext($image, 18, 0, 50, $yPos, $textColor, $fontPath, $item);
        } else {
            imagestring($image, 4, 50, $yPos - 15, $item, $textColor);
        }
        $yPos += 40;
    }

    // 如果有通关密码，添加到图片底部
    if ($passcode) {
        $passcodeColor = imagecolorallocate($image, 0, 0, 0);
        if ($useTtf) {
            // 计算居中位置
            $bbox = imagettfbbox(18, 0, $fontPath, $passcode);
            $textWidth = $bbox[2] - $bbox[0];
            $xPos = (600 - $textWidth) / 2;
            imagettftext($image, 18, 0, $xPos, 370, $passcodeColor, $fontPath, $passcode);
        } else {
            imagestring($image, 4, 200, 355, $passcode, $passcodeColor);
        }
    }

    // 确保目录存在
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // 保存图片
    $result = imagepng($image, $outputPath);
    imagedestroy($image);

    return $result;
}

/**
 * 生成订单详情图片
 *
 * @param bool $randomColor 是否使用随机颜色
 * @param int $randomAngle 文字倾斜角度
 * @param string|null $passcode 通关密码（可选）
 * @param string $outputPath 输出文件路径
 * @return bool 是否生成成功
 */
function generateOrderImage($randomColor = true, $randomAngle = 0, $passcode = null, $outputPath) {
    // 创建画布 600x400
    $image = imagecreatetruecolor(600, 400);

    // 设置背景色（白色）
    $bgColor = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bgColor);

    // 字体路径（相对于includes目录）
    $fontPath = dirname(__DIR__, 4) . '/common/assets/fonts/simhei.ttf';

    // 检查字体文件是否存在
    if (!file_exists($fontPath)) {
        $useTtf = false;
    } else {
        $useTtf = true;
    }

    // 生成随机颜色或固定颜色
    if ($randomColor) {
        $textColor = imagecolorallocate($image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
    } else {
        $textColor = imagecolorallocate($image, 100, 100, 100);
    }

    // 添加"这是订单详情"文字（带随机角度）
    if ($useTtf) {
        imagettftext($image, 24, $randomAngle, 180, 200, $textColor, $fontPath, '这是订单详情');
    } else {
        imagestring($image, 5, 200, 180, '这是订单详情', $textColor);
    }

    // 如果有通关密码，添加到图片底部（黑色，不倾斜）
    if ($passcode) {
        $passcodeColor = imagecolorallocate($image, 0, 0, 0);
        if ($useTtf) {
            // 计算居中位置
            $bbox = imagettfbbox(18, 0, $fontPath, $passcode);
            $textWidth = $bbox[2] - $bbox[0];
            $xPos = (600 - $textWidth) / 2;
            imagettftext($image, 18, 0, $xPos, 370, $passcodeColor, $fontPath, $passcode);
        } else {
            imagestring($image, 4, 200, 355, $passcode, $passcodeColor);
        }
    }

    // 确保目录存在
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // 保存图片
    $result = imagepng($image, $outputPath);
    imagedestroy($image);

    return $result;
}

/**
 * 生成身份证图片
 *
 * @param string $name 用户姓名
 * @param string $idcard 身份证号
 * @param string|null $passcode 通关密码（可选）
 * @param string $outputPath 输出文件路径
 * @return bool 是否生成成功
 */
function generateIdcardImage($name, $idcard, $passcode = null, $outputPath) {
    // 创建画布 600x400
    $image = imagecreatetruecolor(600, 400);

    // 设置背景色（浅黄色，模拟身份证背景）
    $bgColor = imagecolorallocate($image, 255, 250, 240);
    imagefill($image, 0, 0, $bgColor);

    // 字体路径（相对于includes目录）
    $fontPath = dirname(__DIR__, 4) . '/common/assets/fonts/simhei.ttf';

    // 检查字体文件是否存在
    if (!file_exists($fontPath)) {
        $useTtf = false;
    } else {
        $useTtf = true;
    }

    // 标题颜色（深蓝色）
    $titleColor = imagecolorallocate($image, 15, 52, 96);
    // 内容颜色（黑色）
    $textColor = imagecolorallocate($image, 0, 0, 0);

    // 添加标题
    if ($useTtf) {
        imagettftext($image, 24, 0, 200, 50, $titleColor, $fontPath, '身份证信息');
    } else {
        imagestring($image, 5, 220, 25, '身份证信息', $titleColor);
    }

    // 添加分隔线
    $lineColor = imagecolorallocate($image, 200, 200, 200);
    imageline($image, 50, 70, 550, 70, $lineColor);

    // 添加用户信息
    $yPos = 120;
    $infoItems = [
        '姓名：' . $name,
        '身份证号：' . $idcard
    ];

    foreach ($infoItems as $item) {
        if ($useTtf) {
            imagettftext($image, 18, 0, 50, $yPos, $textColor, $fontPath, $item);
        } else {
            imagestring($image, 4, 50, $yPos - 15, $item, $textColor);
        }
        $yPos += 40;
    }

    // 添加边框效果
    $borderColor = imagecolorallocate($image, 180, 180, 180);
    imagerectangle($image, 10, 10, 589, 389, $borderColor);

    // 如果有通关密码，添加到图片底部
    if ($passcode) {
        $passcodeColor = imagecolorallocate($image, 0, 0, 0);
        if ($useTtf) {
            // 计算居中位置
            $bbox = imagettfbbox(18, 0, $fontPath, $passcode);
            $textWidth = $bbox[2] - $bbox[0];
            $xPos = (600 - $textWidth) / 2;
            imagettftext($image, 18, 0, $xPos, 370, $passcodeColor, $fontPath, $passcode);
        } else {
            imagestring($image, 4, 200, 355, $passcode, $passcodeColor);
        }
    }

    // 确保目录存在
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // 保存图片
    $result = imagepng($image, $outputPath);
    imagedestroy($image);

    return $result;
}

/**
 * 生成20位随机通关密码
 *
 * @return string 20位随机字符串（包含大小写字母和数字）
 */
function generatePasscode() {
    // 使用去除易混淆字符的字符集
    // 排除: 0/O/o (零和字母O), 1/l/I (一、小写L和大写i)
    $charset = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $charsetLength = strlen($charset);
    $passcode = '';

    for ($i = 0; $i < 20; $i++) {
        $passcode .= $charset[mt_rand(0, $charsetLength - 1)];
    }

    return $passcode;
}

/**
 * 删除指定目录下的所有PNG文件
 *
 * @param string $directory 目录路径
 * @return int 删除的文件数量
 */
function deletePngFiles($directory) {
    $count = 0;

    if (is_dir($directory)) {
        $files = glob($directory . '*.png');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $count++;
                }
            }
        }
    }

    return $count;
}

/**
 * 删除指定目录及其子目录下的所有PNG文件
 *
 * @param string $directory 目录路径
 * @return int 删除的文件数量
 */
function deleteAllPngFiles($directory) {
    $count = 0;

    if (is_dir($directory)) {
        // 先处理子目录
        $subDirs = glob($directory . '*', GLOB_ONLYDIR);
        if ($subDirs) {
            foreach ($subDirs as $subDir) {
                $count += deleteAllPngFiles($subDir . '/');
            }
        }

        // 处理当前目录的PNG文件
        $count += deletePngFiles($directory);
    }

    return $count;
}
