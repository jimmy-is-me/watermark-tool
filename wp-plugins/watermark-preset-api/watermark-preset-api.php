<?php
/**
 * Plugin Name: Watermark Preset API
 * Description: 提供浮水印工具的會員設定儲存 REST API，並處理 CORS 跨來源請求。
 * Version: 1.0.0
 * Author: jimmy-is-me
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ===== CORS =====
add_action( 'rest_api_init', function() {
    // 允許來自任何來源（或改為指定 GitHub Pages 網址）
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

    // GET /wp-json/watermark/v1/preset
    // 取得目前登入會員的浮水印設定
    register_rest_route( 'watermark/v1', '/preset', [
        'methods'             => 'GET',
        'callback'            => 'watermark_get_preset',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

    // POST /wp-json/watermark/v1/preset
    // 儲存目前登入會員的浮水印設定
    register_rest_route( 'watermark/v1', '/preset', [
        'methods'             => 'POST',
        'callback'            => 'watermark_save_preset',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ] );

} );

/**
 * 讀取浮水印設定
 */
function watermark_get_preset( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    $preset  = get_user_meta( $user_id, 'watermark_preset', true );

    if ( empty( $preset ) ) {
        return rest_ensure_response( [ 'preset' => null ] );
    }

    $decoded = json_decode( $preset, true );
    return rest_ensure_response( [ 'preset' => $decoded ] );
}

/**
 * 儲存浮水印設定
 */
function watermark_save_preset( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    $body    = $request->get_json_params();

    if ( ! isset( $body['preset'] ) ) {
        return new WP_Error( 'missing_preset', '缺少 preset 資料', [ 'status' => 400 ] );
    }

    $preset_json = wp_json_encode( $body['preset'] );
    update_user_meta( $user_id, 'watermark_preset', $preset_json );

    return rest_ensure_response( [
        'success' => true,
        'message' => '設定已儲存',
    ] );
}

// ===== 開放 wp/v2/users/register 端點（不需登入即可註冊）=====
add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        foreach ( $endpoints['/wp/v2/users'] as $key => $endpoint ) {
            if ( isset( $endpoint['methods'] ) && in_array( 'POST', (array) $endpoint['methods'] ) ) {
                // 允許未登入者 POST /wp/v2/users（自行註冊）
                $endpoints['/wp/v2/users'][ $key ]['permission_callback'] = '__return_true';
            }
        }
    }
    return $endpoints;
} );

// ===== 限制自行註冊只能建立 subscriber 角色 =====
add_filter( 'rest_pre_insert_user', function( $prepared_user, $request ) {
    // 強制新註冊用戶為 subscriber，防止提升權限
    if ( ! is_user_logged_in() ) {
        $prepared_user->role = 'subscriber';
    }
    return $prepared_user;
}, 10, 2 );
