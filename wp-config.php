<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ytdutpyd_wp761' );

/** MySQL database username */
define( 'DB_USER', 'ytdutpyd_wp761' );

/** MySQL database password */
define( 'DB_PASSWORD', 'G4p!7-8PS4' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         'nybcs0pkhcqv4dqsjxg6uvig72uu6hfn8fusb3m1lvy4duoqrt1pg2m0ssdefnxf' );
define( 'SECURE_AUTH_KEY',  'xyrwkz17iwr17sc1yv4h2pxz3cccjuxxrtx6ngvvrfxucghaf3cbuuqkqreminwg' );
define( 'LOGGED_IN_KEY',    'osyh3b3bmoh21le35wq9w309it98zqqqot5meevxlcgitgpp44ufe1n1d1savmoz' );
define( 'NONCE_KEY',        'bluknsyl5qsrj4ho2pvvs19jcq7utdqmzjdac1xittusscwp4dadbskhmbxdq952' );
define( 'AUTH_SALT',        'qxd5gyby5yrpbkw2je3sjhgf2ejvyyijbohyzs1v5yrsmmyits7jota3id3cbjgu' );
define( 'SECURE_AUTH_SALT', 'widbb50l9pzgd14s5gphzb4oxiqyxijptht9ks7ysahj9brgmsppapqho4oeur72' );
define( 'LOGGED_IN_SALT',   'wqawdxge0xfsiq9fk2qdgxqfwq9xggwl9vcimh4w0usxki4hhp3i1ynwzqe8wcij' );
define( 'NONCE_SALT',       'h9hsdeb04zv1srtpftrwzav8jdi4bpae3jjkbstugwwdkqafits7pozxb8fseni0' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wptt_';

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
