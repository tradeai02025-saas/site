#!/usr/bin/env bash
# IArandú — Runner das Rotinas de Monitoramento
# Sexta-Feira / Atendimento & Agendamentos

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export PYTHONPATH="$DIR:$PYTHONPATH"

/usr/bin/python3 "$DIR/rotina_orquestrador.py" "$@"
