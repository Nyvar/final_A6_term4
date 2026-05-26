# Monefy API Server

Node.js backend for the Monefy Expo mobile app. Matches the contract in `api/rest.http` and `constants/api.ts`.

## Quick start

```bash
cd api-server
npm install
npm start
```

Server URL: **http://192.168.1.7/final_A6_term4/api**

> **Note:** If XAMPP is already using port 8888, either stop Apache or run this server on another port:  
> `set PORT=8899` (cmd) or `$env:PORT=8899` (PowerShell), then update `app.json` `apiBaseUrl` accordingly.

## Demo account

| Field    | Value      |
|----------|------------|
| Username | `kim`      |
| Password | `12345`    |

Seeded with **$1000 USD** balance and default expense/income categories.

## Test with REST Client

Open `api/rest.http` in VS Code (REST Client extension) and run the **Login** request, then copy the `token` into `@token` for other requests.

## Physical device (phone on same Wi‑Fi)

1. Find your PC IP: `ipconfig` → IPv4 (e.g. `192.168.18.112`)
2. Update `app.json` → `expo.extra.apiBaseUrl` to `http://<YOUR_IP>:8888/final_A6_term4/api`
3. Allow port 8888 in Windows Firewall (run as Administrator):

   ```powershell
   .\scripts\allow-api-firewall.ps1
   ```

4. Start API: `npm start` in `api-server`
5. Start app: `npx expo start` in project root

## Actions

All endpoints use `POST /final_A6_term4/api/?action=<name>` with JSON body. Authenticated routes require `"token": "..."` in the body.

See `api/rest.http` for full examples.
