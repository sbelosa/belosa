import os,io,ftplib,hashlib,pathlib
source=pathlib.Path('release-pro-sync/sync-helper.php').read_bytes()
expected='b7df39e49822b88aed2be5e4bdec9223f95414c8d3888bfe4e1b91b8ef84291a'
target='/public_html/app/helpers/forever_business.php'
private='/fcc-release-20260927/'
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
def read(path):
 out=io.BytesIO();ftp.retrbinary('RETR '+path,out.write);return out.getvalue()
current=read(target)
if current!=source:
 if hashlib.sha256(current).hexdigest()!=expected:raise SystemExit('Concurrent production edit, file left unchanged')
 ftp.storbinary('STOR '+private+'pro-sync-helper-before.php',io.BytesIO(current))
 staged=private+'pro-sync-helper-reviewed.php'
 ftp.storbinary('STOR '+staged,io.BytesIO(source))
 if read(staged)!=source:raise SystemExit('Staging verification failed')
 ftp.rename(staged,target)
if read(target)!=source:raise SystemExit('Published file verification failed')
ftp.quit();print('PRO_SYNC_HELPER_VERIFIED '+hashlib.sha256(source).hexdigest())
