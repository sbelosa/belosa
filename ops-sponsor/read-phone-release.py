import os,ftplib,io,json,gzip,subprocess,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(60):
 names=ftp.nlst()
 if 'sponsor-phone-release.done' in names or 'sponsor-phone-release.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private contact release result not ready')
name='sponsor-phone-release.failed' if 'sponsor-phone-release.failed' in names else 'sponsor-phone-release.done'
b=io.BytesIO();ftp.retrbinary('RETR '+name,b.write);ftp.quit();d=json.loads(b.getvalue())
print(json.dumps({k:d.get(k) for k in ['status','updated_count','sponsors','sponsors_with_phone','profiles_with_manager','relationships_preserved','rollout_preserved','phase','committed']}))
sealed=subprocess.run(['node','ops-sponsor/seal.mjs'],input=gzip.compress(b.getvalue()),check=True,capture_output=True).stdout.decode()
for i in range(0,len(sealed),6000):print('SEALED_PHONE_RESULT '+sealed[i:i+6000])
if name.endswith('.failed'):raise SystemExit('Private contact release failed; encrypted details available')
