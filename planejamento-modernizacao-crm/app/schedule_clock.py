"""IANA Sao Paulo clock shared by the WSL CRM and its child runners."""
import os
import time
from datetime import datetime


ZONE = 'America/Sao_Paulo'

if os.name == 'nt':
    from zoneinfo import ZoneInfo
    _ZONEINFO = ZoneInfo(ZONE)
else:
    # Python 3.8 in WSL has no zoneinfo module. libc uses the installed IANA
    # tzdata; setting TZ once makes naive -> aware conversion deterministic.
    os.environ['TZ'] = ZONE
    time.tzset()
    _ZONEINFO = None


def sao_paulo_now():
    if _ZONEINFO is not None:
        return datetime.now(_ZONEINFO)
    return datetime.now().astimezone()


def sao_paulo_local(value):
    if value.tzinfo is None:
        raise ValueError('Scheduled clock must include a timezone.')
    return value.astimezone(_ZONEINFO)


def sao_paulo_at(day, time_text):
    naive = datetime.strptime(day.isoformat() + ' ' + time_text, '%Y-%m-%d %H:%M')
    if _ZONEINFO is not None:
        return naive.replace(tzinfo=_ZONEINFO)
    return naive.astimezone()
