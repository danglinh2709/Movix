<?php
/**
 * OTT Sticky Header
 */
$current_user = wp_get_current_user();
$avatar_url = get_avatar_url($current_user->ID);
$home_url = home_url('/');
?>
<header class="mu-header" data-mu-header>
  <div class="mu-header__inner">
    <a href="<?php echo $home_url; ?>" class="mu-brand">
      <span class="mu-brand__logo">M</span>
      <span class="mu-brand__text">MOVIE</span>
    </a>

    <nav class="mu-nav">
      <a href="<?php echo $home_url; ?>" class="<?php echo is_front_page() ? 'is-active' : ''; ?>">Home</a>
      <a href="<?php echo home_url('/movies/'); ?>" class="<?php echo is_post_type_archive('movie') ? 'is-active' : ''; ?>">Movies</a>
      <a href="<?php echo home_url('/tv/'); ?>" class="<?php echo is_post_type_archive('tv_show') ? 'is-active' : ''; ?>">TV Shows</a>
      <a href="<?php echo home_url('/trending/'); ?>">Trending</a>
      <a href="<?php echo home_url('/favorites/'); ?>">My List</a>
    </nav>

    <div class="mu-header__right">
      <button class="mu-btn-search" data-mu-open-search aria-label="Search">
        <span class="mu-ico-search"></span>
      </button>
      
      <div class="mu-user-menu">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile" class="mu-avatar">
        <div class="mu-user-dropdown">
          <a href="<?php echo home_url('/profile/'); ?>">Account</a>
          <a href="<?php echo home_url('/vip/'); ?>">VIP Membership</a>
          <a href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
        </div>
      </div>
    </div>
  </div>
</header>
