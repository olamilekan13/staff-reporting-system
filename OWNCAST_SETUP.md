# Owncast Setup Guide

## What is Owncast?
Owncast is a free, self-hosted live streaming server (like your own Twitch).
You stream from OBS → Owncast → your staff watch it embedded in the app.

---

## Development Setup (Windows / Laragon)

1. Download the Owncast Windows binary from GitHub:
   https://github.com/owncast/owncast/releases
   File to download: owncast-0.x.x-windows-64bit.zip

2. Extract the ZIP to: C:\owncast\

3. Run owncast.exe — it starts on http://localhost:8080

4. Open the Owncast admin panel: http://localhost:8080/admin
   Default password: abc123 — CHANGE THIS IMMEDIATELY

5. In admin panel → Configuration → Server:
   Set your Stream Key (you'll use this in OBS)

6. Configure OBS Studio:
   Settings → Stream
   Service: Custom...
   Server: rtmp://localhost/live
   Stream Key: (the key you set in step 5)

7. Update Laravel .env:
   OWNCAST_SERVER_URL=http://localhost:8080
   OWNCAST_STREAM_KEY=your-stream-key
   OWNCAST_EMBED_URL=http://localhost:8080/embed/video

8. Run: php artisan config:clear

---

## Production Setup — Same AWS EC2 Instance

Use this option to start quickly. You can move Owncast to its own server later.

1. SSH into your EC2 instance

2. Install Owncast:
   curl -s https://owncast.online/install.sh | bash
   cd ~/owncast

3. Run Owncast in background:
   nohup ./owncast &
   OR set up as a systemd service (recommended)

4. Open these ports in your EC2 Security Group:
   - Port 8080 (TCP) — Owncast web interface + API
   - Port 1935 (TCP) — RTMP ingest (OBS streams to this)

5. Access admin panel: http://YOUR-EC2-IP:8080/admin
   Change the default password and set your stream key.

6. Update Laravel .env on the SAME server (use localhost):
   OWNCAST_SERVER_URL=http://localhost:8080
   OWNCAST_STREAM_KEY=your-stream-key
   OWNCAST_EMBED_URL=http://localhost:8080/embed/video
   NOTE: Use localhost because Laravel and Owncast are on the same machine.

7. OBS streams to: rtmp://YOUR-EC2-PUBLIC-IP/live

---

## Production Setup — Separate EC2 Instance (Recommended for Scale)

Use a dedicated t3.small (2 vCPU, 2GB RAM) for Owncast.
This prevents streaming from affecting your Laravel app performance.

1. Launch new EC2: t3.small, Ubuntu 22.04
2. Follow steps 2-5 from the "Same Instance" section above
3. On the LARAVEL server, update .env to point to the Owncast server:
   OWNCAST_SERVER_URL=http://OWNCAST-EC2-IP:8080
   OWNCAST_EMBED_URL=http://OWNCAST-EC2-IP:8080/embed/video
4. OBS streams to: rtmp://OWNCAST-EC2-IP/live

---

## How the Data Flows

OBS (your computer or phone)
  → RTMP → Owncast server (port 1935)
     → transcodes to HLS segments
        → Staff browsers load video DIRECTLY from Owncast

Laravel app:
  → Polls GET /api/status on Owncast every 30 seconds (cached)
  → Returns is_live status to Alpine.js on dashboard/sidebar
  → Laravel does NOT proxy the video stream (no bandwidth cost on Laravel)

---

## Owncast API Endpoint Used

GET http://your-owncast-server:8080/api/status
Returns:
{
  "online": true/false,
  "viewerCount": 5,
  "name": "My Stream",
  "lastConnectTime": "2026-02-19T10:00:00Z"
}
