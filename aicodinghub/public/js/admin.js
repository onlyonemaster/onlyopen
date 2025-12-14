/**
 * Admin Panel JavaScript
 * Common functions for admin pages
 */

// Toast notification system
const Toast = {
    success(message) {
        this.show(message, 'success');
    },
    
    error(message) {
        this.show(message, 'error');
    },
    
    info(message) {
        this.show(message, 'info');
    },
    
    warning(message) {
        this.show(message, 'warning');
    },
    
    show(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            info: '<i class="fas fa-info-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>'
        };
        
        toast.innerHTML = `
            ${icons[type]}
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Loading indicator
const Loading = {
    show() {
        if (!document.getElementById('loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>처리 중...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
    },
    
    hide() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
};

// API request helper
async function apiRequest(url, options = {}) {
    try {
        Loading.show();
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json();
        
        Loading.hide();
        
        if (!data.success) {
            throw new Error(data.message || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        Loading.hide();
        console.error('API Error:', error);
        Toast.error(error.message || 'An error occurred');
        throw error;
    }
}

// Confirm dialog
function confirmAction(message) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'confirm-modal';
        modal.innerHTML = `
            <div class="confirm-modal-content">
                <div class="confirm-modal-header">
                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <h3>확인</h3>
                </div>
                <div class="confirm-modal-body">
                    <p>${message}</p>
                </div>
                <div class="confirm-modal-footer">
                    <button class="btn-cancel">취소</button>
                    <button class="btn-confirm">확인</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
        
        const cleanup = (result) => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
            resolve(result);
        };
        
        modal.querySelector('.btn-cancel').onclick = () => cleanup(false);
        modal.querySelector('.btn-confirm').onclick = () => cleanup(true);
        modal.onclick = (e) => {
            if (e.target === modal) cleanup(false);
        };
    });
}

// Member Management Functions
const MemberAdmin = {
    async viewDetail(memberId) {
        try {
            const data = await apiRequest(`/api/admin/members/detail.php?id=${memberId}`);
            
            if (data.success) {
                showMemberDetailModal(data.data);
            }
        } catch (error) {
            console.error('Failed to load member detail:', error);
        }
    },
    
    async updateMember(memberId, updateData) {
        try {
            const data = await apiRequest('/api/admin/members/update.php', {
                method: 'POST',
                body: JSON.stringify({ member_id: memberId, ...updateData })
            });
            
            if (data.success) {
                Toast.success('회원 정보가 수정되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to update member:', error);
        }
    },
    
    async deleteMember(memberId) {
        const confirmed = await confirmAction('정말 이 회원을 삭제하시겠습니까?<br>삭제된 데이터는 복구할 수 없습니다.');
        
        if (!confirmed) return;
        
        try {
            const data = await apiRequest('/api/admin/members/delete.php', {
                method: 'POST',
                body: JSON.stringify({ member_id: memberId })
            });
            
            if (data.success) {
                Toast.success('회원이 삭제되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to delete member:', error);
        }
    },
    
    async changeStatus(memberId, status) {
        const statusNames = { active: '활성', pending: '대기', inactive: '비활성' };
        const confirmed = await confirmAction(`회원 상태를 "${statusNames[status]}"로 변경하시겠습니까?`);
        
        if (!confirmed) return;
        
        try {
            const data = await apiRequest('/api/admin/members/status.php', {
                method: 'POST',
                body: JSON.stringify({ member_id: memberId, status })
            });
            
            if (data.success) {
                Toast.success('회원 상태가 변경되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to change member status:', error);
        }
    }
};

// Board Management Functions
const BoardAdmin = {
    async updateBoard(boardId, updateData) {
        try {
            const data = await apiRequest('/api/admin/boards/update.php', {
                method: 'POST',
                body: JSON.stringify({ board_id: boardId, ...updateData })
            });
            
            if (data.success) {
                Toast.success('게시글이 수정되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to update board:', error);
        }
    },
    
    async deleteBoard(boardId) {
        const confirmed = await confirmAction('정말 이 게시글을 삭제하시겠습니까?<br>삭제된 데이터는 복구할 수 없습니다.');
        
        if (!confirmed) return;
        
        try {
            const data = await apiRequest('/api/admin/boards/delete.php', {
                method: 'POST',
                body: JSON.stringify({ board_id: boardId })
            });
            
            if (data.success) {
                Toast.success('게시글이 삭제되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to delete board:', error);
        }
    }
};

// Festival Management Functions
const FestivalAdmin = {
    async viewDetail(registrationId) {
        try {
            const data = await apiRequest(`/api/admin/festival/detail.php?id=${registrationId}`);
            
            if (data.success) {
                showFestivalDetailModal(data.data);
            }
        } catch (error) {
            console.error('Failed to load festival registration detail:', error);
        }
    },
    
    async approve(registrationId, note = '') {
        const confirmed = await confirmAction('이 신청을 승인하시겠습니까?');
        
        if (!confirmed) return;
        
        try {
            const data = await apiRequest('/api/admin/festival/approve.php', {
                method: 'POST',
                body: JSON.stringify({ registration_id: registrationId, note })
            });
            
            if (data.success) {
                Toast.success('신청이 승인되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to approve registration:', error);
        }
    },
    
    async reject(registrationId, reason) {
        if (!reason || reason.trim() === '') {
            Toast.warning('반려 사유를 입력해주세요.');
            return;
        }
        
        const confirmed = await confirmAction('이 신청을 반려하시겠습니까?');
        
        if (!confirmed) return;
        
        try {
            const data = await apiRequest('/api/admin/festival/reject.php', {
                method: 'POST',
                body: JSON.stringify({ registration_id: registrationId, reason })
            });
            
            if (data.success) {
                Toast.success('신청이 반려되었습니다.');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (error) {
            console.error('Failed to reject registration:', error);
        }
    }
};

// Modal Functions
function showMemberDetailModal(data) {
    const member = data.member;
    const profile = data.profile;
    
    const typeNames = {
        individual: '개인 개발자',
        company: '기업',
        education: '교육기관',
        team: '팀'
    };
    
    const statusNames = {
        active: '활성',
        pending: '대기',
        inactive: '비활성'
    };
    
    let profileHtml = '';
    if (profile) {
        if (member.member_type === 'individual') {
            profileHtml = `
                <div class="profile-section">
                    <h4>개발자 프로필</h4>
                    <p><strong>기술 스택:</strong> ${profile.skills || '-'}</p>
                    <p><strong>경력:</strong> ${profile.experience_years || 0}년</p>
                    <p><strong>레벨:</strong> ${profile.level || '-'}</p>
                    <p><strong>자기소개:</strong> ${profile.bio || '-'}</p>
                </div>
            `;
        } else if (member.member_type === 'company') {
            profileHtml = `
                <div class="profile-section">
                    <h4>기업 정보</h4>
                    <p><strong>회사명:</strong> ${profile.company_name || '-'}</p>
                    <p><strong>산업:</strong> ${profile.industry || '-'}</p>
                    <p><strong>규모:</strong> ${profile.company_size || '-'}</p>
                    <p><strong>웹사이트:</strong> ${profile.website || '-'}</p>
                </div>
            `;
        }
    }
    
    const modal = document.createElement('div');
    modal.className = 'detail-modal';
    modal.innerHTML = `
        <div class="detail-modal-content">
            <div class="detail-modal-header">
                <h3><i class="fas fa-user"></i> 회원 상세 정보</h3>
                <button class="close-btn" onclick="this.closest('.detail-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="detail-modal-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>ID</label>
                        <span>#${member.member_id}</span>
                    </div>
                    <div class="info-item">
                        <label>이름</label>
                        <span>${member.name}</span>
                    </div>
                    <div class="info-item">
                        <label>이메일</label>
                        <span>${member.email}</span>
                    </div>
                    <div class="info-item">
                        <label>전화번호</label>
                        <span>${member.phone}</span>
                    </div>
                    <div class="info-item">
                        <label>회원 유형</label>
                        <span>${typeNames[member.member_type]}</span>
                    </div>
                    <div class="info-item">
                        <label>상태</label>
                        <span class="badge badge-${member.status}">${statusNames[member.status]}</span>
                    </div>
                    <div class="info-item">
                        <label>가입일</label>
                        <span>${member.created_at}</span>
                    </div>
                    <div class="info-item">
                        <label>최종 수정</label>
                        <span>${member.updated_at}</span>
                    </div>
                </div>
                ${profileHtml}
            </div>
            <div class="detail-modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.detail-modal').remove()">닫기</button>
                <button class="btn btn-primary" onclick="this.closest('.detail-modal').remove(); showEditMemberModal(${member.member_id})">수정</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function showEditMemberModal(memberId) {
    // TODO: Implement edit modal
    Toast.info('회원 수정 모달은 다음 단계에서 구현됩니다.');
}

function showFestivalDetailModal(data) {
    const reg = data.registration;
    
    const statusNames = {
        pending: '대기 중',
        approved: '승인됨',
        rejected: '반려됨'
    };
    
    const modal = document.createElement('div');
    modal.className = 'detail-modal';
    modal.innerHTML = `
        <div class="detail-modal-content">
            <div class="detail-modal-header">
                <h3><i class="fas fa-trophy"></i> 페스티벌 신청 상세</h3>
                <button class="close-btn" onclick="this.closest('.detail-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="detail-modal-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>신청 ID</label>
                        <span>#${reg.registration_id}</span>
                    </div>
                    <div class="info-item">
                        <label>이름</label>
                        <span>${reg.name}</span>
                    </div>
                    <div class="info-item">
                        <label>이메일</label>
                        <span>${reg.email}</span>
                    </div>
                    <div class="info-item">
                        <label>전화번호</label>
                        <span>${reg.phone}</span>
                    </div>
                    <div class="info-item">
                        <label>소속</label>
                        <span>${reg.organization || '-'}</span>
                    </div>
                    <div class="info-item">
                        <label>상태</label>
                        <span class="badge badge-${reg.status}">${statusNames[reg.status]}</span>
                    </div>
                    <div class="info-item">
                        <label>신청일</label>
                        <span>${reg.created_at}</span>
                    </div>
                    <div class="info-item">
                        <label>페스티벌</label>
                        <span>${reg.festival_title || '-'}</span>
                    </div>
                </div>
            </div>
            <div class="detail-modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.detail-modal').remove()">닫기</button>
                ${reg.status === 'pending' ? `
                    <button class="btn btn-danger" onclick="this.closest('.detail-modal').remove(); promptRejectReason(${reg.registration_id})">반려</button>
                    <button class="btn btn-success" onclick="this.closest('.detail-modal').remove(); FestivalAdmin.approve(${reg.registration_id})">승인</button>
                ` : ''}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function promptRejectReason(registrationId) {
    const modal = document.createElement('div');
    modal.className = 'confirm-modal';
    modal.innerHTML = `
        <div class="confirm-modal-content">
            <div class="confirm-modal-header">
                <i class="fas fa-times-circle text-red-500"></i>
                <h3>신청 반려</h3>
            </div>
            <div class="confirm-modal-body">
                <label>반려 사유</label>
                <textarea id="reject-reason" class="form-textarea" rows="4" placeholder="반려 사유를 입력해주세요..."></textarea>
            </div>
            <div class="confirm-modal-footer">
                <button class="btn-cancel" onclick="this.closest('.confirm-modal').remove()">취소</button>
                <button class="btn-confirm" onclick="submitReject(${registrationId})">반려</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function submitReject(registrationId) {
    const reason = document.getElementById('reject-reason').value;
    document.querySelector('.confirm-modal').remove();
    FestivalAdmin.reject(registrationId, reason);
}

// Global functions for inline onclick handlers
window.viewMember = (id) => MemberAdmin.viewDetail(id);
window.editMember = (id) => showEditMemberModal(id);
window.deleteMember = (id) => MemberAdmin.deleteMember(id);
window.viewBoard = (id) => window.location.href = `/?page=board&id=${id}`;
window.editBoard = (id) => Toast.info('게시글 수정 기능은 게시글 상세 페이지에서 가능합니다.');
window.deleteBoard = (id) => BoardAdmin.deleteBoard(id);
window.viewRegistration = (id) => FestivalAdmin.viewDetail(id);
window.approveRegistration = (id) => FestivalAdmin.approve(id);
window.rejectRegistration = (id) => promptRejectReason(id);

console.log('Admin JS loaded successfully');
