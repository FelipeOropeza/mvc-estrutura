@echo off
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0forge
php "%BIN_TARGET%" %*
