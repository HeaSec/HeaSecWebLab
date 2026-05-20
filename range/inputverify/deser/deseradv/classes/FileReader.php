<?php
/**
 * 文件读取器类 — POP链终点（执行危险操作）
 * 负责读取和展示文件内容
 *
 * clean() 方法原本用于清理指定目录下的文件，
 * 但由于参数完全由属性决定，可以被利用来读取任意文件。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class FileReader {
    /** @var string 读取器名称 */
    public $readerName = 'file_reader';

    /**
     * @var string 目标文件路径
     * 正常情况下为要清理的文件或目录路径
     */
    public $filename = '/etc/hosts';

    /**
     * @var bool 是否将结果存入全局变量
     * 开启后将文件内容写入 $GLOBALS['__pop_chain_result']
     */
    public $outputToGlobal = false;

    /**
     * 执行清理/读取操作
     * 读取 filename 属性指定的文件内容并返回
     * 如果 outputToGlobal 为 true，同时将内容存入全局变量
     *
     * @param string $path 传入的路径参数（由上级调用者提供）
     * @return string 文件内容或空字符串
     */
    public function clean($path = '') {
        $targetFile = $this->filename;
        $content = @file_get_contents($targetFile);

        if ($this->outputToGlobal && $content !== false) {
            $GLOBALS['__pop_chain_result'] = $content;
        }

        return $content !== false ? $content : '';
    }
}
