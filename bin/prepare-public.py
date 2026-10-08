#!/usr/bin/env python3
from pathlib import Path
import shutil
root=Path(__file__).resolve().parent.parent
target=root/'public'/'assets'
target.mkdir(parents=True,exist_ok=True)
shutil.copytree(root/'assets',target,dirs_exist_ok=True)
print('Public assets prepared.')
