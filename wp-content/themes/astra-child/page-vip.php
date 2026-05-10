<?php
/**
 * Template Name: VIP Pricing (Premium OTT)
 * Premium cinematic VIP pricing page
 */
get_header();
get_template_part('template-parts/streaming/header');

// URL helpers
$movies_url = home_url('/movies/');
$tv_url = home_url('/tv/');
$trending_url = function_exists('mu_get_page_url_by_slug') ? mu_get_page_url_by_slug('trending') : home_url('/trending/');
$home_url = home_url('/');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('VIP Pricing', 'astra-child'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="movie-ui movie-ui--no-sidebar mu-pricing-page">
<?php wp_body_open(); ?>

<!-- ============================================================ -->
<!-- HERO SECTION -->
<!-- ============================================================ -->
<section class="mu-pricing-hero">
    <div class="mu-pricing-hero-icon">
        <svg viewBox="0 0 24 24">
            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
        </svg>
    </div>
    <h1 class="mu-pricing-hero-title"><?php esc_html_e('VIP Pricing', 'astra-child'); ?></h1>
    <p class="mu-pricing-hero-subtitle"><?php esc_html_e('Choose the perfect plan for unlimited entertainment', 'astra-child'); ?></p>
    
    <div class="mu-pricing-features">
        <div class="mu-pricing-feature">
            <div class="mu-pricing-feature-icon">
                <svg viewBox="0 0 24 24" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
            </div>
            <span class="mu-pricing-feature-text"><?php esc_html_e('Unlimited Entertainment', 'astra-child'); ?></span>
        </div>
        <div class="mu-pricing-feature">
            <div class="mu-pricing-feature-icon">
                <svg viewBox="0 0 24 24" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
            </div>
            <span class="mu-pricing-feature-text"><?php esc_html_e('Watch Everywhere', 'astra-child'); ?></span>
        </div>
        <div class="mu-pricing-feature">
            <div class="mu-pricing-feature-icon">
                <svg viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <span class="mu-pricing-feature-text"><?php esc_html_e('Cancel Anytime', 'astra-child'); ?></span>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- BILLING TOGGLE -->
<!-- ============================================================ -->
<div class="mu-pricing-toggle">
    <span class="mu-pricing-toggle-label <?php echo (isset($_COOKIE['pricing_cycle']) && $_COOKIE['pricing_cycle'] === 'yearly') ? '' : 'is-active'; ?>"><?php esc_html_e('Monthly', 'astra-child'); ?></span>
    <div class="mu-pricing-toggle-switch" role="switch" aria-checked="false" tabindex="0">
        <div class="mu-pricing-toggle-knob"></div>
    </div>
    <span class="mu-pricing-toggle-label <?php echo (isset($_COOKIE['pricing_cycle']) && $_COOKIE['pricing_cycle'] === 'yearly') ? 'is-active' : ''; ?>"><?php esc_html_e('Yearly', 'astra-child'); ?></span>
    <span class="mu-pricing-toggle-badge"><?php esc_html_e('Save up to 20%', 'astra-child'); ?></span>
</div>

<!-- ============================================================ -->
<!-- PRICING CARDS -->
<!-- ============================================================ -->
<section class="mu-pricing-cards">
    <!-- Basic Plan -->
    <div class="mu-pricing-card" data-plan="basic">
        <h3 class="mu-pricing-card-name"><?php esc_html_e('Basic', 'astra-child'); ?></h3>
        <p class="mu-pricing-card-desc"><?php esc_html_e('Great for getting started', 'astra-child'); ?></p>
        
        <div class="mu-pricing-card-price">
            <div class="mu-pricing-card-price-main">
                <span class="mu-pricing-card-currency">$</span>
                <span class="mu-pricing-card-amount">5.99</span>
                <span class="mu-pricing-card-period">/month</span>
            </div>
            <div class="mu-pricing-card-annual" style="display: none;"></div>
        </div>
        
        <ul class="mu-pricing-card-features">
            <li class="mu-pricing-card-feature mu-pricing-card-feature--disabled">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                <?php esc_html_e('HD Video Quality (720p)', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Watch on 1 Device', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Unlimited Movies & TV Shows', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Download on 1 Device', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature mu-pricing-card-feature--disabled">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                <?php esc_html_e('No 4K Ultra HD', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature mu-pricing-card-feature--disabled">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                <?php esc_html_e('Ads included', 'astra-child'); ?>
            </li>
        </ul>
        
        <button type="button" class="mu-pricing-card-btn mu-pricing-card-btn--outline">
            <?php esc_html_e('Get Basic', 'astra-child'); ?>
        </button>
    </div>
    
    <!-- Standard Plan -->
    <div class="mu-pricing-card mu-pricing-card--standard" data-plan="standard">
        <span class="mu-pricing-card-badge"><?php esc_html_e('Most Popular', 'astra-child'); ?></span>
        <h3 class="mu-pricing-card-name"><?php esc_html_e('Standard', 'astra-child'); ?></h3>
        <p class="mu-pricing-card-desc"><?php esc_html_e('Best value for your entertainment', 'astra-child'); ?></p>
        
        <div class="mu-pricing-card-price">
            <div class="mu-pricing-card-price-main">
                <span class="mu-pricing-card-currency">$</span>
                <span class="mu-pricing-card-amount">9.99</span>
                <span class="mu-pricing-card-period">/month</span>
            </div>
            <div class="mu-pricing-card-annual" style="display: none;"></div>
        </div>
        
        <ul class="mu-pricing-card-features">
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Full HD Video Quality (1080p)', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Watch on 2 Devices', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Unlimited Movies & TV Shows', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Download on 2 Devices', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('4K Ultra HD', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature mu-pricing-card-feature--disabled">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                <?php esc_html_e('No ad-free experience', 'astra-child'); ?>
            </li>
        </ul>
        
        <button type="button" class="mu-pricing-card-btn mu-pricing-card-btn--primary">
            <?php esc_html_e('Get Standard', 'astra-child'); ?>
        </button>
    </div>
    
    <!-- Premium Plan -->
    <div class="mu-pricing-card mu-pricing-card--premium" data-plan="premium">
        <span class="mu-pricing-card-badge"><?php esc_html_e('Best Experience', 'astra-child'); ?></span>
        <h3 class="mu-pricing-card-name"><?php esc_html_e('Premium', 'astra-child'); ?></h3>
        <p class="mu-pricing-card-desc"><?php esc_html_e('Ultimate streaming experience', 'astra-child'); ?></p>
        
        <div class="mu-pricing-card-price">
            <div class="mu-pricing-card-price-main">
                <span class="mu-pricing-card-currency">$</span>
                <span class="mu-pricing-card-amount">14.99</span>
                <span class="mu-pricing-card-period">/month</span>
            </div>
            <div class="mu-pricing-card-annual" style="display: none;"></div>
        </div>
        
        <ul class="mu-pricing-card-features">
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('4K Ultra HD + HDR', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Watch on 4 Devices', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Unlimited Movies & TV Shows', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Download on 4 Devices', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('No Ads - Ad-Free Experience', 'astra-child'); ?>
            </li>
            <li class="mu-pricing-card-feature">
                <svg class="mu-pricing-card-feature-icon mu-pricing-card-feature-icon--check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php esc_html_e('Priority Customer Support', 'astra-child'); ?>
            </li>
        </ul>
        
        <button type="button" class="mu-pricing-card-btn mu-pricing-card-btn--gold">
            <?php esc_html_e('Get Premium', 'astra-child'); ?>
        </button>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECURE PAYMENT BANNER -->
<!-- ============================================================ -->
<div class="mu-pricing-secure">
    <div class="mu-pricing-secure-info">
        <div class="mu-pricing-secure-icon">
            <svg viewBox="0 0 24 24" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <polyline points="9 12 11 14 15 10"/>
            </svg>
        </div>
        <div>
            <h4 class="mu-pricing-secure-title"><?php esc_html_e('100% Secure Payment', 'astra-child'); ?></h4>
            <p class="mu-pricing-secure-desc"><?php esc_html_e('Your payment information is encrypted and secure. We never store your full card details.', 'astra-child'); ?></p>
        </div>
    </div>
    
    <div class="mu-pricing-secure-logos">
        <div class="mu-pricing-secure-logo" data-payment="visa">
            <span class="mu-pricing-secure-logo-tooltip"><?php esc_html_e('Visa - Worldwide accepted', 'astra-child'); ?></span>
            <svg viewBox="0 0 50 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="35" rx="4" fill="#1A1F71"/>
                <text x="25" y="22" text-anchor="middle" fill="white" font-size="14" font-weight="bold">VISA</text>
            </svg>
        </div>
        <div class="mu-pricing-secure-logo" data-payment="mastercard">
            <span class="mu-pricing-secure-logo-tooltip"><?php esc_html_e('Mastercard - Secure payments', 'astra-child'); ?></span>
            <svg viewBox="0 0 50 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="35" rx="4" fill="#F5F5F5"/>
                <circle cx="18" cy="17" r="10" fill="#EB001B"/>
                <circle cx="32" cy="17" r="10" fill="#F79E1B"/>
                <path d="M25 9.5a10 10 0 0 0 0 15 10 10 0 0 0 0-15z" fill="#FF5F00"/>
            </svg>
        </div>
        <div class="mu-pricing-secure-logo" data-payment="amex">
            <span class="mu-pricing-secure-logo-tooltip"><?php esc_html_e('American Express - Premium', 'astra-child'); ?></span>
            <svg viewBox="0 0 50 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="35" rx="4" fill="#006FCF"/>
                <text x="25" y="22" text-anchor="middle" fill="white" font-size="10" font-weight="bold">AMEX</text>
            </svg>
        </div>
        <div class="mu-pricing-secure-logo" data-payment="paypal">
            <span class="mu-pricing-secure-logo-tooltip"><?php esc_html_e('PayPal - Pay securely online', 'astra-child'); ?></span>
            <svg viewBox="0 0 50 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="35" rx="4" fill="#F5F5F5"/>
                <text x="25" y="22" text-anchor="middle" fill="#003087" font-size="9" font-weight="bold">PayPal</text>
            </svg>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- COMPARE TABLE -->
<!-- ============================================================ -->
<section class="mu-pricing-compare">
    <h2 class="mu-pricing-compare-title"><?php esc_html_e('Compare Plans', 'astra-child'); ?></h2>
    
    <table class="mu-pricing-compare-table">
        <thead>
            <tr>
                <th></th>
                <th><?php esc_html_e('Basic', 'astra-child'); ?></th>
                <th class="mu-pricing-compare-popular"><?php esc_html_e('Standard', 'astra-child'); ?></th>
                <th class="mu-pricing-compare-highlight"><?php esc_html_e('Premium', 'astra-child'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php esc_html_e('Monthly Price', 'astra-child'); ?></td>
                <td>$5.99</td>
                <td class="mu-pricing-compare-popular">$9.99</td>
                <td>$14.99</td>
            </tr>
            <tr>
                <td><?php esc_html_e('Yearly Price (per month)', 'astra-child'); ?></td>
                <td>$4.79</td>
                <td class="mu-pricing-compare-popular">$7.99</td>
                <td>$11.99</td>
            </tr>
            <tr>
                <td><?php esc_html_e('Video Quality', 'astra-child'); ?></td>
                <td>HD (720p)</td>
                <td class="mu-pricing-compare-popular">Full HD (1080p)</td>
                <td>4K Ultra HD</td>
            </tr>
            <tr>
                <td><?php esc_html_e('Devices You Can Watch On', 'astra-child'); ?></td>
                <td>1</td>
                <td class="mu-pricing-compare-popular">2</td>
                <td>4</td>
            </tr>
            <tr>
                <td><?php esc_html_e('Download Devices', 'astra-child'); ?></td>
                <td>1</td>
                <td class="mu-pricing-compare-popular">2</td>
                <td>4</td>
            </tr>
            <tr>
                <td><?php esc_html_e('Ads', 'astra-child'); ?></td>
                <td>
                    <svg class="mu-pricing-compare-cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </td>
                <td class="mu-pricing-compare-popular">
                    <svg class="mu-pricing-compare-cross" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </td>
                <td>
                    <svg class="mu-pricing-compare-check" viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </td>
            </tr>
            <tr>
                <td><?php esc_html_e('Customer Support', 'astra-child'); ?></td>
                <td><?php esc_html_e('Email', 'astra-child'); ?></td>
                <td class="mu-pricing-compare-popular"><?php esc_html_e('Email', 'astra-child'); ?></td>
                <td><?php esc_html_e('Priority 24/7', 'astra-child'); ?></td>
            </tr>
        </tbody>
    </table>
</section>

<!-- ============================================================ -->
<!-- FAQ ACCORDION -->
<!-- ============================================================ -->
<section class="mu-pricing-faq">
    <h2 class="mu-pricing-faq-title"><?php esc_html_e('Frequently Asked Questions', 'astra-child'); ?></h2>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('Can I change my plan later?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('Yes, you can upgrade or downgrade your plan at any time. Changes will take effect at the start of your next billing cycle. If you upgrade, you\'ll be charged the prorated difference immediately.', 'astra-child'); ?>
            </div>
        </div>
    </div>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('Can I get a refund?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('We offer a 7-day money-back guarantee for new subscribers. If you\'re not satisfied within the first 7 days of your subscription, contact our support team for a full refund. After 7 days, refunds are provided on a case-by-case basis.', 'astra-child'); ?>
            </div>
        </div>
    </div>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('What payment methods do you accept?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('We accept all major credit cards (Visa, Mastercard, American Express), PayPal, and various other payment methods depending on your region. All payments are processed securely through our encrypted payment system.', 'astra-child'); ?>
            </div>
        </div>
    </div>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('How does the free trial work?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('New members get a 7-day free trial on all plans. You won\'t be charged until the trial ends. Cancel anytime during the trial period and you won\'t be charged at all.', 'astra-child'); ?>
            </div>
        </div>
    </div>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('Can I cancel anytime?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('Yes, you can cancel your subscription at any time from your account settings. Your access will continue until the end of your current billing period. No cancellation fees or hidden charges.', 'astra-child'); ?>
            </div>
        </div>
    </div>
    
    <div class="mu-pricing-faq-item">
        <button type="button" class="mu-pricing-faq-question">
            <?php esc_html_e('What\'s the difference between plans?', 'astra-child'); ?>
            <svg class="mu-pricing-faq-icon" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="mu-pricing-faq-answer">
            <div class="mu-pricing-faq-answer-inner">
                <?php esc_html_e('The main differences are video quality, number of devices you can watch on simultaneously, and ad-free experience. Premium gives you the best experience with 4K Ultra HD on up to 4 devices with no ads. Standard is great for most households with Full HD on 2 devices. Basic is perfect for individuals wanting HD quality on one device.', 'astra-child'); ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- FOOTER NOTE -->
<!-- ============================================================ -->
<div class="mu-pricing-footer-note">
    <p>
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:4px;">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?php esc_html_e('All plans include our 7-day free trial for new members.', 'astra-child'); ?>
    </p>
    <p>
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:4px;">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?php esc_html_e('Cancel anytime. No questions asked.', 'astra-child'); ?>
    </p>
</div>

<!-- ============================================================ -->
<!-- CHECKOUT MODAL -->
<!-- ============================================================ -->
<div class="mu-pricing-modal" id="mu-pricing-modal">
    <div class="mu-pricing-modal-backdrop"></div>
    <div class="mu-pricing-modal-content">
        <div class="mu-pricing-modal-icon">
            <svg viewBox="0 0 24 24" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
        <h3 class="mu-pricing-modal-title"></h3>
        <p class="mu-pricing-modal-desc"></p>
        <div class="mu-pricing-modal-plan"></div>
        <div class="mu-pricing-modal-actions">
            <button type="button" id="mu-pricing-modal-cancel" class="mu-pricing-modal-btn mu-pricing-modal-btn--cancel">
                <?php esc_html_e('Cancel', 'astra-child'); ?>
            </button>
            <button type="button" id="mu-pricing-modal-confirm" class="mu-pricing-modal-btn mu-pricing-modal-btn--confirm">
                <?php esc_html_e('Proceed to Checkout', 'astra-child'); ?>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TOAST CONTAINER -->
<!-- ============================================================ -->
<style>
.mu-pricing-toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1a1a1a;
    color: #fff;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    z-index: 10000;
    opacity: 0;
    transition: all 0.3s ease;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.1);
}
.mu-pricing-toast.is-visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>

<?php wp_footer(); ?>
</body>
</html>
