import os,io,ftplib,hashlib,json,pathlib,base64,subprocess
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD'])
files={}
for path in json.loads(pathlib.Path('ops-team-outreach/files.json').read_text()):
 b=io.BytesIO();ftp.retrbinary('RETR /public_html/'+path,b.write);data=b.getvalue()
 files[path]={'sha256':hashlib.sha256(data).hexdigest(),'base64':base64.b64encode(data).decode()}
ftp.quit()
r=subprocess.run(['php','ops-team-outreach/seal.php'],input=json.dumps({'files':files}).encode(),stdout=subprocess.PIPE,check=True)
sealed=r.stdout.hex().translate(str.maketrans('0123456789abcdef','ABCDEFGHIJKLMNOP'))
for i in range(0,len(sealed),6000):print('SEALED_OUTREACH_AUDIT '+sealed[i:i+6000])
print('OUTREACH_AUDIT_FILES '+str(len(files)))
