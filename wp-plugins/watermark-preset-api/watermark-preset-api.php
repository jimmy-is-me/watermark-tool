<?php
/**
 * Plugin Name: Watermark Preset API
 * Description: 提供浮水印工具的會員設定儲存 REST API，包含自訂註冊端點，並處理 CORS 跨來源請求。
 * Version: 1.1.0
 * Author: jimmy-is-me
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ===== CORS =====
add_action( 'rest_api_init', function() {
    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
}, 15 );

add_action( 'init', function() {
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
        header( 'HTTP/1.1 200 OK' );
        exit;
    }
} );

// ===== REST API 路由 =====
add_action( 'rest_api_init', function () {

    // POST /wp-json/watermark/v1/register  (自訂註冊端點，不需登入)
    register_rest_route( 'watermark/v1', '/register', [
        'methods'             => 'POST',
        'callback'            => 'watermark_register_user',
        'permission_callback' => '__return_true',
        'args' => [
            'username' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_user' ],
            'email'    => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ],
            'password' => [ 'required' => true, 'type' => 'string' ],
        ],
    ] );

    // GET /wp-json/watermark/v1/preset
    register_rest_route( 'watermark/v1', '/preset', [
        'methods'             => 'GET',
        'callback'            => 'watermark_get_preset',
        'permission_callback' => function () { return is_user_logged_in(); },
    ] );

    // POST /wp-json/watermark/v1/preset
    register_rest_route( 'watermark/v1', '/preset', [
        'methods'             => 'POST',
        'callback'            => 'watermark_save_preset',
        'permission_callback' => function () { return is_user_logged_in(); },
    ] );

} );

/**
 * 自訂註冊端點
 * POST /wp-json/watermark/v1/register
 * body: { username, email, password }
 */
function watermark_register_user( WP_REST_Request $request ) {
    $username = $request->get_param('username');
    $email    = $request->get_param('email');
    $password = $request->get_param('password');

    // 基本驗證
    if ( empty($username) || empty($email) || empty($password) ) {
        return new WP_Error( 'missing_fields', '請填寫所有欄位', [ 'status' => 400 ] );
    }
    if ( strlen($password) < 8 ) {
        return new WP_Error( 'weak_password', '密碼至少需要 8 個字元', [ 'status' => 400 ] );
    }
    if ( ! is_email($email) ) {
        return new WP_Error( 'invalid_email', '電子信筱格式不正確', [ 'status' => 400 ] );
    }
    if ( username_exists($username) ) {
        return new WP_Error( 'username_exists', '此帳號已被使用', [ 'status' => 409 ] );
    }
    if ( email_exists($email) ) {
        return new WP_Error( 'email_exists', '此電子信筱已被註冊', [ 'status' => 409 ] );
    }

    // 建立帳號（強制 subscriber 角色）
    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error($user_id) ) {
        return new WP_Error( 'register_failed', $user_id->get_error_message(), [ 'status' => 500 ] );
    }
    wp_update_user( [ 'ID' => $user_id, 'role' => 'subscriber' ] );

    return rest_ensure_response( [