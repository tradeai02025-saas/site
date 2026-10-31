"""Offline schedule tests: temporary state, simulated clock, no send/start."""
import tempfile
import unittest
from datetime import datetime, timedelta, timezone
from pathlib import Path
from unittest.mock import Mock, patch

import server
from test_email_runner_safety import email_runner as real_email_runner
import whatsapp_runner as real_wa_runner


SP = timezone(timedelta(hours=-3), 'America/Sao_Paulo')


class ScheduleRuntimeTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='schedule-test-')
        self.path_patch = patch.object(server, 'SCHEDULE_STATE_PATH', Path(self.temp.name) / 'state.json')
        self.path_patch.start()
        self.close_patch = patch.object(server, '_close_scheduled_runs')
        self.close_patch.start()
        self.config = {
            'schedule': {'enabled': True, 'weekdays': [0, 1, 2, 3, 4],
                         'times': {'email': '08:00', 'whatsapp': '08:02', 'instagram': '08:05'}},
            'daily_targets': {'email': 60, 'whatsapp': 30,
                              'instagram_5050': 44, 'instagram_5051': 50},
        }

    def tearDown(self):
        self.close_patch.stop()
        self.path_patch.stop()
        self.temp.cleanup()

    def tick(self, now, dispatcher):
        server._scheduler_tick(now, self.config, dispatcher, clock_fn=lambda: now)

    def test_boundaries_weekends_and_pause(self):
        for hour, minute, expected in ((7, 59, False), (8, 0, True),
                                       (17, 59, True), (18, 0, False)):
            self.assertEqual(server._schedule_window_open(
                datetime(2026, 10, 26, hour, minute, tzinfo=SP)), expected)
        self.assertFalse(server._schedule_window_open(datetime(2026, 10, 25, 12, tzinfo=SP)))
        self.assertFalse(server._schedule_window_open(datetime(2026, 10, 31, 12, tzinfo=SP)))
        self.config['schedule']['enabled'] = False
        dispatch = Mock(return_value={'status': 'ok'})
        self.tick(datetime(2026, 10, 26, 8, 5, tzinfo=SP), dispatch)
        dispatch.assert_not_called()

    def test_four_independent_channels_once_after_restart(self):
        now = datetime(2026, 10, 26, 8, 5, tzinfo=SP)
        dispatch = Mock(return_value={'status': 'ok'})
        self.tick(now, dispatch)
        self.assertEqual([call.args[0] for call in dispatch.call_args_list],
                         ['email', 'whatsapp', 'instagram_5050', 'instagram_5051'])
        self.tick(now + timedelta(minutes=1), dispatch)
        self.assertEqual(dispatch.call_count, 4)

    def test_legacy_official_key_blocks_official_replay_only(self):
        server._write_schedule_state({'2026-10-26:instagram': {'state': 'error'}})
        dispatch = Mock(return_value={'status': 'ok'})
        self.tick(datetime(2026, 10, 26, 8, 5, tzinfo=SP), dispatch)
        self.assertIn('instagram_5050', [call.args[0] for call in dispatch.call_args_list])
        self.assertNotIn('instagram_5051', [call.args[0] for call in dispatch.call_args_list])

    def test_safe_deferred_retry_and_uncertain_no_replay(self):
        now = datetime(2026, 10, 26, 8, 0, tzinfo=SP)
        self.config['schedule']['times'].update(whatsapp='17:59', instagram='17:59')
        dispatch = Mock(side_effect=[{'status': 'deferred', 'reason': 'offline'},
                                     {'status': 'ok'}])
        self.tick(now, dispatch)
        self.tick(now + timedelta(seconds=30), dispatch)
        self.assertEqual(dispatch.call_count, 1)
        self.tick(now + timedelta(minutes=1), dispatch)
        self.assertEqual(dispatch.call_count, 2)
        self.tick(now + timedelta(minutes=2), dispatch)
        self.assertEqual(dispatch.call_count, 2)

        server._write_schedule_state({})
        failing = Mock(side_effect=TimeoutError('launch outcome unknown'))
        self.tick(now, failing)
        self.tick(now + timedelta(minutes=1), failing)
        self.assertEqual(failing.call_count, 1)
        self.assertEqual(server._read_schedule_state()['2026-10-26:email']['state'], 'uncertain')

    def test_absolute_18_cutoff_even_with_late_start_time(self):
        self.config['schedule']['times'] = {key: '17:59' for key in ('email', 'whatsapp', 'instagram')}
        dispatch = Mock(return_value={'status': 'ok'})
        self.tick(datetime(2026, 10, 26, 18, 0, tzinfo=SP), dispatch)
        dispatch.assert_not_called()

    def test_blocked_ig_runtime_is_recorded_without_retry_or_start(self):
        now = datetime(2026, 10, 26, 8, 5, tzinfo=SP)
        dispatch = Mock(return_value={'status': 'blocked', 'reason': 'missing recipient guard'})
        self.tick(now, dispatch)
        self.tick(now + timedelta(minutes=1), dispatch)
        self.assertEqual(dispatch.call_count, 4)
        state = server._read_schedule_state()
        self.assertEqual(state['2026-10-26:instagram_5050']['state'], 'blocked')
        self.assertEqual(state['2026-10-26:instagram_5051']['state'], 'blocked')

    def test_recoverable_prestart_ig_blocks_retry_after_runtime_fix_without_replaying_other_channels(self):
        day = '2026-10-28'
        reason = 'Instagram: runtime sem prote\u00e7\u00e3o contra interfer\u00eancia durante a digita\u00e7\u00e3o.'
        server._write_schedule_state({
            f'{day}:email': {'state': 'started', 'attempts': 1, 'result': {'pid': 11}},
            f'{day}:whatsapp': {'state': 'started', 'attempts': 1, 'result': {'pid': 12}},
            f'{day}:instagram_5050': {'state': 'blocked', 'attempts': 1, 'reason': reason},
            f'{day}:instagram_5051': {'state': 'blocked', 'attempts': 1, 'reason': reason},
        })
        now = datetime(2026, 10, 28, 8, 40, tzinfo=SP)
        dispatch = Mock(return_value={'status': 'started', 'pid': 99})
        self.tick(now, dispatch)
        self.assertEqual([call.args[0] for call in dispatch.call_args_list],
                         ['instagram_5050', 'instagram_5051'])
        state = server._read_schedule_state()
        for event in ('instagram_5050', 'instagram_5051'):
            self.assertEqual(state[f'{day}:{event}']['state'], 'started')
            self.assertEqual(state[f'{day}:{event}']['attempts'], 2)
        self.tick(now + timedelta(minutes=1), dispatch)
        self.assertEqual(dispatch.call_count, 2)

    def test_non_guard_block_and_uncertain_state_never_retry(self):
        day = '2026-10-28'
        server._write_schedule_state({
            f'{day}:email': {'state': 'started', 'attempts': 1, 'result': {'pid': 11}},
            f'{day}:whatsapp': {'state': 'started', 'attempts': 1, 'result': {'pid': 12}},
            f'{day}:instagram_5050': {'state': 'blocked', 'attempts': 1,
                                      'reason': 'Nenhum contato elegivel.'},
            f'{day}:instagram_5051': {'state': 'uncertain', 'attempts': 1,
                                      'error': 'resultado desconhecido'},
        })
        dispatch = Mock(return_value={'status': 'started', 'pid': 99})
        self.tick(datetime(2026, 10, 28, 8, 40, tzinfo=SP), dispatch)
        dispatch.assert_not_called()

    def test_scheduled_ig_missing_runtime_guard_never_reads_status_or_starts(self):
        config = self.config
        with patch.object(server, '_instagram_runtime_contract', side_effect=RuntimeError('missing recipient guard')), \
             patch.object(server, '_local_json') as local_json:
            result = server._dispatch_scheduled('instagram_5051', config)
        self.assertEqual(result['status'], 'blocked')
        self.assertIn('missing recipient guard', result['reason'])
        local_json.assert_not_called()

    def test_child_runners_block_before_any_send_outside_window(self):
        with patch.object(real_email_runner, '_scheduled_window_open', return_value=False), \
             patch.object(real_email_runner.smtplib, 'SMTP_SSL') as smtp:
            with self.assertRaisesRegex(RuntimeError, 'window is closed'):
                real_email_runner.run(limit=1, scheduled=True)
            smtp.assert_not_called()
        with patch.object(real_wa_runner, '_scheduled_window_open', return_value=False), \
             patch.object(real_wa_runner.engine, 'send_message') as send:
            with self.assertRaisesRegex(RuntimeError, 'window is closed'):
                real_wa_runner.run(limit=1, scheduled=True)
            send.assert_not_called()

    def test_email_wait_checks_schedule_each_second(self):
        checks = iter([True, False])
        with patch.object(real_email_runner.smtplib, 'SMTP_SSL') as smtp:
            result = real_email_runner._wait_between_recipients(
                10, stop_path=Path(self.temp.name) / 'no-stop',
                sleep_fn=lambda _: None, can_continue=lambda: next(checks, False))
            self.assertFalse(result)
            smtp.assert_not_called()


if __name__ == '__main__':
    unittest.main()
