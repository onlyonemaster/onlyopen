<div class="hero" style="padding: 60px 0;">
    <div class="container">
        <h1 style="text-align: center;">로그인</h1>
    </div>
</div>

<section>
    <div class="container">
        <div style="max-width: 400px; margin: 0 auto;">
            <div class="card">
                <form method="post" action="/api/login.php">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">이메일</label>
                        <input type="email" name="email" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">비밀번호</label>
                        <input type="password" name="password" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">로그인</button>
                </form>
                <p style="text-align: center; margin-top: 20px;">
                    계정이 없으신가요? <a href="?page=register" style="color: var(--primary-color);">회원가입</a>
                </p>
            </div>
        </div>
    </div>
</section>
