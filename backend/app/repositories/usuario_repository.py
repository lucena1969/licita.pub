"""
Repository para operações de banco de dados relacionadas a usuários
"""
from sqlalchemy.orm import Session
from app.models.usuario import Usuario
from typing import Optional
from datetime import datetime


class UsuarioRepository:
    """Repository para acesso a dados de usuários"""

    def __init__(self, db: Session):
        self.db = db

    def buscar_por_id(self, usuario_id: str) -> Optional[Usuario]:
        """Busca usuário por ID"""
        return self.db.query(Usuario).filter(Usuario.id == usuario_id).first()

    def buscar_por_email(self, email: str) -> Optional[Usuario]:
        """Busca usuário por email"""
        return self.db.query(Usuario).filter(Usuario.email == email).first()

    def buscar_por_cpf_cnpj(self, cpf_cnpj: str) -> Optional[Usuario]:
        """Busca usuário por CPF/CNPJ"""
        return self.db.query(Usuario).filter(Usuario.cpf_cnpj == cpf_cnpj).first()

    def buscar_por_token_verificacao(self, token: str) -> Optional[Usuario]:
        """Busca usuário por token de verificação"""
        return self.db.query(Usuario).filter(
            Usuario.token_verificacao == token,
            Usuario.token_verificacao_expira > datetime.utcnow()
        ).first()

    def buscar_por_token_reset(self, token: str) -> Optional[Usuario]:
        """Busca usuário por token de reset de senha"""
        return self.db.query(Usuario).filter(
            Usuario.token_reset_senha == token,
            Usuario.token_reset_senha_expira > datetime.utcnow()
        ).first()

    def criar(self, usuario: Usuario) -> Usuario:
        """Cria um novo usuário"""
        self.db.add(usuario)
        self.db.commit()
        self.db.refresh(usuario)
        return usuario

    def atualizar(self, usuario: Usuario) -> Usuario:
        """Atualiza um usuário existente"""
        usuario.updated_at = datetime.utcnow()
        self.db.commit()
        self.db.refresh(usuario)
        return usuario

    def deletar(self, usuario: Usuario) -> None:
        """Deleta um usuário"""
        self.db.delete(usuario)
        self.db.commit()

    def verificar_email(self, usuario: Usuario) -> Usuario:
        """Marca email como verificado"""
        usuario.email_verificado = True
        usuario.token_verificacao = None
        usuario.token_verificacao_expira = None
        return self.atualizar(usuario)

    def contar_usuarios(self) -> int:
        """Conta total de usuários"""
        return self.db.query(Usuario).count()

    def contar_usuarios_ativos(self) -> int:
        """Conta usuários ativos"""
        return self.db.query(Usuario).filter(Usuario.ativo == True).count()
