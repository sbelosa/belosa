import os,io,ftplib,hashlib,json,pathlib,subprocess,tempfile,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
private='/fcc-release-20260927/team-outreach-20260928/files/'
try:ftp.mkd(private)
except ftplib.error_perm as e:
 if not str(e).startswith('550'):raise
def read(path,optional=False):
 out=io.BytesIO()
 try:ftp.retrbinary('RETR '+path,out.write)
 except ftplib.error_perm as e:
  if optional and str(e).startswith('550'):return None
  raise
 return out.getvalue()
def sha(data):return hashlib.sha256(data).hexdigest()
migration=read('/fcc-release-20260927/team-outreach-20260928/migration.done')
if json.loads(migration)['schema_sha256']!=sha(pathlib.Path('release-team-outreach/schema.sql').read_bytes()):raise SystemExit('Schema migration must complete before publication')
prepared=[]
for i,e in enumerate(json.loads(pathlib.Path('ops-team-outreach/manifest.json').read_text())):
 target='/public_html/'+e['path'];before=read(target,optional=e.get('before') is None and 'patches' not in e)
 if 'patches' in e:
  source=before.decode()
  for patch in e['patches']:
   if patch['new'] in source:continue
   if source.count(patch['old'])!=1:raise SystemExit('Concurrent code edit requires review: '+e['path'])
   source=source.replace(patch['old'],patch['new'],1)
  source=source.encode()
 else:
  source=pathlib.Path('release-team-outreach/files',e['path']).read_bytes()
  if sha(source)!=e['sha256']:raise SystemExit('Local payload mismatch')
  if before!=source and (sha(before) if before is not None else None)!=e.get('before'):raise SystemExit('Concurrent production edit: '+e['path'])
 if e['path'].endswith('.php'):
  with tempfile.NamedTemporaryFile(suffix='.php') as f:
   f.write(source);f.flush();subprocess.run(['php','-l',f.name],check=True,stdout=subprocess.DEVNULL)
 if before==source:continue
 staged=private+str(i)+'.reviewed';backup=private+str(i)+'.before'
 if before is not None:
  ftp.storbinary('STOR '+backup,io.BytesIO(before))
  if read(backup)!=before:raise SystemExit('Backup verification failed')
 ftp.storbinary('STOR '+staged,io.BytesIO(source))
 if read(staged)!=source:raise SystemExit('Staging verification failed')
 prepared.append((e['path'],target,staged,backup,before,source))
published=[]
try:
 for path,target,staged,backup,before,source in prepared:
  if read(target,optional=before is None)!=before:raise RuntimeError('Concurrent change during publication')
  ftp.rename(staged,target);published.append((path,target,staged,backup,before,source))
  if read(target)!=source:raise RuntimeError('Published bytes mismatch')
  if path=='app/init.php':time.sleep(3)
except Exception:
 for path,target,staged,backup,before,source in reversed(published):
  if before is not None:
   rollback=backup+'.rollback';ftp.storbinary('STOR '+rollback,io.BytesIO(before));ftp.rename(rollback,target)
 raise
for path,target,staged,backup,before,source in prepared:print('OUTREACH_FILE_VERIFIED '+path+' '+sha(source))
ftp.storbinary('STOR /fcc-release-20260927/team-outreach-20260928/verify.enabled',io.BytesIO(b'1'))
ftp.quit();print('OUTREACH_FILES_PUBLISHED '+str(len(prepared)))
