import os,ftplib,io,json,gzip,subprocess,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(20):
 names=ftp.nlst()
 if 'sponsor-rest-inventory.failed' in names:raise SystemExit('Private inventory failed')
 if 'sponsor-final-inventory.json' in names or 'sponsor-research.failed' in names:break
 time.sleep(5)
else:raise SystemExit('Private inventory not ready')
b=io.BytesIO();name='sponsor-final-inventory.json' if 'sponsor-final-inventory.json' in names else 'sponsor-rest-inventory.json'
ftp.retrbinary('RETR '+name,b.write);d=json.loads(b.getvalue())
if 'sponsor-research.failed' in names:
 failure=io.BytesIO();ftp.retrbinary('RETR sponsor-research.failed',failure.write)
 d['research_failure']=json.loads(failure.getvalue());print('RESEARCH_RESULT_FAILED')
ftp.quit()
print(json.dumps({'inventory_time':d['at'],'users':len(d['users']),'team_records':len(d['team']),'new_links':(d.get('research') or {}).get('count')}))
sealed=subprocess.run(['node','ops-sponsor/seal.mjs'],input=gzip.compress(json.dumps(d).encode()),check=True,capture_output=True).stdout.decode()
for i in range(0,len(sealed),6000):print('SEALED_INVENTORY '+sealed[i:i+6000])
