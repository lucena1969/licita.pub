-- Script para remover a constraint de foreign key da tabela atas_registro_preco
-- Isso permite inserir ATAs sem precisar ter o órgão cadastrado previamente

USE u590097272_licitapub;

-- Remover a constraint existente
ALTER TABLE `atas_registro_preco` DROP FOREIGN KEY `fk_atas_orgao`;

-- Opcional: Tornar o campo nullable
ALTER TABLE `atas_registro_preco` MODIFY COLUMN `orgao_gerenciador_id` VARCHAR(50) NULL COMMENT 'ID do órgão gerenciador (opcional)';

SELECT 'Constraint removida com sucesso!' as status;
