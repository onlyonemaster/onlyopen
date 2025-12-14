from sqlalchemy import Column, String, DateTime, Index, Integer, Boolean, Enum
from sqlalchemy.sql import func
import uuid
from config.database import Base

class User(Base):
    __tablename__ = "users"

    id = Column(String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    email = Column(String(255), unique=True, nullable=False, index=True)
    name = Column(String(100), nullable=True)
    password_hash = Column(String(255), nullable=False)
    phone = Column(String(20), nullable=True)
    user_type = Column(String(50), nullable=False, index=True)
    status = Column(String(50), default="ACTIVE", nullable=False, index=True)

    # Email verification
    email_verified = Column(Integer, default=0, nullable=True)  # tinyint(1)
    email_verified_at = Column(DateTime, nullable=True)
    email_verification_token = Column(String(255), nullable=True)  # 해시된 토큰
    email_verification_token_expires_at = Column(DateTime, nullable=True)  # 토큰 만료시간

    # Additional security
    phone_verified = Column(Integer, default=0, nullable=True)  # tinyint(1)
    two_fa_enabled = Column(Integer, default=0, nullable=True)  # tinyint(1)

    # Timestamps
    created_at = Column(DateTime, default=func.now(), nullable=False, index=True)
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now(), nullable=False)
    last_login_at = Column(DateTime, nullable=True)
    deleted_at = Column(DateTime, nullable=True)

    # Profile fields
    bio = Column(String(500), nullable=True)
    avatar_url = Column(String(255), nullable=True)
    company = Column(String(100), nullable=True)
    position = Column(String(100), nullable=True)
    skills = Column(String(500), nullable=True)  # 쉼표로 구분된 기술 스택
    portfolio_url = Column(String(255), nullable=True)
    github_url = Column(String(255), nullable=True)
    linkedin_url = Column(String(255), nullable=True)
    experience_years = Column(String(50), nullable=True)
    hourly_rate = Column(String(50), nullable=True)

    __table_args__ = (
        Index('idx_email_status', 'email', 'status'),
        Index('idx_user_type_status', 'user_type', 'status'),
    )
