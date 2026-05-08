<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'movie_wp' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1:3307' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'f{I-X^0f:) qt)Eg10OqN@k~b7sa@bEgS|;i{;99J^])U;VR<3i*r`bKL(wQOMqz' );
define( 'SECURE_AUTH_KEY',  ')9s@`Xc),z<5!UQ`W!sMvb{)J>o>=`7{o5xw7aM=(^n43[A~N1d,jIwdj +!`v_=' );
define( 'LOGGED_IN_KEY',    'y7  (uZyb<`^B}P=>vT.xp|FA=F#)C 0,B&HxXL1w[/ N2k!}npo3%-p@2Psu=B0' );
define( 'NONCE_KEY',        'e$+,vG]?VwuMZX]e_p!_gIxXw_su|*|%09^Pi1m-%T{W*iCyy{.Ihm{vWb:`W{ q' );
define( 'AUTH_SALT',        'td_qU%>pc[eT0,ejFp(*KYtgx~|=ZE,;=9dgPuCKDBEt63/bEN+Bou9u-D)TaNq(' );
define( 'SECURE_AUTH_SALT', 'vlTROxoMD{Q/p_8t.6IJ6)!5IT+,>cT5nh`r(s<qMT=n%,g!O.xi:fN(TJeBpWXX' );
define( 'LOGGED_IN_SALT',   '}C>`g`3#T8C(wXwF`x{r-:g#DqHfJqrN}).Z)By-GC&>Sg+MoS.NJh{T=vn*)fI(' );
define( 'NONCE_SALT',       'Vk&RX`mSe|=9@Oy4_u6XHf?KtS:9z,={^vC.eDVcctawzs!~KB?t6e5l#*Pk[|#K' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
