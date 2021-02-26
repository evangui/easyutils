<?php
/*
 * token认证相关助手函数
 *
 * helper_token.php
 */
use Firebase\JWT\JWT;

/**
 * 根据payload生成 jwt token
 * @param array $payload
 * @return string
 */
function create_token($payload) {
    $access_token = JWT::encode($payload, env('jwt_key'));
    return $access_token;
}

/**
 * 根据payload生成 jwt token
 * @param array $payload
 * @return string
 */
function decode_token($token) {
    //解密token
    return JWT::decode($token, env('jwt_key'), array('HS256'));
}

/**
 * 博库用户&读者token统一生成函数
 * @param $uid
 * @param $from_aid
 * @param $readers_id
 * @param string $reader_id
 * @param string $reader_password
 * @return string
 */
function bk_create_token($uid, $from_aid, $readers_id, $reader_id='', $reader_password='')
{
    //生成access_token
    $payload = [
        'uid'         => $uid,
        'from_aid'    => $from_aid,
        'readers_id'  => $readers_id,
        'reader_id'   => $reader_id,
        'rdpass'   => $reader_password,
        'login_time'  => time(),
    ];
    return create_token($payload);
}
