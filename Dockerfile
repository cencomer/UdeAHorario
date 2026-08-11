FROM nginx:alpine
COPY index.html /usr/share/nginx/html/
COPY manifest.json /usr/share/nginx/html/
COPY sw.js /usr/share/nginx/html/
COPY icons/ /usr/share/nginx/html/icons/
COPY js/ /usr/share/nginx/html/js/
COPY data/data.json /usr/share/nginx/html/data/data.json
EXPOSE 80
