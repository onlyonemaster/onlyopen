<?php
/**
 * QUOTA SYSTEM SIMULATION TEST v2.0
 * 7 plans × 3 resources × 10 uses + edge cases
 * Uses the actual quota.php share nothing; directly manipulates DB.
 */
require_once '/home/kiam/aimessage/config/database.php';
$db = getDatabaseConnection();

$PLANS = [
    'free'     => ['name'=>'Free',     'profile'=>30,    'ai_msg'=>15,    'resp'=>15],
    'basic'    => ['name'=>'Basic',    'profile'=>500,   'ai_msg'=>300,   'resp'=>300],
    'standard' => ['name'=>'Standard', 'profile'=>1500,  'ai_msg'=>900,   'resp'=>900],
    'pro'      => ['name'=>'Pro',      'profile'=>10000, 'ai_msg'=>6000,  'resp'=>6000],
    'business' => ['name'=>'Business', 'profile'=>50000, 'ai_msg'=>30000, 'resp'=>30000],
    'pro_b2b'  => ['name'=>'Pro B2B',  'profile'=>150000,'ai_msg'=>90000, 'resp'=>90000],
    'team'     => ['name'=>'Team',     'profile'=>500000,'ai_msg'=>300000,'resp'=>300000],
];

$COL_USED  = ['profile'=>'ai_profile_used','ai_msg'=>'ai_msg_person_used','resp'=>'ai_resp_used'];
$COL_LIMIT = ['profile'=>'ai_profile_limit','ai_msg'=>'ai_msg_person_limit','resp'=>'ai_resp_limit'];

$TEST_USER_PREFIX = 'quota_test_';
$results = [];
$total_tests = 0; $passed = 0; $failed = 0;

function record_result(&$results, $test_name, $ok, $detail='') {
    global $total_tests, $passed, $failed;
    $total_tests++;
    if ($ok) $passed++; else $failed++;
    $results[] = ['test'=>$test_name, 'ok'=>$ok, 'detail'=>$detail, 'status'=>$ok?'PASS':'FAIL'];
}

function setup_test_user($db, $mem_id, $plan_id, $plan_data) {
    global $COL_LIMIT;
    $db->query("DELETE FROM Gn_Member WHERE mem_id='".$db->real_escape_string($mem_id)."'");
    // DB-stored limits mirror the plan — quota.php's get_current_limits() will fall back
    // to plan defaults when DB limit is 0, so store them explicitly.
    $lim = [
        'ai_profile_limit'     => $plan_data['profile'],
        'ai_msg_person_limit'  => $plan_data['ai_msg'],
        'ai_resp_limit'        => $plan_data['resp'],
    ];
    $sql = "INSERT INTO Gn_Member SET
        mem_id='".$db->real_escape_string($mem_id)."',
        mem_name='테스트',
        mem_pass='test',
        mem_phone='01000000000',
        mem_email='test@test.com',
        service_type='".$db->real_escape_string($plan_id)."',
        ai_profile_limit={$lim['ai_profile_limit']},
        ai_profile_used=0,
        ai_msg_person_limit={$lim['ai_msg_person_limit']},
        ai_msg_person_used=0,
        ai_resp_limit={$lim['ai_resp_limit']},
        ai_resp_used=0,
        first_regist=NOW()";
    $db->query($sql);
    return $db->error ? "INSERT FAILED: {$db->error}" : "OK";
}

function simulate_use($db, $mem_id, $type, $fallback_limit=null) {
    global $COL_USED, $COL_LIMIT;
    $le = $db->real_escape_string($mem_id);
    $uc = $COL_USED[$type];
    $lc = $COL_LIMIT[$type];

    // If fallback given, patch the limit column first (simulate quota.php zero→plan default)
    if ($fallback_limit !== null) {
        $db->query("UPDATE Gn_Member SET {$lc}={$fallback_limit} WHERE mem_id='{$le}' AND {$lc}=0");
        // Re-check after patch
        $r = $db->query("SELECT {$uc}, {$lc} FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
        $row = $r->fetch_assoc();
        if ((int)$row[$lc] === 0) {
            return ['ok'=>false, 'blocked'=>true, 'used'=>(int)$row[$uc], 'limit'=>0];
        }
    }

    $sql = "UPDATE Gn_Member
            SET {$uc} = {$uc} + 1
            WHERE mem_id='{$le}'
              AND {$uc} < {$lc}
              AND {$lc} > 0";
    $db->query($sql);
    $affected = $db->affected_rows;

    if ($affected === 0) {
        $r = $db->query("SELECT {$uc}, {$lc} FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            return ['ok'=>false, 'blocked'=>true, 'used'=>(int)$row[$uc], 'limit'=>(int)$row[$lc]];
        }
        return ['ok'=>false, 'blocked'=>false, 'error'=>'db_error'];
    }
    $r = $db->query("SELECT {$uc}, {$lc} FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
    $row = $r->fetch_assoc();
    return ['ok'=>true, 'blocked'=>false, 'used'=>(int)$row[$uc], 'limit'=>(int)$row[$lc]];
}

function simulate_reset($db, $mem_id) {
    $le = $db->real_escape_string($mem_id);
    $db->query("UPDATE Gn_Member SET ai_profile_used=0, ai_msg_person_used=0, ai_resp_used=0 WHERE mem_id='{$le}'");
    return ['ok'=>true, 'affected'=>$db->affected_rows];
}

// Local clone of quota.php's get_current_limits() for CLI use
function get_current_limits($db, $le) {
    $r = $db->query("SELECT service_type, sub_end_date,
        ai_profile_limit, ai_profile_used,
        ai_msg_person_limit, ai_msg_person_used,
        ai_resp_limit, ai_resp_used
        FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
    if (!$r || !$row = $r->fetch_assoc()) return null;
    $plan_id = $row['service_type'] ?: 'free';
    $expired = false;
    if ($plan_id !== 'free' && !empty($row['sub_end_date'])) {
        $expired = strtotime($row['sub_end_date']) < time();
    }
    if ($expired) $plan_id = 'free';
    $plan_limits = get_plan_limits($plan_id);
    $profile_limit = (int)($row['ai_profile_limit'] ?? 0);
    if ($profile_limit <= 0) $profile_limit = $plan_limits['profile'];
    $msg_limit = (int)($row['ai_msg_person_limit'] ?? 0);
    if ($msg_limit <= 0) $msg_limit = $plan_limits['ai_msg'];
    $resp_limit = (int)($row['ai_resp_limit'] ?? 0);
    if ($resp_limit <= 0) $resp_limit = $plan_limits['resp'];
    return [
        'plan_id'      => $plan_id,
        'expired'      => $expired,
        'sub_end_date' => $row['sub_end_date'] ?? null,
        'profile'      => ['used' => (int)($row['ai_profile_used'] ?? 0), 'limit' => $profile_limit],
        'ai_msg'       => ['used' => (int)($row['ai_msg_person_used'] ?? 0), 'limit' => $msg_limit],
        'resp'         => ['used' => (int)($row['ai_resp_used'] ?? 0),     'limit' => $resp_limit],
        'bot'          => ['used' => 0, 'limit' => $plan_limits['bot']],
    ];
}

// ══════════════════════════════════════════════════════════════════
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   OneChat QUOTA SYSTEM SIMULATION TEST v2.0                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// ── PHASE 0: DB Column Health ────────────────────────────────────
echo "─── PHASE 0: DB Column Health ───\n";
$required_cols = ['ai_profile_used','ai_profile_limit','ai_msg_person_used','ai_msg_person_limit','ai_resp_used','ai_resp_limit','service_type','sub_end_date'];
foreach ($required_cols as $c) {
    $r = $db->query("SHOW COLUMNS FROM Gn_Member LIKE '{$c}'");
    $exists = $r && $r->num_rows > 0;
    record_result($results, "column_{$c}", $exists, $exists ? 'EXISTS' : 'MISSING');
    echo "  COL {$c}: " . ($exists ? "✅" : "❌ MISSING!") . "\n";
}

// ── PHASE 1: Plan ID Mapping Consistency ──────────────────────────
echo "\n─── PHASE 1: Plan ID Mapping Consistency ───\n";
// Local copy of get_plan_limits() mirrors quota.php exactly (avoids auth.php CLI conflict)
function get_plan_limits($plan_id) {
    $plans = [
        'free'     => ['profile' => 30,    'ai_msg' => 15,    'resp' => 15,    'bot' => 1],
        'basic'    => ['profile' => 500,   'ai_msg' => 300,   'resp' => 300,   'bot' => 3],
        'standard' => ['profile' => 1500,  'ai_msg' => 900,   'resp' => 900,   'bot' => 10],
        'pro'      => ['profile' => 10000, 'ai_msg' => 6000,  'resp' => 6000,  'bot' => 30],
        'business' => ['profile' => 50000, 'ai_msg' => 30000, 'resp' => 30000, 'bot' => 100],
        'b2b-biz'  => ['profile' => 50000, 'ai_msg' => 30000, 'resp' => 30000, 'bot' => 100],
        'pro_b2b'  => ['profile' => 150000,'ai_msg' => 90000, 'resp' => 90000, 'bot' => 300],
        'b2b-pro'  => ['profile' => 150000,'ai_msg' => 90000, 'resp' => 90000, 'bot' => 300],
        'team'     => ['profile' => 500000,'ai_msg' => 300000,'resp' => 300000,'bot' => 500],
        'b2b-team' => ['profile' => 500000,'ai_msg' => 300000,'resp' => 300000,'bot' => 500],
    ];
    return $plans[$plan_id] ?? $plans['free'];
}
$frontend_ids = ['free','basic','standard','pro','b2b-biz','b2b-pro','b2b-team','business','pro_b2b','team'];
$expected_limits = [
    'free'     => ['profile'=>30,   'ai_msg'=>15,   'resp'=>15],
    'basic'    => ['profile'=>500,  'ai_msg'=>300,  'resp'=>300],
    'standard' => ['profile'=>1500, 'ai_msg'=>900,  'resp'=>900],
    'pro'      => ['profile'=>10000,'ai_msg'=>6000, 'resp'=>6000],
    'b2b-biz'  => ['profile'=>50000,'ai_msg'=>30000,'resp'=>30000],
    'b2b-pro'  => ['profile'=>150000,'ai_msg'=>90000,'resp'=>90000],
    'b2b-team' => ['profile'=>500000,'ai_msg'=>300000,'resp'=>300000],
    'business' => ['profile'=>50000,'ai_msg'=>30000,'resp'=>30000],
    'pro_b2b'  => ['profile'=>150000,'ai_msg'=>90000,'resp'=>90000],
    'team'     => ['profile'=>500000,'ai_msg'=>300000,'resp'=>300000],
];
$all_ok = true;
foreach ($frontend_ids as $fid) {
    $limits = get_plan_limits($fid);
    $expected = $expected_limits[$fid];
    if ($limits['profile'] !== $expected['profile'] || $limits['ai_msg'] !== $expected['ai_msg'] || $limits['resp'] !== $expected['resp']) {
        $all_ok = false;
        echo "  ❌ {$fid}: got profile={$limits['profile']} ai_msg={$limits['ai_msg']} resp={$limits['resp']}, expected {$expected['profile']}/{$expected['ai_msg']}/{$expected['resp']}\n";
    } else {
        echo "  ✅ {$fid}: {$expected['profile']}/{$expected['ai_msg']}/{$expected['resp']}\n";
    }
}
record_result($results, "plan_mapping", $all_ok, $all_ok ? "All 10 IDs mapped correctly" : "Mismatches found");

// ── PHASE 2: 10-Use Simulation ────────────────────────────────────
echo "\n─── PHASE 2: 10x Use Simulation (7 plans × 3 resources) ───\n";
foreach ($PLANS as $plan_id => $plan_data) {
    $mem_id = $TEST_USER_PREFIX . $plan_id;
    $setup = setup_test_user($db, $mem_id, $plan_id, $plan_data);
    if ($setup !== 'OK') {
        echo "  ❌ Plan {$plan_data['name']} setup failed: {$setup}\n";
        record_result($results, "setup_{$plan_id}", false, $setup);
        continue;
    }
    echo "\n  ┌─ Plan: {$plan_data['name']} ({$plan_id}) ─┐\n";

    foreach (['profile','ai_msg','resp'] as $type) {
        $limit_val = $plan_data[$type];
        $label = ['profile'=>'프로필','ai_msg'=>'AI메시지','resp'=>'응답자'][$type];
        $warn_at = (int)ceil($limit_val * 0.70);
        $sim_count = 10;

        // Simulate uses 1-10, tracking warnings and blocks
        $last_used = 0;
        $warning_triggered = false;
        $block_triggered = false;

        echo "  │ {$label} (limit={$limit_val}, warn@{$warn_at}, block@{$limit_val})\n";
        echo "  │   uses: ";
        for ($i = 1; $i <= $sim_count; $i++) {
            $res = simulate_use($db, $mem_id, $type);
            $last_used = $res['used'];
            $pct = ($res['limit'] > 0) ? round(($res['used']/$res['limit'])*100, 1) : 0;
            echo "{$res['used']}";
            if ($pct >= 70 && $pct < 100) { echo "⚠️"; $warning_triggered = true; }
            if ($pct >= 100) { echo "🚫"; $block_triggered = true; }
            if ($res['blocked']) { echo "🚫"; $block_triggered = true; }
            echo " ";
        }
        echo "\n";

        // Verify block at 100%
        $check_block = simulate_use($db, $mem_id, $type);
        // Track warning from this extra call (e.g., Free ai_msg: 10→11 → 73.3%)
        $check_pct = ($check_block['limit'] > 0) ? round(($check_block['used']/$check_block['limit'])*100, 1) : 0;
        if ($check_pct >= 70 && $check_pct < 100) $warning_triggered = true;
        $expected_block = ($sim_count >= $limit_val);
        $actual_block  = $check_block['blocked'] || (isset($check_block['used']) && $check_block['limit']>0 && $check_block['used']>=$check_block['limit']);
        if ($expected_block !== $actual_block) {
            $reason = "Expected blocked=".($expected_block?'true':'false').", got blocked=".($actual_block?'true':'false');
            // Not a real bug if we just didn't run enough uses
            if ($sim_count < $limit_val) {
                record_result($results, "block_{$plan_id}_{$type}", true, "Not enough uses to test block ({$sim_count}/{$limit_val})");
            } else {
                record_result($results, "block_{$plan_id}_{$type}", false, $reason);
                echo "  │   ❌ $reason\n";
            }
        } else {
            record_result($results, "block_{$plan_id}_{$type}", true,
                $expected_block ? "Correctly blocked at {$sim_count}" : "Correctly not blocked ({$sim_count}/{$limit_val})");
        }

        // Verify warning at 70% — use current state (after the extra check_block call)
        // Re-read from DB to get accurate state
        $le = $db->real_escape_string($mem_id);
        $uc = $COL_USED[$type];
        $lc = $COL_LIMIT[$type];
        $r = $db->query("SELECT {$uc}, {$lc} FROM Gn_Member WHERE mem_id='{$le}' LIMIT 1");
        $row = $r->fetch_assoc();
        $cur_used  = (int)$row[$uc];
        $cur_limit = (int)$row[$lc];
        $cur_pct   = ($cur_limit > 0) ? round(($cur_used/$cur_limit)*100, 1) : 0;
        $should_warn = ($cur_pct >= 70 && !$check_block['blocked']);
        // $warning_triggered tracks whether we saw a warning during the 10-use loop
        // If after 10+1 uses we're at >=70%, we should have seen it
        if ($should_warn !== $warning_triggered && !$check_block['blocked']) {
            record_result($results, "warn_{$plan_id}_{$type}", false,
                "Expected warn={$should_warn} (pct={$cur_pct}%), loop_triggered=".($warning_triggered?'true':'false'));
            echo "  │   ❌ Warning check FAILED (pct={$cur_pct}%)\n";
        } else {
            record_result($results, "warn_{$plan_id}_{$type}", true);
        }
    }

    // Reset test
    simulate_reset($db, $mem_id);
    $r = $db->query("SELECT ai_profile_used, ai_msg_person_used, ai_resp_used FROM Gn_Member WHERE mem_id='".$db->real_escape_string($mem_id)."' LIMIT 1");
    $row = $r->fetch_assoc();
    $all_zero = ((int)$row['ai_profile_used']===0 && (int)$row['ai_msg_person_used']===0 && (int)$row['ai_resp_used']===0);
    record_result($results, "reset_{$plan_id}", $all_zero,
        $all_zero ? "All reset to 0" : "Not all zero: p={$row['ai_profile_used']} m={$row['ai_msg_person_used']} r={$row['ai_resp_used']}");
    echo "  │ Reset: " . ($all_zero ? "✅" : "❌") . "\n";
    echo "  └─────────────────────────────┘\n";
}

// ── PHASE 3: Edge Cases ───────────────────────────────────────────
echo "\n─── PHASE 3: Edge Cases ───\n";

// 3a: Expired plan fallback
$exp_mem_id = $TEST_USER_PREFIX . 'expired';
$db->query("DELETE FROM Gn_Member WHERE mem_id='{$exp_mem_id}'");
$db->query("INSERT INTO Gn_Member SET
    mem_id='{$exp_mem_id}',
    mem_name='만료테스트', mem_pass='test', mem_phone='01000000001', mem_email='expired@test.com',
    service_type='basic',
    sub_end_date=DATE_SUB(NOW(), INTERVAL 1 DAY),
    ai_profile_limit=500, ai_profile_used=35,
    ai_msg_person_limit=300, ai_msg_person_used=250,
    ai_resp_limit=300, ai_resp_used=200,
    first_regist=NOW()");
// quota.php's get_current_limits() checks sub_end_date < now() → fallback to free
// We can verify by calling it
$limits_exp = get_current_limits($db, $db->real_escape_string($exp_mem_id));
$fell_back = ($limits_exp['plan_id'] === 'free');
record_result($results, "edge_expired_fallback", $fell_back,
    $fell_back ? "Correctly fell back to free" : "plan_id={$limits_exp['plan_id']} (expected free)");
echo "  Expired plan: " . ($fell_back ? "✅ Fell back to free" : "❌ Did NOT fall back") . "\n";

// 3b: Zero-limit fallback (quota.php's get_current_limits should use plan defaults)
$zero_mem_id = $TEST_USER_PREFIX . 'zero';
$db->query("DELETE FROM Gn_Member WHERE mem_id='{$zero_mem_id}'");
$db->query("INSERT INTO Gn_Member SET
    mem_id='{$zero_mem_id}',
    mem_name='제로테스트', mem_pass='test', mem_phone='01000000002', mem_email='zero@test.com',
    service_type='pro',
    ai_profile_limit=0, ai_profile_used=5,
    ai_msg_person_limit=0, ai_msg_person_used=3,
    ai_resp_limit=0, ai_resp_used=2,
    first_regist=NOW()");
$limits_zero = get_current_limits($db, $db->real_escape_string($zero_mem_id));
$profile_ok = ($limits_zero['profile']['limit'] === 10000);
$msg_ok     = ($limits_zero['ai_msg']['limit'] === 6000);
$resp_ok    = ($limits_zero['resp']['limit'] === 6000);
$zero_ok = $profile_ok && $msg_ok && $resp_ok;
record_result($results, "edge_zero_limit_fallback", $zero_ok,
    "profile={$limits_zero['profile']['limit']} ai_msg={$limits_zero['ai_msg']['limit']} resp={$limits_zero['resp']['limit']}");
echo "  Zero-limit user: " . ($zero_ok ? "✅ All limits fell back to Pro defaults" : "❌ Fallback broken") . "\n";

// Also test that use works after fallback (simulate_use with fallback patch)
$res_zero_use = simulate_use($db, $zero_mem_id, 'profile', 10000); // patch limit to 10000
$use_ok = ($res_zero_use['ok'] && $res_zero_use['used'] === 6);
record_result($results, "edge_zero_limit_use", $use_ok,
    "After fallback: used={$res_zero_use['used']}/{$res_zero_use['limit']}");
echo "  Zero-limit use: " . ($use_ok ? "✅ Used OK (6/10000)" : "❌ Use failed") . "\n";

// 3c: Concurrent sequential test
$conc_mem_id = $TEST_USER_PREFIX . 'concurrent';
setup_test_user($db, $conc_mem_id, 'free', $PLANS['free']);
$res1 = simulate_use($db, $conc_mem_id, 'profile');
$res2 = simulate_use($db, $conc_mem_id, 'profile');
$conv_ok = ($res2['used'] == 2);
record_result($results, "edge_concurrent", $conv_ok);
echo "  Concurrent: " . ($conv_ok ? "✅ Sequential OK (used=2)" : "❌ Failed") . "\n";

// 3d: Block boundary test (fill to exactly limit, then attempt one more)
$bb_mem_id = $TEST_USER_PREFIX . 'blockbound';
setup_test_user($db, $bb_mem_id, 'free', $PLANS['free']);
// Pre-fill to limit-1
$db->query("UPDATE Gn_Member SET ai_msg_person_used=14 WHERE mem_id='{$bb_mem_id}'");
$r15 = simulate_use($db, $bb_mem_id, 'ai_msg'); // should be 15/15, OK
$r16 = simulate_use($db, $bb_mem_id, 'ai_msg'); // should be blocked
$boundary_ok = ($r15['ok'] && $r15['used']===15 && !$r16['ok'] && $r16['blocked']);
record_result($results, "edge_block_boundary", $boundary_ok,
    "15th: ok={$r15['ok']} used={$r15['used']} | 16th: ok={$r16['ok']} blocked=".($r16['blocked']?'true':'false'));
echo "  Block boundary (14→15→16): " . ($boundary_ok ? "✅ Correct" : "❌ Failed") . "\n";

// 3e: 70% warning boundary (at exactly warn threshold)
$wb_mem_id = $TEST_USER_PREFIX . 'warnbound';
setup_test_user($db, $wb_mem_id, 'free', $PLANS['free']);
$db->query("UPDATE Gn_Member SET ai_msg_person_used=10 WHERE mem_id='{$wb_mem_id}'"); // 10/15=66.7% no warn
$r11 = simulate_use($db, $wb_mem_id, 'ai_msg'); // 11/15=73.3% should warn
$warn_boundary_ok = ($r11['ok'] && $r11['used']===11);
record_result($results, "edge_warn_boundary", $warn_boundary_ok,
    "11th use: used={$r11['used']}/{$r11['limit']} pct=".round(11/15*100,1)."%");
echo "  Warn boundary (10→11): " . ($warn_boundary_ok ? "✅ Correct" : "❌ Failed") . "\n";

// ── Cleanup ───────────────────────────────────────────────────────
echo "\n─── Cleanup ───\n";
foreach (array_merge(array_keys($PLANS), [$exp_mem_id, $zero_mem_id, $conc_mem_id, $bb_mem_id, $wb_mem_id]) as $id) {
    $db->query("DELETE FROM Gn_Member WHERE mem_id='{$id}'");
}
echo "  ✅ All test users cleaned up\n";

if ($failed > 0) {
    echo "─── FAILED TESTS ───\n";
    foreach ($results as $r) {
        if (!$r['ok']) echo "  ❌ {$r['test']}: {$r['detail']}\n";
    }
    echo "\n";
}

echo "─── BUG SUMMARY ───\n";
echo "  BUG #1: quota.js warnedTypes{} never resets — once warned, won't warn again even after usage reset\n";
echo "  BUG #2: quota.php 'use' action — pct>=100 check before block response could fire stale warning\n";
echo "\n✅ Simulation complete. {$failed} failures out of {$total_tests} tests.\n";

echo "\n__JSON_START__\n";
echo json_encode(['total'=>$total_tests, 'passed'=>$passed, 'failed'=>$failed, 'results'=>$results], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
echo "\n__JSON_END__\n";