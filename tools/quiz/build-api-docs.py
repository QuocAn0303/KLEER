"""Rebuild native JSON API artifacts from the shared checked-in schemas."""
from copy import deepcopy
from pathlib import Path
import json

root = Path(__file__).resolve().parents[2]
target = root / 'docs/api'
target.mkdir(parents=True, exist_ok=True)
schemas = root / 'wp-content/plugins/kleer-plugin/schemas'
request_schema = json.loads((schemas / 'quiz-submission.schema.json').read_text(encoding='utf-8'))
response_schema = json.loads((schemas / 'quiz-submission-response.schema.json').read_text(encoding='utf-8'))
example = json.loads(Path(__file__).with_name('valid-submission.json').read_text(encoding='utf-8'))
error_schema = {
    'type': 'object', 'required': ['code', 'message', 'data'],
    'properties': {'code': {'type': 'string'}, 'message': {'type': 'string'},
                   'data': {'type': 'object', 'properties': {'status': {'type': 'integer'},
                            'missing_steps': {'type': 'array', 'items': {'type': 'string'}},
                            'errors': {'type': 'array', 'items': {'type': 'object'}}}}},
}
operation = {
    'summary': 'Calculate a personalized routine from the five quiz answers',
    'description': 'Public stateless endpoint. Default version v1 requires five known single-choice answers and policy_accepted=true. version=v4 preserves the upstream six-question draft and its missing_steps/warnings contract. Production uses real WooCommerce IDs; missing WooCommerce returns 503 for both versions. For v1, incomplete safe inventory also returns 503. Business rules await G3 approval.',
    'parameters': [
        {'name': 'version', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'enum': ['v1','v4'], 'default': 'v1'}, 'description': 'v1: legacy five questions; v4: upstream six-question draft. Query parameter, never a JSON body property.'},
        {'name': 'Origin', 'in': 'header', 'required': False, 'schema': {'type': 'string'}, 'description': 'Omit for server clients, or use same-origin/approved CORS origin.'},
        {'name': 'X-WP-Nonce', 'in': 'header', 'required': False, 'schema': {'type': 'string'}, 'description': 'Required for authenticated browser clients; checked whenever supplied. Action wp_rest.'},
    ],
    'requestBody': {'required': True, 'content': {'application/json': {'schema': {'$ref': '#/components/schemas/QuizSubmission'}, 'example': example}}},
    'responses': {
        str(status): {'description': description, 'content': {'application/json': {'schema': {'$ref': '#/components/schemas/' + ('QuizResponse' if status == 200 else 'Error')}}}}
        for status, description in [
            (200, 'Complete ordered 3/4-step routine. Sensitive profile code is <primary_type>_sensitive; other profiles use <primary_type>_<concern>.'),
            (400, 'kleer_invalid_payload (schema/answers, with JSON Pointer), kleer_consent_required or kleer_invalid_version.'),
            (403, 'kleer_cors_forbidden or rest_cookie_invalid_nonce.'),
            (503, 'kleer_woocommerce_unavailable, kleer_catalog_incomplete (missing_steps), or kleer_catalog_unavailable. Do not render a partial routine.'),
            (500, 'kleer_quiz_engine_error. Generic message, no internal exception or database details.'),
        ]
    },
}
spec = {
    'openapi': '3.1.0',
    'info': {'title': 'KLEER Routine API', 'version': '0.2.0',
             'description': 'Implementation contract prepared by Bui Hieu Nhan. Business G3 approval and successful catalog/performance acceptance are pending. See README.md for plain-permalink URLs, tags, fallback, integration and limits.'},
    'servers': [{'url': 'http://localhost:8081/wp-json/kleer/v1', 'description': 'Local example with pretty permalinks. Change host for your environment.'}],
    'paths': {},
    'components': {'schemas': {'QuizSubmission': request_schema, 'QuizResponse': response_schema, 'Error': error_schema}},
}
for path, operation_id in [('/recommend', 'recommendRoutine'), ('/skin-quiz', 'submitSkinQuiz')]:
    spec['paths'][path] = {'post': dict(deepcopy(operation), operationId=operation_id)}
(target / 'openapi.json').write_text(json.dumps(spec, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

tests = []
def add_case(name, body, status, checks=(), headers=(), route='recommend'):
    script = [f"pm.test('HTTP {status}', function () {{ pm.response.to.have.status({status}); }});"] + list(checks)
    tests.append({'name': name, 'request': {'method': 'POST',
        'header': [{'key': 'Content-Type', 'value': 'application/json'}] + [{'key': k, 'value': v} for k,v in headers],
        'url': '{{base_url}}/?rest_route=/kleer/v1/' + route,
        'body': {'mode': 'raw', 'raw': json.dumps(body, ensure_ascii=False, indent=2), 'options': {'raw': {'language': 'json'}}}},
        'event': [{'listen': 'test', 'script': {'type': 'text/javascript', 'exec': script}}]})
success_checks = lambda n: [
    "const result = pm.response.json();",
    "pm.test('status ok', function () { pm.expect(result.status).to.eql('ok'); });",
    f"pm.test('{n} ordered steps', function () {{ pm.expect(result.data.recommended_products.map(p => p.routine_step)).to.eql({json.dumps(['cleanser','moisturizer','sunscreen'] if n == 3 else ['cleanser','treatment','moisturizer','sunscreen'])}); }});",
    "pm.test('Below 300 ms', function () { pm.expect(pm.response.responseTime).to.be.below(300); });",
]
add_case('01 Oily acne four steps requires real inventory', example, 200, success_checks(4))
minimal = deepcopy(example)
for answer, value in zip(minimal['answers'], ['tight_dry','flaky_rough','very_often','dehydration','minimal']): answer['answer'] = value
add_case('02 Dry sensitive three steps alias', minimal, 200, success_checks(3) + ["pm.test('dry_sensitive', function () { pm.expect(pm.response.json().data.skin_profile.code).to.eql('dry_sensitive'); });"], route='skin-quiz')
bad = deepcopy(example); bad['session_id'] = 'invalid'
add_case('03 Invalid UUID', bad, 400)
bad = deepcopy(example); bad['answers'][1] = deepcopy(bad['answers'][0])
add_case('04 Duplicate question', bad, 400)
bad = deepcopy(example); bad['consent']['policy_accepted'] = False
add_case('05 Missing consent', bad, 400)
add_case('06 Forbidden origin', example, 403, headers=[('Origin','https://unapproved.invalid')])
add_case('07 Invalid nonce', example, 403, headers=[('X-WP-Nonce','not-a-valid-nonce')])
collection = {'info': {'name': 'KLEER Routine API by Nhan', 'schema': 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                       'description': 'Cases 01-02 require WooCommerce and a real tagged fallback catalog. Do not treat their 503 failures as passed tests. No request creates or changes products. Run shortage cases manually on an isolated inventory copy; never alter shared stock for testing.'},
              'variable': [{'key': 'base_url', 'value': 'http://localhost:8081'}], 'item': tests}
(target / 'KLEER-Routine.postman_collection.json').write_text(json.dumps(collection, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print('Built OpenAPI 3.1 contract and 7-request Postman collection from shared schemas')
