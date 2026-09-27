import os,ftplib,io,json,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(12):
 names=ftp.nlst()
 status=[x for x in names if x.split('/')[-1] in ('sponsor-release-v2.done','sponsor-release-v2.failed')]
 if status:break
 time.sleep(10)
if not status:raise SystemExit('Installer has not completed yet')
for name in status:
 b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue());print(json.dumps({k:d[k] for k in ('status','phase','message','file','line','started_at','completed_at','linked_count','team_notifications') if k in d}));
 if name.endswith('.failed'):raise SystemExit('Guarded installer stopped; production review required')
for attempt in range(24):
 names=ftp.nlst()
 batches=[n for n in names if 'sponsor-links-' in n and n.endswith(('.done','.failed'))]
 if len(batches)>=3:break
 time.sleep(5)
if len(batches)!=3:raise SystemExit('Sponsor batches are not complete yet')
linked_count=0
for name in names:
 if 'sponsor-links-' in name and name.endswith(('.done','.failed')):
  b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue());print(json.dumps({'batch':name.split('/')[-1],'status':d.get('status'),'count':d.get('count',len(d.get('linked',[]))),'error':d.get('error')}));linked_count+=d.get('count',0)
  if d.get('status')!='LINKED':raise SystemExit('A sponsor batch requires review')
if linked_count!=30:raise SystemExit('Expected 30 reviewed sponsor links')
print('VERIFIED_SPONSOR_LINKS '+str(linked_count))
roots=[n for n in ftp.nlst() if 'sponsor-root-' in n and n.endswith(('.done','.failed'))]
root_count=0;root_visible=0;root_direct=0
for name in roots:
 b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue())
 print(json.dumps({'root_batch':name.split('/')[-1],'status':d.get('status'),'count':d.get('count',len(d.get('linked',[]))),'direct':d.get('direct'),'visible':d.get('visible'),'error':d.get('error')}))
 if d.get('status')!='LINKED':raise SystemExit('Canonical sponsor batch requires review')
 root_count+=d.get('count',0);root_visible=max(root_visible,d.get('visible',0));root_direct=max(root_direct,d.get('direct',0))
if roots:
 if len(roots)!=3 or root_count!=29 or root_visible<54 or root_direct<29:raise SystemExit('Canonical sponsor batches not complete yet')
 print('VERIFIED_CANONICAL_SPONSOR '+json.dumps({'new_links':root_count,'direct':root_direct,'visible':root_visible}))
else:print('CANONICAL_SPONSOR_INSTALLER_STAGED_AWAITING_PRIVATE_INPUT')
ftp.quit()
