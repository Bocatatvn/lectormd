#!/usr/bin/env bash
set -euo pipefail

BASE="http://localhost:8080"
PASSED=0
FAILED=0

pass() { PASSED=$((PASSED + 1)); }
fail() { echo "FAIL: $1"; FAILED=$((FAILED + 1)); }

run_py() { python3 -c "$1" 2>/dev/null && pass || fail "$2"; }

echo "══════════════════════════════════════"
echo "  API Integration Tests"
echo "══════════════════════════════════════"

# ── GET /api/projects ──
echo ""
echo "── /api/projects ──"
run_py "
import json, urllib.request
d = json.loads(urllib.request.urlopen('$BASE/api/projects').read())
assert isinstance(d, list)
assert len(d) >= 2
ids = [p['id'] for p in d]
assert 'lectormd' in ids
assert 'secret' in ids
print(f'  Projects: {len(d)} found, ids={ids}')
" "GET /api/projects"

# ── GET /api/projects/lectormd/files ──
echo ""
echo "── GET /api/projects/lectormd/files ──"
run_py "
import json, urllib.request
d = json.loads(urllib.request.urlopen('$BASE/api/projects/lectormd/files').read())
assert 'files' in d
assert len(d['files']) >= 3
paths = [f['path'] for f in d['files']]
assert any('Index' in p for p in paths)
assert any('Arquitectura' in p for p in paths)
print(f'  Files: {len(d[\"files\"])} found')
" "GET lectormd files"

# ── GET file with space in name ──
echo ""
echo "── GET file with spaces ──"
run_py "
import json, urllib.request
url = '$BASE/api/projects/lectormd/files/02%20Arquitectura.md'
d = json.loads(urllib.request.urlopen(url).read())
assert d['path'] == '02 Arquitectura.md'
assert 'html' in d
assert d['metadata'].get('title') == 'Arquitectura'
print(f'  Loaded: {d[\"path\"]}')
print(f'  Title:  {d[\"metadata\"].get(\"title\")}')
" "GET file with spaces"

# ── GET non-existent file → 404 ──
echo ""
echo "── GET non-existent file ──"
code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/projects/lectormd/files/no-existe.md")
if [ "$code" = "404" ]; then pass; echo "  404 OK"; else fail "Expected 404, got $code"; fi

# ── GET locked project without token → 403 ──
echo ""
echo "── GET locked project (no token) ──"
code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/projects/secret/files")
if [ "$code" = "403" ]; then pass; echo "  403 OK"; else fail "Expected 403, got $code"; fi

# ── GET locked project WITH token → 200 ──
echo ""
echo "── GET locked project (with token) ──"
run_py "
import json, urllib.request
req = urllib.request.Request('$BASE/api/projects/secret/files')
req.add_header('Cookie', 'token_secret=secret123')
d = json.loads(urllib.request.urlopen(req).read())
assert 'files' in d
print(f'  Unlocked: {len(d[\"files\"])} files')
" "GET locked with token"

# ── POST unlock with correct token ──
echo ""
echo "── POST unlock (correct) ──"
run_py "
import json, urllib.request
data = json.dumps({'token': 'secret123'}).encode()
req = urllib.request.Request('$BASE/api/projects/secret/unlock', data=data, method='POST')
req.add_header('Content-Type', 'application/json')
d = json.loads(urllib.request.urlopen(req).read())
assert d.get('ok') == True
print('  Token validado')
" "POST unlock correct"

# ── POST unlock with wrong token ──
echo ""
echo "── POST unlock (incorrect) ──"
run_py "
import json, urllib.request
data = json.dumps({'token': 'wrong'}).encode()
req = urllib.request.Request('$BASE/api/projects/secret/unlock', data=data, method='POST')
req.add_header('Content-Type', 'application/json')
d = json.loads(urllib.request.urlopen(req).read())
assert d.get('ok') == False
assert 'error' in d
print('  Token rechazado')
" "POST unlock incorrect"

# ── SPA routing ──
echo ""
echo "── SPA routing (shareable URL) ──"
run_py "
import urllib.request
html = urllib.request.urlopen('$BASE/lectormd/02%20Arquitectura.md').read().decode()
assert 'app.js' in html
assert 'doc-viewer' in html
print(f'  SPA served: {len(html)} bytes')
" "SPA routing"

# ── Static assets ──
echo ""
echo "── Static assets (absolute paths) ──"
code_css=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/css/style.css")
code_js=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/js/app.js")
if [ "$code_css" = "200" ] && [ "$code_js" = "200" ]; then
  pass; echo "  CSS: $code_css, JS: $code_js"
else
  fail "CSS: $code_css, JS: $code_js"
fi

# ── Summary ──
echo ""
echo "══════════════════════════════════════"
echo "  Results"
echo "══════════════════════════════════════"
echo "  Passed: $PASSED"
echo "  Failed: $FAILED"
echo "══════════════════════════════════════"
exit $(( FAILED > 0 ? 1 : 0 ))
