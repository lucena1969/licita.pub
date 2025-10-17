"""
Service com lógica de negócio para usuários
"""
from sqlalchemy.orm import Session
from app.models.usuario import Usuario
from app.repositories.usuario_repository import UsuarioRepository
from app.schemas.usuario import UsuarioCreate, UsuarioUpdate
from app.core.security import (
    hash_password,
    verify_password,
    create_access_token,
    generate_verification_token
)
from app.utils.email import enviar_email_verificacao, enviar_email_reset_senha
from datetime import datetime, timedelta
from typing import Optional
from fastapi import HTTPException, status
import logging

logger = logging.getLogger(__name__)


class UsuarioService:
    """Service para lógica de negócio de usuários"""

    def __init__(self, db: Session):
        self.repository = UsuarioRepository(db)

    async def registrar_usuario(self, usuario_data: UsuarioCreate) -> Usuario:
        """
        Registra um novo usuário

        Args:
            usuario_data: Dados do usuário a ser criado

        Returns:
            Usuário criado

        Raises:
            HTTPException: Se email ou CPF/CNPJ já existir
        """
        # Verificar se email já existe
        usuario_existente = self.repository.buscar_por_email(usuario_data.email)
        if usuario_existente:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Email já cadastrado"
            )

        # Verificar se CPF/CNPJ já existe (se fornecido)
        if usuario_data.cpf_cnpj:
            cpf_cnpj_existente = self.repository.buscar_por_cpf_cnpj(usuario_data.cpf_cnpj)
            if cpf_cnpj_existente:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="CPF/CNPJ já cadastrado"
                )

        # Criar usuário
        novo_usuario = Usuario(
            email=usuario_data.email,
            senha=hash_password(usuario_data.senha),
            nome=usuario_data.nome,
            telefone=usuario_data.telefone,
            cpf_cnpj=usuario_data.cpf_cnpj,
            email_verificado=False,
            ativo=True
        )

        # Gerar token de verificação
        token_verificacao = generate_verification_token()
        novo_usuario.token_verificacao = token_verificacao
        novo_usuario.token_verificacao_expira = datetime.utcnow() + timedelta(days=7)

        # Salvar no banco
        usuario_criado = self.repository.criar(novo_usuario)

        # Enviar email de verificação (async, não bloqueia)
        try:
            await enviar_email_verificacao(
                email=usuario_criado.email,
                nome=usuario_criado.nome,
                token=token_verificacao
            )
            logger.info(f"Email de verificação enviado para {usuario_criado.email}")
        except Exception as e:
            logger.error(f"Erro ao enviar email de verificação: {e}")
            # Não falha o cadastro se o email não for enviado

        return usuario_criado

    def autenticar(self, email: str, senha: str) -> Optional[Usuario]:
        """
        Autentica um usuário

        Args:
            email: Email do usuário
            senha: Senha em texto plano

        Returns:
            Usuário se autenticado, None caso contrário
        """
        usuario = self.repository.buscar_por_email(email)

        if not usuario:
            return None

        if not verify_password(senha, usuario.senha):
            return None

        if not usuario.ativo:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Usuário desativado"
            )

        return usuario

    def gerar_token_acesso(self, usuario: Usuario) -> str:
        """
        Gera token JWT de acesso

        Args:
            usuario: Usuário autenticado

        Returns:
            Token JWT
        """
        data = {
            "sub": usuario.id,
            "email": usuario.email,
            "nome": usuario.nome
        }
        return create_access_token(data)

    async def verificar_email(self, token: str) -> Usuario:
        """
        Verifica email do usuário

        Args:
            token: Token de verificação

        Returns:
            Usuário verificado

        Raises:
            HTTPException: Se token inválido ou expirado
        """
        usuario = self.repository.buscar_por_token_verificacao(token)

        if not usuario:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Token inválido ou expirado"
            )

        return self.repository.verificar_email(usuario)

    def buscar_usuario_por_id(self, usuario_id: str) -> Usuario:
        """
        Busca usuário por ID

        Args:
            usuario_id: ID do usuário

        Returns:
            Usuário encontrado

        Raises:
            HTTPException: Se usuário não encontrado
        """
        usuario = self.repository.buscar_por_id(usuario_id)

        if not usuario:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Usuário não encontrado"
            )

        return usuario

    def atualizar_usuario(self, usuario_id: str, usuario_data: UsuarioUpdate) -> Usuario:
        """
        Atualiza dados do usuário

        Args:
            usuario_id: ID do usuário
            usuario_data: Dados a serem atualizados

        Returns:
            Usuário atualizado

        Raises:
            HTTPException: Se usuário não encontrado
        """
        usuario = self.buscar_usuario_por_id(usuario_id)

        # Atualizar campos fornecidos
        if usuario_data.nome is not None:
            usuario.nome = usuario_data.nome
        if usuario_data.telefone is not None:
            usuario.telefone = usuario_data.telefone
        if usuario_data.cpf_cnpj is not None:
            # Verificar se CPF/CNPJ já existe em outro usuário
            cpf_existente = self.repository.buscar_por_cpf_cnpj(usuario_data.cpf_cnpj)
            if cpf_existente and cpf_existente.id != usuario_id:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="CPF/CNPJ já cadastrado para outro usuário"
                )
            usuario.cpf_cnpj = usuario_data.cpf_cnpj

        return self.repository.atualizar(usuario)

    def alterar_senha(self, usuario_id: str, senha_atual: str, senha_nova: str) -> Usuario:
        """
        Altera senha do usuário

        Args:
            usuario_id: ID do usuário
            senha_atual: Senha atual
            senha_nova: Nova senha

        Returns:
            Usuário atualizado

        Raises:
            HTTPException: Se senha atual incorreta
        """
        usuario = self.buscar_usuario_por_id(usuario_id)

        # Verificar senha atual
        if not verify_password(senha_atual, usuario.senha):
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Senha atual incorreta"
            )

        # Atualizar senha
        usuario.senha = hash_password(senha_nova)
        return self.repository.atualizar(usuario)

    async def solicitar_reset_senha(self, email: str) -> bool:
        """
        Solicita reset de senha

        Args:
            email: Email do usuário

        Returns:
            True se email enviado com sucesso
        """
        usuario = self.repository.buscar_por_email(email)

        # Não revelar se o email existe ou não (segurança)
        if not usuario:
            logger.warning(f"Tentativa de reset para email não cadastrado: {email}")
            return True  # Retorna true para não revelar

        # Gerar token de reset
        token_reset = generate_verification_token()
        usuario.token_reset_senha = token_reset
        usuario.token_reset_senha_expira = datetime.utcnow() + timedelta(hours=1)

        self.repository.atualizar(usuario)

        # Enviar email
        try:
            await enviar_email_reset_senha(
                email=usuario.email,
                nome=usuario.nome,
                token=token_reset
            )
            logger.info(f"Email de reset de senha enviado para {usuario.email}")
            return True
        except Exception as e:
            logger.error(f"Erro ao enviar email de reset: {e}")
            raise HTTPException(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                detail="Erro ao enviar email de reset"
            )

    def resetar_senha(self, token: str, senha_nova: str) -> Usuario:
        """
        Reseta senha usando token

        Args:
            token: Token de reset
            senha_nova: Nova senha

        Returns:
            Usuário atualizado

        Raises:
            HTTPException: Se token inválido
        """
        usuario = self.repository.buscar_por_token_reset(token)

        if not usuario:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Token inválido ou expirado"
            )

        # Atualizar senha e limpar token
        usuario.senha = hash_password(senha_nova)
        usuario.token_reset_senha = None
        usuario.token_reset_senha_expira = None

        return self.repository.atualizar(usuario)
