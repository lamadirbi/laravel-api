@echo off
REM تشغيل صندوق البريد التجريبي Mailpit
REM الواجهة: http://127.0.0.1:8025
REM SMTP لـ Laravel: 127.0.0.1:2525  (1025 غالباً محجوز في Windows)
echo.
echo  Mailpit inbox:  http://127.0.0.1:8025
echo  SMTP:           127.0.0.1:2525
echo  اضغط Ctrl+C لإيقافه
echo.
mailpit --smtp 127.0.0.1:2525 --listen 127.0.0.1:8025
