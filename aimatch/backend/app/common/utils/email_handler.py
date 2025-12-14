import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import os
from typing import Optional

class EmailHandler:
    """이메일 발송을 담당하는 핸들러"""

    # 환경 변수에서 설정 (또는 기본값)
    SMTP_SERVER = os.getenv("SMTP_SERVER", "smtp.gmail.com")
    SMTP_PORT = int(os.getenv("SMTP_PORT", "587"))
    SMTP_EMAIL = os.getenv("SMTP_EMAIL", "noreply@aimatchpro.com")
    SMTP_PASSWORD = os.getenv("SMTP_PASSWORD", "")
    SENDER_NAME = "AIMatch Pro"

    @staticmethod
    def send_email(
        to_email: str,
        subject: str,
        html_content: str,
        text_content: Optional[str] = None
    ) -> bool:
        """
        이메일 발송

        Args:
            to_email: 받는 사람 이메일
            subject: 이메일 제목
            html_content: HTML 형식의 내용
            text_content: 텍스트 형식의 내용 (선택사항)

        Returns:
            bool: 성공 여부
        """
        try:
            # SMTP 서버 연결
            server = smtplib.SMTP(EmailHandler.SMTP_SERVER, EmailHandler.SMTP_PORT)
            server.starttls()
            server.login(EmailHandler.SMTP_EMAIL, EmailHandler.SMTP_PASSWORD)

            # 이메일 구성
            msg = MIMEMultipart("alternative")
            msg["Subject"] = subject
            msg["From"] = f"{EmailHandler.SENDER_NAME} <{EmailHandler.SMTP_EMAIL}>"
            msg["To"] = to_email

            # 텍스트 버전이 없으면 HTML에서 추출
            if not text_content:
                text_content = html_content.replace("<br>", "\n").replace("<br/>", "\n")

            # MIME 파트 추가 (텍스트 먼저, HTML 나중)
            part_text = MIMEText(text_content, "plain")
            part_html = MIMEText(html_content, "html")
            msg.attach(part_text)
            msg.attach(part_html)

            # 이메일 발송
            server.sendmail(EmailHandler.SMTP_EMAIL, to_email, msg.as_string())
            server.quit()

            return True
        except Exception as e:
            print(f"이메일 발송 실패: {str(e)}")
            return False

    @staticmethod
    def send_verification_email(to_email: str, verification_token: str, name: str = "사용자") -> bool:
        """
        이메일 인증 메일 발송

        Args:
            to_email: 받는 사람 이메일
            verification_token: 인증 토큰
            name: 사용자 이름

        Returns:
            bool: 성공 여부
        """
        verification_url = f"https://open.kiam.kr/verify-email?token={verification_token}"

        html_content = f"""
        <html>
        <head>
            <style>
                body {{ font-family: Arial, sans-serif; background-color: #f5f5f5; }}
                .container {{ max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; }}
                .header {{ background-color: #295CFF; color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }}
                .content {{ padding: 20px; }}
                .button {{ display: inline-block; background-color: #295CFF; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; margin: 20px 0; }}
                .footer {{ text-align: center; color: #666; font-size: 12px; margin-top: 20px; }}
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>AIMatch Pro</h1>
                </div>
                <div class="content">
                    <p>안녕하세요, {name}님!</p>
                    <p>AIMatch Pro에 가입해주셔서 감사합니다.</p>
                    <p>아래 버튼을 클릭하여 이메일을 인증해주세요.</p>
                    <a href="{verification_url}" class="button">이메일 인증하기</a>
                    <p>또는 다음 링크를 브라우저에서 열어주세요:</p>
                    <p><a href="{verification_url}">{verification_url}</a></p>
                    <p>이 링크는 24시간 동안 유효합니다.</p>
                </div>
                <div class="footer">
                    <p>AIMatch Pro | support@aimatchpro.com</p>
                    <p>자동 발송 메일입니다. 답장하지 말아주세요.</p>
                </div>
            </div>
        </body>
        </html>
        """

        return EmailHandler.send_email(
            to_email=to_email,
            subject="[AIMatch Pro] 이메일 인증",
            html_content=html_content
        )

    @staticmethod
    def send_password_reset_email(to_email: str, reset_token: str, name: str = "사용자") -> bool:
        """
        비밀번호 재설정 메일 발송 (향후 기능)

        Args:
            to_email: 받는 사람 이메일
            reset_token: 재설정 토큰
            name: 사용자 이름

        Returns:
            bool: 성공 여부
        """
        reset_url = f"https://open.kiam.kr/reset-password?token={reset_token}"

        html_content = f"""
        <html>
        <head>
            <style>
                body {{ font-family: Arial, sans-serif; background-color: #f5f5f5; }}
                .container {{ max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; }}
                .header {{ background-color: #295CFF; color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }}
                .content {{ padding: 20px; }}
                .button {{ display: inline-block; background-color: #295CFF; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; margin: 20px 0; }}
                .footer {{ text-align: center; color: #666; font-size: 12px; margin-top: 20px; }}
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>AIMatch Pro</h1>
                </div>
                <div class="content">
                    <p>안녕하세요, {name}님!</p>
                    <p>비밀번호 재설정 요청이 들어왔습니다.</p>
                    <p>아래 버튼을 클릭하여 새로운 비밀번호를 설정해주세요.</p>
                    <a href="{reset_url}" class="button">비밀번호 재설정</a>
                    <p>이 링크는 1시간 동안 유효합니다.</p>
                    <p>비밀번호 재설정을 요청하지 않으셨다면, 이 메일을 무시해주세요.</p>
                </div>
                <div class="footer">
                    <p>AIMatch Pro | support@aimatchpro.com</p>
                </div>
            </div>
        </body>
        </html>
        """

        return EmailHandler.send_email(
            to_email=to_email,
            subject="[AIMatch Pro] 비밀번호 재설정",
            html_content=html_content
        )
