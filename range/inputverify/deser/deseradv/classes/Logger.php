<?php
/**
 * 日志记录器类 — POP链中间节点（第一层转发）
 * 负责记录系统运行过程中的各类日志信息
 *
 * initialize() 方法会将日志消息写入指定的存储介质。
 * 如果 storage 属性被篡改为 CacheCleaner 对象，则写入操作会触发
 * CacheCleaner 的 __toString() 方法。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class Logger {
    /** @var string 日志记录器名称 */
    public $loggerName = 'system_logger';

    /**
     * @var string 日志存储路径
     * 正常情况下用于指定日志文件的保存路径
     */
    public $logFile = '/tmp/system.log';

    /**
     * @var mixed 日志存储介质（private 属性）
     * 正常情况下为文件路径字符串或文件句柄（内部使用）
     *
     * 注意：此属性为 private，序列化时属性名格式为 \0Logger\0storage（共15字节）
     */
    private $storage = null;

    /**
     * 初始化日志记录器
     * 将格式化的日志消息写入 storage 指定的存储介质
     */
    public function initialize() {
        $message = date('[Y-m-d H:i:s]') . ' Logger initialized';
        // 拼接操作：当 $this->storage 为对象时触发 __toString()
        $result = $this->storage . $this->logFile;
        return $result;
    }
}
