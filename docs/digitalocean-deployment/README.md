# DigitalOcean Deployment Guide for Glyph

This folder contains a comprehensive guide for deploying Glyph to DigitalOcean using your GitHub Student Pack $200 credit.

## Quick Links

| Document | Description |
|----------|-------------|
| [01-OVERVIEW.md](./01-OVERVIEW.md) | Cost summary, architecture, and your configuration |
| [02-PRODUCTION-READINESS.md](./02-PRODUCTION-READINESS.md) | What's ready vs. what needs preparation |
| [03-LOCAL-PREPARATION.md](./03-LOCAL-PREPARATION.md) | Steps to prepare files locally before deployment |
| [04-SERVER-SETUP.md](./04-SERVER-SETUP.md) | Creating and configuring the DigitalOcean Droplet |
| [05-DEPLOY-APPLICATION.md](./05-DEPLOY-APPLICATION.md) | Deploying the Laravel application |
| [06-SECURITY-HARDENING.md](./06-SECURITY-HARDENING.md) | Security best practices and credential rotation |
| [07-MAINTENANCE.md](./07-MAINTENANCE.md) | Ongoing maintenance and quick reference commands |

## Your Configuration

| Setting | Value |
|---------|-------|
| **Architecture** | Single 2GB Droplet ($12/month) |
| **Region** | Singapore (SGP1) |
| **Domain** | Droplet IP (can add domain later) |
| **Duration** | 4-5 months |
| **Total Cost** | $48-$60 |
| **Remaining Credit** | $140-$152 |

## Deployment Workflow

```
┌─────────────────────────────────────────────────────────────┐
│  Step 1: Prepare local files (03-LOCAL-PREPARATION.md)     │
│          - Update .env.example                              │
│          - Create nginx config                              │
│          - Build frontend assets                            │
├─────────────────────────────────────────────────────────────┤
│  Step 2: Create DigitalOcean Droplet (04-SERVER-SETUP.md)  │
│          - Create 2GB Droplet in Singapore                  │
│          - Install PHP, MySQL, nginx, Supervisor            │
├─────────────────────────────────────────────────────────────┤
│  Step 3: Deploy application (05-DEPLOY-APPLICATION.md)     │
│          - Clone repository                                 │
│          - Configure environment                            │
│          - Run migrations                                   │
├─────────────────────────────────────────────────────────────┤
│  Step 4: Security hardening (06-SECURITY-HARDENING.md)     │
│          - Rotate credentials                               │
│          - Set up backups                                   │
└─────────────────────────────────────────────────────────────┘
```

## Prerequisites

Before starting deployment:

- [ ] GitHub Student Pack claimed
- [ ] DigitalOcean $200 credit activated
- [ ] SSH key generated (or password authentication)
- [ ] Steam API key ready
- [ ] Agora.io credentials ready
- [ ] Gmail App Password ready

## Support

If you encounter issues during deployment, share the error logs with Claude Code for troubleshooting assistance.
