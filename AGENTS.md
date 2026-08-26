# Project Instructions

## Local-first deployment workflow

- Every bug fix and feature change must be implemented in the local workspace first.
- The local workspace is the source of truth. Never edit application source code directly on the live server.
- Deploy only the exact files already changed locally, and only after the user explicitly requests a live update.
- If a problem is found on the live site, fix it locally first and then deploy the same local code so local and live remain identical.
- Create a live backup before replacing deployed files.
