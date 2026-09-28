import os,io,ftplib,hashlib,pathlib
source=pathlib.Path('release-shared/shared-admin-form.php').read_bytes()
expected='f4d90d7f378e8f5c50dedf7884b840acff9cd7e194884ffcd3441dacfa93bf56'
target='/public_html/themes/altum/views/admin/partials/sponsor-fields.php'
private='/fcc-release-20260927/'
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
def read(path):
 out=io.BytesIO();ftp.retrbinary('RETR '+path,out.write);return out.getvalue()
current=read(target)
if current!=source:
 if hashlib.sha256(current).hexdigest()!=expected:raise SystemExit('Concurrent production edit, file left unchanged')
 ftp.storbinary('STOR '+private+'shared-admin-form-before.php',io.BytesIO(current))
 staged=private+'shared-admin-form-reviewed.php'
 ftp.storbinary('STOR '+staged,io.BytesIO(source))
 if read(staged)!=source:raise SystemExit('Staging verification failed')
 ftp.rename(staged,target)
if read(target)!=source:raise SystemExit('Published file verification failed')
ftp.quit();print('SHARED_ADMIN_FORM_VERIFIED '+hashlib.sha256(source).hexdigest())
