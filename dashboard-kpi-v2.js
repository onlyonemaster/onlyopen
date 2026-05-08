// Version 2.0 - Total gap calculation (big leg + small total)
// Last updated: 2026-03-04 10:10
// ============================================
// 대시보드 KPI 로드 (v2.0 - 대실적+소실적 합산)
// ============================================
console.log('[Dashboard KPI] Script loaded - v2.0');

async function loadDashboardKPI(memberId) {
    console.log('[Dashboard KPI] Loading KPI for member:', memberId);
    try {
        const API_URL = window.API_BASE_URL || '/api';
        const token = localStorage.getItem('session_token');
    const response = await fetch(`${API_URL}/dashboard/${memberId}`, { headers: { 'Authorization': 'Bearer ' + token } });
        const result = await response.json();
        
        if (!result.success) {
            console.error('[Dashboard] Failed to load KPI:', result.error);
            return;
        }
        
        const data = result.data;
        console.log('[Dashboard] KPI loaded:', data);
        
        // 직급 정의
        const RANK_THRESHOLDS = {
            'NONE': 0,
            'V1': 5000,
            'V2': 30000,
            'V3': 100000,
            'V4': 300000,
            'V5': 1000000,
            'V6': 3000000,
            'V7': 10000000,
            'V8': 30000000
        };
        
        // KPI 카드 업데이트
        const kpiRank = document.getElementById('kpi-rank');
        const kpiBigLeg = document.getElementById('kpi-big-leg');
        const kpiSmallTotal = document.getElementById('kpi-small-total');
        const kpiGap = document.getElementById('kpi-gap');
        const nextRankLabel = document.getElementById('next-rank-label');
        const targetRankLabel = document.getElementById('target-rank-label');
        const balanceRate = document.getElementById('balance-rate');
        
        if (kpiRank) kpiRank.textContent = data.currentRank || 'NONE';
        if (kpiBigLeg) kpiBigLeg.textContent = '$' + (data.bigLeg || 0).toLocaleString();
        if (kpiSmallTotal) kpiSmallTotal.textContent = '$' + (data.smallTotal || 0).toLocaleString();
        
        // 다음 직급 표시
        if (data.nextRank && data.nextRank !== 'MAX') {
            if (nextRankLabel) nextRankLabel.textContent = data.nextRank;
            if (targetRankLabel) targetRankLabel.textContent = data.nextRank;
            
            // 필요 금액 계산 (정확한 계산)
            // 승급 조건: min(대실적, 소실적) ≥ 임계값
            // 따라서 둘 다 임계값 이상이어야 함
            const nextRankThreshold = RANK_THRESHOLDS[data.nextRank];
            const bigLeg = data.bigLeg || 0;
            const smallTotal = data.smallTotal || 0;
            
            // 대실적 부족분
            const bigLegGap = Math.max(0, nextRankThreshold - bigLeg);
            // 소실적 부족분
            const smallTotalGap = Math.max(0, nextRankThreshold - smallTotal);
            // 총 필요금액 = 대실적 부족분 + 소실적 부족분
            const totalGap = bigLegGap + smallTotalGap;
            
            if (kpiGap) kpiGap.textContent = '$' + totalGap.toLocaleString();
            
            console.log(`[Dashboard] Rank calculation:`, {
                currentRank: data.currentRank,
                nextRank: data.nextRank,
                bigLeg: bigLeg,
                smallTotal: smallTotal,
                nextThreshold: nextRankThreshold,
                bigLegGap: bigLegGap,
                smallTotalGap: smallTotalGap,
                totalGap: totalGap
            });
        } else {
            if (nextRankLabel) nextRankLabel.textContent = '최고';
            if (targetRankLabel) targetRankLabel.textContent = data.currentRank;
            if (kpiGap) kpiGap.textContent = '$0';
        }
        
        // 균형률 표시
        if (balanceRate) {
            balanceRate.textContent = (data.balanceRate || 0).toFixed(2) + '%';
        }
        
    } catch (error) {
        console.error('[Dashboard] Error loading KPI:', error);
    }
}

// 전역으로 노출
window.loadDashboardKPI = loadDashboardKPI;
