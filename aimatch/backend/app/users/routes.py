from fastapi import APIRouter, Depends, HTTPException, status, Header
from sqlalchemy.orm import Session
from config.database import get_db
from app.common.utils.jwt_handler import JWTHandler
from app.users.schemas import UserProfileResponse, UserProfileUpdate, PasswordChangeRequest, PasswordChangeResponse
from app.users.services import UserService
from typing import Optional

router = APIRouter(prefix="/api/users", tags=["users"])


@router.get("/me", response_model=UserProfileResponse)
async def get_my_profile(
    authorization: Optional[str] = Header(None),
    db: Session = Depends(get_db)
):
    """현재 사용자 프로필 조회"""
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid authorization header")

    token = authorization.replace("Bearer ", "")
    user_id = JWTHandler.verify_token(token)
    if not user_id:
        raise HTTPException(status_code=401, detail="Invalid token")

    try:
        user = UserService.get_profile(db, user_id)
        return user
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))


@router.put("/me", response_model=UserProfileResponse)
async def update_my_profile(
    request: UserProfileUpdate,
    authorization: Optional[str] = Header(None),
    db: Session = Depends(get_db)
):
    """현재 사용자 프로필 수정"""
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid authorization header")

    token = authorization.replace("Bearer ", "")
    user_id = JWTHandler.verify_token(token)
    if not user_id:
        raise HTTPException(status_code=401, detail="Invalid token")

    try:
        user = UserService.update_profile(db, user_id, request)
        return user
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))


@router.post("/change-password", response_model=PasswordChangeResponse)
async def change_password(
    request: PasswordChangeRequest,
    authorization: Optional[str] = Header(None),
    db: Session = Depends(get_db)
):
    """비밀번호 변경"""
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid authorization header")

    token = authorization.replace("Bearer ", "")
    user_id = JWTHandler.verify_token(token)
    if not user_id:
        raise HTTPException(status_code=401, detail="Invalid token")

    try:
        UserService.change_password(db, user_id, request)
        return {
            "message": "Password changed successfully",
            "success": True
        }
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/{user_id}", response_model=UserProfileResponse)
async def get_user_by_id(
    user_id: str,
    db: Session = Depends(get_db)
):
    """다른 사용자 프로필 조회 (공개 정보)"""
    try:
        user = UserService.get_profile(db, user_id)
        return user
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))


@router.get("/search/by-type")
async def search_by_type(
    user_type: str,
    limit: int = 20,
    db: Session = Depends(get_db)
):
    """사용자 유형으로 검색"""
    users = UserService.get_users_by_type(db, user_type, limit)
    return [
        UserProfileResponse.from_orm(user) for user in users
    ]


@router.get("/search/query")
async def search_users(
    q: str,
    limit: int = 10,
    db: Session = Depends(get_db)
):
    """사용자 검색"""
    if not q or len(q) < 2:
        raise HTTPException(status_code=400, detail="Search query must be at least 2 characters")

    users = UserService.search_users(db, q, limit)
    return [
        UserProfileResponse.from_orm(user) for user in users
    ]
