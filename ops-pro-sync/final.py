import os,ftplib,io,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
with open('release-pro-sync/final.php','rb') as f:ftp.storbinary('STOR pro-sync-final.php',f)
for attempt in range(108):
 names=ftp.nlst()
 if 'pro-sync-final.done' in names or 'pro-sync-final.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private audit has not run yet')
if 'pro-sync-final.failed' in names:raise SystemExit('Private audit requires review')
b=io.BytesIO();ftp.retrbinary('RETR pro-sync-final.sealed.json',b.write)
sealed=b.getvalue().hex().translate(str.maketrans('0123456789abcdef','ABCDEFGHIJKLMNOP'))
for i in range(0,len(sealed),6000):print('SEALED_PRO_FINAL '+sealed[i:i+6000])
b=io.BytesIO();ftp.retrbinary('RETR pro-sync-final.done',b.write);print('PRO_AUDIT '+b.getvalue().decode());ftp.quit()
