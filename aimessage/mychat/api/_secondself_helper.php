<?php
/**
 * 🌌 Second Self 헬퍼 함수
 * ─────────────────────────────────────────────────────────────
 * 다른 mychat API 들이 호출하는 영구 기록 / atom 추출 진입점.
 *
 *   ss_source_mirror_chat_turn()   대화 turn 한 줄을 ss_sources 에 영구 보존
 *   ss_source_mirror_pair()        user+assistant 한 쌍을 한 번에 미러링
 *   ss_log_event()                 sanctum_log 가 아닌 시스템 이벤트 로그
 *
 * 작성: 아리 — 2026-06-05 (맹약 발효 직후)
 */

if (!function_exists('ss_source_mirror_chat_turn')) {

/**
 * 마이챗 대화 turn 1건을 ss_sources 에 미러링.
 *
 * @param mysqli  $db
 * @param string  $user_id          mychat mem_id (예: 'onlysong')
 * @param string  $role             'user' | 'assistant' | 'ari' | 'system'
 * @param string  $content          대화 본문
 * @param int|null $linked_chat_id  연결된 mychat_chat_history.id (있으면)
 * @param string  $channel          'mychat' | 'sanctum' | 'genspark' | 'phone_sync'
 * @return int|false   생성된 source_id, 실패 시 false
 */
function ss_source_mirror_chat_turn(
    $db, $user_id, $role, $content, $linked_chat_id = null, $channel = 'mychat'
) {
    if (!$db || $user_id === '' || $content === '') return false;

    $valid_roles = ['user','assistant','ari','system'];
    if (!in_array($role, $valid_roles, true)) $role = 'user';

    $st = $db->prepare("
        INSERT INTO ss_sources
            (user_id, kind, chat_role, linked_chat_id, chat_channel,
             text_content, process_status)
        VALUES (?, 'chat_turn', ?, ?, ?, ?, 'pending')
    ");
    if (!$st) {
        error_log('[SS] mirror prepare failed: ' . $db->error);
        return false;
    }

    $st->bind_param(
        'ssiss',
        $user_id, $role, $linked_chat_id, $channel, $content
    );

    if (!$st->execute()) {
        error_log('[SS] mirror execute failed: ' . $st->error);
        $st->close();
        return false;
    }
    $sid = $st->insert_id;
    $st->close();
    return $sid;
}

/**
 * user 발화 + assistant 응답을 한 쌍으로 미러링.
 * chat.php 에서 가장 흔히 쓰는 패턴.
 *
 * @return array{user_sid:int|false, ai_sid:int|false}
 */
function ss_source_mirror_pair(
    $db, $user_id, $user_msg, $ai_reply,
    $linked_user_chat_id = null, $linked_ai_chat_id = null,
    $channel = 'mychat'
) {
    $user_sid = ss_source_mirror_chat_turn(
        $db, $user_id, 'user', $user_msg, $linked_user_chat_id, $channel
    );
    $ai_sid = ss_source_mirror_chat_turn(
        $db, $user_id, 'assistant', $ai_reply, $linked_ai_chat_id, $channel
    );
    return ['user_sid' => $user_sid, 'ai_sid' => $ai_sid];
}

/**
 * 시스템 이벤트 로그 — sanctum_log 와 별개의 가벼운 audit trail.
 * (지금은 PHP error_log 로만 흘려보낸다. 추후 ss_events 테이블 추가 가능)
 */
function ss_log_event($event, $data = []) {
    $line = '[SS-EVENT] ' . $event;
    if ($data) $line .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    error_log($line);
}

} // !function_exists
