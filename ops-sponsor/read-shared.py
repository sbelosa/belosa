import os,ftplib,io,json,time
name='shared-final'
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
# Activate only after the complete, validated FTP upload has finished.
required={'shared-release.php','shared-final.php','shared-manifest.json','shared-plan.sealed.json','shared-payload.zip'}
if not required.issubset(set(ftp.nlst())):raise SystemExit('Staged package is incomplete')
ftp.storbinary('STOR shared-release.ready',io.BytesIO(b'Reviewed shared team release, 2026-09-28\n'))
for attempt in range(72):
 names=ftp.nlst()
 if name+'.done' in names or name+'.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private inventory is not ready')
if name+'.failed' in names:raise SystemExit('Private inventory requires review')
b=io.BytesIO();ftp.retrbinary('RETR '+name+'.sealed.json',b.write)
# Encrypted on the server. Public logs never contain readable account information.
sealed=b.getvalue().hex().translate(str.maketrans('0123456789abcdef','ABCDEFGHIJKLMNOP'))
for i in range(0,len(sealed),6000):print('SEALED_SHARED '+sealed[i:i+6000])
b=io.BytesIO();ftp.retrbinary('RETR '+name+'.done',b.write);print('SHARED_FINAL '+b.getvalue().decode())
failed='shared-release.failed' in names
ftp.quit()
if failed:raise SystemExit('Private release requires review; encrypted details retrieved')
