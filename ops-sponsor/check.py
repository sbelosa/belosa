import os,ftplib,io,json,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(12):
 names=ftp.nlst()
 status=[x for x in names if x.split('/')[-1] in ('sponsor-release.done','sponsor-release.failed')]
 if status:break
 time.sleep(10)
if not status:raise SystemExit('Installer has not completed yet')
for name in status:
 b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue());print(json.dumps({k:d[k] for k in ('status','phase','message','file','line','started_at','completed_at','linked_count','team_notifications') if k in d}));
 if name.endswith('.failed'):
  import zipfile,hashlib,base64,zlib
  b=io.BytesIO();ftp.retrbinary('RETR sponsor-live-code.zip',b.write);old=zipfile.ZipFile(b);refresh={}
  for path in old.namelist():
   current=io.BytesIO();ftp.retrbinary('RETR /public_html/'+path,current.write)
   if current.getvalue()!=old.read(path):refresh[path]={'sha256':hashlib.sha256(current.getvalue()).hexdigest(),'zlib':base64.b64encode(zlib.compress(current.getvalue(),9)).decode()}
  print('SPONSOR_REFRESH_JSON '+json.dumps(refresh,separators=(',',':')))
  raise SystemExit('Guarded installer stopped; production review required')
for name in names:
 if 'sponsor-links-' in name and name.endswith(('.done','.failed')):
  b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue());print(json.dumps({'batch':name.split('/')[-1],'status':d.get('status'),'count':d.get('count',len(d.get('linked',[]))),'error':d.get('error')}))
ftp.quit()
