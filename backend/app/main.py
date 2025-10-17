"""
Entry point da aplicação FastAPI
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.config import settings
from app.api.v1 import auth
import logging

# Configurar logging
logging.basicConfig(
    level=getattr(logging, settings.log_level),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)

# Criar aplicação FastAPI
app = FastAPI(
    title="Licita.pub API",
    description="API da plataforma de licitações públicas do Brasil",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Configurar CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        settings.frontend_url,
        "http://localhost:5173",
        "http://localhost:3000",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Incluir routers
app.include_router(auth.router, prefix="/api/v1")

# Health check
@app.get("/")
async def root():
    """Endpoint raiz"""
    return {
        "message": "Licita.pub API",
        "version": "1.0.0",
        "status": "online"
    }


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "environment": settings.environment
    }


# Evento de startup
@app.on_event("startup")
async def startup_event():
    """Executado ao iniciar a aplicação"""
    logger.info("=" * 60)
    logger.info("Licita.pub API iniciando...")
    logger.info(f"Ambiente: {settings.environment}")
    logger.info(f"Frontend URL: {settings.frontend_url}")
    logger.info("=" * 60)


# Evento de shutdown
@app.on_event("shutdown")
async def shutdown_event():
    """Executado ao desligar a aplicação"""
    logger.info("Licita.pub API finalizando...")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8000,
        reload=True  # Hot reload em desenvolvimento
    )
