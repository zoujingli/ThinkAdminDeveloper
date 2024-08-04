@echo off
setlocal

git pull

echo Deleting remote tags...
for /f "delims=" %%x in ('git tag') do git push --delete origin %%x

echo Deleting local tags...
for /f "delims=" %%i in ('git tag') do git tag -d %%i

echo All tags deleted.
pause