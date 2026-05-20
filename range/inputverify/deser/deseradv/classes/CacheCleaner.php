<?php
/**
 * 缓存清理器类 — POP链中间节点（第二层转发）
 * 负责清理系统缓存数据和临时文件
 *
 * __toString() 方法会在对象被当作字符串使用时自动调用，
 * 内部会调用 cleaner 属性指向对象的 clean() 方法。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class CacheCleaner {
    /** @var string 清理器名称 */
    public $cleanerName = 'cache_cleaner';

    /**
     * @var string 缓存目录路径
     * 正常情况下指定要清理的缓存目录
     */
    public $cacheDir = '/tmp/cache/';

    /**
     * @var mixed 清理策略处理器（protected 属性）
     * 正常情况下为清理策略对象或闭包（子类可访问）
     *
     * 注意：此属性为 protected，序列化时属性名格式为 \0*\0cleaner（共10字节）
     */
    protected $cleaner = null;

    /**
     * 字符串转换方法 — 当对象被用于字符串上下文时自动触发
     * 调用 cleaner 属性指向对象的 clean() 方法并返回结果
     */
    public function __toString() {
        if (is_object($this->cleaner) && method_exists($this->cleaner, 'clean')) {
            return $this->cleaner->clean($this->cacheDir);
        }
        return '';
    }
}
