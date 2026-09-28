import os,ftplib,io,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
with open('release-pro-sync/audit.php','rb') as f:ftp.storbinary('STOR pro-sync-audit.php',f)
for attempt in range(108):
 names=ftp.nlst()
 if 'pro-sync-audit.done' in names or 'pro-sync-audit.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private audit has not run yet')
if 'pro-sync-audit.failed' in names:raise SystemExit('Private audit requires review')
b=io.BytesIO();ftp.retrbinary('RETR pro-sync-audit.sealed.json',b.write)
sealed=b.getvalue().hex().translate(str.maketrans('0123456789abcdef','ABCDEFGHIJKLMNOP'))
for i in range(0,len(sealed),6000):print('SEALED_PRO_AUDIT '+sealed[i:i+6000])
b=io.BytesIO();ftp.retrbinary('RETR pro-sync-audit.done',b.write);print('PRO_AUDIT '+b.getvalue().decode());ftp.quit()
