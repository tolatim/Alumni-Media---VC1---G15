# Step 1: Build the application
FROM node:24-alpine AS build-stage

WORKDIR /app

ARG VITE_API_URL
ARG VITE_WS_URL

ENV VITE_API_URL=$VITE_API_URL
ENV VITE_WS_URL=$VITE_WS_URL

COPY package*.json ./
RUN npm install

COPY . .

RUN npm run build

# Step 2: Serve the application with Nginx
FROM nginx:alpine AS production-stage

COPY ./nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Expose port 80 to the outside world
EXPOSE 80

# Start Nginx when the container launches
CMD ["nginx", "-g", "daemon off;"]
