import secrets
import hashlib
from datetime import datetime, timedelta
from app.common.utils.jwt_handler import JWTHandler

class TokenHandler:
    """인증 관련 토큰(이메일 인증, 비밀번호 재설정 등)을 관리하는 핸들러"""

    @staticmethod
    def generate_verification_token() -> str:
        """
        이메일 인증 토큰 생성

        Returns:
            str: 인증 토큰
        """
        return secrets.token_urlsafe(32)

    @staticmethod
    def generate_reset_token() -> str:
        """
        비밀번호 재설정 토큰 생성

        Returns:
            str: 재설정 토큰
        """
        return secrets.token_urlsafe(32)

    @staticmethod
    def hash_token(token: str) -> str:
        """
        토큰을 해시하여 데이터베이스에 저장

        Args:
            token: 원본 토큰

        Returns:
            str: 해시된 토큰
        """
        return hashlib.sha256(token.encode()).hexdigest()

    @staticmethod
    def create_email_verification_token(user_id: str) -> tuple[str, str]:
        """
        이메일 인증 토큰 생성 및 만료시간 설정

        Args:
            user_id: 사용자 ID

        Returns:
            tuple: (토큰, 해시된 토큰)
        """
        token = TokenHandler.generate_verification_token()
        hashed_token = TokenHandler.hash_token(token)
        return token, hashed_token

    @staticmethod
    def create_password_reset_token(user_id: str) -> tuple[str, str]:
        """
        비밀번호 재설정 토큰 생성

        Args:
            user_id: 사용자 ID

        Returns:
            tuple: (토큰, 해시된 토큰)
        """
        token = TokenHandler.generate_reset_token()
        hashed_token = TokenHandler.hash_token(token)
        return token, hashed_token
