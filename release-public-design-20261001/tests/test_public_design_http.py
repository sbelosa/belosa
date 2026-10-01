#!/usr/bin/env python3
"""Read-only public presentation regression. Never follows purchase links or submits messages."""
from pathlib import Path
from urllib.request import urlopen,Request
from urllib.parse import urlsplit,parse_qs
from bs4 import BeautifulSoup
import json,subprocess,concurrent.futures,sys,time
ROOT=Path(__file__).resolve().parents[1]
BASE=sys.argv[1].rstrip('/') if len(sys.argv)>1 else 'http://localhost:8095'
assert BASE in ('http://localhost:8095','https://forevercard.club')
OUT=ROOT/'local/public-design-20261001'
if BASE.startswith('https'):
 inventory=json.loads((OUT/'content-inventory.json').read_text())
else:
 code='''define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require 'app/init.php';if(DATABASE_NAME!=='fcc_partner_local')exit(1);$all=[];foreach(['blog_posts'=>'blog_post_id','pages'=>'page_id','blog_posts_categories'=>'blog_posts_category_id','pages_categories'=>'pages_category_id'] as $t=>$id){$where=in_array($t,['blog_posts','pages'])?' WHERE is_published=1':'';$all[$t]=['published'=>fcc_partner_rows('SELECT `'.$id.'` id,url,language FROM `'.$t.'`'.$where)];}echo json_encode($all);'''
 inventory=json.loads(subprocess.check_output(['docker','compose','-f','compose.partner.yml','exec','-T','app','php','-r',code],cwd=ROOT))
langs={'Hrvatski':'hr','english':'en','Slovenščina':'sl','Deutsch':'de','Español':'es','Srpski':'sr','Shqip':'sq','Crnogorski':'cnr','Français':'fr'}
def fetch(path,cookie=''):
 with urlopen(Request(BASE+path,headers={'User-Agent':'FCC-design-verification','Cookie':cookie}),timeout=40) as r:
  html=r.read().decode();s=BeautifulSoup(html,'html.parser')
  assert r.status==200,path
  assert not any(x in html for x in ['Fatal error','Uncaught Error','Warning:','Internal server error']),path+' server error'
  return s,r.url
checks=[]
def verify(path,selector=None,cookie=''):
 s,url=fetch(path,cookie)
 assert s.select_one('body[data-fcc-public-design="20261001"]'),path+' shared public shell'
 assert s.select_one('.fcw-header') and s.select_one('.fcw-footer'),path+' public navigation'
 assert s.select_one('link[href*="fcc-public.css?v="]'),path+' versioned public CSS'
 assert not s.select('a[href*="design=classic"]:not(.fcw-skip)'),path+' retired design link'
 assert len(s.select('h1'))==1,path+' single page title'
 if selector:assert s.select_one(selector),path+' expected layout'
 schemas=[json.loads(n.get_text()) for n in s.select('script[type="application/ld+json"]')]
 if s.select_one('[data-fcc-public-list],.fl-discovery'):
  graph=next(g['@graph'] for g in schemas if '@graph' in g)
  collection=next(g for g in graph if g['@type']=='CollectionPage')
  assert collection['mainEntity']['numberOfItems']==len(s.select('.fl-grid article')),path+' visible structured list'
  assert any(g['@type']=='BreadcrumbList' for g in graph),path+' structured breadcrumbs'
  assert all('ref=' not in row['url'] for row in collection['mainEntity']['itemListElement']),path+' canonical item identities'
 checks.append(path)
 return s
for locale in ('en','hr','sl','de','es'):
 s=verify('/'+locale+'/', '[data-fcc-public-home]')
 assert s.html['lang'].split('-')[0]==locale,locale+' page language'
 s=verify('/'+locale+'/blog?design=classic','.fl-discovery')
 assert len(s.select('[data-fl-product]'))>10,locale+' catalog available to guests'
 s=verify('/'+locale+'/blog?view=articles','[data-fcc-public-list]')
 pagination=s.select('.fcw-pagination a[href]')
 assert pagination and all(parse_qs(urlsplit(a['href']).query).get('view')==['articles'] for a in pagination),locale+' archive pagination retains view'
 verify('/'+locale+'/blog?view=articles&page=2','[data-fcc-public-list]')
 verify('/'+locale+'/blog?search=aloe','[data-fcc-public-list]')
 s=verify('/'+locale+'/blog?search=zzzzNoMatch27492','[data-fcc-public-list]');assert s.select_one('.fcw-empty'),locale+' helpful empty results'
 verify('/'+locale+'/pages')
for p in ['/featured-apps','/recommended-sponsors','/page/contact']:
 verify(p)
# Walk every existing published content URL; suppress page-view counters per request.
paths=[]
for table,prefix,cookie_prefix in [('blog_posts','blog','blog_post_view_'),('pages','page','page_view_'),('blog_posts_categories','blog/category',''),('pages_categories','pages','')]:
 for row in inventory[table]['published']:
  if row['language'] not in langs or '://' in row['url']:continue
  p='/'+langs[row['language']]+'/'+prefix+'/'+row['url']
  cookie=cookie_prefix+str(row['id'])+'=1' if cookie_prefix else ''
  paths.append((p,cookie))
def inspect(args):
 p,cookie=args
 try:
  s=verify(p,cookie=cookie)
  if BASE.startswith('https'):time.sleep(.15)
  return {'path':p,'ok':True}
 except Exception as e:return {'path':p,'ok':False,'error':str(e)}
results=[]
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
 for result in pool.map(inspect,paths):
  results.append(result)
  if len(results)%100==0:print('Checked',len(results),'of',len(paths),'published routes',flush=True)
failed=[x for x in results if not x['ok']]
report={'status':'FAIL' if failed else 'PASS','base':BASE,'route_count':len(checks),'published_content_routes':len(paths),'failed':failed}
(OUT/('production-http.json' if BASE.startswith('https') else 'local-http.json')).write_text(json.dumps(report,indent=2,ensure_ascii=False))
print(json.dumps(report,ensure_ascii=False));sys.exit(bool(failed))
