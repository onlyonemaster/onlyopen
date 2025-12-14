from pydantic import BaseModel, EmailStr
from datetime import datetime
from typing import Optional

class SignUpRequest(BaseModel):
    email: EmailStr
    password: str
    name: str
    user_type: str = "DEVELOPER"
    phone: Optional[str] = None

class LoginRequest(BaseModel):
    email: EmailStr
    password: str

class TokenRefreshRequest(BaseModel):
    refresh_token: str

class TokenResponse(BaseModel):
    access_token: str
    refresh_token: str
    token_type: str = "bearer"

class UserResponse(BaseModel):
    id: str
    email: str
    name: Optional[str]
    phone: Optional[str]
    user_type: str
    status: str
    email_verified: int
    created_at: datetime
    last_login_at: Optional[datetime]

    class Config:
        from_attributes = True

class SignUpResponse(BaseModel):
    user: UserResponse
    tokens: TokenResponse

class LoginResponse(BaseModel):
    user: UserResponse
    tokens: TokenResponse
