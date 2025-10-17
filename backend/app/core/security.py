"""
Funções de segurança: hashing de senhas, JWT tokens, etc.
"""
from datetime import datetime, timedelta
from typing import Optional
from jose import JWTError, jwt
from passlib.context import CryptContext
from app.config import settings
import secrets

# Contexto para hash de senhas com bcrypt
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


def hash_password(password: str) -> str:
    """
    Gera hash bcrypt de uma senha

    Args:
        password: Senha em texto plano

    Returns:
        Hash bcrypt da senha
    """
    return pwd_context.hash(password)


def verify_password(plain_password: str, hashed_password: str) -> bool:
    """
    Verifica se uma senha corresponde ao hash

    Args:
        plain_password: Senha em texto plano
        hashed_password: Hash armazenado no banco

    Returns:
        True se a senha está correta, False caso contrário
    """
    return pwd_context.verify(plain_password, hashed_password)


def create_access_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    """
    Cria um token JWT de acesso

    Args:
        data: Dados a serem codificados no token (ex: {"sub": user_id})
        expires_delta: Tempo de expiração customizado

    Returns:
        Token JWT como string
    """
    to_encode = data.copy()

    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=settings.access_token_expire_minutes)

    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, settings.secret_key, algorithm=settings.algorithm)

    return encoded_jwt


def decode_access_token(token: str) -> Optional[dict]:
    """
    Decodifica e valida um token JWT

    Args:
        token: Token JWT para decodificar

    Returns:
        Payload do token se válido, None caso contrário
    """
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[settings.algorithm])
        return payload
    except JWTError:
        return None


def generate_verification_token() -> str:
    """
    Gera um token seguro para verificação de email

    Returns:
        Token aleatório de 32 bytes em hexadecimal
    """
    return secrets.token_urlsafe(32)


def generate_reset_token() -> str:
    """
    Gera um token seguro para reset de senha

    Returns:
        Token aleatório de 32 bytes em hexadecimal
    """
    return secrets.token_urlsafe(32)


def create_email_verification_token(email: str) -> str:
    """
    Cria um token JWT para verificação de email

    Args:
        email: Email do usuário

    Returns:
        Token JWT
    """
    expires = timedelta(hours=settings.email_verification_expire_hours)
    data = {"sub": email, "type": "email_verification"}
    return create_access_token(data, expires_delta=expires)


def verify_email_token(token: str) -> Optional[str]:
    """
    Verifica um token de confirmação de email

    Args:
        token: Token JWT

    Returns:
        Email do usuário se válido, None caso contrário
    """
    payload = decode_access_token(token)

    if payload and payload.get("type") == "email_verification":
        return payload.get("sub")

    return None
