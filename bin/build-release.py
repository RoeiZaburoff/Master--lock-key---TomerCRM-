#!/usr/bin/env python3
"""Produce shared-hosting and WordPress archives without local credentials or customer data."""
from pathlib import Path
import hashlib, json, shutil, subprocess, zipfile
root=Path(__file__).resolve().parent.parent
out=root/'dist';out.mkdir(exist_ok=True)
stage=out/'siteground';stage.mkdir(exist_ok=True)
public=stage/'public_html';private=stage/'crm-private'
if any(stage.iterdir()):
    # Only this script's generated staging directory is replaced.
    shutil.rmtree(stage)
public.mkdir(parents=True);private.mkdir()
for folder in ['app','views','libs','database','bin']:
    shutil.copytree(root/folder,private/folder,ignore=shutil.ignore_patterns('__pycache__','*.pyc'))
(private/'config').mkdir()
for f in ['app.php','database.php','local.example.php']:
    shutil.copy2(root/'config'/f,private/'config'/f)
for f in ['autoload.php','bootstrap.php']:
    shutil.copy2(root/f,private/f)
(private/'.htaccess').write_text('Require all denied\n')
for d in ['pdf','logos','inventory']:(private/'storage'/d).mkdir(parents=True)
for f in ['index.php','.htaccess']:shutil.copy2(root/'public'/f,public/f)
shutil.copytree(root/'assets',public/'assets')
shutil.copytree(root/'docs',stage/'instructions')
shutil.copy2(root/'README.md',stage/'README.md')
try:commit=subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,stderr=subprocess.DEVNULL,text=True).strip()
except subprocess.CalledProcessError:commit='uncommitted'
(stage/'BUILD-INFO.json').write_text(json.dumps({'commit':commit,'brand':'MasterLocknKey','voice_enabled':False},indent=2)+'\n')
manifest={str(p.relative_to(stage)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(stage.rglob('*')) if p.is_file()}
(stage/'SHA256-MANIFEST.json').write_text(json.dumps(manifest,indent=2)+'\n')
for p in stage.rglob('*'):
    p.chmod(0o755 if p.is_dir() else 0o644)
stage.chmod(0o755)
def archive(src:Path,dst:Path):
    with zipfile.ZipFile(dst,'w',zipfile.ZIP_DEFLATED) as z:
        for p in sorted(src.rglob('*')):
            # Empty private storage directories are required on a fresh install.
            z.write(p,p.relative_to(src))
archive(stage,out/'masterlocknkey-siteground.zip')
plugin=root/'integrations'/'wordpress'
archive(plugin,out/'masterlocknkey-wordpress-plugin.zip')
print('Created masterlocknkey-siteground.zip and masterlocknkey-wordpress-plugin.zip')
