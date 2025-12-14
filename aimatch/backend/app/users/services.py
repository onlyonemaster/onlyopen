from sqlalchemy.orm import Session
from datetime import datetime
from app.users.models import User
from app.users.schemas import UserProfileUpdate, PasswordChangeRequest
from app.common.utils.password import PasswordHandler


class UserService:
    @staticmethod
    def get_profile(db: Session, user_id: str) -> User:
        """사용자 프로필 조회"""
        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise ValueError("User not found")
        return user

    @staticmethod
    def update_profile(db: Session, user_id: str, request: UserProfileUpdate) -> User:
        """사용자 프로필 수정"""
        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise ValueError("User not found")

        # 수정 가능한 필드 업데이트
        update_data = request.dict(exclude_unset=True)
        for field, value in update_data.items():
            if value is not None:
                setattr(user, field, value)

        user.updated_at = datetime.utcnow()
        db.commit()
        db.refresh(user)
        return user

    @staticmethod
    def change_password(db: Session, user_id: str, request: PasswordChangeRequest) -> bool:
        """비밀번호 변경"""
        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise ValueError("User not found")

        # 현재 비밀번호 검증
        if not PasswordHandler.verify_password(request.current_password, user.password_hash):
            raise ValueError("Current password is incorrect")

        # 새 비밀번호와 확인 비밀번호 일치 확인
        if request.new_password != request.confirm_password:
            raise ValueError("New passwords do not match")

        # 새 비밀번호가 현재 비밀번호와 다른지 확인
        if PasswordHandler.verify_password(request.new_password, user.password_hash):
            raise ValueError("New password must be different from current password")

        # 비밀번호 업데이트
        user.password_hash = PasswordHandler.hash_password(request.new_password)
        user.updated_at = datetime.utcnow()
        db.commit()
        db.refresh(user)
        return True

    @staticmethod
    def get_user_by_email(db: Session, email: str) -> User:
        """이메일로 사용자 조회"""
        user = db.query(User).filter(User.email == email).first()
        if not user:
            raise ValueError("User not found")
        return user

    @staticmethod
    def search_users(db: Session, query: str, limit: int = 10) -> list:
        """사용자 검색 (이름, 회사, 기술로 검색)"""
        search_pattern = f"%{query}%"
        users = db.query(User).filter(
            User.status == "ACTIVE",
            (
                User.name.ilike(search_pattern) |
                User.company.ilike(search_pattern) |
                User.skills.ilike(search_pattern)
            )
        ).limit(limit).all()
        return users

    @staticmethod
    def get_users_by_type(db: Session, user_type: str, limit: int = 20) -> list:
        """사용자 유형으로 사용자 목록 조회"""
        users = db.query(User).filter(
            User.user_type == user_type,
            User.status == "ACTIVE",
            User.email_verified == 1
        ).limit(limit).all()
        return users
