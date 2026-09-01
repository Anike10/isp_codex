$ErrorActionPreference='Stop'
$targetDir='G:\xampp\htdocs\isp_codex\proxmox-full-backup-final-20260901'
$files=@(
  'vzdump-lxc-105-2026_09_01-21_34_34.log','vzdump-lxc-105-2026_09_01-21_34_34.tar.zst',
  'vzdump-qemu-101-2026_09_01-21_37_43.log','vzdump-qemu-101-2026_09_01-21_37_43.vma.zst',
  'vzdump-qemu-102-2026_09_01-21_39_41.log','vzdump-qemu-102-2026_09_01-21_39_41.vma.zst',
  'vzdump-qemu-103-2026_09_01-21_41_09.log','vzdump-qemu-103-2026_09_01-21_41_09.vma.zst',
  'vzdump-qemu-104-2026_09_01-21_44_10.log','vzdump-qemu-104-2026_09_01-21_44_10.vma.zst',
  'vzdump-qemu-107-2026_09_01-21_45_14.log','vzdump-qemu-107-2026_09_01-21_45_14.vma.zst',
  'vzdump-qemu-112-2026_09_01-21_47_46.log','vzdump-qemu-112-2026_09_01-21_47_46.vma.zst',
  'vzdump-qemu-115-2026_09_01-21_48_03.log','vzdump-qemu-115-2026_09_01-21_48_03.vma.zst',
  'vzdump-qemu-100-2026_09_01-21_15_50.log','vzdump-qemu-100-2026_09_01-21_15_50.vma.zst',
  'vzdump-lxc-105-2026_09_01-21_48_59.log','vzdump-lxc-105-2026_09_01-21_49_02.log','vzdump-lxc-110-2026_09_01-21_49_54.log','vzdump-lxc-222-2026_09_01-21_49_57.log'
)

foreach($f in $files){
  if($f -like 'vzdump-qemu-100*'){
    $src='/root/vzdump_full_20260901_214300/'+$f
  } else {
    $src='/var/lib/vz/dump/'+$f
  }

  $dst=Join-Path $targetDir $f
  if(Test-Path $dst){ Remove-Item $dst -Force }

  Write-Output "COPY_START:$f"
  & 'C:\Program Files\PuTTY\pscp.exe' -batch -q -P 2233 -pw 'hafsa@leponvi' "root@162.4.6.8:$src" $dst
  $ec=${LASTEXITCODE}
  if($ec -ne 0){ Write-Output "COPY_FAIL:${f}:${ec}"; break }
  Write-Output "COPY_OK:$f"
}
Write-Output 'DONE'
