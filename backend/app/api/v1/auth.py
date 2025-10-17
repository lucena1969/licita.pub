"""
Endpoints de autenticação
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.schemas.usuario import (
    UsuarioCreate,
    UsuarioResponse,
    UsuarioLogin,
    Token,
    SolicitarResetSenha,
    ResetSenha,
    AlterarSenha,
    UsuarioUpdate
)
from app.services.usuario_service import UsuarioService
from app.api.deps import get_current_user, get_current_active_user
from app.models.usuario import Usuario

router = APIRouter(prefix="/auth", tags=["Autenticação"])


@router.post("/registrar", response_model=UsuarioResponse, status_code=status.HTTP_201_CREATED)
async def registrar(
    usuario_data: UsuarioCreate,
    db: Session = Depends(get_db)
):
    """
    Registra um novo usuário

    - **email**: Email válido (único)
    - **senha**: Mínimo 8 caracteres, com letra maiúscula, minúscula e número
    - **nome**: Nome completo
    - **telefone**: Telefone (opcional)
    - **cpf_cnpj**: CPF (11 dígitos) ou CNPJ (14 dígitos) - opcional
    """
    service = UsuarioService(db)
    usuario = await service.registrar_usuario(usuario_data)
    return usuario


@router.post("/login", response_model=Token)
async def login(
    credentials: UsuarioLogin,
    db: Session = Depends(get_db)
):
    """
    Realiza login e retorna token JWT

    - **email**: Email do usuário
    - **senha**: Senha
    """
    service = UsuarioService(db)
    usuario = service.autenticar(credentials.email, credentials.senha)

    if not usuario:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Email ou senha incorretos",
            headers={"WWW-Authenticate": "Bearer"},
        )

    # Gerar token
    token = service.gerar_token_acesso(usuario)

    return {"access_token": token, "token_type": "bearer"}


@router.get("/me", response_model=UsuarioResponse)
async def obter_usuario_atual(
    current_user: Usuario = Depends(get_current_active_user)
):
    """
    Retorna dados do usuário autenticado atual

    Requer autenticação (Bearer Token)
    """
    return current_user


@router.put("/me", response_model=UsuarioResponse)
async def atualizar_usuario_atual(
    usuario_data: UsuarioUpdate,
    current_user: Usuario = Depends(get_current_active_user),
    db: Session = Depends(get_db)
):
    """
    Atualiza dados do usuário autenticado

    Requer autenticação (Bearer Token)
    """
    service = UsuarioService(db)
    usuario_atualizado = service.atualizar_usuario(current_user.id, usuario_data)
    return usuario_atualizado


@router.post("/alterar-senha", response_model=dict)
async def alterar_senha(
    dados: AlterarSenha,
    current_user: Usuario = Depends(get_current_active_user),
    db: Session = Depends(get_db)
):
    """
    Altera senha do usuário autenticado

    - **senha_atual**: Senha atual para confirmação
    - **senha_nova**: Nova senha (mínimo 8 caracteres)
    - **confirmar_senha_nova**: Confirmação da nova senha

    Requer autenticação (Bearer Token)
    """
    service = UsuarioService(db)
    service.alterar_senha(current_user.id, dados.senha_atual, dados.senha_nova)
    return {"message": "Senha alterada com sucesso"}


@router.get("/verificar-email/{token}", response_model=dict)
async def verificar_email(
    token: str,
    db: Session = Depends(get_db)
):
    """
    Verifica email do usuário usando token enviado por email

    - **token**: Token de verificação recebido por email
    """
    service = UsuarioService(db)
    await service.verificar_email(token)
    return {"message": "Email verificado com sucesso"}


@router.post("/solicitar-reset-senha", response_model=dict)
async def solicitar_reset_senha(
    dados: SolicitarResetSenha,
    db: Session = Depends(get_db)
):
    """
    Solicita reset de senha (envia email com link)

    - **email**: Email da conta
    """
    service = UsuarioService(db)
    await service.solicitar_reset_senha(dados.email)
    return {"message": "Se o email estiver cadastrado, você receberá instruções para reset"}


@router.post("/resetar-senha", response_model=dict)
async def resetar_senha(
    dados: ResetSenha,
    db: Session = Depends(get_db)
):
    """
    Reseta senha usando token recebido por email

    - **token**: Token recebido por email
    - **senha_nova**: Nova senha
    - **confirmar_senha_nova**: Confirmação
    """
    service = UsuarioService(db)
    service.resetar_senha(dados.token, dados.senha_nova)
    return {"message": "Senha resetada com sucesso"}
