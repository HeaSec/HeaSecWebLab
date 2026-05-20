<?php
/**
 * HeaSec天积安全团队 - HTML上下文XSS过滤类
 * 版本: v1.0.0
 * 创建日期: 2026-01-14
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: 实现HTML上下文XSS过滤绕过靶场的三关过滤规则
 */

class HeaSec_HTMLContextFilter {

    /**
     * 第一关：无引号属性注入过滤
     * 输入：<input type="text" value=[用户输入] readonly>
     * 过滤：' " () = 等字符
     */
    public static function filterLevel1($input) {
        // 转义尖括号
        $filtered = str_replace(['<', '>'], '', $input);
        // 过滤危险字符：单引号、双引号、左右括号、空格（保留等号用于属性赋值，反引号用于绕过括号）
        $dangerous = ['\'', '"', '(', ')', ' '];
        $filtered = str_replace($dangerous, '', $filtered);
        return $filtered;
    }

    /**
     * 第二关：隐蔽属性注入过滤
     * 输入：<input type="hidden" value="[用户输入]" id="user-input">
     * 过滤：alert/eval关键字（大小写不敏感）
     */
    public static function filterLevel2($input) {
        // 转义尖括号
        $filtered = str_replace(['<', '>'], '', $input);
        // 移除alert和eval关键字（大小写不敏感）
        $filtered = preg_replace('/alert|eval/i', '', $filtered);
        return $filtered;
    }

    /**
     * 第三关：Referer注入过滤（a标签href属性）
     * 输入：<a href="[用户输入]">访问来源</a>
     * 过滤：使用 htmlspecialchars 转义双引号，但不转义单引号
     *
     * 绕过方式：javascript:alert(heasec) 伪协议注入
     */
    public static function filterLevel3($input) {
        // 使用 htmlspecialchars 转义双引号（ENT_COMPAT）
        // 不转义单引号，但本关使用双引号包裹属性
        return htmlspecialchars($input, ENT_COMPAT, 'UTF-8');
    }

    /**
     * 根据关卡应用相应的过滤规则
     *
     * @param int $level 关卡编号（1, 2, 3）
     * @param string $input 用户输入
     * @return string 过滤后的输出
     */
    public static function applyFilter($level, $input) {
        $input = trim($input);

        switch ($level) {
            case 1:
                return self::filterLevel1($input);
            case 2:
                return self::filterLevel2($input);
            case 3:
                return self::filterLevel3($input);
            default:
                return $input;
        }
    }

    /**
     * 获取关卡配置信息
     *
     * @param int $level 关卡编号
     * @return array|null 关卡配置
     */
    public static function getLevelConfig($level) {
        $configs = [
            1 => [
                'title' => '第一关 · 无引号属性注入',
                'name' => '无引号属性注入',
                'description' => '"尖括号已经被我们转义了，别想创建新标签"',
                'hint' => '请触发 alert(heasec) 弹窗',
                'input_placeholder' => '输入XSS代码，例如：onmouseover=alert(heasec)',
            ],
            2 => [
                'title' => '第二关 · 隐蔽属性注入',
                'name' => '隐蔽属性注入',
                'description' => '"这次我们加了引号保护，还过滤了alert和eval关键字。就算你能注入代码，也找不到执行的方法。"',
                'hint' => '请触发 alert(heasec) 弹窗',
                'input_placeholder' => '输入XSS代码',
            ],
            3 => [
                'title' => '第三关 · Referer注入',
                'name' => 'Referer注入',
                'description' => '"我们的系统会记录您的访问来源，双引号都会被转义。既然不能属性逃逸，你还有其他办法吗？"',
                'hint' => '请触发 alert(heasec) 弹窗',
                'input_placeholder' => '输入 Referer 值',
                'button_text' => '测试Referer',
            ],
        ];

        return isset($configs[$level]) ? $configs[$level] : null;
    }
}
