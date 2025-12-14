from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from app.auth.schemas import (
    SignUpRequest, LoginRequest, TokenRefreshRequest,
    SignUpResponse, LoginResponse, UserResponse
)
from app.auth.services import AuthService
from app.common.utils.jwt_handler import JWTHandler
from app.common.dependencies.auth import get_current_user, get_db
from app.users.models import User

router = APIRouter(prefix="/auth", tags=["auth"])

@router.post("/signup", status_code=status.HTTP_201_CREATED, response_model=SignUpResponse)
async def signup(request: SignUpRequest, db: Session = Depends(get_db)):
    try:
        user, verification_token = AuthService.signup(db, request)

        # 이메일 미인증 상태이므로 토큰은 발급하지 않음
        # 프론트엔드에서 사용자에게 이메일 인증 요청
        return {
            "user": user,
            "tokens": {
                "access_token": "",  # 이메일 인증 후 발급
                "refresh_token": ""
            }
        }
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

@router.post("/login", response_model=LoginResponse)
async def login(request: LoginRequest, db: Session = Depends(get_db)):
    try:
        user = AuthService.login(db, request)

        # 이메일 인증 여부 확인
        if user.email_verified != 1:
            raise ValueError("Email not verified. Please check your email for verification link.")

        access_token = JWTHandler.create_access_token(user.id)
        refresh_token = JWTHandler.create_refresh_token(user.id)

        return {
            "user": user,
            "tokens": {
                "access_token": access_token,
                "refresh_token": refresh_token
            }
        }
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(e))

@router.post("/refresh")
async def refresh_token(request: TokenRefreshRequest, db: Session = Depends(get_db)):
    try:
        access_token = AuthService.refresh_token(db, request.refresh_token)
        return {
            "access_token": access_token,
            "token_type": "bearer"
        }
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(e))

@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
async def logout(current_user: User = Depends(get_current_user)):
    return None

@router.get("/me", response_model=UserResponse)
async def get_me(current_user: User = Depends(get_current_user)):
    return current_user

@router.post("/verify-email", response_model=LoginResponse)
async def verify_email(token: str = Query(...), db: Session = Depends(get_db)):
    """
    이메일 인증 토큰으로 이메일 인증 수행

    Args:
        token: 인증 토큰 (Query parameter)
        db: 데이터베이스 세션

    Returns:
        LoginResponse: 인증된 사용자 정보 및 토큰
    """
    try:
        user = AuthService.verify_email(db, token)
        access_token = JWTHandler.create_access_token(user.id)
        refresh_token = JWTHandler.create_refresh_token(user.id)

        return {
            "user": user,
            "tokens": {
                "access_token": access_token,
                "refresh_token": refresh_token
            }
        }
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))
