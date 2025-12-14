import os
from dotenv import load_dotenv

load_dotenv()

# Application
app_name = "AIMatch Pro"
app_version = "1.0.0"
debug = os.getenv("DEBUG", "False") == "True"

# Database
database_url = os.getenv("DATABASE_URL", "mysql+pymysql://aimatch_app:aimatch123@localhost:3306/aimatch")
database_echo = os.getenv("DATABASE_ECHO", "False") == "True"

# JWT
secret_key = os.getenv("JWT_SECRET_KEY", "your-secret-key-change-in-production")
algorithm = os.getenv("JWT_ALGORITHM", "HS256")
access_token_expire_minutes = int(os.getenv("JWT_ACCESS_TOKEN_EXPIRE_MINUTES", "60"))
refresh_token_expire_days = int(os.getenv("JWT_REFRESH_TOKEN_EXPIRE_DAYS", "7"))

# Redis
redis_url = os.getenv("REDIS_URL", "redis://localhost:6379/0")

# CORS
cors_origins = [
    "https://open.kiam.kr",
    "http://open.kiam.kr",
    "http://localhost",
    "http://localhost:3000",
    "http://localhost:3001",
    "http://localhost:3002",
    "http://localhost:5173",
    "http://localhost:8000",
    "http://127.0.0.1:5173",
    "http://127.0.0.1:3000",
    "http://127.0.0.1:3001",
    "http://127.0.0.1:3002"
]
