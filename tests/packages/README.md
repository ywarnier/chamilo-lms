This folder contains packaged versions of Chamilo. This only exists in the `packaged` branch and serves for systems where, exceptionnally, only direct `git clone` commands are allowed, as these might also block composer or yarn and prevent proper on-system packaging.

Files are split in 50MB chunks to avoid Github's restrictions on file size. Installing git lfs is recommended, with `git lfs install` and adding the `*.p` extension to the git lfs tracking:
```
git lfs install
git lfs track '*.chunk'
```

Split:
```
split -b 49M {version}.tar.gz {version} --additional-suffix=.chunk.txt
rm {version}.tar.gz
git add {version}*
git commit -m "Misc: Add chunks of packaged {version}" .
```

Join:
```
cat {version}* >> {version}.tar.gz
tar zxf {version}.tar.gz
```
