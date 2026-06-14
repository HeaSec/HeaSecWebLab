<?php
/**
 * HeaSec天积安全团队 - 上传结果闪存组件（PRG模式）
 * Upload Result Flash Component (Post-Redirect-Get Pattern)
 * 版本: v1.0.0
 * 创建日期: 2026-06-12
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明：实现PRG（Post-Redirect-Get）模式，解决POST表单提交后页面刷新或重置时
 *       浏览器重新提交表单数据（导致文件被重复上传）的问题。
 *
 * 原理：
 *   1. POST阶段：文件上传处理完成后，将上传结果存入session，再通过302/303重定向到GET请求
 *   2. GET阶段：从session读取上传结果（一次性消费，读取后立即清除），渲染页面
 *   3. 由于最终页面以GET方式加载，location.reload()只会发送GET请求，不会重新提交POST表单
 */

/**
 * 上传结果闪存管理类
 * 负责PRG模式下的session闪存存储与一次性读取
 */
class HeaSec_UploadResultFlash
{
    /**
     * session闪存的键名（使用靶场唯一前缀，避免与其他靶场的session数据冲突）
     */
    const FLASH_KEY = 'heasec_blacklist_upload_result';

    /**
     * 确保session已启动
     * 使用session_status判断，避免重复启动导致警告
     *
     * @return void
     */
    private static function ensureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * POST阶段：将上传结果存入session闪存，并重定向到GET请求
     * 此方法调用后会立即exit终止脚本，后续代码不会执行
     *
     * @param array $uploadResult 上传结果数组（来自HeaSec_UploadBypassDetector::processUpload）
     * @return void
     */
    public static function storeAndRedirect($uploadResult)
    {
        self::ensureSession();
        // 将上传结果存入session闪存，供重定向后的GET请求读取
        $_SESSION[self::FLASH_KEY] = $uploadResult;

        // 构造重定向URL：取当前请求URI并去除查询参数，保持目录形式访问（/blacklist/）
        // 避免重定向到 /blacklist/index.php 造成URL不一致
        $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');

        // 清理可能存在的输出缓冲，防止已有输出导致重定向头（header）失效
        if (ob_get_level() > 0) {
            ob_clean();
        }

        // 使用303 See Other重定向，明确指示浏览器以GET方式请求新地址（PRG标准做法）
        header('Location: ' . $redirectUrl, true, 303);
        exit;
    }

    /**
     * GET阶段：读取并清除session闪存（一次性消费）
     * 读取后立即unset，确保刷新页面时不会再次显示上传结果（符合PRG语义）
     *
     * @return array|null 上传结果数组；若无闪存数据则返回null
     */
    public static function readOnce()
    {
        self::ensureSession();
        if (isset($_SESSION[self::FLASH_KEY])) {
            $result = $_SESSION[self::FLASH_KEY];
            // 一次性消费：读取后立即清除，防止刷新页面时重复显示
            unset($_SESSION[self::FLASH_KEY]);
            return $result;
        }
        return null;
    }
}
