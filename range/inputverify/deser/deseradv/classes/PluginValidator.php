<?php
/**
 * 插件数据验证器类
 * 用于对插件提交的数据进行校验、过滤和转换处理
 *
 * 该验证器支持多种内置的处理策略（如长度校验、格式转换等），
 * 也支持自定义回调函数进行灵活的数据处理。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class PluginValidator {
    /** @var string 验证器名称 */
    public $validatorName = 'default_validator';

    /** @var mixed 待验证的输入数据 */
    public $inputData = 'sample data';

    /**
     * @var string 回调函数名称
     * 用于对输入数据进行处理的回调函数。
     * 内置支持的回调包括: strlen, strtoupper, strtolower, md5, trim 等
     * 也可设置为自定义回调函数名以扩展处理能力
     */
    public $callbackFunc = 'strlen';

    /**
     * @var array 传递给回调函数的额外参数列表
     * 第一个参数位置固定为 inputData，后续参数从此数组中依次取出
     */
    public $callbackArgs = [];

    /**
     * @var bool 是否启用严格模式
     * 严格模式下仅允许使用预定义的安全回调函数列表中的函数
     */
    public $strictMode = false;

    /** @var array 严格模式下允许的回调函数白名单 */
    public $allowedCallbacks = [
        'strlen', 'strtoupper', 'strtolower', 'trim',
        'ltrim', 'rtrim', 'ucfirst', 'ucwords', 'md5', 'sha1'
    ];

    /**
     * 执行数据验证和处理
     * 根据配置的回调函数对输入数据进行处理并返回结果
     *
     * @return mixed 处理结果
     */
    public function validate() {
        // 如果启用了严格模式，检查回调是否在白名单中
        if ($this->strictMode && !in_array($this->callbackFunc, $this->allowedCallbacks)) {
            return "错误：不允许的回调函数 '{$this->callbackFunc}'";
        }

        // 构建回调参数列表：第一个参数固定为 inputData，后面追加 callbackArgs
        $params = array_merge([$this->inputData], $this->callbackArgs);

        // 调用回调函数进行处理
        $result = call_user_func_array($this->callbackFunc, $params);

        return $result;
    }
}
