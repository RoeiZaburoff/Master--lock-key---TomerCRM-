#!/usr/bin/env python3
import argparse, http.cookiejar, urllib.request, urllib.parse, urllib.error, json, pathlib, re, time, uuid, hashlib, hmac
p=argparse.ArgumentParser();p.add_argument('--base',default='http://127.0.0.1:8081');p.add_argument('--password-file',required=True);p.add_argument('--settings-file');p.add_argument('--integrations',action='store_true');p.add_argument('--security',action='store_true');a=p.parse_args()
base=a.base.rstrip('/');password=pathlib.Path(a.password_file).read_text().strip()
def check(ok,name):
    if not ok:raise RuntimeError(name)
    print('PASS',name)
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(path,body=None,headers=None,opener=client):
    try:
        r=opener.open(urllib.request.Request(base+path,data=body,headers=headers or {}),timeout=15);return r.status,r.read(),r.geturl()
    except urllib.error.HTTPError as e:return e.code,e.read(),e.geturl()
def login(user,pwd):
    code,page,_=request('/login');token=re.search(rb'name="_csrf" value="([^"]+)"',page).group(1).decode()
    return request('/login',urllib.parse.urlencode({'username':user,'password':pwd,'_csrf':token}).encode())
check(request('/health')[0]==200,'health check')
check(request('/intake',opener=urllib.request.build_opener())[2].endswith('/login'),'protected page requires login')
code,page,url=login('admin',password);check(code==200 and url.rstrip('/')==base and b'MasterLocknKey' in page,'session login and brand')
ids=json.loads(pathlib.Path(a.settings_file).read_text()) if a.settings_file else {}
for path in ['/customers','/leads','/leads/create','/calendar','/inventory','/settings','/reports','/invoices','/intake']:
    code,page,_=request(path);check(code==200 and b'Fatal error' not in page and b'Warning:' not in page,'page '+path)
if 'invoice' in ids:
    code,pdf,_=request('/invoices/'+str(ids['invoice'])+'/pdf');check(code==200 and pdf.startswith(b'%PDF-'),'invoice PDF')
for path in ['/config/local.php','/database/migrations/001_core.sql','/bin/migrate.php','/.env','/setpass.php']:
    check(request(path)[0] in [403,404],'private file blocked '+path)
check(request('/settings/general',b'cc_fee_percentage=9',{'Content-Type':'application/x-www-form-urlencoded'})[0]==403,'missing CSRF rejected')
if a.security:
    request('/logout');check(login('operator-fixture',password)[0]==200,'ordinary user can login')
    for path in ['/settings','/invoices','/inventory','/intake','/lead-sources','/contractors']:
        check(request(path)[0]==403,'ordinary user cannot access '+path)
    check(request('/leads')[0]==200,'ordinary user retains operational lead access')
    request('/logout');login('admin',password)
if not a.integrations:
    for path in ['/api/v1/website-inquiries','/api/v1/voice/retell']:
        check(request(path,b'{}',{'Content-Type':'application/json'})[0]==503,'disabled by default '+path)
else:
    event=str(uuid.uuid4());data={'event_id':event,'full_name':'Website fixture','phone':'202-555-0123','company_id':ids['company_b'],'description':'Needs a lock'}
    def website(payload,stamp=None,signature=None):
        raw=json.dumps(payload,separators=(',',':')).encode();stamp=stamp or str(int(time.time()))
        digest=signature or hmac.new(ids['website_secret'].encode(),stamp.encode()+b'.'+raw,hashlib.sha256).hexdigest()
        return request('/api/v1/website-inquiries',raw,{'Content-Type':'application/json','X-CRM-Integration':'wordpress-main','X-CRM-Timestamp':stamp,'X-CRM-Signature':digest})
    check(website(data,signature='0'*64)[0]==401,'unsigned website request rejected')
    check(website(data,stamp=str(int(time.time())-400))[0]==401,'stale website signature rejected')
    code,body,_=website(data);check(code==202 and json.loads(body)['accepted'],'signed website request queued')
    check(json.loads(website(data)[1])['duplicate'],'website retry deduplicated')
    check(website(data|{'full_name':'Different'})[0]==409,'idempotency conflict rejected')
    def voice(event_type,agent='fixture-agent',signature=None):
        data={'event':event_type,'call':{'call_id':'fixture-call','agent_id':agent,'from_number':'202-555-0123','to_number':'202-555-0140','transcript':'Do not store this fixture transcript','call_analysis':{'call_summary':'Needs a new lock','custom_analysis_data':{'full_name':'Voice fixture','priority_hint':'urgent'}}}}
        raw=json.dumps(data,separators=(',',':')).encode();stamp=str(int(time.time()*1000))
        sig=signature or 'v='+stamp+',d='+hmac.new(ids['retell_secret'].encode(),raw+stamp.encode(),hashlib.sha256).hexdigest()
        return request('/api/v1/voice/retell',raw,{'Content-Type':'application/json','X-Retell-Signature':sig})
    check(voice('call_analyzed',signature='invalid')[0]==401,'unsigned voice request rejected')
    check(voice('call_analyzed','unknown-agent')[0]==403,'unknown agent rejected')
    check(voice('call_analyzed')[0]==202,'signed analyzed-call fixture queued')
    check(voice('call_started')[0]==202,'out-of-order started fixture queued')
    check(json.loads(voice('call_analyzed')[1])['duplicate'],'voice retry deduplicated')
    check(request('/api/v1/voice/retell',b'x'*262145,{'Content-Type':'application/json'})[0]==413,'oversized payload rejected')
