FROM erseco/alpine-php-webserver

# Switch to root to set up the application
USER root

# Copy application source (public web root)
COPY --chown=nobody:nobody public_html/ /app/public_html/

# Copy Nginx security configuration
COPY config/nginx-security.conf /etc/nginx/conf.d/default.conf

# Create config directory with correct ownership so the app can read mounted config
RUN mkdir -p /app/config && chown nobody:nobody /app/config

# Do NOT copy config/config.php — it contains secrets and must be mounted at runtime

# Switch back to non-root user
USER nobody

EXPOSE 8080
