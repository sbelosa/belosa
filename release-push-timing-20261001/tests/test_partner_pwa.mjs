// Exercise actual registration and service-worker code against isolated browser mocks.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const root=new URL('../',import.meta.url);
const installer=fs.readFileSync(new URL('themes/altum/assets/js/fcc-partner-install.js',root),'utf8');
const worker=fs.readFileSync(new URL('fcc-partner-sw.js',root),'utf8');
const base='https://fcc.example/';const checks=[];
const check=(label,ok)=>{assert.ok(ok,label);checks.push(label);};
async function install(registrations=[],fail=false,secure=true) {
 const writes=[],removed=[],status={hidden:true};
 const regs=registrations.map(([scope,script,waiting])=>({scope:base+scope,active:{scriptURL:base+script},waiting:waiting?{scriptURL:base+waiting}:null,unregister:async()=>removed.push(scope)}));
 const context={URL,location:{origin:'https://fcc.example'},window:{fccT:s=>s,isSecureContext:secure,matchMedia:()=>({matches:false}),addEventListener(){}},document:{querySelector:s=>s==='base'?{href:base}:s==='[data-install-status]'?status:null},navigator:{serviceWorker:{getRegistrations:async()=>regs,register:async(...args)=>{if(fail)throw new Error('offline');writes.push(args);}}}};
 vm.runInNewContext(installer,context);await new Promise(resolve=>setImmediate(resolve));
 return {writes,removed,status};
}
let result=await install();
check('new installs use a dedicated /partner worker scope',result.writes[0][1].scope===base+'partner');
result=await install([['','service-worker.js']]);
check('existing public root worker is kept',result.writes.length===1&&result.removed.length===0);
result=await install([['','fcc-partner-sw.js']]);
check('earlier Partner root registration migrates to dedicated scope',result.writes.length===1&&result.removed[0]==='');
result=await install([['','fcc-partner-sw.js','service-worker.js']]);
check('a foreign pending worker is never unregistered',result.removed.length===0);
result=await install([['partner','other-worker.js']]);
check('foreign Partner scope is not overwritten',!result.writes.length&&!result.status.hidden);
result=await install([['partner/cards','other-worker.js']]);
check('overlapping child worker requires explicit review',!result.writes.length);
result=await install([['','fcc-partner-sw.js']],true);
check('failed migration preserves old worker and exposes useful status',!result.removed.length&&!result.status.hidden);
result=await install([],false,false);check('insecure contexts never register',!result.writes.length);

// Install controls must track browser acceptance and the real standalone state.
async function installUI({standalone=false,ua='',touch=0,accept=true}={}) {
 const events={},clicks={},moved=[],status={hidden:true,textContent:''},guide={hidden:false},open={},other={hidden:true};
 const button={hidden:true,disabled:false,addEventListener:(n,f)=>clicks[n]=f};
 const sections=['ios','android'].map(device=>({dataset:{installDevice:device}}));
 const nodes={'[data-install-guide]':guide,'[data-install-open]':open,'[data-install-other]':other,'[data-install-other-content]':{append:n=>moved.push(n.dataset.installDevice)}};
 const page={querySelector:s=>nodes[s],querySelectorAll:()=>sections};
 const mode={matches:standalone,addEventListener(){}};
 const nodesDoc={'[data-install-page]':page,'[data-install]':button,'[data-install-status]':status};
 const context={URL,location:{origin:'https://fcc.example'},window:{fccT:s=>s,isSecureContext:true,matchMedia:()=>mode,addEventListener:(n,f)=>events[n]=f},document:{querySelector:s=>nodesDoc[s]||null},navigator:{userAgent:ua,maxTouchPoints:touch}};
 vm.runInNewContext(installer,context);
 let prompts=0;events.beforeinstallprompt({preventDefault(){},prompt:async()=>prompts++,userChoice:Promise.resolve({outcome:accept?'accepted':'dismissed'})});
 return {button,status,guide,open,other,moved,prompts:()=>prompts,click:()=>clicks.click(),events};
}
let ui=await installUI({ua:'iPhone'});
check('iPhone places Android instructions in optional disclosure',ui.moved.join(',')==='android'&&!ui.other.hidden);
ui=await installUI({ua:'Macintosh',touch:5});
check('iPad desktop user agent still gets Safari guidance',ui.moved.join(',')==='android');
ui=await installUI({ua:'Android'});
check('Android keeps its primary guide and one install action',ui.moved.join(',')==='ios'&&!ui.button.hidden);
await ui.click();check('accepted install clears reusable prompt and confirms',ui.prompts()===1&&ui.button.hidden&&ui.status.textContent.includes('potvrđena'));
ui=await installUI({accept:false});await ui.click();
check('dismissal never claims successful installation',ui.prompts()===1&&ui.button.hidden&&!ui.status.textContent.includes('potvrđena'));
ui=await installUI({standalone:true});
check('installed app hides redundant install guide and prompt',ui.button.hidden&&ui.guide.hidden&&ui.open.textContent==='Otvori moj FCC');
await ui.click();check('standalone never opens a second install prompt',ui.prompts()===0);

const events={},cached=[],deleted=[],claims=[];
const context={URL,self:{skipWaiting:async()=>true,location:{origin:'https://fcc.example'},clients:{claim:async()=>claims.push(true)},addEventListener:(name,fn)=>events[name]=fn},caches:{open:async()=>({addAll:async urls=>cached.push(...urls)}),keys:async()=>['fcc-partner-shell-v1','fcc-partner-shell-v2','fcc-partner-shell-v3','public-card-cache'],delete:async key=>deleted.push(key),match:async path=>'cache:'+path},fetch:async request=>'network:'+request.url};
vm.runInNewContext(worker,context);
let pending;events.install({waitUntil:p=>pending=p});await pending;
check('offline install stores only public fallback and icons',cached.length===3&&cached.every(path=>path.startsWith('/partner-assets/')));
events.activate({waitUntil:p=>pending=p});await pending;
check('activation keeps unrelated public-card cache',deleted.length===3&&deleted.includes('fcc-partner-shell-v1')&&deleted.includes('fcc-partner-shell-v2')&&deleted.includes('fcc-partner-shell-v3'));
async function fetchRoute(path,method='GET',mode='navigate'){
 let promise;events.fetch({request:{url:base+path,method,mode},respondWith:p=>promise=p});return promise?await promise:null;
}
check('authenticated navigation always uses network',await fetchRoute('partner/contacts')==='network:'+base+'partner/contacts');
context.fetch=async()=>{throw new Error('offline');};
check('failed private navigation shows generic offline page',await fetchRoute('partner/contacts')==='cache:/partner-assets/offline.html');
for(const path of ['ana-demo','blog/article','vip-funnel/7/demo','link/3','account-plan','pay/2','admin/users','partner/state']) {
 check('private cache never intercepts '+path,await fetchRoute(path,'GET',path==='partner/state'?'cors':'navigate')===null);
}
check('form POST is never intercepted or queued',await fetchRoute('partner/contacts','POST')===null);
const pushed=[];context.self.registration={showNotification:async(title,options)=>pushed.push({title,options})};
events.push({data:{json:()=>({title:'PRIVATE NAME',body:'PRIVATE CONTENT',url:'https://evil.example/'})},waitUntil:p=>pending=p});await pending;
check('push lock-screen payload stays generic',pushed[0].title==='FCC Partner'&&!JSON.stringify(pushed).includes('PRIVATE'));
check('push destination stays inside authenticated inbox',pushed[0].options.data.url==='/partner/notifications');
events.push({data:{json:()=>({url:'https://fcc.example/partner/webinars/45'})},waitUntil:p=>pending=p});await pending;
check('webinar notification opens its exact authenticated occurrence',pushed[1].options.data.url==='/partner/webinars/45');
for(const url of ['https://evil.example/partner/webinars/45','/partner/webinars/45?token=secret','//evil.example/partner/webinars/45','/partner/webinars/45/join','/partner/webinars/0'])check('unsafe event push destination rejected '+url,context.fccNotificationPath(url)==='/partner/notifications');
events.push({data:{json:()=>({url:'https://fcc.example/partner/contacts?id=76'})},waitUntil:p=>pending=p});await pending;
check('business notification opens its exact authenticated contact',pushed[2].options.data.url==='/partner/contacts?id=76');
for(const url of ['https://evil.example/partner/contacts?id=76','/partner/contacts?id=76&token=secret','/partner/contacts?id=0','/partner/contacts?id=76#private'])check('unsafe contact destination rejected '+url,context.fccNotificationPath(url)==='/partner/notifications');
let opened,focused=false;context.self.clients.matchAll=async()=>[{url:base+'partner',navigate:async u=>{opened=u;},focus:async()=>{focused=true;}}];
events.notificationclick({notification:{data:{url:'/partner/contacts?id=76'},close(){}},waitUntil:p=>pending=p});await pending;
check('click focuses existing app at the contact',opened===base+'partner/contacts?id=76'&&focused);
console.log(JSON.stringify({status:'PASS',count:checks.length,checks},null,2));

for(const path of ['/partner','/partner?view=step','/partner?view=progress','/partner/team','/partner/team?member=176'])check('daily and team destinations retained '+path,context.fccNotificationPath(path)===path);
for(const path of ['/partner?view=admin','/partner/team?member=0','/partner/team?member=176&token=x','https://evil.test/partner'])check('invalid daily or team destination blocked '+path,context.fccNotificationPath(path)==='/partner/notifications');
console.log(JSON.stringify({total:checks.length}));

for(const locale of ['hr','en','sl','de','es']){
 for(const kind of ['service','admin','daily','team','cc','followup','tasks','education','webinar','webinar_reminder','webinar_changed','webinar_cancelled','webinar_postponed','support','approval','nfc','test','contact_lead','guest_registration','team_group']){
  const p=context.fccPushPresentation({schema:2,kind,locale,sound:true,title:'PRIVATE NAME',body:'PRIVATE DATA'});
  check('safe localized type '+locale+' '+kind,p.title.startsWith('FCC · ')&&p.options.body.length>20&&!JSON.stringify(p).includes('PRIVATE')&&p.options.silent===false);
 }
}
let p=context.fccPushPresentation({schema:2,kind:'summary',locale:'hr',counts:{team:7,admin:3,unknown:999},sound:false});
check('ten simultaneous events use one explanatory summary',p.title==='Novosti u FCC-u'&&p.options.body.includes('10.')&&p.options.body.includes('Napredak tima: 7')&&p.options.body.includes('Administracija: 3'));
check('silent sound preference never sets forbidden vibration option',p.options.silent===true&&!Object.hasOwn(p.options,'vibrate'));
p=context.fccPushPresentation({schema:2,kind:'test',locale:'hr',sound:true});
check('sound uses normal platform tone and one short vibration without repeat alerts',p.options.silent===false&&p.options.vibrate.join(',')==='60'&&p.options.renotify===false);
for(const kind of ['constructor','__proto__','unknown']){
 p=context.fccPushPresentation({schema:2,kind,locale:'__proto__',body:'PRIVATE'});
 check('unrecognized or prototype type remains generic '+kind,p.title==='FCC Partner'&&!p.options.body.includes('PRIVATE'));
}
for(const data of [null,[],42,{schema:2,kind:'summary',counts:{team:-1,service:'PRIVATE'}},{schema:2,kind:'summary',counts:{team:99999}}])check('malformed data has safe fallback',context.fccPushPresentation(data).title==='FCC Partner');
for(const path of ['/partner/points?updates=1','/partner/education','/partner/contacts?filter=due','/partner/share?type=webinar'])check('safe category destination '+path,context.fccNotificationPath(path)===path);
console.log(JSON.stringify({status:'PASS',total:checks.length}));

p=context.fccPushPresentation({schema:2,kind:'cc',locale:'hr',ccDetails:'Dobrovoljno uključeni CC detalji',sound:false});
check('explicit CC detail payload is honored only for CC',p.options.body==='Dobrovoljno uključeni CC detalji');
p=context.fccPushPresentation({schema:2,kind:'service',locale:'hr',ccDetails:'PRIVATE'});
check('CC opt-in field cannot change other notification types',!p.options.body.includes('PRIVATE'));
console.log(JSON.stringify({status:'PASS',total:checks.length}));
