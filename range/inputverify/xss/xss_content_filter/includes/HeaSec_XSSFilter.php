<?php
/**
 * HeaSec XSS过滤验证类
 * 版本: v1.0.0
 * 创建日期: 2025-12-31
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 此类用于XSS过滤绕过靶场的输入验证逻辑
 *       模拟现实中存在缺陷的XSS过滤实现
 */

class HeaSec_XSSFilter {

    /**
     * 过滤类型常量
     */
    const FILTER_CASE_SENSITIVE = 1;  // 第一关：大小写过滤
    const FILTER_SINGLE_REPLACE = 2;  // 第二关：单次替换过滤
    const FILTER_COMMENT_SPLIT = 3;   // 第三关：注释分割过滤

    /**
     * 验证结果常量
     */
    const VALIDATION_BLOCKED = 'blocked';     // 被拦截
    const VALIDATION_PASSED = 'passed';       // 通过验证
    const VALIDATION_FAILED = 'failed';       // 验证失败（无弹窗）

    /**
     * 验证XSS输入
     *
     * @param string $input 用户输入的XSS代码
     * @param int $level 关卡编号（1, 2, 3）
     * @return array 验证结果数组
     */
    public static function validate($input, $level) {
        // 去除首尾空格
        $input = trim($input);

        // 空输入检查
        if (empty($input)) {
            return [
                'status' => self::VALIDATION_FAILED,
                'message' => '请输入XSS代码'
            ];
        }

        // 根据关卡执行不同的验证逻辑
        switch ($level) {
            case 1:
                return self::validateLevel1($input);
            case 2:
                return self::validateLevel2($input);
            case 3:
                return self::validateLevel3($input);
            default:
                return [
                    'status' => self::VALIDATION_FAILED,
                    'message' => '无效的关卡编号'
                ];
        }
    }

    /**
     * 第一关：初级审查员（大小写敏感过滤）
     *
     * 防御方式：只检测小写的 'script' 关键词并拦截
     * 缺陷：HTML标签名对浏览器来说是大小写不敏感的
     *
     * @param string $input 用户输入
     * @return array 验证结果
     */
    private static function validateLevel1($input) {
        // 检查是否包含弹窗逻辑（这是通关的基础）
        if (!self::containsAlert($input)) {
            return [
                'status' => self::VALIDATION_FAILED,
                'message' => 'XSS代码未包含弹窗逻辑，请确保包含alert(heasec)函数'
            ];
        }

        // 拦截式验证：直接检查原始输入是否包含小写的 'script' 关键词
        if (strpos($input, 'script') !== false) {
            // 检测到小写script，直接拦截
            return [
                'status' => self::VALIDATION_BLOCKED,
                'message' => '检测到script关键词，已被拦截！提示：HTML标签名对浏览器来说是大小写不敏感的'
            ];
        }

        // 没有检测到小写script，通过验证（可能是大小写绕过）
        return [
            'status' => self::VALIDATION_PASSED,
            'message' => '验证通过'
        ];
    }

    /**
     * 第二关：正则大师（单次替换过滤）
     *
     * 防御方式：使用正则表达式单次替换所有大小写的 'script'
     * 缺陷：只替换一次，双写可以绕过（如 <sc<script>ript>）
     *
     * @param string $input 用户输入
     * @return array 验证结果
     */
    private static function validateLevel2($input) {
        // 检查是否包含弹窗逻辑
        if (!self::containsAlert($input)) {
            return [
                'status' => self::VALIDATION_FAILED,
                'message' => 'XSS代码未包含弹窗逻辑，请确保包含alert(heasec)函数'
            ];
        }

        // 应用第二关的过滤规则：单次替换
        $filtered = self::filterLevel2($input);

        // 第二关特殊验证逻辑：
        // 双写绕过后，虽然不包含完整的<script>标签，但仍包含'script'字符串片段
        // 例如：'<sc<>ript>alert(1)</script>' 包含 '<ript>' 中的 'script' 子串
        if (stripos($filtered, 'script') !== false) {
            return [
                'status' => self::VALIDATION_PASSED,
                'message' => '成功绕过！点击按钮进入下一关'
            ];
        }

        // 过滤后没有script字符串
        return [
            'status' => self::VALIDATION_BLOCKED,
            'message' => '检测到script关键词，已被过滤！提示：单次替换可以通过双写绕过'
        ];
    }

    /**
     * 第三关：文本清洗官（注释分割绕过）
     *
     * 防御方式：尝试用字符串替换移除script标签
     * 缺陷：未处理HTML注释，注释分割的script无法被匹配
     *
     * @param string $input 用户输入
     * @return array 验证结果
     */
    private static function validateLevel3($input) {
        // 检查是否包含弹窗逻辑
        if (!self::containsAlert($input)) {
            return [
                'status' => self::VALIDATION_FAILED,
                'message' => 'XSS代码未包含弹窗逻辑，请确保包含alert(heasec)函数'
            ];
        }

        // 第三关特殊验证逻辑：
        // 应用过滤器后，需要模拟前端执行时移除注释的情况
        $filtered = self::filterLevel3($input);

        // 前端执行时会移除HTML注释，然后浏览器解析
        // 所以我们需要检查：移除注释后是否还包含 <script> 标签
        $afterRemoveComment = preg_replace('/<!--.*?-->/s', '', $filtered);

        if (self::containsScriptTag($afterRemoveComment)) {
            return [
                'status' => self::VALIDATION_PASSED,
                'message' => '成功绕过！可点击按钮返回第一关'
            ];
        }

        // 移除注释后没有script标签
        return [
            'status' => self::VALIDATION_BLOCKED,
            'message' => '检测到script关键词，已被过滤！提示：可以利用HTML注释分割关键词'
        ];
    }

    /**
     * 第一关过滤器：大小写敏感的单次替换
     *
     * 模拟初级开发者写的过滤代码
     * 只替换小写的 'script'，大小写混合的不会被替换
     *
     * @param string $input 用户输入
     * @return string 过滤后的内容
     */
    public static function filterLevel1($input) {
        // ❌ 缺陷：只替换小写的 'script'
        // ❌ 缺陷：str_replace 会替换所有匹配项（但只匹配小写）
        return str_replace('script', '', $input);
    }

    /**
     * 第二关过滤器：大小写不敏感的单次替换
     *
     * 模拟开发者尝试改进，使用正则表达式
     * 但只替换一次，双写可以绕过
     *
     * @param string $input 用户输入
     * @return string 过滤后的内容
     */
    private static function filterLevel2($input) {
        // ✅ 改进：使用正则表达式，/i 修饰符实现大小写不敏感
        // ❌ 缺陷：只替换一次（第4个参数限制替换次数）
        //        <sc<script>ript>alert(heasec)</script> 只删除第一个"script"
        //        变成 <sc+ript>alert(heasec)</script>，浏览器仍可解析为 <script>
        return preg_replace('/script/i', '', $input, 1);
    }

    /**
     * 第三关过滤器：只替换完整的script关键词
     *
     * 模拟开发者认为替换完整的'script'字符串就安全了
     * 但未考虑HTML注释可以分割关键词
     *
     * @param string $input 用户输入
     * @return string 过滤后的内容
     */
    private static function filterLevel3($input) {
        // ❌ 缺陷：只匹配完整的'script'字符串
        //        <scr<!--test-->ipt> 中的 'script' 被注释分割，无法匹配
        //        浏览器解析HTML时会忽略注释，将 <scr<!--test-->ipt> 解析为 <script>
        return preg_replace('/script/i', '', $input);
    }

    /**
     * 检测输入是否包含alert弹窗逻辑
     *
     * @param string $input 用户输入
     * @return bool 是否包含alert
     */
    private static function containsAlert($input) {
        $lowerInput = strtolower($input);
        return strpos($lowerInput, 'alert(') !== false;
    }

    /**
     * 检测输入是否包含<script>标签（任意大小写）
     *
     * @param string $input 用户输入
     * @return bool 是否包含script标签
     */
    private static function containsScriptTag($input) {
        // 检查是否包含 <script> 开始标签
        // /i 修饰符表示大小写不敏感
        return preg_match('/<script[^>]*>/i', $input);
    }

    /**
     * 渲染XSS代码到HTML（用于测试）
     *
     * 警告：此方法仅用于靶场环境，生产环境严禁使用
     *
     * @param string $input XSS代码
     * @return string HTML内容
     */
    public static function renderForTest($input) {
        // 直接返回未过滤的输入
        return $input;
    }
}
