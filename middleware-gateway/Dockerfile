FROM node:20-alpine

# Install curl for health checks
RUN apk add --no-cache curl

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install production dependencies. Fall back to npm install when no lockfile is present.
RUN if [ -f package-lock.json ]; then npm ci --omit=dev; else npm install --omit=dev; fi

# Copy application code
COPY server.js .

# Create logs directory
RUN mkdir -p logs

# Set environment
ENV NODE_ENV=production

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD curl -f http://localhost:3000/api/v3/health || exit 1

# Expose port
EXPOSE 3000

# Run application
CMD ["node", "server.js"]
