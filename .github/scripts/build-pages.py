#!/usr/bin/env python3
"""Build the Basics of Full Stack GitHub Pages site."""
from pathlib import Path
import runpy
runpy.run_path(str(Path(__file__).resolve().parents[2] / 'course/build.py'), run_name='__main__')
