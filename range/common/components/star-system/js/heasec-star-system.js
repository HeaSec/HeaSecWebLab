/**
 * HeaSec天积安全团队 - 华丽星星成就系统公共组件
 * Luxury Star Achievement System - Common Component
 * 版本: v2.0.0
 * 创建日期: 2025-11-08
 * 迁移日期: 2025-11-08
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 从/test/star/迁移到/range/common/components/star-system/
 * 专为靶场项目设计的公共星星系统组件
 */

class HeaSecStarSystem {
    constructor(containerSelector, options = {}) {
        // 基础配置
        this.container = typeof containerSelector === 'string' ?
            document.querySelector(containerSelector) : containerSelector;

        if (!this.container) {
            // [HeaSec Log Cleanup - 2025-11-22]
            // console.error('[HeaSec] 容器未找到:', containerSelector);
            return;
        }

        // 默认配置
        this.config = {
            starCount: 3,
            size: 48,
            gap: 12,
            animated: false,
            interactive: false,
            particles: true,
            autoUnlock: false,
            unlockDelay: 500,
            theme: 'luxury',
            showCongrats: false,
            ...options
        };

        // 状态管理
        this.stars = [];
        this.isAnimating = false;
        this.currentUnlocked = 0;

        // 初始化
        this.init();

        // 挂载实例到 DOM 元素，方便外部调用
        this.container._heasecStarInstance = this;
    }

    /**
     * 初始化星星系统
     */
    init() {
        this.setupContainer();
        this.createStars();
        this.bindEvents();

        // 从data属性加载星星数据
        this.loadStarData();

        if (this.config.animated) {
            this.startInitialAnimation();
        }

        // [HeaSec Log Cleanup - 2025-11-22]
        // console.log('[HeaSec] 华丽星星系统已初始化', this.config);
    }

    /**
     * 设置容器样式
     */
    setupContainer() {
        this.container.className = 'heasec-star-system';

        // 应用预设主题类
        if (this.config.theme && this.config.theme !== 'luxury') {
            this.container.classList.add(`heasec-star-theme-${this.config.theme}`);
        }

        this.container.style.setProperty('--heasec-star-size', `${this.config.size}px`);
        this.container.style.setProperty('--heasec-star-gap', `${this.config.gap}px`);
    }

    /**
     * 创建星星
     */
    createStars() {
        // 清空现有内容
        this.container.innerHTML = '';

        for (let i = 0; i < this.config.starCount; i++) {
            const star = this.createStar(i);
            this.stars.push(star);
            this.container.appendChild(star.element);
        }
    }

    /**
     * 创建单个星星
     */
    createStar(index) {
        const starElement = document.createElement('div');
        starElement.className = 'heasec-star heasec-star-gray';
        starElement.setAttribute('data-star-index', index);
        starElement.setAttribute('data-title', `成就星星 ${index + 1}`);
        starElement.setAttribute('aria-label', `成就星星 ${index + 1}, 未解锁`);

        // 使用图片作为星星内容
        const img = document.createElement('img');
        img.className = 'star-svg';
        img.alt = `Achievement Star ${index + 1}`;
        img.style.width = '100%';
        img.style.height = '100%';
        img.src = this.getStarImagePath('gray');
        starElement.appendChild(img);

        // 添加粒子容器
        let particleContainer = null;
        if (this.config.particles) {
            particleContainer = this.createParticles();
            starElement.appendChild(particleContainer);
        }

        const star = {
            element: starElement,
            img: img,
            particles: particleContainer,
            index: index,
            isUnlocked: false,
            isAnimating: false
        };

        return star;
    }

    /**
     * 获取星星图片路径
     */
    getStarImagePath(state) {
        if (!this._resolvedBasePath) {
            // 优先从自身配置读取 basePath
            if (this.config.basePath) {
                this._resolvedBasePath = this.config.basePath;
            } else {
                // 从父元素的 data-config.commonBasePath 推导
                const parent = this.container.closest('[data-config]');
                if (parent) {
                    try {
                        const parentConfig = JSON.parse(parent.getAttribute('data-config'));
                        if (parentConfig.commonBasePath) {
                            this._resolvedBasePath = parentConfig.commonBasePath + 'components/star-system/';
                        }
                    } catch (e) { /* ignore */ }
                }
            }
            this._resolvedBasePath = this._resolvedBasePath || 'components/star-system/';
        }
        return `${this._resolvedBasePath}assets/svg/star-${state}.svg`;
    }

    /**
     * 获取星星SVG内容（内联，避免外部依赖）
     */


    /**
     * 创建粒子效果
     */
    createParticles() {
        const particleContainer = document.createElement('div');
        particleContainer.className = 'star-particles';

        for (let i = 0; i < 6; i++) {
            const particle = document.createElement('div');
            particle.className = 'star-particle';
            particleContainer.appendChild(particle);
        }

        return particleContainer;
    }

    /**
     * 绑定事件
     */
    bindEvents() {
        if (!this.config.interactive) return;

        this.stars.forEach((star, index) => {
            // 点击事件
            star.element.addEventListener('click', (e) => {
                e.preventDefault();
                this.onStarClick(star, e);
            });

            // 悬停事件
            star.element.addEventListener('mouseenter', (e) => {
                this.onStarHover(star, true, e);
            });

            star.element.addEventListener('mouseleave', (e) => {
                this.onStarHover(star, false, e);
            });

            // 键盘事件
            star.element.setAttribute('tabindex', '0');
            star.element.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.onStarClick(star, e);
                }
            });
        });
    }

    /**
     * 加载星星数据（从配置中读取解锁数量）
     */
    loadStarData() {
        // 从配置中读取已解锁数量（由 PHP 传入 data-heasec-star）
        const unlockedCount = this.config.unlockedCount || 0;
        for (let i = 0; i < unlockedCount && i < this.config.starCount; i++) {
            setTimeout(() => {
                this.unlockStar(i, false); // 不使用动画，静默解锁
            }, i * 100);
        }
    }

    /**
     * 星星点击事件
     */
    onStarClick(star, event) {
        if (star.isAnimating) return;

        // 创建点击效果
        this.createClickEffect(star, event);

        // 如果星星未解锁，解锁它
        if (!star.isUnlocked) {
            this.unlockStar(star.index);
        }

        // 触发自定义事件
        this.triggerEvent('starClick', {
            star: star,
            index: star.index,
            isUnlocked: star.isUnlocked
        });
    }

    /**
     * 星星悬停事件
     */
    onStarHover(star, isEntering, event) {
        if (isEntering && star.isUnlocked) {
            // 可以在这里添加悬停音效
            this.createHoverEffect(star);
        }

        this.triggerEvent('starHover', {
            star: star,
            index: star.index,
            isEntering: isEntering,
            isUnlocked: star.isUnlocked
        });
    }

    /**
     * 解锁星星
     */
    unlockStar(index, withAnimation = true) {
        const star = this.stars[index];
        if (!star || star.isUnlocked || star.isAnimating) return false;

        star.isAnimating = true;
        star.isUnlocked = true;
        this.currentUnlocked++;

        // 更新样式类
        star.element.className = 'heasec-star heasec-star-gold';
        star.element.setAttribute('aria-label', `成就星星 ${index + 1}, 已解锁`);

        // 更新SVG
        star.img.src = this.getStarImagePath('gold');

        // 播放解锁动画
        if (withAnimation) {
            star.element.classList.add('heasec-star-unlocking');

            // 动画结束
            setTimeout(() => {
                star.element.classList.remove('heasec-star-unlocking');
                star.isAnimating = false;

                this.triggerEvent('starUnlocked', {
                    star: star,
                    index: star.index,
                    totalUnlocked: this.currentUnlocked
                });

                // 检查是否全部解锁
                if (this.currentUnlocked === this.stars.length && this.config.showCongrats) {
                    this.showCongratulations();
                }
            }, 1500);
        } else {
            star.isAnimating = false;
            this.triggerEvent('starUnlocked', {
                star: star,
                index: star.index,
                totalUnlocked: this.currentUnlocked
            });

            // 检查是否全部解锁
            if (this.currentUnlocked === this.stars.length && this.config.showCongrats) {
                this.showCongratulations();
            }
        }

        return true;
    }

    /**
     * 批量解锁星星
     */
    unlockMultipleStars(count, withAnimation = true) {
        const actualCount = Math.min(count, this.stars.length);
        const delay = withAnimation ? this.config.unlockDelay : 0;

        for (let i = 0; i < actualCount; i++) {
            setTimeout(() => {
                this.unlockStar(i, withAnimation);
            }, i * delay);
        }
    }

    /**
     * 重置所有星星
     */
    resetStars(withAnimation = true) {
        this.stars.forEach((star, index) => {
            if (star.isUnlocked) {
                star.isUnlocked = false;
                star.isAnimating = withAnimation;

                // 更新样式类
                star.element.className = 'heasec-star heasec-star-gray';
                star.element.setAttribute('aria-label', `成就星星 ${index + 1}, 未解锁`);

                // 更新SVG
                star.img.src = this.getStarImagePath('gray');

                if (withAnimation) {
                    setTimeout(() => {
                        star.isAnimating = false;
                    }, 300);
                }
            }
        });

        this.currentUnlocked = 0;
        this.triggerEvent('starsReset', { totalStars: this.stars.length });
    }

    /**
     * 显示恭喜弹窗
     */
    showCongratulations() {
        // 检查是否已加载恭喜弹窗组件
        if (typeof HeaSecCongratsModal !== 'undefined') {
            try {
                HeaSecCongratsModal.show({
                    title: '🏆 全部成就解锁！',
                    message: `恭喜你成功解锁了全部${this.stars.length}颗星星！你已经成为真正的安全大师！`,
                    buttonText: '继续挑战',
                    particleCount: 12,
                    animationDuration: 3000,
                    onShow: function () {
                        // [HeaSec Log Cleanup - 2025-11-22]
                        // console.log('🎉 恭喜消息已显示 - 用户完成所有成就');
                    },
                    onClose: function () {
                        // [HeaSec Log Cleanup - 2025-11-22]
                        // console.log('✅ 用户选择继续挑战');
                    }
                });
            } catch (error) {
                // [HeaSec Log Cleanup - 2025-11-22]
                // console.error('[HeaSec] 恭喜弹窗显示失败:', error);
                this.showFallbackCongratulations();
            }
        } else {
            // [HeaSec Log Cleanup - 2025-11-22]
            // console.warn('[HeaSec] 恭喜弹窗组件未加载，显示备用提示');
            this.showFallbackCongratulations();
        }

        this.triggerEvent('allStarsUnlocked', {
            totalStars: this.stars.length
        });
    }

    /**
     * 备用恭喜提示
     */
    showFallbackCongratulations() {
        // 创建一个简单的恭喜消息
        const congratsDiv = document.createElement('div');
        congratsDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.95), rgba(255, 165, 0, 0.95));
            color: white;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            z-index: 20000;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            animation: heasecCongratsFadeIn 0.5s ease-out;
        `;
        congratsDiv.innerHTML = `
            <h3 style="margin: 0 0 15px 0; font-size: 24px;">🎉 恭喜！</h3>
            <p style="margin: 0;">您已成功解锁所有成就星星！</p>
            <button onclick="this.parentElement.remove()" style="
                margin-top: 20px;
                padding: 10px 20px;
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid white;
                color: white;
                border-radius: 10px;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
            ">确定</button>
        `;

        document.body.appendChild(congratsDiv);

        // 添加动画样式
        if (!document.querySelector('#heasec-congrats-fallback-style')) {
            const style = document.createElement('style');
            style.id = 'heasec-congrats-fallback-style';
            style.textContent = `
                @keyframes heasecCongratsFadeIn {
                    from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
                    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                }
            `;
            document.head.appendChild(style);
        }

        // 5秒后自动移除
        setTimeout(() => {
            if (congratsDiv.parentElement) {
                congratsDiv.remove();
            }
        }, 5000);
    }

    /**
     * 创建点击效果
     */
    createClickEffect(star, event) {
        const rect = star.element.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        const ripple = document.createElement('div');
        ripple.style.cssText = `
            position: absolute;
            width: 20px;
            height: 20px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.8) 0%, transparent 70%);
            border-radius: 50%;
            left: ${x}px;
            top: ${y}px;
            transform: translate(-50%, -50%);
            pointer-events: none;
            animation: heasec-ripple 0.6s ease-out;
            z-index: 1000;
        `;

        star.element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    /**
     * 创建悬停效果
     */
    createHoverEffect(star) {
        // 可以在这里添加悬停特效
        if (this.config.particles && star.particles) {
            star.particles.style.animation = 'none';
            setTimeout(() => {
                star.particles.style.animation = '';
            }, 10);
        }
    }

    /**
     * 初始动画
     */
    startInitialAnimation() {
        this.stars.forEach((star, index) => {
            star.element.style.opacity = '0';
            star.element.style.transform = 'translateY(20px) scale(0.8)';

            setTimeout(() => {
                star.element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                star.element.style.opacity = '1';
                star.element.style.transform = 'translateY(0) scale(1)';
            }, index * 150);
        });
    }

    /**
     * 触发自定义事件
     */
    triggerEvent(eventName, data) {
        const event = new CustomEvent(`heasec:${eventName}`, {
            detail: data,
            bubbles: true,
            cancelable: true
        });
        this.container.dispatchEvent(event);
    }

    /**
     * 公共API方法
     */

    // 获取当前解锁数量
    getUnlockedCount() {
        return this.currentUnlocked;
    }

    // 获取总星星数量
    getTotalStars() {
        return this.stars.length;
    }

    // 检查是否全部解锁
    isAllUnlocked() {
        return this.currentUnlocked === this.stars.length;
    }

    // 获取指定星星状态
    getStarStatus(index) {
        const star = this.stars[index];
        return star ? {
            index: index,
            isUnlocked: star.isUnlocked,
            isAnimating: star.isAnimating
        } : null;
    }

    // 设置配置
    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this.setupContainer();
    }

    // 销毁星星系统
    destroy() {
        this.stars.forEach(star => {
            star.element.remove();
        });
        this.stars = [];
        this.container.innerHTML = '';
        this.container.className = '';
    }
}

// 添加CSS动画样式
const style = document.createElement('style');
style.textContent = `
    @keyframes heasec-ripple {
        0% {
            width: 20px;
            height: 20px;
            opacity: 1;
        }
        100% {
            width: 100px;
            height: 100px;
            opacity: 0;
        }
    }

    .heasec-star-unlocking {
        animation: heasec-starUnlock 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes heasec-starUnlock {
        0% {
            transform: scale(0.8) rotate(0deg);
            filter: grayscale(100%) saturate(0) brightness(0.5);
        }
        50% {
            transform: scale(1.2) rotate(180deg);
            filter: grayscale(50%) saturate(0.5) brightness(0.8);
        }
        100% {
            transform: scale(1) rotate(360deg);
            filter: grayscale(0%) saturate(1.4) brightness(1.1);
        }
    }
`;
document.head.appendChild(style);

// 导出到全局
window.HeaSecStarSystem = HeaSecStarSystem;

// 自动初始化支持
document.addEventListener('DOMContentLoaded', function () {
    const autoElements = document.querySelectorAll('[data-heasec-star]:not([data-heasec-initialized])');
    autoElements.forEach(element => {
        try {
            const config = element.dataset.heasecStar ?
                JSON.parse(element.dataset.heasecStar) : {};
            new HeaSecStarSystem(element, config);
            // 标记为已初始化，防止重复初始化
            element.setAttribute('data-heasec-initialized', 'true');
        } catch (error) {
            // [HeaSec Log Cleanup - 2025-11-22]
            // console.error('[HeaSec] 自动初始化失败:', error, element);
        }
    });
});

// [HeaSec Log Cleanup - 2025-11-22]
// console.log('[HeaSec] 华丽星星系统已加载 - 天积安全团队 v2.0.0');