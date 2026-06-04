<?php
/**
 * VerManager - Onlyone OneChat 버전 관리 시스템
 * 접속 URL: https://kiam.kr/vermanger/
 */

define('DATA_DIR', __DIR__ . '/data/');

function rd($f) { $p=DATA_DIR.$f; return file_exists($p)?json_decode(file_get_contents($p),true)??[]:[]; }
function esc($s) { return htmlspecialchars((string)$s); }

$ver  = rd('version.json');
$imps = rd('improvements.json');

$cur    = $ver['current_version'] ?? '1.0.0';
$rels   = $ver['releases'] ?? [];
$totalI = count($imps);
$totalR = count($rels);
$wk     = date('Y-m-d', strtotime('-7 days'));
$thisWk = count(array_filter($imps, fn($i)=>($i['date']??'')>=$wk));
$unver  = count(array_filter($imps, fn($i)=>empty($i['related_version'])));

$cats = []; foreach($imps as $i){$c=$i['category']??'기타'; $cats[$c]=($cats[$c]??0)+1;} arsort($cats);

$sug = 'patch'; if($unver>=5&&$unver<10)$sug='minor'; if($unver>=10)$sug='major';

function badge($t){$m=['major'=>['red','MAJOR'],'minor'=>['orange','MINOR'],'patch'=>['green','PATCH']];$c=$m[$t][0]??'gray';$l=$m[$t][1]??$t;return"<span class='badge badge-$c'>$l</span>";}
function cbadge($c){$cs=['UI'=>'blue','기능'=>'purple','보안'=>'red','성능'=>'orange','버그'=>'pink'];$co=$cs[$c]??'gray';return"<span class='badge badge-$co'>$c</span>";}

$tab = $_GET['tab'] ?? 'dashboard';
?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VerManager | Onlyone OneChat</title>
<link rel="stylesheet" href="/vermanger/css/style.css">
</head>
<body>

<header class="vmh">
  <div class="vmw">
    <div class="vmh-i">
      <a href="/vermanger/" class="vmh-l">
        <span class="vmh-ico">🔢</span>
        <span class="vmh-t">VerManager</span>
        <span class="vmh-sub">Onlyone OneChat</span>
      </a>
      <nav class="vmh-n">
        <a href="/vermanger/" class="<?=$tab==='dashboard'?'on':''?>">📊 대시보드</a>
        <a href="?tab=improvements" class="<?=$tab==='improvements'?'on':''?>">📋 개선사항</a>
        <a href="?tab=changelog" class="<?=$tab==='changelog'?'on':''?>">📝 CHANGELOG</a>
        <a href="?tab=history" class="<?=$tab==='history'?'on':''?>">📜 히스토리</a>
      </nav>
    </div>
  </div>
</header>

<main class="vmw vmm">
<?php if($tab==='dashboard'): ?>

<section class="vm-hero">
  <div>
    <div class="vm-hero-lbl">현재 버전</div>
    <div class="vm-hero-name">Onlyone OneChat <strong>v<?=esc($cur)?></strong></div>
    <div class="vm-hero-date">마지막 릴리즈: <?=esc($ver['last_release']??'없음')?></div>
  </div>
  <div class="vm-hero-btns">
    <button class="vmb vmb1" onclick="openRelease()">🚀 새 버전 릴리즈</button>
    <button class="vmb vmb2" onclick="location.href='?tab=improvements'">📋 개선사항 관리</button>
  </div>
</section>

<div class="vm-sc">
  <div class="vm-sc-i"><b><?=$totalR?></b>총 릴리즈</div>
  <div class="vm-sc-i"><b><?=$totalI?></b>총 개선사항</div>
  <div class="vm-sc-i"><b><?=$thisWk?></b>이번 주</div>
  <div class="vm-sc-i vm-sc-w"><b><?=$unver?></b>미배정</div>
</div>

<?php if($unver>0): ?>
<div class="vm-sug">
  <span>💡 <strong><?=$unver?>개</strong> 미배정 — 추천: <strong><?=strtoupper($sug)?></strong></span>
  <button class="vmb vmb1 vmb-sm" onclick="openRelease()">릴리즈</button>
</div>
<?php endif; ?>

<div class="vm-g2">
  <div class="vm-card">
    <h3>📊 카테고리 분포</h3>
    <?php $maxC=max($cats?:[1]); foreach($cats as $c=>$n): ?>
    <div class="vm-cb">
      <span><?=cbadge($c)?> <?=esc($c)?></span>
      <div class="vm-cbt"><div class="vm-cbf" style="width:<?=round($n/$maxC*100)?>%"></div></div>
      <span><?=$n?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="vm-card">
    <h3>🆕 최근 개선사항</h3>
    <?php foreach(array_slice(array_reverse($imps),0,5) as $i): ?>
    <div class="vm-ii">
      <div class="vm-ii-h"><?=cbadge($i['category'])?> <span class="vm-ii-d"><?=esc($i['date'])?></span> <?=empty($i['related_version'])?'<span class="vm-ii-u">미배정</span>':'<span class="vm-ii-v">📌 v'.esc($i['related_version']).'</span>'?></div>
      <div class="vm-ii-t"><?=esc($i['title'])?></div>
      <div class="vm-ii-de"><?=esc(mb_substr($i['description'],0,60))?><?=mb_strlen($i['description'])>60?'…':''?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<section class="vm-card">
  <h3>📜 최근 릴리즈</h3>
  <?php foreach(array_slice(array_reverse($rels),0,10) as $r): ?>
  <div class="vm-ti">
    <div class="vm-ti-d <?=$r['type']?>"></div>
    <div>
      <strong>v<?=esc($r['version'])?></strong> <?=badge($r['type'])?> <span class="vm-ti-dt"><?=esc($r['date'])?></span>
      <?php if(!empty($r['note'])): ?><div class="vm-ti-n"><?=esc($r['note'])?></div><?php endif; ?>
      <?php if(!empty($r['improvement_ids'])): ?><div>포함: <?=count($r['improvement_ids'])?>건</div><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($rels)): ?><p class="vm-empty">첫 릴리즈를 만들어보세요!</p><?php endif; ?>
</section>

<?php elseif($tab==='improvements'): ?>
<section class="vm-card">
  <div class="vm-ch">
    <h3>📋 소스 개선 관리</h3>
    <div style="display:flex;gap:8px;">
      <button class="vmb vmb2 vmb-sm" onclick="syncImps()">🔄 동기화</button>
      <button class="vmb vmb1 vmb-sm" onclick="openAddImp()">➕ 추가</button>
    </div>
  </div>
  <div class="vm-fb">
    <select class="vms" id="fCat" onchange="filterImps()"><option value="">전체 카테고리</option><option value="UI">UI</option><option value="기능">기능</option><option value="보안">보안</option><option value="성능">성능</option><option value="버그">버그</option><option value="기타">기타</option></select>
    <select class="vms" id="fVer" onchange="filterImps()"><option value="">전체 버전</option><option value="unassigned">미배정</option><?php foreach($rels as $r): ?><option value="<?=esc($r['version'])?>">v<?=esc($r['version'])?></option><?php endforeach; ?></select>
    <span id="impCnt">총 <?=$totalI?>건</span>
  </div>
  <div class="vm-to">
  <table class="vmt" id="impTbl">
    <thead><tr><th><input type="checkbox" id="selAll" onchange="toggleAll(this)"></th><th>날짜</th><th>카테고리</th><th>제목</th><th>설명</th><th>상태</th><th>버전</th><th>관리</th></tr></thead>
    <tbody>
    <?php foreach(array_reverse($imps) as $i): ?>
    <tr data-cat="<?=esc($i['category'])?>" data-ver="<?=esc($i['related_version']??'unassigned')?>">
      <td><input type="checkbox" value="<?=$i['id']?>" class="icb"></td>
      <td><?=esc($i['date'])?></td>
      <td><?=cbadge($i['category'])?></td>
      <td><?=esc($i['title'])?></td>
      <td><?=esc(mb_substr($i['description'],0,40))?><?=mb_strlen($i['description'])>40?'…':''?></td>
      <td><span class="badge badge-green"><?=esc($i['status'])?></span></td>
      <td><?=!empty($i['related_version'])?'v'.esc($i['related_version']):'<span class="vm-ii-u">미배정</span>'?></td>
      <td><button class="vmb-ico" onclick="editImp(<?=$i['id']?>)">✏️</button> <button class="vmb-ico vmb-ico-d" onclick="delImp(<?=$i['id']?>)">🗑️</button></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="vm-ia">
    <span>선택: <strong id="selCnt">0</strong>건</span>
    <button class="vmb vmb1 vmb-sm" onclick="releaseSel()">🚀 선택 릴리즈</button>
  </div>
</section>

<?php elseif($tab==='changelog'): ?>
<section class="vm-card">
  <h3>📝 CHANGELOG</h3>
  <?php $cl=DATA_DIR.'CHANGELOG.md'; if(file_exists($cl)): ?>
  <div class="vm-cl"><?php
    foreach(explode("\n",file_get_contents($cl)) as $l){
      $l=esc($l); $l=preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',$l);
      if(preg_match('/^## (.+)$/',$l,$m)) echo "<h2>{$m[1]}</h2>";
      elseif(preg_match('/^# (.+)$/',$l,$m)) echo "<h1>{$m[1]}</h1>";
      elseif(preg_match('/^- (.+)$/',$l,$m)) echo "<li>{$m[1]}</li>";
      elseif(trim($l)==='') echo "<br>";
      else echo "<p>$l</p>";
    }?></div>
  <?php else: ?><p class="vm-empty">CHANGELOG가 아직 없습니다.</p><?php endif; ?>
</section>

<?php elseif($tab==='history'): ?>
<section class="vm-card">
  <h3>📜 전체 릴리즈 히스토리</h3>
  <?php if($rels): ?>
  <div class="vm-to"><table class="vmt"><thead><tr><th>#</th><th>버전</th><th>유형</th><th>날짜</th><th>이전</th><th>노트</th><th>개선</th></tr></thead><tbody>
  <?php foreach(array_reverse($rels) as $k=>$r): ?>
  <tr><td><?=count($rels)-$k?></td><td><strong>v<?=esc($r['version'])?></strong></td><td><?=badge($r['type'])?></td><td><?=esc($r['date'])?></td><td>v<?=esc($r['previous_version']??'-')?></td><td><?=esc($r['note']??'')?></td><td><?=count($r['improvement_ids']??[])?>건</td></tr>
  <?php endforeach; ?></tbody></table></div>
  <?php else: ?><p class="vm-empty">히스토리가 없습니다.</p><?php endif; ?>
</section>
<?php endif; ?>
</main>

<!-- ★ Release Modal -->
<div class="vmo" id="relMod"><div class="vmmd"><div class="vmmd-h"><h3>🚀 새 버전 릴리즈</h3><button class="vmmd-x" onclick="cls('relMod')">✕</button></div>
<div class="vmmd-b">
  <div class="vmfg"><label>릴리즈 유형</label>
    <div class="vmts">
      <label class="vmtp"><input type="radio" name="rType" value="patch" checked><div class="vmtc"><div class="vmtci">🟢</div><div>PATCH</div><div>버그 수정</div><div>→ <strong>v1.0.1</strong></div></div></label>
      <label class="vmtm"><input type="radio" name="rType" value="minor"><div class="vmtc"><div class="vmtci">🟡</div><div>MINOR</div><div>기능 추가</div><div>→ <strong>v1.1.0</strong></div></div></label>
      <label class="vmtM"><input type="radio" name="rType" value="major"><div class="vmtc"><div class="vmtci">🔴</div><div>MAJOR</div><div>대규모 변경</div><div>→ <strong>v2.0.0</strong></div></div></label>
    </div>
  </div>
  <div class="vmfg"><label>포함 개선사항</label><div id="relImpLst" class="vmis"><?php foreach(array_filter($imps,fn($i)=>empty($i['related_version'])) as $i):?><label class="vmio"><input type="checkbox" name="rImps[]" value="<?=$i['id']?>"><span>[<?=esc($i['category'])?>] <?=esc($i['title'])?></span></label><?php endforeach;if(empty(array_filter($imps,fn($i)=>empty($i['related_version']))))echo'<p class="vm-empty">미배정 개선사항 없음</p>';?></div></div>
  <div class="vmfg"><label for="rNote">릴리즈 노트</label><textarea id="rNote" class="vmta" rows="3" placeholder="변경사항 요약..."></textarea></div>
</div>
<div class="vmmd-f"><button class="vmb vmb2" onclick="cls('relMod')">취소</button><button class="vmb vmb1" onclick="doRelease()">🚀 릴리즈</button></div></div></div>

<!-- ★ Add/Edit Improvement Modal -->
<div class="vmo" id="impMod"><div class="vmmd"><div class="vmmd-h"><h3 id="impModT">➕ 개선사항 추가</h3><button class="vmmd-x" onclick="cls('impMod')">✕</button></div>
<div class="vmmd-b">
  <input type="hidden" id="eImpId">
  <div class="vmfg"><label for="iTitle">제목 *</label><input id="iTitle" class="vmi" placeholder="제목"></div>
  <div class="vmfg"><label for="iDesc">설명</label><textarea id="iDesc" class="vmta" rows="3" placeholder="설명..."></textarea></div>
  <div class="vmfr">
    <div class="vmfg"><label for="iCat">카테고리</label><select id="iCat" class="vms"><option value="UI">UI</option><option value="기능">기능</option><option value="보안">보안</option><option value="성능">성능</option><option value="버그">버그</option><option value="기타">기타</option></select></div>
    <div class="vmfg"><label for="iStat">상태</label><select id="iStat" class="vms"><option value="완료">완료</option><option value="진행중">진행중</option><option value="예정">예정</option></select></div>
  </div>
</div>
<div class="vmmd-f"><button class="vmb vmb2" onclick="cls('impMod')">취소</button><button class="vmb vmb1" id="saveImpBtn" onclick="saveImp()">저장</button></div></div></div>

<div id="toast" class="vmtoast" style="display:none"></div>
<script src="/vermanger/js/app.js"></script>
</body>
</html>