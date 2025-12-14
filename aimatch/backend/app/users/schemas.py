from pydantic import BaseModel, EmailStr, Field
from datetime import datetime
from typing import Optional

class UserProfileUpdate(BaseModel):
    """사용자 프로필 수정 스키마"""
    name: Optional[str] = Field(None, max_length=100)
    phone: Optional[str] = Field(None, max_length=20)
    bio: Optional[str] = Field(None, max_length=500)
    avatar_url: Optional[str] = Field(None, max_length=255)
    company: Optional[str] = Field(None, max_length=100)
    position: Optional[str] = Field(None, max_length=100)
    skills: Optional[str] = Field(None, max_length=500)  # 쉼표로 구분된 기술 스택
    portfolio_url: Optional[str] = Field(None, max_length=255)
    github_url: Optional[str] = Field(None, max_length=255)
    linkedin_url: Optional[str] = Field(None, max_length=255)
    experience_years: Optional[str] = Field(None, max_length=50)
    hourly_rate: Optional[str] = Field(None, max_length=50)

    class Config:
        from_attributes = True


class UserProfileResponse(BaseModel):
    """사용자 프로필 응답 스키마"""
    id: str
    email: str
    name: Optional[str]
    phone: Optional[str]
    user_type: str
    status: str
    bio: Optional[str]
    avatar_url: Optional[str]
    company: Optional[str]
    position: Optional[str]
    skills: Optional[str]
    portfolio_url: Optional[str]
    github_url: Optional[str]
    linkedin_url: Optional[str]
    experience_years: Optional[str]
    hourly_rate: Optional[str]
    email_verified: int
    created_at: datetime
    updated_at: datetime
    last_login_at: Optional[datetime]

    class Config:
        from_attributes = True


class PasswordChangeRequest(BaseModel):
    """비밀번호 변경 요청 스키마"""
    current_password: str = Field(..., min_length=1)
    new_password: str = Field(..., min_length=8)
    confirm_password: str = Field(..., min_length=8)

    class Config:
        from_attributes = True


class PasswordChangeResponse(BaseModel):
    """비밀번호 변경 응답 스키마"""
    message: str
    success: bool
