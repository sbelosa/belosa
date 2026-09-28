import os,io,ftplib,json,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
base='/fcc-release-20260927/team-outreach-20260928/'
for name in ['migration.done','verification.done','outreach.failed']:
 b=io.BytesIO()
 try:ftp.retrbinary('RETR '+base+name,b.write)
 except ftplib.error_perm as e:
  if str(e).startswith('550'):print(name+' PENDING');continue
  raise
 data=json.loads(b.getvalue());print(name+' '+json.dumps(data))
 if name=='outreach.failed':raise SystemExit('Private verification failed')
ftp.quit()

# Final production verification after the outreach publication.
# Recheck after CLI asset initialization.
