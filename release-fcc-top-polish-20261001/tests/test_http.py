from pathlib import Path
import http.cookiejar,urllib.request,urllib.parse,urllib.error,re,json
BASE='http://localhost:8095'
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def call(self,path,data=None):
  req=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
  try:r=self.opener.open(req,timeout=20)
  except urllib.error.HTTPError as e:r=e
  self.status=r.code;self.url=r.url;self.headers=r.headers;self.body=r.read().decode();return self.body
 def login(self,email):self.call('/login');self.call('/login',{'email':email,'password':'FCC-Lokalno-2026!'})
def fields(body):return dict(re.findall(r'name="([^"<>]+)"[^>]*value="([^"<>]*)"',body))
checks=[]
def check(name,value):assert value,name;checks.append(name)
guest=Client();guest.call('/partner/top');check('Anonymous visitor must sign in','/login' in guest.url and 'ft-card' not in guest.body)
pro=Client();pro.login('admin@fcc.test');pro.call('/partner/top');check('PRO page loads',pro.status==200 and 'ft-hero' in pro.body)
check('Private response cannot be shared by caches','private' in pro.headers.get('Cache-Control','') and 'no-store' in pro.headers.get('Cache-Control',''))
for days in [7,30,60]:
 pro.call('/partner/top?days='+str(days)+'&month=2026-09-01');check('Selected window '+str(days),pro.status==200 and f'value="{days}" selected' in pro.body)
pro.call('/partner/settings');f=fields(pro.body);old={k:f.get(k) for k in ['version','recommendations_goal','invitations_goal','education_goal']};
pro.call('/partner/settings',{'action':'top_settings','token':'invalid',**old,'recommendations_goal':'919'});check('Invalid CSRF cannot save', 'value="919"' not in pro.body)
pro.call('/partner/settings');f=fields(pro.body);v=f.get('version');pro.call('/partner/settings',{'action':'top_settings','token':f['token'],'version':v,'recommendations_goal':'0','invitations_goal':'5','education_goal':'3'});check('Zero goal rejected','od 1 do 1000' in pro.body)
pro.call('/partner/settings');f=fields(pro.body);pro.call('/partner/settings',{'action':'top_settings','token':f['token'],'version':'9999999','recommendations_goal':'3','invitations_goal':'5','education_goal':'3'});check('Stale settings cannot overwrite newer changes','drugom prozoru' in pro.body)
pro.call('/partner/more');check('More links to Top', 'partner/top' in pro.body and 'FCC Top 10' in pro.body)
pro.call('/fcc-results');check('Existing click thresholds preserved',pro.status==200 and '15+' in pro.body and '50+' in pro.body)
print(json.dumps({'status':'passed','checks':len(checks),'details':checks},indent=2))
