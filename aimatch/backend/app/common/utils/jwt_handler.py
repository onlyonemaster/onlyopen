from datetime import datetime, timedelta
from jose import jwt, JWTError
from config.settings import secret_key, algorithm, access_token_expire_minutes, refresh_token_expire_days

class JWTHandler:
    @staticmethod
    def create_access_token(subject: str) -> str:
        expire = datetime.utcnow() + timedelta(minutes=access_token_expire_minutes)
        to_encode = {"sub": subject, "exp": expire}
        encoded_jwt = jwt.encode(to_encode, secret_key, algorithm=algorithm)
        return encoded_jwt

    @staticmethod
    def create_refresh_token(subject: str) -> str:
        expire = datetime.utcnow() + timedelta(days=refresh_token_expire_days)
        to_encode = {"sub": subject, "exp": expire, "type": "refresh"}
        encoded_jwt = jwt.encode(to_encode, secret_key, algorithm=algorithm)
        return encoded_jwt

    @staticmethod
    def verify_token(token: str) -> str:
        try:
            payload = jwt.decode(token, secret_key, algorithms=[algorithm])
            subject: str = payload.get("sub")
            if subject is None:
                return None
            return subject
        except JWTError:
            return None

    @staticmethod
    def get_subject_from_token(token: str) -> str:
        return JWTHandler.verify_token(token)
