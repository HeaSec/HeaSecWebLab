<?php
/**
 * HeaSec Path Helper Class
 * 
 * 用于自动计算和管理项目路径，替代硬编码的路径配置
 * 
 * @package HeaSec\Common
 * @version v1.0.0
 */
class HeaSecPath
{
    /**
     * 获取Common组件的相对URL路径 (用于 HTML src/href)
     * 例如: ../../../common/
     * 
     * @return string 以斜杠结尾的相对路径
     */
    public static function getCommonUrl()
    {
        return self::calculateRelativePathToCommon();
    }

    /**
     * 获取Common组件的服务器绝对路径 (用于 PHP include/require)
     * 
     * @return string
     */
    public static function getCommonDir()
    {
        // 本文件位于 range/common/classes/HeaSecPath.php
        // 所以 range/common/ 应该是当前目录的上级目录
        return dirname(__DIR__) . '/';
    }

    /**
     * 计算从当前执行脚本到 range/common/ 的相对路径
     * 
     * @return string
     */
    private static function calculateRelativePathToCommon()
    {
        // 当前执行脚本的绝对路径 (例如: D:/.../range/examples/index.php)
        // 注意：这里使用 SCRIPT_FILENAME 而不是 __FILE__，因为我们需要相对于调用者的路径
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
        $scriptDir = dirname($scriptPath);

        // common 目录的绝对路径
        $commonPath = str_replace('\\', '/', self::getCommonDir());

        // 移除末尾的斜杠以进行比较
        $commonPath = rtrim($commonPath, '/');

        // 如果就在 common 目录下（极其罕见，但为了健壮性）
        if ($scriptDir === $commonPath) {
            return './';
        }

        // 计算相对路径
        return self::getRelativePath($scriptDir, $commonPath);
    }

    /**
     * 计算两个绝对路径之间的相对路径
     * 
     * @param string $from 源路径 (绝对路径)
     * @param string $to 目标路径 (绝对路径)
     * @return string
     */
    public static function getRelativePath($from, $to)
    {
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);

        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        // 移除公共前缀 (兼容Windows大小写)
        while (count($fromParts) > 0 && count($toParts) > 0 && strtolower($fromParts[0]) === strtolower($toParts[0])) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        // 计算需要向上的层级
        $upCount = count($fromParts);
        $relativePath = str_repeat('../', $upCount);

        // 添加目标路径的剩余部分
        if (count($toParts) > 0) {
            $relativePath .= implode('/', $toParts) . '/';
        }

        // 如果结果为空，说明是当前目录
        if (empty($relativePath)) {
            $relativePath = './';
        }

        return $relativePath;
    }

    /**
     * 获取网站根目录的相对路径 (例如: ../../../)
     * 
     * @return string
     */
    public static function getRootPath()
    {
        // 本文件位于 range/common/classes/HeaSecPath.php
        // 假设网站根目录是 range 目录的上级目录
        // range/common/classes -> 3层
        $webRoot = dirname(dirname(dirname(__DIR__)));

        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
        $scriptDir = dirname($scriptPath);
        $webRoot = str_replace('\\', '/', $webRoot);

        return self::getRelativePath($scriptDir, $webRoot);
    }
}
