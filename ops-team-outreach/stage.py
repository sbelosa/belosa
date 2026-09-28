import os,io,ftplib,pathlib
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
base='/fcc-release-20260927/team-outreach-20260928/'
try:ftp.mkd(base)
except ftplib.error_perm as e:
 if not str(e).startswith('550'):raise
for name in ['schema.sql','run.php']:
 data=pathlib.Path('release-team-outreach',name).read_bytes();ftp.storbinary('STOR '+base+name,io.BytesIO(data))
 b=io.BytesIO();ftp.retrbinary('RETR '+base+name,b.write)
 if b.getvalue()!=data:raise SystemExit('Private staging mismatch')
ftp.quit();print('OUTREACH_MIGRATION_STAGED')
