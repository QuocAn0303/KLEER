"""Prevent a fast empty/error/wrong-version response from passing acceptance."""
import copy
import unittest
from benchmark import routine_valid


class BenchmarkValidation(unittest.TestCase):
    def setUp(self):
        self.body = {'status': 'ok', 'data': {'version': 'v4-draft',
            'routine_steps_selected': ['cleanser', 'serum'], 'missing_steps': [],
            'recommended_products': [{'id': 101, 'price': 100000, 'routine_step': 'cleanser'},
                                     {'id': 205, 'price': 200000, 'routine_step': 'serum'}]}}

    def test_complete_two_product_v4_is_valid(self):
        self.assertTrue(routine_valid(self.body, 'v4'))

    def test_wrong_version_empty_duplicate_and_wrong_steps_fail(self):
        for change in [lambda d: d.update(version='v1'), lambda d: d.update(recommended_products=[]),
                       lambda d: d['recommended_products'][1].update(id=101),
                       lambda d: d['recommended_products'][1].update(routine_step='toner'),
                       lambda d: d['recommended_products'][0].update(price=True),
                       lambda d: d.update(routine_steps_selected=[{}, 'serum'])]:
            body = copy.deepcopy(self.body)
            change(body['data'])
            with self.subTest(body=body):
                self.assertFalse(routine_valid(body, 'v4'))

    def test_missing_steps_need_explicit_acceptance_and_warning(self):
        data = self.body['data']
        data['routine_steps_selected'].append('moisturizer')
        data['missing_steps'] = ['moisturizer']
        data['warnings'] = ['Inventory gap']
        self.assertFalse(routine_valid(self.body, 'v4'))
        self.assertTrue(routine_valid(self.body, 'v4', allow_missing=True))
        data['warnings'] = []
        self.assertFalse(routine_valid(self.body, 'v4', allow_missing=True))

    def test_non_object_responses_fail(self):
        for body in [[], None, {'status': 'error'}, {'status': 'ok', 'data': []}]:
            self.assertFalse(routine_valid(body, 'v4'))


if __name__ == '__main__':
    unittest.main()
