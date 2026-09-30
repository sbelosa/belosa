#!/usr/bin/env python3
from pathlib import Path
exec(Path(__file__).with_name('test_partner_update_http.py').read_text().split('checks=[]')[0])
def php(code):
 r=subprocess.run(['docker','compose','-f','compose.partner.yml','exec','-T','app','php','-r',"require 'local/sponsor-team-fixtures.php';"+code],check=True,text=True,capture_output=True);return json.loads(r.stdout) if r.stdout.strip() else None
r=php('echo json_encode(fcc_team_qa_create());');ids=list(r.values());a=r['sponsor'];m=r['member'];checks=[]
def check(label,ok):
 assert ok,label
 checks.append(label)
def cleanup():
 php('fcc_partner_query("DELETE FROM fcc_team_outreach WHERE actor_user_id IN ('+','.join(map(str,ids))+')");fcc_team_qa_cleanup('+json.dumps(ids)+');')
atexit.register(cleanup)
php('fcc_team_qa_assign('+str(m)+','+str(a)+',false,["team_phone"=>"+385990000002","team_contact_confirmed"=>1]);')
email=php('echo json_encode(fcc_partner_one("SELECT email FROM users WHERE user_id=?",['+str(a)+'])["email"]);');client=Client(email);path='/partner/team?member='+str(m)
client.get(path);token=client.fields['token'];html_page=client.body
check('Profile renders new composer',client.status==200 and 'data-team-outreach' in html_page and 'Pripremi s Coachom' in html_page)
hero=re.search(r'<section class="ft-sponsor">(.*?)</section>',html_page,re.S).group(1)
check('Primary contact card targets selected member, never sponsor','tel:+385990000002' in hero and '+385990000001' not in hero)
check('Legacy agreements remain accessible','Novi dogovor ili zahtjev za pomoć' in html_page)
check('Composer is available without an upgrade banner','<span class="fcc-pro-badge">PRO</span>' not in re.search(r'<div class="ft-outreach".*?</noscript>',html_page,re.S).group(0))
stamp=re.search(r'data-contact-stamp="([^"]+)"',html_page).group(1)
body={'action':'team_outreach','operation':'open','member_id':m,'purpose':'checkin','message':'Bok! Čćžđš & + 🪴\n\nhttps://forevercard.club/vip-edukacija','request_key':'e'*32,'contact_stamp':stamp,'due_date':time.strftime('%Y-%m-%d')}
client.post(path,{**body,'token':'bad'});check('JSON endpoint enforces CSRF',client.status==422 and not json.loads(client.body)['ok'])
client.post(path,{**body,'token':token});j=json.loads(client.body);check('Authenticated request succeeds',client.status==200 and j['ok'])
url=urllib.parse.urlparse(j['whatsapp_url']);check('Full edited message and member phone preserved',url.path=='/385990000002' and urllib.parse.parse_qs(url.query)['text'][0]==body['message'])
row=j['draft'];check('Endpoint records only open intent',row['opened_at'] and not row['sent_self_reported_at'])
client.get('/partner');check('Chosen due reminder appears under Today','Provjeri razgovor s timom' in client.body and 'Slanje nije potvrđeno' in client.body)
client.post(path,{**body,'token':token,'operation':'confirm','draft_id':row['id'],'version':row['version']});j=json.loads(client.body);check('Explicit send confirmation accepted',j['ok'] and j['draft']['sent_self_reported_at'])
row=j['draft']
for op,state in [('unconfirm','draft'),('copy','pending'),('not_sent','draft'),('copy','pending'),('confirm','sent'),('remove','removed'),('restore','sent')]:
 client.post(path,{**body,'token':token,'operation':op,'draft_id':row['id'],'version':row['version']});j=json.loads(client.body)
 check('HTTP correction '+op+' yields '+state,j['ok'] and j['draft']['state']==state);row=j['draft']
 if op=='remove':
  client.get('/partner');check('Removed record no longer creates a Today reminder','Provjeri razgovor s timom' not in client.body)
  client.get(path);check('Removed record has restore action','data-history-operation="restore"' in client.body)
client.get(path);check('History separates preparation, sending and removed records',all('data-team-group="'+key+'"' in client.body for key in ['preparations','confirmed','removed_group']))
client.get(path);check('Recorded text is escaped in history',html.escape(body['message']).replace('\n','<br />\n') in client.body or 'Čćžđš &amp; +' in client.body)
php('fcc_partner_query("UPDATE users SET plan_id=\'free\',plan_settings=\'{}\' WHERE user_id=?",['+str(a)+']);')
client.post(path,{**body,'token':token});j=json.loads(client.body);check('Expired/free entitlement rejects stale form with upgrade link',client.status==403 and not j['ok'] and 'account-plan?feature=team' in j['upgrade_url'])
client.get('/partner');check('Free has no team reminder disclosure','Provjeri razgovor s timom' not in client.body)
print(json.dumps({'passed':len(checks),'checks':checks},ensure_ascii=False,indent=2))
