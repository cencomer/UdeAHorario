# Build stage
FROM golang:1.22-alpine AS builder
WORKDIR /app
COPY go.mod ./
COPY main.go ./
RUN go build -ldflags="-s -w" -o api-horario .

# Run stage
FROM alpine:3.19
WORKDIR /app
COPY --from=builder /app/api-horario .
COPY data/ ./data/
EXPOSE 8080
CMD ["./api-horario"]
