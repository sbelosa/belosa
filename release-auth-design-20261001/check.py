from pathlib import Path
from bs4 import BeautifulSoup
from urllib.request import Request,urlopen
from urllib.error import HTTPError
import json,sys,re,hashlib
base=sys.argv[1] if len(sys.argv)>1 else 'http://localhost:8095'
assert base in ('http://localhost:8095','https://forevercard.club')
b=Path('local/auth-design-20261001');checks=[];disabled=[]
for lang in ('en','hr','sl','de','es'):
 for route in ('login','register','lost-password','resend-activation','sent-activation'):
  path='/'+lang+'/'+route
  try:r=urlopen(Request(base+path,headers={'Cookie':'theme_style=dark'}),timeout=30)
  except HTTPError as e:
   if e.code==404 and base.startswith('http:') and route in ('resend-activation','sent-activation'):
    disabled.append(path);continue
   raise
  html=r.read().decode();s=BeautifulSoup(html,'html.parser');assert r.status==200 and s.select_one('body[data-fcc-auth-design="20261001"][data-theme-style="light"]'),path
  assert s.select_one('link[href*="fcc-auth.css?v="]') and s.select_one('link[href*="fcc-public.css?v="]'),path
  assert 'bootstrap-dark' not in html and not s.select('link[href*="fcc-partner-premium.css"]'),path
  assert len(s.select('h1'))==1 and not any(x in html for x in ('Fatal error','Warning:','Internal server error')),path
  if route=='register':
   x=s.select_one('[name="meta_foreverId"]');assert x['pattern']=='[0-9]{12}' and x.has_attr('required')
   assert s.select_one('[name="registration_website"]') and s.select_one('[name="registration_token"]')
   assert s.select_one('img[src^="data:image/png;base64,"]')
  checks.append(path)
if base.startswith('http:'):
 for route in ('login','register'):
  old=(b/'before/themes/altum/views'/route/'index.php').read_text();new=(Path('themes/altum/views')/route/'index.php').read_text()
  old=re.sub(r'<\?.*?\?>',lambda m:hashlib.sha256(m[0].encode()).hexdigest(),old,flags=re.S)
  new=re.sub(r'<\?.*?\?>',lambda m:hashlib.sha256(m[0].encode()).hexdigest(),new,flags=re.S)
  for tag in ('input','select','button'):
   assert re.findall('<'+tag+r'\b[^>]*>',old,re.S)==re.findall('<'+tag+r'\b[^>]*>',new,re.S),route+' '+tag+' unchanged'
 checks.append('Original form fields, security attributes and action buttons unchanged')
 exec(Path('scripts/test_partner_update_http.py').read_text().split('checks=[]')[0])
 for email in ('suradnik@fcc.test','admin@fcc.test'):
  c=Client(email);assert '/partner' in c.url;checks.append('Existing local login '+email)
(b/('local-http.json' if base.startswith('http:') else 'production-http.json')).write_text(json.dumps({'status':'PASS','checks':checks,'disabled_local_routes':disabled},indent=2));print('PASS',len(checks),'targeted authentication checks')
