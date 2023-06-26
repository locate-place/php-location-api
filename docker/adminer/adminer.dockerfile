# Add needed arguments
ARG IMAGE_ADD

# Use latest nginx image
FROM ${IMAGE_ADD}adminer:latest

# Copy nginx configuration
COPY config/login-servers.php /var/www/html/plugins-enabled/login-servers.php
