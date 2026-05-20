<?php
/**
 * 插件管理器类 — POP链入口类
 * 负责管理系统中所有已加载的插件实例
 *
 * 当对象被销毁时（如脚本结束、unset等），__destruct 方法会自动调用，
 * 遍历所有已注册的插件并执行其初始化操作。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class PluginManager {
    /** @var string 管理器名称 */
    public $managerName = 'default_manager';

    /**
     * @var array 已注册的插件列表
     * 存储所有需要管理的插件对象，__destruct 时会遍历此数组
     */
    public $plugins = [];

    /**
     * 析构方法 — POP链的触发入口
     * 脚本结束或对象销毁时自动调用，遍历 plugins 数组中的每个插件对象
     */
    public function __destruct() {
        foreach ($this->plugins as $plugin) {
            if (is_object($plugin) && method_exists($plugin, 'initialize')) {
                $plugin->initialize();
            }
        }
    }
}
