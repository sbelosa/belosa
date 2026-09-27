import os,ftplib,io,json,gzip,subprocess,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(36):
 names=ftp.nlst()
 if 'sponsor-phone-inspect.failed' in names:raise SystemExit('Private contact snapshot failed')
 if 'sponsor-phone-inspect.json' in names:break
 time.sleep(5)
else:raise SystemExit('Private contact snapshot not ready')
b=io.BytesIO();ftp.retrbinary('RETR sponsor-phone-inspect.json',b.write);ftp.quit();d=json.loads(b.getvalue())
print(json.dumps({'at':d['at'],'records':len(d['contacts']),'files':len(d['files'])}))
sealed=subprocess.run(['node','ops-sponsor/seal.mjs'],input=gzip.compress(b.getvalue()),check=True,capture_output=True).stdout.decode()
for i in range(0,len(sealed),6000):print('SEALED_CONTACTS '+sealed[i:i+6000])
