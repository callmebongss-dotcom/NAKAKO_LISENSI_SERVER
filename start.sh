#!/bin/bash
# NAKAKO LICENSE SERVER - Start script for Railway/Linux
# Uses PORT env or defaults to 8080
PORT=${PORT:-8080}
echo "Starting NAKAKO LICENSE SERVER on port $PORT..."
php -S 0.0.0.0:$PORT -t public public/index.php
