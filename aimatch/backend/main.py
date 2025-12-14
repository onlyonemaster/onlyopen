from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from config.settings import cors_origins, app_name, app_version
from app.auth.routes import router as auth_router
from app.users.routes import router as users_router

app = FastAPI(
    title=app_name,
    version=app_version,
    description="AIMatch Pro - AI-powered coding intermediary platform API",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check endpoint
@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": app_name, "version": app_version}

# Include routers
app.include_router(auth_router)
app.include_router(users_router)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
