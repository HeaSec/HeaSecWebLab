<?php
/**
 * HeaSec天积安全团队 - 恭喜弹窗模板
 * Congratulations Modal Template
 * 版本: v2.0.0
 * 迁移日期: 2025-11-08
 * 团队: 天积安全 (HeavenlySecret)
 */
?>

<!-- HeaSec 恭喜弹窗 -->
<div class="heasec-congrats-modal-overlay" id="heasec-congrats-modal" style="display: none;">
    <div class="heasec-congrats-modal">
        <div class="heasec-congrats-header">
            <div class="heasec-congrats-icon">
                <svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="trophyGold" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#FFD700;stop-opacity:1" />
                            <stop offset="50%" style="stop-color:#FFA500;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#B8860B;stop-opacity:1" />
                        </linearGradient>
                        <filter id="trophyGlow">
                            <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <path d="M 25,15 Q 25,5 35,5 L 45,5 Q 55,5 55,15 L 55,20 L 60,20 Q 70,20 70,30 Q 70,40 60,40 L 55,40 L 55,50 Q 55,60 45,60 L 35,60 Q 25,60 25,50 L 25,40 L 20,40 Q 10,40 10,30 Q 10,20 20,20 L 25,20 Z M 40,50 L 40,65 L 35,70 L 35,75 L 45,75 L 45,70 L 40,65 Z"
                          fill="url(#trophyGold)"
                          stroke="#8B4513"
                          stroke-width="2"
                          filter="url(#trophyGlow)"/>
                </svg>
            </div>
            <h3 class="heasec-congrats-title">恭喜全部解锁！</h3>
        </div>

        <div class="heasec-congrats-content">
            <p class="heasec-congrats-message">您已成功解锁所有成就星星！</p>
            <div class="heasec-congrats-stars">
                <div class="mini-star-gold">⭐</div>
                <div class="mini-star-gold">⭐</div>
                <div class="mini-star-gold">⭐</div>
            </div>
        </div>

        <div class="heasec-congrats-footer">
            <button class="heasec-congrats-btn" onclick="this.closest('.heasec-congrats-modal-overlay').style.display='none'">
                太棒了！
            </button>
        </div>

        <!-- 粒子效果容器 -->
        <div class="heasec-congrats-particles">
            <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="congrats-particle"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>