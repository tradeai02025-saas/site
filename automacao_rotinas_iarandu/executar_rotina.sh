#!/usr/bin/env bash
# IArandú — Runner das Rotinas de Monitoramento
# Sexta-Feira / Atendimento & Agendamentos

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export PYTHONPATH="$DIR:$PYTHONPATH"

if [ -x "/home/agent7/.alfred-venv/bin/python3" ]; then
    PYTHON_BIN="/home/agent7/.alfred-venv/bin/python3"
else
    PYTHON_BIN="/usr/bin/python3"
fi

"$PYTHON_BIN" "$DIR/rotina_orquestrador.py" "$@"
