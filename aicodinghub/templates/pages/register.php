<div class="hero" style="padding: 60px 0;">
    <div class="container">
        <h1 style="text-align: center;">회원가입</h1>
    </div>
</div>

<section>
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="card">
                <form method="post" action="/api/register.php">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">회원 유형</label>
                        <select name="member_type" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                            <option value="">선택하세요</option>
                            <option value="individual">개인 개발자</option>
                            <option value="company">기업</option>
                            <option value="education">교육기관</option>
                            <option value="team">팀</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">이름</label>
                        <input type="text" name="name" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">이메일</label>
                        <input type="email" name="email" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">비밀번호</label>
                        <input type="password" name="password" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">연락처</label>
                        <input type="tel" name="phone" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">가입하기</button>
                </form>
            </div>
        </div>
    </div>
</section>
