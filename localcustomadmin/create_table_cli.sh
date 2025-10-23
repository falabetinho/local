#!/bin/bash
# Script para criar a tabela de sincronização WordPress usando a CLI do Moodle
# 
# USO:
# 1. Dê permissão de execução: chmod +x create_table_cli.sh
# 2. Execute: ./create_table_cli.sh
#
# OU execute diretamente o comando PHP abaixo:
# php admin/cli/install_database.php --component=local_localcustomadmin

echo "=========================================="
echo "Criação da tabela de sincronização WordPress"
echo "=========================================="
echo ""

# Detectar o diretório raiz do Moodle
MOODLE_DIR="/home/betinho/projetos/moodle"

if [ ! -d "$MOODLE_DIR" ]; then
    echo "Erro: Diretório do Moodle não encontrado em $MOODLE_DIR"
    echo "Por favor, edite este script e defina o caminho correto."
    exit 1
fi

echo "Diretório do Moodle: $MOODLE_DIR"
echo ""

# Navegar até o diretório do Moodle
cd "$MOODLE_DIR" || exit 1

echo "Executando a atualização do banco de dados..."
echo ""

# Executar o upgrade via CLI
php admin/cli/upgrade.php --non-interactive

echo ""
echo "=========================================="
echo "Processo concluído!"
echo "=========================================="
echo ""
echo "A tabela mdl_local_customadmin_wp_mapping deve ter sido criada."
echo ""
echo "Para verificar, execute:"
echo "mysql -u seu_usuario -p seu_banco -e 'DESCRIBE mdl_local_customadmin_wp_mapping;'"
