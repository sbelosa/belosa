import os,ftplib,io,json,gzip,subprocess,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(60):
 names=ftp.nlst()
 if 'sponsor-gen-inventory.done' in names or 'sponsor-gen-inventory.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private contact release result not ready')
name='sponsor-gen-inventory.failed' if 'sponsor-gen-inventory.failed' in names else 'sponsor-gen-inventory.done'
b=io.BytesIO();ftp.retrbinary('RETR '+('sponsor-gen-inventory.json' if name.endswith('.done') else name),b.write);ftp.quit();d=json.loads(b.getvalue())
print(json.dumps({'at':d.get('at'),'users':len(d.get('users',[])),'team':len(d.get('team',[]))}))
sealed=subprocess.run(['node','ops-sponsor/seal.mjs'],input=gzip.compress(b.getvalue()),check=True,capture_output=True).stdout.decode()
for i in range(0,len(sealed),6000):print('SEALED_GEN_INVENTORY '+sealed[i:i+6000])
if name.endswith('.failed'):raise SystemExit('Private contact release failed; encrypted details available')
