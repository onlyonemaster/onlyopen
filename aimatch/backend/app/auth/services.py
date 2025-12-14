from sqlalchemy.orm import Session
from datetime import datetime, timedelta
from app.users.models import User
from app.auth.schemas import SignUpRequest, LoginRequest
from app.common.utils.password import PasswordHandler
from app.common.utils.jwt_handler import JWTHandler
from app.common.utils.token_handler import TokenHandler
from app.common.utils.email_handler import EmailHandler

class AuthService:
    @staticmethod
    def signup(db: Session, request: SignUpRequest) -> tuple[User, str]:
        """
        회원가입 처리 및 인증 이메일 발송

        Returns:
            tuple: (User 객체, 인증 토큰)
        """
        existing_user = db.query(User).filter(User.email == request.email).first()
        if existing_user:
            raise ValueError("Email already registered")

        # 이메일 인증 토큰 생성
        verification_token, hashed_token = TokenHandler.create_email_verification_token(None)

        user = User(
            email=request.email,
            password_hash=PasswordHandler.hash_password(request.password),
            name=request.name,
            phone=request.phone,
            user_type=request.user_type,
            status="ACTIVE",
            email_verified=0,  # 이메일 인증 전까지 0
            email_verification_token=hashed_token,
            email_verification_token_expires_at=datetime.utcnow() + timedelta(hours=24)
        )
        db.add(user)
        db.commit()
        db.refresh(user)

        # 인증 이메일 발송
        EmailHandler.send_verification_email(
            to_email=request.email,
            verification_token=verification_token,
            name=request.name
        )

        return user, verification_token

    @staticmethod
    def login(db: Session, request: LoginRequest) -> User:
        user = db.query(User).filter(User.email == request.email).first()
        if not user or not PasswordHandler.verify_password(request.password, user.password_hash):
            raise ValueError("Invalid email or password")

        user.last_login_at = datetime.utcnow()
        db.commit()
        db.refresh(user)
        return user

    @staticmethod
    def refresh_token(db: Session, refresh_token: str) -> str:
        user_id = JWTHandler.verify_token(refresh_token)
        if not user_id:
            raise ValueError("Invalid refresh token")

        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise ValueError("User not found")

        return JWTHandler.create_access_token(user.id)

    @staticmethod
    def get_user_by_id(db: Session, user_id: str) -> User:
        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise ValueError("User not found")
        return user

    @staticmethod
    def verify_email(db: Session, verification_token: str) -> User:
        """
        이메일 인증 토큰으로 이메일 인증 수행

        Args:
            db: 데이터베이스 세션
            verification_token: 인증 토큰

        Returns:
            User: 인증된 사용자

        Raises:
            ValueError: 토큰이 유효하지 않거나 만료된 경우
        """
        # 토큰 해시
        hashed_token = TokenHandler.hash_token(verification_token)

        # 토큰과 만료시간이 일치하는 사용자 찾기
        user = db.query(User).filter(
            User.email_verification_token == hashed_token,
            User.email_verification_token_expires_at > datetime.utcnow()
        ).first()

        if not user:
            raise ValueError("Invalid or expired verification token")

        # 이메일 인증 완료
        user.email_verified = 1
        user.email_verified_at = datetime.utcnow()
        user.email_verification_token = None
        user.email_verification_token_expires_at = None

        db.commit()
        db.refresh(user)
        return user
