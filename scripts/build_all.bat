@echo off

echo - Begin X Library Batch Build -

rem Create a list of all .js files in x/lib, that file will be used by x.xcp.

del lib\x_files.txt
for %%f in (lib\*.js) do echo %%~nf >> lib\x_files.txt

rem Run XC on all .xcp files in the current dirctory.

for %%f in (*.xcp) do xc\xc %%~nf

echo - End X Library Batch Build -
