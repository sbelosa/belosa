import os,ftplib,io,json,time
ftp=ftplib.FTP();ftp.connect(os.environ['FTP_SERVER'],int(os.environ.get('FTP_PORT') or 21),timeout=30);ftp.login(os.environ['FTP_USERNAME'],os.environ['FTP_PASSWORD']);ftp.cwd('/fcc-release-20260927')
for attempt in range(60):
 names=[x.split('/')[-1] for x in ftp.nlst()]
 found=next((n for n in ['mobile-billing-install.failed','mobile-billing-install.done'] if n in names),None)
 if found:break
 time.sleep(5)
else:raise SystemExit('Private installer is staged but has not completed')
b=io.BytesIO();ftp.retrbinary('RETR '+found,b.write);ftp.quit();d=json.loads(b.getvalue())
print(json.dumps({k:d[k] for k in ['status','at','files','retired_references','expired_count','stripe_subscription_count','payment_history_preserved','referral_urls_preserved','rollout_preserved','phase','message','committed'] if k in d}))
if found.endswith('.failed'):raise SystemExit('Guarded installer failed and requires review')
