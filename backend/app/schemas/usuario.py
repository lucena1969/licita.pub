"""
Schemas Pydantic para validação de dados de usuários
"""
from pydantic import BaseModel, EmailStr, Field, validator
from typing import Optional
from datetime import datetime
from app.models.usuario import PlanoEnum
import re


class UsuarioBase(BaseModel):
    """Schema base de usuário"""
    email: EmailStr
    nome: str = Field(..., min_length=3, max_length=255)
    telefone: Optional[str] = None
    cpf_cnpj: Optional[str] = None


class UsuarioCreate(UsuarioBase):
    """Schema para criação de usuário"""
    senha: str = Field(..., min_length=8, max_length=100)
    confirmar_senha: str

    @validator('confirmar_senha')
    def senhas_devem_coincidir(cls, v, values):
        if 'senha' in values and v != values['senha']:
            raise ValueError('As senhas não coincidem')
        return v

    @validator('senha')
    def senha_forte(cls, v):
        """Valida força da senha"""
        if len(v) < 8:
            raise ValueError('Senha deve ter no mínimo 8 caracteres')
        if not re.search(r'[A-Z]', v):
            raise ValueError('Senha deve conter pelo menos uma letra maiúscula')
        if not re.search(r'[a-z]', v):
            raise ValueError('Senha deve conter pelo menos uma letra minúscula')
        if not re.search(r'[0-9]', v):
            raise ValueError('Senha deve conter pelo menos um número')
        return v

    @validator('cpf_cnpj')
    def validar_cpf_cnpj(cls, v):
        """Validação básica de CPF/CNPJ"""
        if v:
            # Remove caracteres não numéricos
            numeros = re.sub(r'\D', '', v)
            if len(numeros) not in [11, 14]:
                raise ValueError('CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos')
        return v


class UsuarioUpdate(BaseModel):
    """Schema para atualização de usuário"""
    nome: Optional[str] = Field(None, min_length=3, max_length=255)
    telefone: Optional[str] = None
    cpf_cnpj: Optional[str] = None


class UsuarioResponse(UsuarioBase):
    """Schema de resposta de usuário (sem senha)"""
    id: str
    email_verificado: bool
    ativo: bool
    plano: PlanoEnum
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True  # Pydantic v2 (antes era orm_mode = True)


class UsuarioLogin(BaseModel):
    """Schema para login"""
    email: EmailStr
    senha: str


class Token(BaseModel):
    """Schema de token JWT"""
    access_token: str
    token_type: str = "bearer"


class TokenData(BaseModel):
    """Schema dos dados do token"""
    user_id: Optional[str] = None


class AlterarSenha(BaseModel):
    """Schema para alteração de senha"""
    senha_atual: str
    senha_nova: str = Field(..., min_length=8, max_length=100)
    confirmar_senha_nova: str

    @validator('confirmar_senha_nova')
    def senhas_devem_coincidir(cls, v, values):
        if 'senha_nova' in values and v != values['senha_nova']:
            raise ValueError('As senhas não coincidem')
        return v


class SolicitarResetSenha(BaseModel):
    """Schema para solicitar reset de senha"""
    email: EmailStr


class ResetSenha(BaseModel):
    """Schema para resetar senha com token"""
    token: str
    senha_nova: str = Field(..., min_length=8, max_length=100)
    confirmar_senha_nova: str

    @validator('confirmar_senha_nova')
    def senhas_devem_coincidir(cls, v, values):
        if 'senha_nova' in values and v != values['senha_nova']:
            raise ValueError('As senhas não coincidem')
        return v
