<?php
/**
 * HeaSec天积安全团队 - HTTP内容管理类
 * 版本: v1.0.0
 * 创建日期: 2025-11-03
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 专门处理HTTP内容长度计算和输出缓冲管理
 * 从index.php中分离出来，提高代码可维护性
 */

class HeaSec_ContentManager {

    /**
     * 获取当前输出缓冲的近似内容长度
     * @return int 内容长度
     */
    public static function getCurrentContentLength() {
        // 获取当前输出缓冲的内容
        $content = ob_get_contents();

        // 如果输出缓冲为空，返回一个基于页面结构的合理估算
        if (empty($content)) {
            // 基于当前页面结构的估算值（包含请求解析表格等）
            return 45000 + strlen($_SERVER['REQUEST_URI']) + 1000; // 基础页面 + URL长度 + 动态内容估算
        }

        return strlen($content);
    }

    /**
     * 页面结束时的处理函数
     * @return void
     */
    public static function handlePageOutput() {
        // 获取缓冲内容
        $content = ob_get_clean();

        // 计算实际内容长度
        $actualContentLength = strlen($content);

        // 将实际内容长度添加到全局变量中，供解析函数使用
        $GLOBALS['actual_content_length'] = $actualContentLength;

        // 输出内容
        echo $content;
    }
}
?>