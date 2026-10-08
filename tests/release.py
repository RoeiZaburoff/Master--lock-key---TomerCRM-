#!/usr/bin/env python3
from pathlib import Path
import hashlib,json,zipfile
root=Path(__file__).resolve().parent.parent
with zipfile.ZipFile(root/'dist/masterlocknkey-siteground.zip') as z:
    names=z.namelist()
    assert 'public_html/index.php' in names and 'crm-private/bootstrap.php' in names
    assert 'crm-private/database/migrations/003_initial_warehouse.sql' in names
    assert all(n.startswith(('public_html/','crm-private/','instructions/')) or n in ['README.md','BUILD-INFO.json','SHA256-MANIFEST.json'] for n in names)
    assert not any(n.endswith('/local.php') or '/node_modules/' in n or '/tests/' in n or '/.git/' in n or '/.env' in n for n in names)
    assert all(n.endswith('/') for n in names if n.startswith('crm-private/storage/'))
    assert all('crm-private/storage/'+d+'/' in names for d in ['logos','inventory','pdf'])
    assert not any(n.startswith('public_html/') and n.endswith('.php') and n!='public_html/index.php' for n in names)
    for n,digest in json.loads(z.read('SHA256-MANIFEST.json')).items():
        assert hashlib.sha256(z.read(n)).hexdigest()==digest,n
    assert b"'RETELL_ENABLED' => false" in z.read('crm-private/config/local.example.php')
    assert b"'WEBSITE_ENABLED' => false" in z.read('crm-private/config/local.example.php')
    assert json.loads(z.read('BUILD-INFO.json'))['voice_enabled'] is False
    print('PASS SiteGround archive layout, privacy exclusions and all manifest checksums')
with zipfile.ZipFile(root/'dist/masterlocknkey-wordpress-plugin.zip') as z:
    assert z.namelist()==['masterlocknkey-crm/','masterlocknkey-crm/masterlocknkey-crm.php']
    print('PASS WordPress plugin ZIP structure')
