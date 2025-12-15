# Overview - DigitalOcean Hosting for Glyph

## Feasibility Summary

**Verdict: YES - DigitalOcean hosting is fully feasible with your $200 GitHub Student Pack credit.**

## Your Selected Configuration

| Setting | Choice |
|---------|--------|
| **Architecture** | Single 2GB Droplet ($12/month) |
| **Region** | Singapore (SGP1) - lowest latency for Malaysia |
| **Domain** | Droplet IP initially (can add domain later) |
| **Credit Status** | Already claimed |
| **Duration** | 4-5 months |
| **Total Cost** | $48-$60 |
| **Remaining Credit** | $140-$152 |

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                   DigitalOcean Droplet                      │
│                   Ubuntu 22.04 LTS - 2GB                    │
│                   Region: Singapore (SGP1)                  │
├─────────────────────────────────────────────────────────────┤
│  nginx (reverse proxy)                                      │
│    ├── Port 80 → Laravel PHP-FPM                           │
│    └── /app → Proxy to Reverb WebSocket (port 8080)        │
├─────────────────────────────────────────────────────────────┤
│  PHP 8.2-FPM                                                │
│    └── Laravel 12 Application                               │
├─────────────────────────────────────────────────────────────┤
│  MySQL 8.0 (local)                                          │
│    └── Glyph Database                                        │
├─────────────────────────────────────────────────────────────┤
│  Supervisor (Process Manager)                               │
│    ├── Laravel Reverb (WebSocket server)                    │
│    ├── Queue Worker (2 processes)                           │
│    └── Scheduler (every minute)                             │
└─────────────────────────────────────────────────────────────┘
```

## Why This Architecture?

1. **Cost-effective**: $12/month vs $27+ with managed services
2. **All-in-one**: No network latency between app and database
3. **Sufficient resources**: 2GB handles Laravel + MySQL + Reverb for moderate traffic
4. **Easy scaling**: Can resize droplet if needed (4GB = $24/month)

## Cost Breakdown

### Your Configuration
| Service | Monthly Cost | 5-Month Total |
|---------|--------------|---------------|
| 2GB Droplet (Singapore) | $12 | $60 |
| **Total** | **$12/month** | **$60** |
| **Your $200 Credit** | | **$200** |
| **Remaining After 5 Months** | | **$140** |

### Budget Flexibility
With $140 remaining after 5 months, you could:
- Extend hosting for 11+ more months
- Upgrade to 4GB droplet ($24/month) if needed
- Add automated backups ($2.40/month)
- Purchase a domain (~$10-15/year)

### Optional Add-ons
| Service | Monthly Cost | Notes |
|---------|--------------|-------|
| Droplet Backups | $2.40 | Automated weekly backups (recommended) |
| Upgrade to 4GB | +$12 | If performance issues arise ($24/month total) |
| Domain Name | ~$10-15/year | From Namecheap, Cloudflare, etc. |

## Services Running on the Droplet

| Service | Purpose | Port |
|---------|---------|------|
| nginx | Web server / reverse proxy | 80 |
| PHP-FPM | Laravel application | Unix socket |
| MySQL | Database | 3306 (localhost only) |
| Laravel Reverb | WebSocket server | 8080 |
| Queue Worker | Background jobs | N/A |
| Scheduler | Cron tasks | N/A |

## External Services (Not hosted on DigitalOcean)

| Service | Purpose | Cost |
|---------|---------|------|
| Agora.io | Voice chat | Free tier available |
| Steam API | Authentication & game data | Free |
| Gmail SMTP | Email sending | Free |
| Telegram Bot | Notifications (optional) | Free |

## GitHub Student Pack Credit Details

- **Credit Amount**: $200
- **Validity**: 12 months from activation
- **Eligibility**: Verified via GitHub Education
- **Redemption**: education.github.com/pack → DigitalOcean offer

## Sources

- [DigitalOcean Pricing](https://www.digitalocean.com/pricing)
- [DigitalOcean Laravel Hosting](https://www.digitalocean.com/solutions/laravel-hosting)
- [GitHub Student Developer Pack - DigitalOcean](https://www.digitalocean.com/github-students)
- [Laravel Reverb Documentation](https://laravel.com/docs/12.x/reverb)
