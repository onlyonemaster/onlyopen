#!/usr/bin/env python3
"""
OneChat 범용 마케팅 퍼널 목업 HTML 빌더
-----------------------------------------
사용법:
  python3 build.py --config config/sample1_cheongdam.json
  python3 build.py --config config/sample2_hakwon.json --out output/hakwon01.html

구조:
  config/   ← 시나리오별 JSON 설정 파일 (업종·회사별로 하나씩)
  output/   ← 생성된 HTML 파일
  build.py  ← 이 템플릿 엔진 (수정 불필요)

지원 업종: 한의원, 학원, 부동산, 리조트, 피트니스 등
공통 퍼널: 광고 → 진단/평가 → 신뢰 → 전환 → 유지 → 분석 → ROI
"""

import os, sys, json, argparse

# ──────────────────────────────────────────
# 공통 CSS (navy/gold 프리미엄 테마)
# ──────────────────────────────────────────

CSS_COMMON = '''*{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0A1628;--navy2:#0D1F3C;--navy3:#162947;
  --ocean:#0E6BA8;--ocean2:#1A85CC;--ocean3:#48CAE4;
  --gold:#C9A84C;--gold2:#E2C068;--gold-light:#FDF3DC;
  --dawn:#023E8A;--wave:#90E0EF;--white:#FFFFFF;
  --danger:#EF4444;--med-green:#2E7D32;--med-green2:#22C55E;
  --header-bg:#0D1F3C;--header-sub:#7A9BB5;--chat-bg:#F0F4F8;
  --msg-ai-bg:#FFFFFF;--msg-ai-border:#D0DAEB;
  --msg-user-bg:#E0F2FE;--msg-user-border:#90CAF9;
  --msg-text:#1E293B;--time-color:#94A3B8;
  --tabbar-bg:#0A1628;--tab-active:#0E6BA8;
}
html,body{
  font-family:'Apple SD Gothic Neo','Malgun Gothic',-apple-system,BlinkMacSystemFont,sans-serif;
  background:var(--navy);color:#1E293B;height:100%;overflow:hidden;
}
#app-shell{
  position:fixed;inset:0;display:flex;flex-direction:column;
  max-width:480px;margin:0 auto;background:var(--chat-bg);overflow:hidden;
}
#inv-banner{
  flex-shrink:0;background:linear-gradient(90deg,#0A1628,#023E8A,#0A1628);
  border-bottom:1px solid rgba(201,168,76,0.4);padding:6px 14px;
  display:flex;align-items:center;gap:8px;z-index:100;
}
.ib-brand{font-size:13px;font-weight:800;color:var(--gold);letter-spacing:1px;}
.ib-tag{font-size:11px;color:rgba(255,255,255,0.5);flex:1;text-align:center;}
.ib-badge{background:var(--gold);color:var(--navy);font-size:10px;font-weight:800;padding:2px 8px;border-radius:10px;}
#funnel-bar{
  flex-shrink:0;background:rgba(10,22,40,0.97);
  border-bottom:1px solid rgba(201,168,76,0.15);padding:6px 10px;
  display:flex;align-items:center;gap:0;overflow-x:auto;scrollbar-width:none;z-index:99;
}
#funnel-bar::-webkit-scrollbar{display:none;}
.fs{display:flex;align-items:center;gap:4px;flex-shrink:0;}
.fs-dot{
  width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:800;border:1.5px solid rgba(255,255,255,0.12);
  background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.3);transition:all 0.2s;
}
.fs-lbl{font-size:10px;color:rgba(255,255,255,0.25);white-space:nowrap;transition:all 0.4s;}
.fs.active .fs-dot{background:var(--gold);border-color:var(--gold2);color:var(--navy);box-shadow:0 0 10px rgba(201,168,76,0.5);}
.fs.active .fs-lbl{color:var(--gold);font-weight:700;}
.fs.done .fs-dot{background:rgba(201,168,76,0.2);border-color:var(--gold);color:var(--gold);}
.fs.done .fs-lbl{color:rgba(201,168,76,0.55);}
.fa{color:rgba(255,255,255,0.15);font-size:10px;padding:0 3px;flex-shrink:0;}
.screen{display:none;flex-direction:column;flex:1;min-height:0;overflow-y:auto;
  animation:fadeUp 0.35s ease both;-webkit-overflow-scrolling:touch;}
.screen.active{display:flex;}
.scr-dark{background:var(--navy);}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}

/* Instagram */
#scr-insta{background:#fff;flex-direction:column;overflow-y:auto;}
.insta-statusbar{flex-shrink:0;background:#fff;padding:12px 16px 4px;display:flex;align-items:center;justify-content:space-between;}
.sb-time{font-size:14px;font-weight:700;color:#000;}
.sb-icons{display:flex;align-items:center;gap:6px;}
.sb-signal,.sb-wifi,.sb-battery{color:#000;}
.insta-header{flex-shrink:0;background:#fff;padding:6px 14px 8px;display:flex;align-items:center;border-bottom:0.5px solid rgba(0,0,0,0.1);}
.insta-wordmark{height:32px;flex:1;}
.insta-hdr-icons{display:flex;align-items:center;gap:6px;}
.ih-icon-btn{background:none;border:none;cursor:pointer;width:28px;height:28px;display:flex;align-items:center;justify-content:center;padding:2px;}
.ih-icon-btn svg{width:24px;height:24px;}
.insta-stories{flex-shrink:0;background:#fff;padding:10px 0 12px 12px;display:flex;gap:12px;overflow-x:auto;scrollbar-width:none;border-bottom:0.5px solid rgba(0,0,0,0.08);}
.story-item{display:flex;flex-direction:column;align-items:center;gap:5px;flex-shrink:0;}
.story-ring{width:62px;height:62px;border-radius:50%;padding:2.5px;background:linear-gradient(45deg,#FEDA77,#F58529,#DD2A7B,#8134AF,#515BD4);display:flex;align-items:center;justify-content:center;}
.story-av{width:55px;height:55px;border-radius:50%;border:2.5px solid #fff;display:flex;align-items:center;justify-content:center;font-size:22px;overflow:hidden;}
.story-lbl{font-size:11px;color:#000;text-align:center;max-width:66px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.feed-post-hdr{flex-shrink:0;background:#fff;padding:10px 12px;display:flex;align-items:center;gap:10px;}
.feed-av-wrap{width:32px;height:32px;border-radius:50%;padding:2px;background:linear-gradient(45deg,#FEDA77,#F58529,#DD2A7B,#8134AF);flex-shrink:0;}
.feed-av{width:100%;height:100%;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:12px;}
.feed-name-wrap{flex:1;}
.feed-name{font-size:13px;font-weight:700;color:#000;line-height:1.3;}
.feed-location{font-size:11px;color:#555;}
.feed-follow-btn{background:none;border:none;color:#0095F6;font-size:14px;font-weight:700;cursor:pointer;}
.feed-more-btn{background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:4px;}
.ad-visual{width:100%;height:210px;position:relative;overflow:hidden;flex-shrink:0;}
.ad-sponsored-bar{position:absolute;top:0;left:0;right:0;background:rgba(0,0,0,0.32);padding:5px 12px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(2px);}
.ad-sp-text{font-size:11px;color:rgba(255,255,255,0.75);font-weight:600;}
.ad-sp-badge{background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;}
.ad-copy-wrap{position:absolute;bottom:14%;left:0;right:0;text-align:center;padding:0 20px;z-index:10;}
.ad-copy-main{font-size:26px;font-weight:900;color:#fff;line-height:1.3;text-shadow:0 2px 16px rgba(0,0,0,0.55);letter-spacing:-0.5px;}
.ad-copy-main em{color:#FFE082;font-style:normal;}
.ad-copy-sub{font-size:14px;color:rgba(255,255,255,0.88);margin-top:7px;text-shadow:0 1px 8px rgba(0,0,0,0.45);font-weight:500;}
.ad-copy-badge{display:inline-block;background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.5);color:#fff;font-size:12px;font-weight:700;padding:4px 13px;border-radius:20px;margin-top:9px;backdrop-filter:blur(4px);}
.ad-bottom{flex-shrink:0;background:#fff;padding:10px 12px 8px;}
.ad-action-row{display:flex;align-items:center;margin-bottom:9px;}
.ad-icon-btn{background:none;border:none;cursor:pointer;padding:3px;display:flex;align-items:center;justify-content:center;}
.ad-icon-btn svg{width:24px;height:24px;}
.ad-icon-btn+.ad-icon-btn{margin-left:8px;}
.ad-bookmark-btn{margin-left:auto;}
.ad-likes{font-size:13px;font-weight:700;color:#000;margin-bottom:5px;}
.ad-caption{font-size:13px;color:#000;margin-bottom:6px;line-height:1.5;}
.ad-caption b{font-weight:700;}
.ad-more{color:#8e8e8e;}
.ad-comments-link{font-size:13px;color:#8e8e8e;margin-bottom:6px;}
.ad-timestamp{font-size:11px;color:#8e8e8e;margin-bottom:10px;letter-spacing:0.2px;}
.insta-cta{width:100%;padding:13px;background:#0095F6;border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.3s;}
.insta-cta:hover{background:#1AA0F7;}
.insta-bottom-nav{flex-shrink:0;background:#fff;border-top:0.5px solid rgba(0,0,0,0.15);padding:8px 0 10px;display:flex;align-items:center;justify-content:space-around;}
.ibn-btn{background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:4px 10px;}
.ibn-btn svg{width:26px;height:26px;}
.ibn-profile{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#90caf9,#42a5f5);border:1.5px solid transparent;}

/* KIAM Chat */
.kiam-header{flex-shrink:0;background:var(--header-bg);padding:11px 14px 9px;display:flex;align-items:flex-start;gap:10px;}
.kh-back{color:#fff;font-size:20px;opacity:0.7;cursor:pointer;margin-top:2px;flex-shrink:0;}
.kh-avatar-char{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;}
.kh-center{flex:1;}
.kh-title{font-size:17px;font-weight:700;color:#fff;line-height:1.3;}
.kh-sub{font-size:12px;color:var(--header-sub);margin-top:2px;}
.kh-badges{display:flex;gap:5px;margin-top:5px;}
.kh-badge{border-radius:10px;padding:2px 8px;font-size:10px;font-weight:700;border:1px solid;display:flex;align-items:center;gap:3px;}
.kh-badge .bdot{width:5px;height:5px;border-radius:50%;}
.kh-badge.myai{color:#22C55E;border-color:#22C55E;}
.kh-badge.myai .bdot{background:#22C55E;}
.kh-badge.user{color:#48CAE4;border-color:#48CAE4;}
.kh-badge.state{color:#94A3B8;border-color:#94A3B8;}
.kh-badge.aimode{color:#22C55E;border-color:#22C55E;}
.kh-badge.aimode .bdot{background:#22C55E;}
.kh-badge.manager{color:var(--gold);border-color:var(--gold);}
.kh-badge.manager .bdot{background:var(--gold);}
.kh-info{font-size:20px;opacity:0.5;color:#fff;flex-shrink:0;}
.green-bar{flex-shrink:0;background:linear-gradient(90deg,#0A2B15,#143D1E);padding:8px 14px;display:flex;align-items:center;gap:9px;}
.gb-icon{font-size:18px;}
.gb-main{font-size:13px;font-weight:700;color:#22C55E;}
.gb-sub{font-size:11px;color:rgba(255,255,255,0.45);}
.kiam-tabs{flex-shrink:0;background:var(--header-bg);border-bottom:1px solid rgba(255,255,255,0.06);display:flex;padding:0 10px;gap:2px;}
.kt{padding:8px 12px;font-size:13px;color:rgba(255,255,255,0.38);border-bottom:2px solid transparent;cursor:pointer;transition:all 0.2s;white-space:nowrap;}
.kt.active{color:var(--gold);border-bottom-color:var(--gold);font-weight:700;}
.kiam-msgs{flex:1;overflow-y:auto;padding:16px 12px;display:flex;flex-direction:column;gap:11px;background:var(--chat-bg);scroll-behavior:smooth;}
.date-div{text-align:center;font-size:12px;color:var(--time-color);padding:4px 0;}

/* Bubbles */
.row-ai{display:flex;align-items:flex-end;gap:7px;opacity:0;transform:translateY(10px)}
.ai-av{width:32px;height:32px;border-radius:50%;background:var(--header-bg);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.ai-bubble{max-width:74%;background:var(--msg-ai-bg);border:1px solid var(--msg-ai-border);border-radius:18px 18px 18px 4px;padding:11px 15px;font-size:16px;color:var(--msg-text);line-height:1.7;box-shadow:0 1px 4px rgba(0,0,0,0.07);}
.ai-time{font-size:10px;color:var(--time-color);margin-top:3px;padding-left:2px;}
.row-user{display:flex;align-items:flex-end;gap:7px;flex-direction:row-reverse;opacity:0;transform:translateY(10px)}
.user-av{width:32px;height:32px;border-radius:50%;background:var(--ocean);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.user-bubble{max-width:74%;background:var(--msg-user-bg);border:1px solid var(--msg-user-border);border-radius:18px 18px 4px 18px;padding:11px 15px;font-size:16px;color:var(--msg-text);line-height:1.7;box-shadow:0 1px 4px rgba(0,0,0,0.07);}
.user-time{font-size:10px;color:var(--time-color);margin-top:3px;text-align:right;padding-right:2px;}
.typing-row{display:flex;align-items:flex-end;gap:7px;opacity:0;transition:opacity 0.3s;}
.typing-row.vis{opacity:1;}
.typing-dots{background:var(--msg-ai-bg);border:1px solid var(--msg-ai-border);border-radius:18px 18px 18px 4px;padding:11px 15px;display:flex;align-items:center;gap:5px;box-shadow:0 1px 4px rgba(0,0,0,0.07);}
.td{width:7px;height:7px;background:#94A3B8;border-radius:50%;animation:tdBounce 1.2s infinite;}
.td:nth-child(2){animation-delay:.2s}
.td:nth-child(3){animation-delay:.4s}
@keyframes tdBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-7px)}}
@keyframes msgSlideIn{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}.chat-item{opacity:0;transform:translateY(14px)}.chat-item.vis{animation:msgSlideIn .38s ease both;opacity:1;transform:translateY(0)}.row-ai.vis,.row-user.vis,.widget-card.vis,.booking-card.vis,.promise-card.vis,.profile-transition.vis,.manager-profile.vis,.i-pill.vis,.choice-group.vis{animation:msgSlideIn .38s ease both;opacity:1;transform:translateY(0)}

/* Choices */
.choice-group{display:flex;flex-wrap:wrap;gap:8px;padding:3px 0 3px 39px;opacity:0;transform:translateY(7px);transition:opacity 0.38s,transform 0.38s;}
.choice-group.vis{opacity:1;transform:translateY(0);}
.choice-btn{padding:10px 18px;border:1.5px solid rgba(201,168,76,0.5);background:rgba(201,168,76,0.08);color:#C9A84C;font-size:15px;font-weight:600;border-radius:22px;cursor:pointer;transition:all 0.22s;white-space:nowrap;}
.choice-btn:hover{background:var(--gold);color:var(--navy);}
.choice-btn:disabled{opacity:0.45;cursor:default;}
.choice-btn.sel{background:var(--gold);color:var(--navy);border-color:var(--gold);}

/* Widget cards */
.widget-card{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:14px;padding:12px;opacity:0;transform:translateY(10px)}
.widget-card.vis{opacity:1;transform:translateY(0);}
.widget-card-img{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.widget-card-body{flex:1;min-width:0;}
.widget-card-title{font-size:13px;font-weight:700;color:#1F2937;}
.widget-card-desc{font-size:11px;color:#6B7280;margin-top:2px;}
.widget-card-btn{background:var(--ocean);color:#fff;border:none;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0;transition:all 0.2s;}

/* Booking */
.booking-card{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:14px;padding:14px;margin-bottom:10px;opacity:0;transform:translateY(10px)}
.bc-title{font-size:14px;font-weight:700;color:#1F2937;margin-bottom:6px;}
.booking-slots{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;}
.booking-slot{padding:8px 14px;border:1.5px solid rgba(0,0,0,0.12);background:#fff;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;}
.booking-slot.sel{background:var(--med-green);color:#fff;border-color:var(--med-green);}
.booking-confirm{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--gold),#A67C2A);color:var(--navy);font-size:15px;font-weight:800;cursor:pointer;transition:all 0.3s;box-shadow:0 3px 14px rgba(201,168,76,0.3);}
.booking-confirm:hover{transform:translateY(-1px);}
.profile-transition{text-align:center;font-size:11px;color:var(--time-color);padding:8px 0;opacity:0}
.manager-profile{display:flex;align-items:center;gap:10px;padding:8px 0;opacity:0}
.manager-avatar{width:36px;height:36px;border-radius:50%;background:var(--ocean);display:flex;align-items:center;justify-content:center;font-size:18px;}
.manager-name{font-size:13px;font-weight:700;color:var(--msg-text);}
.manager-role{font-size:11px;color:#6B7280;}

/* Promise cards */
.promise-card{display:flex;align-items:flex-start;gap:10px;background:#fff;border:1px solid rgba(0,0,0,0.06);border-radius:12px;padding:12px 14px;opacity:0;transform:translateY(10px)}
.pc-icon{font-size:20px;flex-shrink:0;margin-top:2px;}
.pc-title{font-size:13px;font-weight:700;color:#1F2937;margin-bottom:3px;}
.pc-desc{font-size:12px;color:#6B7280;line-height:1.6;}

/* Dark screens */
.res-lbl{font-size:11px;font-weight:700;color:rgba(255,255,255,0.28);letter-spacing:1.5px;padding:18px 16px 0;margin-bottom:12px;background:var(--navy);}
.dark-card{background:var(--navy2);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:20px 18px;margin:0 16px 16px;}
.dark-card-title{font-size:13px;color:rgba(255,255,255,0.45);margin-bottom:13px;font-weight:600;}
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.kpi-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 8px;text-align:center;}
.kpi-val{font-size:22px;font-weight:900;color:#fff;}
.kpi-val.green{color:var(--gold);}
.kpi-val.amber{color:#E2C068;}
.kpi-label{font-size:10px;color:rgba(255,255,255,0.38);margin-top:3px;}

/* Compare table */
.compare-table{width:100%;border-collapse:collapse;font-size:12px;}
.compare-table th{color:rgba(255,255,255,0.35);font-weight:600;padding:6px 8px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.1);}
.compare-table td{padding:8px;border-bottom:1px solid rgba(255,255,255,0.05);color:rgba(255,255,255,0.7);}
.compare-table td.highlight{color:var(--gold);font-weight:700;}

/* Funnel steps */
.funnel-step{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
.funnel-step-bar{height:22px;border-radius:4px;display:flex;align-items:center;padding-left:8px;font-size:11px;font-weight:700;color:#fff;}
.funnel-step-pct{font-size:10px;color:rgba(255,255,255,0.35);min-width:36px;text-align:right;}

/* Trust list */
.trust-item{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.trust-name{font-size:11px;color:rgba(255,255,255,0.45);min-width:40px;}
.trust-bar-track{flex:1;height:8px;background:rgba(255,255,255,0.08);border-radius:4px;overflow:hidden;}
.trust-bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--ocean),var(--gold));transition:width 1s ease;}
.trust-val{font-size:11px;color:#fff;font-weight:700;min-width:36px;text-align:right;}

/* Briefing summary */
.briefing-card{padding:0 16px 40px;}
.briefing-card h2{font-size:22px;font-weight:900;color:var(--gold);text-align:center;margin-bottom:8px;}
.subtitle{font-size:13px;color:rgba(255,255,255,0.4);text-align:center;margin-bottom:24px;}
.briefing-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px;}
.brief-item{background:var(--navy2);border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;text-align:center;}
.bi-icon{font-size:24px;margin-bottom:8px;}
.bi-title{font-size:13px;font-weight:800;color:#fff;margin-bottom:4px;}
.bi-desc{font-size:11px;color:rgba(255,255,255,0.45);line-height:1.5;}
.brief-roi{text-align:center;background:linear-gradient(135deg,rgba(201,168,76,0.15),rgba(201,168,76,0.05));border:1px solid rgba(201,168,76,0.35);border-radius:16px;padding:24px;margin-bottom:20px;}
.roi-label{font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:6px;}
.roi-val{font-size:36px;font-weight:900;color:var(--gold);margin-bottom:8px;}
.roi-desc{font-size:13px;color:rgba(255,255,255,0.55);line-height:1.6;}
.brief-cta{width:100%;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,var(--ocean),var(--dawn));color:#fff;font-size:16px;font-weight:800;cursor:pointer;transition:all 0.3s;box-shadow:0 6px 22px rgba(14,107,168,0.4);}
.brief-cta:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(14,107,168,0.55);}

/* Insight pill */
.i-pill{display:inline-flex;align-items:center;opacity:0;transform:translateY(7px);gap:5px;background:rgba(14,107,168,0.12);border:1px solid rgba(72,202,228,0.35);color:#7DD3F0;font-size:12px;font-weight:600;padding:5px 10px;border-radius:8px;margin:6px 0;letter-spacing:0.2px;line-height:1.5;flex-shrink:0;}
.i-pill::before{content:'💡';font-size:11px;}
.i-pill b{color:var(--gold);}

/* Toggle & input */
.ai-toggle-bar{flex-shrink:0;background:#F7F8FA;border-top:1px solid rgba(0,0,0,0.06);padding:8px 14px;display:flex;align-items:center;gap:9px;}
.toggle-pill{width:40px;height:22px;background:#22C55E;border-radius:11px;position:relative;flex-shrink:0;display:flex;align-items:center;padding:2px;}
.toggle-pill::after{content:'🤖';font-size:12px;position:absolute;right:3px;width:18px;height:18px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;}
.ai-toggle-lbl{font-size:13px;color:#6B7280;display:flex;align-items:center;gap:5px;}
.chat-input-bar{flex-shrink:0;background:#F7F8FA;border-top:1px solid rgba(0,0,0,0.07);padding:9px 12px;display:flex;align-items:center;gap:8px;}
.chat-inp{flex:1;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:22px;padding:10px 16px;color:var(--msg-text);font-size:15px;outline:none;}
.chat-inp::placeholder{color:#B0B8C4;}
.chat-send{width:38px;height:38px;border-radius:50%;background:var(--ocean);border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:17px;color:white;flex-shrink:0;}

/* Bottom nav */
.bottom-nav{flex-shrink:0;background:var(--tabbar-bg);padding:8px 9px;display:flex;align-items:center;gap:4px;}
.bn-tab{flex:1;padding:8px 4px;border:none;border-radius:8px;background:transparent;color:rgba(255,255,255,0.35);font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;white-space:nowrap;}
.bn-tab.active{background:var(--tab-active);color:#fff;}

/* Transition overlay */
#trans-overlay{position:fixed;inset:0;background:var(--navy);z-index:9999;opacity:0;pointer-events:none;transition:opacity 0.28s ease;}
#trans-overlay.on{opacity:1;pointer-events:all;}

/* Body region hover */
.body-region:hover{filter:brightness(1.1);cursor:pointer;}
.body-region.active{fill:#66bb6a!important;stroke:#43a047!important;}
'''

# ──────────────────────────────────────────
# SVG 아이콘 모음
# ──────────────────────────────────────────

INSTA_SIGNAL = '<svg class="sb-signal" width="17" height="12" viewBox="0 0 17 12" fill="currentColor"><rect x="0" y="7" width="3" height="5" rx="0.5"/><rect x="4.5" y="5" width="3" height="7" rx="0.5"/><rect x="9" y="2.5" width="3" height="9.5" rx="0.5"/><rect x="13.5" y="0" width="3" height="12" rx="0.5"/></svg>'
INSTA_WIFI = '<svg class="sb-wifi" width="16" height="12" viewBox="0 0 16 12" fill="currentColor"><path d="M8 9.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/><path d="M8 6.2C9.7 6.2 11.2 6.9 12.3 8l1.3-1.3C12.1 5.2 10.1 4.2 8 4.2S3.9 5.2 2.4 6.7L3.7 8C4.8 6.9 6.3 6.2 8 6.2z" opacity=".7"/><path d="M8 2.5C11 2.5 13.7 3.8 15.5 5.9L16.8 4.6C14.6 2.2 11.5.5 8 .5S1.4 2.2-.8 4.6L.5 5.9C2.3 3.8 5 2.5 8 2.5z" opacity=".4"/></svg>'
INSTA_BATTERY = '<svg class="sb-battery" width="25" height="12" viewBox="0 0 25 12" fill="currentColor"><rect x="0" y="1" width="21" height="10" rx="2.5" stroke="currentColor" stroke-width="1.2" fill="none"/><rect x="1.5" y="2.5" width="16" height="7" rx="1.5" fill="currentColor" opacity="0.9"/><path d="M22 4.2v3.6a1.8 1.8 0 0 0 0-3.6z" fill="currentColor" opacity="0.6"/></svg>'

INSTA_WORDMARK = '<svg class="insta-wordmark" viewBox="0 0 235 55" fill="#000" xmlns="http://www.w3.org/2000/svg"><path d="M14.6 0C6.5 0 0 6.5 0 14.6v25.8C0 48.5 6.5 55 14.6 55h25.8C48.5 55 55 48.5 55 40.4V14.6C55 6.5 48.5 0 40.4 0H14.6zm0 5h25.8C45.7 5 50 9.3 50 14.6v25.8C50 45.7 45.7 50 40.4 50H14.6C9.3 50 5 45.7 5 40.4V14.6C5 9.3 9.3 5 14.6 5zm27.9 7a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM27.5 13C19.5 13 13 19.5 13 27.5S19.5 42 27.5 42 42 35.5 42 27.5 35.5 13 27.5 13zm0 5a9.5 9.5 0 1 1 0 19 9.5 9.5 0 0 1 0-19z"/><text x="65" y="41" font-family="\'Billabong\',\'Georgia\',serif" font-size="46" fill="#000" letter-spacing="-1">Instagram</text></svg>'
INSTA_HEART = '<button class="ih-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>'
INSTA_SEND = '<button class="ih-icon-btn"><svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8"><path d="M21.99 2L2 10.5l7.5 3.5L13 21.5l3-7.5z"/><line x1="10" y1="14" x2="15.5" y2="8.5"/></svg></button>'

INSTA_VERIFIED = '<svg width="13" height="13" viewBox="0 0 24 24" fill="#0095F6" style="vertical-align:-2px;margin-left:2px"><path d="M9 12l2 2 4-4M7.8 3.2L5.4 5.6H2v3.4L-.4 11.4 2 13.8V17l3.4.4L7.8 20.8l2.2-2.4 2 .8 2-.8 2.2 2.4 2.4-2.4L22 17v-3.2l2.4-2.4-2.4-2.4V5.6h-3.4L16.2 3.2l-2.2 2.4-2-.8-2 .8z"/></svg>'
INSTA_MORE = '<svg viewBox="0 0 24 24" fill="#000" width="24" height="24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>'

FEED_HEART = '<svg viewBox="0 0 24 24" width="24" height="24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="none" stroke="#000" stroke-width="2"/></svg>'
FEED_COMMENT = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#000" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
FEED_SHARE = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#000" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>'
FEED_BOOKMARK = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#000" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>'

BOTNAV_HOME = '<svg viewBox="0 0 24 24" width="26" height="26" fill="#000"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>'
BOTNAV_SEARCH = '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#000" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
BOTNAV_REELS = '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#000" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="3"/><path d="M8 2v20M2 12h6M16 8l-4 4 4 4"/></svg>'
BOTNAV_SHOP = '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#000" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>'


# ──────────────────────────────────────────
# 공통 UI 빌더 헬퍼 함수
# ──────────────────────────────────────────

def build_kiam_header(header_cfg, prev_step):
    """KIAM 채팅 헤더 생성"""
    badges = ''.join([
        f'<span class="kh-badge {b["type"]}"><span class="bdot"></span>{b["label"]}</span>'
        for b in header_cfg.get('badges', [])
    ])
    avatar_color = header_cfg.get('avatar_color', 'var(--ocean),var(--ocean2)')
    return f'''<div class="kiam-header">
      <div class="kh-back" onclick="jumpToStep({prev_step})">←</div>
      <div class="kh-avatar-char" style="background:linear-gradient(135deg,{avatar_color})">{header_cfg.get('avatar_emoji', '🏥')}</div>
      <div class="kh-center">
        <div class="kh-title">{header_cfg['title']}</div>
        <div class="kh-sub">{header_cfg['sub']}</div>
        <div class="kh-badges">{badges}</div>
      </div>
      <span class="kh-info">ℹ</span>
    </div>'''


def build_green_bar(bar_cfg, default_style='background:linear-gradient(90deg,#0A2B15,#143D1E)',
                    default_main_color='#22C55E'):
    """그린 인포 바 생성"""
    style = bar_cfg.get('style', default_style)
    main_color = bar_cfg.get('main_color', default_main_color)
    return f'''<div class="green-bar" style="{style}">
      <span class="gb-icon">{bar_cfg['emoji']}</span>
      <div>
        <div class="gb-main" style="color:{main_color}">{bar_cfg['main']}</div>
        <div class="gb-sub">{bar_cfg['sub']}</div>
      </div>
    </div>'''


def build_body_svg(regions):
    """SVG 신체 부위 맵 생성 (의료 전용)"""
    shapes = []
    for r in regions:
        cls = f'class="body-region" data-region="{r["region"]}" data-code="{r["code"]}"'
        if r['shape'] == 'rect':
            shapes.append(f'<rect x="{r["x"]}" y="{r["y"]}" width="{r["w"]}" height="{r["h"]}" rx="{r["rx"]}" {cls} fill="#e8f5e9" stroke="#a5d6a7" stroke-width="1.5" style="cursor:pointer"/>')
        elif r['shape'] == 'ellipse':
            shapes.append(f'<ellipse cx="{r["cx"]}" cy="{r["cy"]}" rx="{r["rx"]}" ry="{r["ry"]}" {cls} fill="#e8f5e9" stroke="#a5d6a7" stroke-width="1.5" style="cursor:pointer"/>')
    shapes_str = '\n'.join(shapes)
    return f'<div style="text-align:center;padding:8px 0"><svg width="200" height="360" viewBox="0 0 220 400" id="bodySvg">\n{shapes_str}\n</svg></div>'


def build_chat_footer():
    """채팅 하단 토글 + 입력창 + 탭바"""
    return '''<div class="ai-toggle-bar"><div class="toggle-pill"></div><div class="ai-toggle-lbl"><span style="font-size:15px">🤖</span>AI가 자동으로 응답합니다</div></div>
    <div class="chat-input-bar"><input class="chat-inp" placeholder="메시지를 입력하세요..." readonly /><button class="chat-send">➤</button></div>
    <div class="bottom-nav"><button class="bn-tab active">전체대화</button><button class="bn-tab">AI대화</button><button class="bn-tab">HI대화</button></div>'''


def build_ai_row(emoji, text, time="14:02"):
    """AI 말풍선 한 줄"""
    return f'<div class="row-ai"><div class="ai-av">{emoji}</div><div><div class="ai-bubble">{text}</div><div class="ai-time">{time}</div></div></div>'


def build_user_row(text, time="14:05"):
    """사용자 말풍선 한 줄"""
    return f'<div class="row-user"><div class="user-av">👤</div><div><div class="user-bubble">{text}</div><div class="user-time">{time}</div></div></div>'


def build_choices(buttons):
    """선택 버튼 그룹"""
    btns = ''.join([f'<button class="choice-btn">{b}</button>' for b in buttons])
    return f'<div class="choice-group">{btns}</div>'


# ──────────────────────────────────────────
# 화면별 렌더링 함수
# ──────────────────────────────────────────

def render_screen_insta(cfg):
    """화면1: 인스타그램 광고"""
    ad = cfg['screen_insta']
    m = ad['metrics']
    vis = ad['ad_visual']
    stories = ad['stories']

    stories_html = ''.join([
        f'<div class="story-item"><div class="story-ring"><div class="story-av" style="background:linear-gradient(135deg,{s["gradient"]})">{s["emoji"]}</div></div><div class="story-lbl">{s["label"]}</div></div>'
        for s in stories
    ])

    copy_main = vis['copy_main'].replace('\n', '<br>')
    copy_highlight = vis.get('copy_highlight', '')
    if copy_highlight:
        copy_main = copy_main.replace(copy_highlight, f'<em>{copy_highlight}</em>')

    company = cfg['company']
    insta_username = ad.get('insta_username', company['insta_username'])

    return f'''  <div id="scr-insta" class="screen active">
    <div class="insta-statusbar"><span class="sb-time">9:41</span><div class="sb-icons">{INSTA_SIGNAL}{INSTA_WIFI}{INSTA_BATTERY}</div></div>
    <div class="insta-header">{INSTA_WORDMARK}<div class="insta-hdr-icons">{INSTA_HEART}{INSTA_SEND}</div></div>
    <div class="insta-stories">{stories_html}</div>
    <div class="feed-post-hdr"><div class="feed-av-wrap"><div class="feed-av">{company['avatar_emoji']}</div></div><div class="feed-name-wrap"><div class="feed-name">{insta_username} {INSTA_VERIFIED}</div><div class="feed-location">{company['insta_location']}</div></div><button class="feed-more-btn">{INSTA_MORE}</button></div>
    <div class="ad-visual"><div style="position:absolute;inset:0;background:{vis['bg_gradient']};z-index:0"></div><div style="position:absolute;top:15%;left:10%;font-size:40px;z-index:1">{vis['deco_top_left']}</div><div style="position:absolute;bottom:20%;right:8%;font-size:32px;z-index:1">{vis['deco_bottom_right']}</div><div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,22,40,0.50),transparent 38%,transparent 55%,rgba(10,22,40,0.70));pointer-events:none;z-index:1"></div><div class="ad-sponsored-bar" style="z-index:2"><span class="ad-sp-text">{insta_username}</span><span class="ad-sp-badge">스폰서</span></div><div class="ad-copy-wrap" style="z-index:2"><div class="ad-copy-main">{copy_main}</div><div class="ad-copy-sub">{vis['copy_sub']}</div><div class="ad-copy-badge">{vis['copy_badge']}</div></div></div>
    <div class="ad-bottom"><div class="ad-action-row"><button class="ad-icon-btn">{FEED_HEART}</button><button class="ad-icon-btn">{FEED_COMMENT}</button><button class="ad-icon-btn">{FEED_SHARE}</button><button class="ad-icon-btn ad-bookmark-btn">{FEED_BOOKMARK}</button></div><div class="ad-likes">좋아요 <b>{m['likes']:,}개</b></div><div class="ad-caption"><b>{insta_username}</b> {ad['caption_text']}</div><div class="ad-comments-link">댓글 {m['comments']:,}개 모두 보기</div><div class="ad-timestamp">3시간 전 · ↗ 공유 {m['shares']:,}회</div><div class="i-pill">{ad['insight_pill']}</div><button class="insta-cta" onclick="jumpToStep(2)">{ad['cta_text']}</button></div>
    <div class="insta-bottom-nav"><button class="ibn-btn">{BOTNAV_HOME}</button><button class="ibn-btn">{BOTNAV_SEARCH}</button><button class="ibn-btn">{BOTNAV_REELS}</button><button class="ibn-btn">{BOTNAV_SHOP}</button><button class="ibn-btn"><div class="ibn-profile"></div></button></div>
  </div>'''


def render_screen_chat(cfg):
    """화면2: AI 진단 채팅"""
    sc = cfg['screen_chat']
    header = build_kiam_header(sc['header'], 1)
    green_bar = build_green_bar(sc['green_bar'])
    tabs = '''<div class="kiam-tabs"><div class="kt active">전체대화</div><div class="kt">AI대화</div><div class="kt">HI대화</div></div>'''
    date_div = '<div class="date-div">2026-05-04</div>'
    ai_msg = build_ai_row('🤖', sc['ai_first_message'].replace('\n', '<br>'))
    body_svg = build_body_svg(sc['body_regions'])
    msgs_content = f'{date_div}{ai_msg}{body_svg}'
    footer = build_chat_footer()

    return f'''  <div id="scr-chat" class="screen">
    {header}
    {green_bar}
    {tabs}
    <div class="kiam-msgs" id="chat-msgs">{msgs_content}</div>
    {footer}
  </div>'''


def render_screen_trust(cfg):
    """화면3: 신뢰 형성"""
    sc = cfg['screen_trust']
    header = build_kiam_header(sc['header'], 2)
    green_bar = build_green_bar(sc['green_bar'])
    date_div = f'<div class="date-div">{sc["date_label"]}</div>'
    avatar = sc.get('avatar_emoji', '🌿')

    messages_html = ''.join([build_ai_row(avatar, msg.replace('\n', '<br>'), "09:15" if i == 0 else "09:16") for i, msg in enumerate(sc['ai_messages'])])

    cards_html = ''
    for card in sc.get('content_cards', []):
        cards_html += f'<div class="widget-card" style="margin:0 0 0 39px;width:auto"><div class="widget-card-img" style="background:{card["icon_bg"]}">{card["icon_emoji"]}</div><div class="widget-card-body"><div class="widget-card-title">{card["title"]}</div><div class="widget-card-desc">{card["desc"]}</div></div><button class="widget-card-btn">{card["btn_text"]}</button></div>'

    pill = f'<div class="i-pill">{sc["insight_pill"]}</div>'
    footer = build_chat_footer()

    return f'''  <div id="scr-trust" class="screen">
    {header}
    {green_bar}
    <div class="kiam-tabs"><div class="kt active">전체대화</div><div class="kt">AI대화</div><div class="kt">HI대화</div></div>
    <div class="kiam-msgs">{date_div}
    {messages_html}
    {cards_html}
    {pill}
    </div>
    {footer}
  </div>'''


def render_screen_book(cfg):
    """화면4: 예약 전환"""
    sc = cfg['screen_book']
    header = build_kiam_header(sc['header'], 3)
    intro_emoji = sc.get('avatar_emoji_intro', '💉')
    date_div = f'<div class="date-div">{sc["date_label"]}</div>'
    intro_msg = build_ai_row(intro_emoji, sc['intro_message'].replace('\n', '<br>'), "11:22")

    treatment = sc['treatment']
    treatment_html = f'<div class="booking-card"><div class="bc-title" style="display:flex;align-items:center;gap:8px"><span style="font-size:22px">{treatment["emoji"]}</span> {treatment["name"]}</div><div style="font-size:12px;color:#6B7280;margin:4px 0 8px;line-height:1.6">{treatment["desc"]}<br><span style="color:#2E7D32;font-weight:700">{treatment["effectiveness"]}</span> ({cfg["company"]["name"]} 자체 데이터)</div><div style="background:#e8f5e9;border-radius:8px;padding:10px;font-size:11px;color:#1B5E20;margin-bottom:10px">📌 <b>내게 맞는 이유:</b> {treatment["why_me"]}</div></div>'

    booking = sc['booking']
    slots_html = ''.join([f'<button class="booking-slot" onclick="selectSlot(this)">{s}</button>' for s in booking['slots']])
    booking_html = f'<div class="booking-card" id="bookingWidget"><div class="bc-title">📅 진료 예약하기</div><div style="font-size:12px;color:#6B7280;margin-bottom:8px">{booking["date_label"]}</div><div class="booking-slots" id="bookingSlots">{slots_html}</div><button class="booking-confirm" id="bookingConfirmBtn" onclick="confirmBooking()">{booking["confirm_btn"]}</button><div id="bookingResult" style="display:none;text-align:center;padding:12px;background:#e8f5e9;border-radius:10px;margin-top:10px"><div style="font-size:18px;margin-bottom:4px">✅</div><div style="font-size:14px;font-weight:700;color:#2E7D32">예약이 확정되었습니다!</div><div style="font-size:12px;color:#6B7280;margin-top:2px">{booking["success_date"]} <span id="bookedSlot"></span> · {cfg["company"]["name"]}</div></div></div>'

    manager = sc['manager']
    manager_profile = f'<div class="profile-transition">────────────</div><div class="manager-profile"><div class="manager-avatar">{manager["emoji"]}</div><div><div class="manager-name">{manager["name"]}</div><div class="manager-role">{manager["role"]}</div></div></div>'

    manager_msgs = ''.join([build_ai_row(manager['emoji'], msg.replace('\n', '<br>'), "11:30") for msg in sc['manager_messages']])

    pill = f'<div class="i-pill">{sc["insight_pill"]}</div>'
    footer = build_chat_footer()

    return f'''  <div id="scr-book" class="screen">
    {header}
    <div class="kiam-msgs">{date_div}{intro_msg}{treatment_html}{booking_html}{manager_profile}{manager_msgs}{pill}</div>
    {footer}
  </div>'''


def render_screen_post(cfg):
    """화면5: 사후 관리 (노쇼 방지)"""
    sc = cfg['screen_post']
    header = build_kiam_header(sc['header'], 4)
    green_bar = build_green_bar(sc['green_bar'],
        default_style='background:linear-gradient(90deg,#0A2B15,#143D1E)',
        default_main_color='#22C55E')
    date_div = f'<div class="date-div">{sc["date_label"]}</div>'
    avatar = sc.get('avatar_emoji', '📋')

    msgs_html = ''
    msgs = sc['messages']
    times = ["14:30", "14:31"]
    for i, msg in enumerate(msgs):
        t = times[i] if i < len(times) else "14:31"
        msgs_html += build_ai_row(avatar, msg.replace('\n', '<br>'), t)

    cards_html = ''
    for card in sc.get('promise_cards', []):
        cards_html += f'<div class="promise-card"><div class="pc-icon">{card["emoji"]}</div><div><div class="pc-title">{card["title"]}</div><div class="pc-desc">{card["desc"]}</div></div></div>'

    d1 = sc.get('d1_reminder', {})
    d1_html = ''
    if d1:
        d1_label = d1.get('date_label', '··· D-1 ···')
        d1_avatar = d1.get('avatar_emoji', '📋')
        d1_msg = d1.get('message', '').replace('\n', '<br>')
        d1_html = f'<div class="profile-transition">{d1_label}</div>{build_ai_row(d1_avatar, d1_msg, "09:00")}'

    pill = f'<div class="i-pill">{sc["insight_pill"]}</div>'

    return f'''  <div id="scr-post" class="screen">
    {header}
    {green_bar}
    <div class="kiam-msgs">{date_div}{msgs_html}{cards_html}{d1_html}{pill}</div>
    {build_chat_footer()}
  </div>'''


def render_screen_after(cfg):
    """화면6: 진료 후 회복 관리"""
    sc = cfg['screen_after']
    header = build_kiam_header(sc['header'], 5)
    date_div = f'<div class="date-div">{sc["date_label"]}</div>'
    avatar = sc.get('avatar_emoji', '💚')

    msgs = sc['messages']
    times = ["09:30", "09:31", "09:35"]
    msgs_html = ''
    for i, msg in enumerate(msgs):
        t = times[i] if i < len(times) else "09:35"
        msgs_html += build_ai_row(avatar, msg.replace('\n', '<br>'), t)

    recovery = sc.get('recovery_card', {})
    recovery_html = ''
    if recovery:
        recovery_html = f'<div class="promise-card"><div class="pc-icon">{recovery["emoji"]}</div><div><div class="pc-title">{recovery["title"]}</div><div class="pc-desc">{recovery["desc"]}</div></div></div>'

    choices_html = build_choices(sc.get('choice_buttons', []))
    user_reply = build_user_row(sc.get('user_reply', ''), "09:35")
    pill = f'<div class="i-pill">{sc["insight_pill"]}</div>'

    return f'''  <div id="scr-after" class="screen">
    {header}
    <div class="kiam-msgs">{date_div}{msgs_html}{recovery_html}{choices_html}{user_reply}{pill}</div>
    {build_chat_footer()}
  </div>'''


def render_screen_dash(cfg):
    """화면7: KPI 대시보드 (다크 화면)"""
    sc = cfg['screen_dash']

    kpi_html = ''.join([
        f'<div class="kpi-card"><div class="kpi-val {k["color"]}">{k["val"]}</div><div class="kpi-label">{k["label"]}</div></div>'
        for k in sc['kpi_values']
    ])

    headers_html = ''.join([f'<th>{h}</th>' for h in sc['compare_headers']])
    rows_html = ''.join([
        f'<tr><td>{r["metric"]}</td><td>{r["before"]}</td><td class="highlight">{r["after"]}</td><td class="highlight">{r["change"]}</td></tr>'
        for r in sc['compare_rows']
    ])

    return f'''  <div id="scr-dash" class="screen scr-dark">
    <div class="res-lbl">{sc["section_label"]}</div>
    <div class="dark-card" style="text-align:center"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["kpi_title"]}</div><div class="kpi-grid">{kpi_html}</div></div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["funnel_title"]}</div><div id="funnelDash"></div></div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["trust_title"]}</div><div id="trustDash"></div></div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["compare_title"]}</div><table class="compare-table"><thead><tr>{headers_html}</tr></thead><tbody>{rows_html}</tbody></table></div>
  </div>'''


def render_screen_noshow(cfg):
    """화면8: 노쇼 방지 알고리즘 (다크 화면)"""
    sc = cfg['screen_noshow']

    algo_headers = ''.join([f'<th>{h}</th>' for h in sc['algorithm_headers']])
    algo_rows = ''.join([
        f'<tr><td>{r["factor"]}</td><td class="highlight">{r["weight"]}</td><td>{r["method"]}</td></tr>'
        for r in sc['algorithm_rows']
    ])

    risk_headers = ''.join([f'<th>{h}</th>' for h in sc['risk_headers']])
    risk_rows = ''.join([
        f'<tr><td style="color:{r["zone_color"]};font-weight:700">{r["zone"]}</td><td>{r["score"]}</td><td>{r["alert"]}</td><td>{r["effect"]}</td></tr>'
        for r in sc['risk_rows']
    ])

    return f'''  <div id="scr-noshow" class="screen scr-dark">
    <div class="res-lbl">{sc["section_label"]}</div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["algorithm_title"]}</div><table class="compare-table"><thead><tr>{algo_headers}</tr></thead><tbody>{algo_rows}</tbody></table></div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["risk_tiers_title"]}</div><table class="compare-table"><thead><tr>{risk_headers}</tr></thead><tbody>{risk_rows}</tbody></table></div>
    <div class="dark-card"><div class="dark-card-title" style="font-size:15px;color:var(--gold)">{sc["current_risks_title"]}</div><div id="noshowDash"></div></div>
  </div>'''


def render_screen_summary(cfg):
    """화면9: ROI 요약 (다크 화면)"""
    sc = cfg['screen_summary']

    items_html = ''.join([
        f'<div class="brief-item"><div class="bi-icon">{b["emoji"]}</div><div class="bi-title">{b["title"]}</div><div class="bi-desc">{b["desc"]}</div></div>'
        for b in sc['brief_items']
    ])

    roi = sc['roi']

    return f'''  <div id="scr-summary" class="screen scr-dark">
    <div class="res-lbl">{sc["section_label"]}</div>
    <div class="briefing-card"><h2>{sc["title"]}</h2><div class="subtitle">{sc["subtitle"]}</div><div class="briefing-grid">{items_html}</div><div class="brief-roi"><div class="roi-label">{roi["label"]}</div><div class="roi-val">{roi["value"]}</div><div class="roi-desc">{roi["desc"]}</div></div><button class="brief-cta" onclick="jumpToStep(1)">{sc["cta_text"]}</button></div>
  </div>'''


# ──────────────────────────────────────────
# 공통 JavaScript (퍼널 로직)
# ──────────────────────────────────────────

def build_js(cfg):
    """퍼널 JS 생성"""
    steps = cfg['funnel_steps']
    step_count = len(steps)

    # screenMap
    map_lines = ', '.join([f'{s["id"]}:\'{s["screen_id"]}\'' for s in steps])

    # 퍼널 데이터 (대시보드용)
    funnel_items = cfg['screen_dash']['funnel_data']
    max_val = max(f['val'] for f in funnel_items)
    funnel_js = ','.join([f'{{label:\'{f["label"]}\',val:{f["val"]},color:\'{f["color"]}\'}}' for f in funnel_items])

    # 신뢰 점수
    trust_items = cfg['screen_dash']['trust_scores']
    trust_js = ','.join([f'{{name:\'{t["name"]}\',pct:{t["pct"]}}}' for t in trust_items])

    # 노쇼 위험도
    noshow_items = cfg['screen_noshow']['current_risks']
    noshow_js = ','.join([f'{{name:\'{n["name"]}\',risk:{n["risk"]},color:\'{n["color"]}\'}}' for n in noshow_items])

    # 진단 응답 (chat)
    chat_cfg = cfg['screen_chat']
    quick_replies = chat_cfg.get('choice_quick_replies', [])
    quick_btns = ''.join([f'<button class="choice-btn">{r}</button>' for r in quick_replies])
    diag_msg = chat_cfg.get('diagnosis_complete_message', '진단 완료').replace('\n', '\\n')

    js = f'''<script>
const nowTime=()=>{{const d=new Date();return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0')}}
const visEl=el=>setTimeout(()=>el.classList.add('vis'),40)
function scrollBottom(id){{const el=document.getElementById(id);if(!el)return;setTimeout(()=>{{el.scrollTop=el.scrollHeight+9999}},80)}}function revealChatMsgs(container){{if(!container)return;const all=container.querySelectorAll('.row-ai,.row-user,.choice-group,.widget-card,.booking-card,.promise-card,.profile-transition,.manager-profile,.i-pill,.body-region');all.forEach(function(el){{el.classList.remove('vis');}});var delay=300;all.forEach(function(el,i){{if(el.classList.contains('row-ai')){{setTimeout(function(){{var typing=document.createElement('div');typing.className='typing-row vis';typing.innerHTML='<div class="ai-av">🤖</div><div class="typing-dots"><div class="td"></div><div class="td"></div><div class="td"></div></div>';el.before(typing);container.scrollTop=container.scrollHeight+9999;setTimeout(function(){{typing.remove();el.classList.add('vis');container.scrollTop=container.scrollHeight+9999;}},1200);}},delay);delay+=1800;}}else if(el.classList.contains('row-user')){{setTimeout(function(){{el.classList.add('vis');container.scrollTop=container.scrollHeight+9999;}},delay);delay+=700;}}else{{setTimeout(function(){{el.classList.add('vis');container.scrollTop=container.scrollHeight+9999;}},delay);delay+=350;}}}});}}
let currentStep=1
const totalSteps={step_count}
const screenMap={{{map_lines}}}
function setFunnel(n){{for(let i=1;i<=totalSteps;i++){{const el=document.getElementById('fs'+i);if(!el)continue;el.classList.remove('active','done');if(i<n)el.classList.add('done');else if(i===n)el.classList.add('active')}}}}
function goScreen(id){{return new Promise(res=>{{const ov=document.getElementById('trans-overlay');ov.classList.add('on');setTimeout(()=>{{document.querySelectorAll('.screen').forEach(s=>s.classList.remove('active'));const s=document.getElementById(id);s.classList.add('active');ov.classList.remove('on');const m=s.querySelector('.kiam-msgs');if(m)revealChatMsgs(m);res()}},280)}})}}
async function jumpToStep(step){{currentStep=step;setFunnel(step);await goScreen(screenMap[step]);if(step===7)setTimeout(buildFunnelDash,600);if(step===8)setTimeout(buildNoShowDash,600)}}
function nextStep(){{if(currentStep<totalSteps)jumpToStep(currentStep+1)}}
function prevStep(){{if(currentStep>1)jumpToStep(currentStep-1)}}
let selectedRegion=null
document.addEventListener('click',function(e){{const region=e.target.closest('.body-region');if(!region)return;const all=document.querySelectorAll('.body-region');for(let i=0;i<all.length;i++){{all[i].classList.remove('active');all[i].setAttribute('fill','#e8f5e9')}}region.classList.add('active');region.setAttribute('fill','#66bb6a');selectedRegion={{name:region.getAttribute('data-region'),code:region.getAttribute('data-code')}};const msgs=document.getElementById('chat-msgs');const ur=document.createElement('div');ur.className='row-user vis';ur.innerHTML='<div><div class="user-bubble">'+selectedRegion.name+'이/가 아파요</div><div class="user-time">'+nowTime()+'</div></div><div class="user-av">👤</div>';msgs.appendChild(ur);scrollBottom('chat-msgs');setTimeout(()=>{{const typing=document.createElement('div');typing.className='typing-row vis';typing.innerHTML='<div class="ai-av">🤖</div><div class="typing-dots"><div class="td"></div><div class="td"></div><div class="td"></div></div>';msgs.appendChild(typing);scrollBottom('chat-msgs');setTimeout(()=>{{typing.remove();const aiRow=document.createElement('div');aiRow.className='row-ai vis';aiRow.innerHTML='<div class="ai-av">🤖</div><div><div class="ai-bubble"><b>'+selectedRegion.name+'</b> 통증을 확인했습니다.<br><br>추가로 알려주세요:<br>• 통증 강도 (1~10)<br>• 통증 지속 기간<br>• 악화되는 자세</div><div class="ai-time">'+nowTime()+'</div></div>';msgs.appendChild(aiRow);scrollBottom('chat-msgs');const choiceGrp=document.createElement('div');choiceGrp.className='choice-group';choiceGrp.innerHTML='{quick_btns}';msgs.appendChild(choiceGrp);setTimeout(()=>choiceGrp.classList.add('vis'),40);scrollBottom('chat-msgs');choiceGrp.querySelectorAll('.choice-btn').forEach(b=>{{b.onclick=()=>{{choiceGrp.querySelectorAll('.choice-btn').forEach(cb=>{{cb.disabled=true;cb.classList.add('sel')}});appendUserQuick(b.textContent)}}}});const pill=document.createElement('div');pill.className='i-pill vis';pill.innerHTML='원챗 인사이트: 신체 부위 직접 선택 → 촉감 있는 진단 경험. 클릭 후 대화 참여율 94%.';msgs.appendChild(pill);scrollBottom('chat-msgs')}},1400)}},400)}})
function appendUserQuick(text){{const msgs=document.getElementById('chat-msgs');const ur=document.createElement('div');ur.className='row-user vis';ur.innerHTML='<div><div class="user-bubble">'+text+'</div><div class="user-time">'+nowTime()+'</div></div><div class="user-av">👤</div>';msgs.appendChild(ur);scrollBottom('chat-msgs');setTimeout(()=>{{const typing=document.createElement('div');typing.className='typing-row vis';typing.innerHTML='<div class="ai-av">🤖</div><div class="typing-dots"><div class="td"></div><div class="td"></div><div class="td"></div></div>';msgs.appendChild(typing);scrollBottom('chat-msgs');setTimeout(()=>{{typing.remove();const aiRow=document.createElement('div');aiRow.className='row-ai vis';aiRow.innerHTML='<div class="ai-av">🤖</div><div><div class="ai-bubble">{diag_msg}</div><div class="ai-time">'+nowTime()+'</div></div>';msgs.appendChild(aiRow);scrollBottom('chat-msgs')}},1200)}},400)}}
function selectSlot(el){{document.querySelectorAll('#bookingSlots .booking-slot').forEach(b=>b.classList.remove('sel'));el.classList.add('sel')}}
function confirmBooking(){{const sel=document.querySelector('#bookingSlots .booking-slot.sel');if(!sel){{alert('시간을 선택해주세요.');return}}document.getElementById('bookedSlot').textContent=sel.textContent;document.getElementById('bookingResult').style.display='block';document.getElementById('bookingConfirmBtn').textContent='예약 완료됨';document.getElementById('bookingConfirmBtn').disabled=true}}
function buildFunnelDash(){{const data=[{funnel_js}];const max={max_val};let html='';data.forEach(d=>{{const pct=((d.val/max)*100).toFixed(0);html+='<div class="funnel-step"><span style="font-size:11px;color:rgba(255,255,255,0.4);min-width:60px">'+d.label+'</span><div class="funnel-step-bar" style="width:'+pct+'%;background:'+d.color+'">'+d.val+'</div><span class="funnel-step-pct">'+pct+'%</span></div>'}});document.getElementById('funnelDash').innerHTML=html;const trusts=[{trust_js}];let thtml='';trusts.forEach(t=>{{thtml+='<div class="trust-item"><span class="trust-name">'+t.name+'</span><div class="trust-bar-track"><div class="trust-bar-fill" style="width:'+t.pct+'%"></div></div><span class="trust-val">'+t.pct+'%</span></div>'}});document.getElementById('trustDash').innerHTML=thtml}}
function buildNoShowDash(){{const patients=[{noshow_js}];let html='';patients.forEach(p=>{{html+='<div class="trust-item"><span class="trust-name">'+p.name+'</span><div class="trust-bar-track"><div class="trust-bar-fill" style="width:'+p.risk+'%;background:linear-gradient(90deg,'+p.color+','+p.color+')"></div></div><span class="trust-val" style="color:'+p.color+'">'+p.risk+'%</span></div>'}});document.getElementById('noshowDash').innerHTML=html}}
document.addEventListener('keydown',function(e){{if(e.key==='ArrowRight'||e.key==='ArrowDown'){{e.preventDefault();nextStep()}}else if(e.key==='ArrowLeft'||e.key==='ArrowUp'){{e.preventDefault();prevStep()}}}})
setFunnel(1)
</script>
</body>
</html>'''
    return js


# ──────────────────────────────────────────
# 퍼널 바 + 인비테이션 배너 생성
# ──────────────────────────────────────────

def build_funnel_bar(steps):
    items = []
    for i, s in enumerate(steps):
        if i > 0:
            items.append('<div class="fa">›</div>')
        items.append(f'<div class="fs active" id="fs{s["id"]}" onclick="jumpToStep({s["id"]})" style="cursor:pointer"><div class="fs-dot">{s["id"]}</div><div class="fs-lbl">{s["label"]}</div></div>')
    return ''.join(items)


def build_inv_banner(cfg):
    company = cfg['company']
    brand = f'OneChat × {company["brand_name"]}'
    tag = company.get('banner_tag', '')
    badge = company.get('banner_badge', '')
    return f'''<div id="inv-banner">
    <span class="ib-brand">{brand}</span>
    <span class="ib-tag">{tag}</span>
    <span class="ib-badge">{badge}</span>
  </div>'''


def build_header_pre(cfg):
    steps = cfg['funnel_steps']
    banner = build_inv_banner(cfg)
    funnel = build_funnel_bar(steps)
    return f'''<div id="trans-overlay"></div>
<div id="app-shell">
  {banner}
  <div id="funnel-bar">
    {funnel}
  </div>
'''


# ──────────────────────────────────────────
# 메인 빌드 함수
# ──────────────────────────────────────────

def build(config_path, out_path):
    with open(config_path, 'r', encoding='utf-8') as f:
        cfg = json.load(f)

    company = cfg['company']
    page_title = company.get('page_title', f'원챗(OneChat) × {company["name"]} — 고객사 브리핑')
    lang = cfg.get('_meta', {}).get('lang', 'ko')

    parts = []

    # 1. DOCTYPE + head + CSS
    parts.append(f'<!DOCTYPE html>\n<html lang="{lang}">\n<head>\n<meta charset="UTF-8">\n<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">\n<title>{page_title}</title>\n<style>\n')
    parts.append(CSS_COMMON)
    parts.append('\n</style>\n</head>\n<body>\n')

    # 2. 헤더 (배너 + 퍼널 바)
    parts.append(build_header_pre(cfg))

    # 3. 화면 렌더링
    key_map = {'scr-insta': 'screen_insta', 'scr-chat': 'screen_chat', 'scr-trust': 'screen_trust',
               'scr-book': 'screen_book', 'scr-post': 'screen_post', 'scr-after': 'screen_after',
               'scr-dash': 'screen_dash', 'scr-noshow': 'screen_noshow', 'scr-summary': 'screen_summary'}

    renderers = {
        'screen_insta': render_screen_insta,
        'screen_chat': render_screen_chat,
        'screen_trust': render_screen_trust,
        'screen_book': render_screen_book,
        'screen_post': render_screen_post,
        'screen_after': render_screen_after,
        'screen_dash': render_screen_dash,
        'screen_noshow': render_screen_noshow,
        'screen_summary': render_screen_summary,
    }

    for step in cfg['funnel_steps']:
        screen_key = key_map.get(step['screen_id'], f'screen_{step["screen_id"].replace("scr-", "")}')
        renderer = renderers.get(screen_key)
        if renderer:
            parts.append('\n' + renderer(cfg))

    # 4. 푸터 (app-shell 닫기)
    parts.append('\n</div><!-- /app-shell -->\n')

    # 5. JS
    parts.append('\n' + build_js(cfg))

    full_html = ''.join(parts)

    os.makedirs(os.path.dirname(out_path) or '.', exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(full_html)

    size = os.path.getsize(out_path)
    print(f"SUCCESS: Wrote {size:,} bytes to {out_path}")
    return out_path


# ──────────────────────────────────────────
# CLI 진입점
# ──────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(
        description='OneChat 범용 마케팅 퍼널 목업 HTML 빌더',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog='''예시:
  python3 build.py --config config/sample1_cheongdam.json
  python3 build.py --config config/sample2_hakwon.json --out output/hakwon01.html

구조:
  onechat-template/
  ├── build.py          ← 템플릿 엔진 (수정 불필요)
  ├── config/           ← 시나리오별 JSON 설정 파일
  │   ├── sample1_cheongdam.json
  │   └── sample2_xxx.json (향후 추가)
  └── output/           ← 생성된 HTML 파일
      └── sample1.html''')

    parser.add_argument('--config', required=True, help='JSON 설정 파일 경로')
    parser.add_argument('--out', default=None, help='출력 HTML 경로 (기본: output/<sample_id>.html)')

    args = parser.parse_args()

    if not os.path.exists(args.config):
        print(f'ERROR: 설정 파일을 찾을 수 없습니다: {args.config}', file=sys.stderr)
        sys.exit(1)

    if args.out is None:
        with open(args.config, 'r', encoding='utf-8') as f:
            cfg_temp = json.load(f)
        sample_id = cfg_temp.get('_meta', {}).get('sample_id', 'output')
        out_name = f'{sample_id}.html'
        script_dir = os.path.dirname(os.path.abspath(__file__))
        args.out = os.path.join(script_dir, 'output', out_name)

    build(args.config, args.out)


if __name__ == '__main__':
    main()