# লাইভে দ্রুত আপডেট (Simple Live Update Notes)

**শেষ আপডেট (লোকাল ডেস্কটপে):** 2026-08-11

## 1) লোকালে সব পরীক্ষা শেষে কমিট করা
1. `git status` clean আছে কিনা দেখুন
2. `php artisan test` (যদি সম্ভব)
3. `git add .` + `git commit -m "..."`

## 2) লগইন তথ্য (পাসওয়ার্ড ছাড়া নোটে)
- Proxmox host: `162.4.6.8`
- SSH port: `2233`
- SSH user: `root`
- VM ID: `102`
- VM private IP: `192.168.8.252`
- App path (VM ভেতর): `/home/isp.us.com.bd/isp_codex`
- App runtime user: `ispus3797`
- SSH host key (documented): `SHA256:ajsC09Yg/+hgcn2YAETDOYavBgcqxEqQDQcyeYRd33c`

## 3) সবচেয়ে সহজ আপডেট ফ্লো (প্রতিবার এক কমান্ড)

### Option A: Proxmox CLI দিয়ে (প্রস্তাবিত)
```bash
plink -P 2233 -ssh root@162.4.6.8 -hostkey "SHA256:ajsC09Yg/+hgcn2YAETDOYavBgcqxEqQDQcyeYRd33c" ^
"qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git pull --ff-only origin main && php artisan optimize:clear'"
```
> এখানে পাসওয়ার্ড চাইলে একই কমান্ডটি একইভাবে দিন, কিন্তু ফাইলে পাসওয়ার্ড সেভ করবেন না।

### Option B: যদি VM-এ SSH সরাসরি কাজ করে
```bash
ssh -p 2233 root@162.4.6.8
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc "cd /home/isp.us.com.bd/isp_codex && git pull --ff-only origin main && php artisan optimize:clear"
```

## 4) অতিরিক্ত পরিষ্কার কাজ (সবসময় শেষে)
- Git status নিশ্চিত করুন: শুধু প্রত্যাশিত পরিবর্তন আছে কিনা
- রুট কনফিগ/লাইব্রেরি না বদলালে শুধু এই তিন লাইন প্রয়োজন:
  - `git pull --ff-only`
  - `php artisan optimize:clear`
  - ব্রাউজারে চেক: `https://isp.us.com.bd/customers`

## 5) জরুরি ব্যাকআপ (যদি পরিবর্তন বেশি হয়)
```bash
qm guest exec 102 -- bash -lc "tar -czf /home/isp.us.com.bd/isp_codex/storage/app/deploy_backups/isp_codex_$(date +%Y%m%d_%H%M%S).tgz -C /home/isp.us.com.bd isp_codex"
```

## 6) 5 মিনিটে ডাবল চেক
- লগইন পেজ ওপেন হয়
- গ্রাহক পেজে নতুন বাটন দেখা যায়
- মিক্রোটিক রাউটার show page-এ inactive profile এলার্ট/বাটন কাজ করে

**নিরাপত্তা নিয়ম:** এই নোটে **পাসওয়ার্ড লিখবেন না**। প্রতিবার লগইন টাইমে টাইপ দিয়ে দিন।