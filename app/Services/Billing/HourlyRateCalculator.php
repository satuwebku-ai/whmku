<?php

namespace App\Services\Billing;

use App\Models\Server;

/**
 * Hitung biaya per jam dari spesifikasi VM + kartu harga SERVER
 * tempat VM itu berjalan (bukan kartu harga global) -- supaya tiap
 * provider cloud (IDCloudHost sekarang, provider lain nanti) bisa
 * punya tarif sendiri-sendiri tanpa saling memengaruhi.
 *
 * Formula (persis seperti yang ditentukan):
 *   biaya/jam = (harga_CPU x jumlah_vCPU)
 *             + (harga_RAM x jumlah_GB_RAM)
 *             + (harga_storage_main x ukuran_disk_GB)
 *             + (harga_backup x ukuran_disk_GB, jika backup aktif)
 *             + (harga_snapshot x ukuran_snapshot_GB, jika ada snapshot)
 *             + (harga_windows_license x vCPU, jika OS Windows)
 */
class HourlyRateCalculator
{
    /**
     * @param  array{vcpu:int,ram:int,disk:int,os_name:string,backup_enabled:bool,snapshot_gb:float}  $spec
     *                RAM dalam MB (konsisten dengan format IdCloudHostService), disk & snapshot dalam GB.
     */
    public static function calculate(Server $server, array $spec): float
    {
        $vcpu = (float) ($spec['vcpu'] ?? 0);
        $ramGb = (float) ($spec['ram'] ?? 0) / 1024; // disimpan dalam MB, formula butuh GB
        $diskGb = (float) ($spec['disk'] ?? 0);
        $snapshotGb = (float) ($spec['snapshot_gb'] ?? 0);
        $backupActive = (bool) ($spec['backup_enabled'] ?? false);
        $isWindows = str_contains(strtolower($spec['os_name'] ?? ''), 'windows');

        $total = 0.0;
        $total += $vcpu * (float) ($server->price_per_vcpu_hour ?? 0);
        $total += $ramGb * (float) ($server->price_per_ram_gb_hour ?? 0);
        $total += $diskGb * (float) ($server->price_per_storage_gb_hour ?? 0);

        if ($backupActive) {
            $total += $diskGb * (float) ($server->price_per_backup_gb_hour ?? 0);
        }

        if ($snapshotGb > 0) {
            $total += $snapshotGb * (float) ($server->price_per_snapshot_gb_hour ?? 0);
        }

        if ($isWindows) {
            $total += $vcpu * (float) ($server->price_windows_license_per_vcpu_hour ?? 0);
        }

        return round($total, 4);
    }

    /**
     * Rincian per komponen -- dipakai untuk ditampilkan ke admin/klien
     * (mis. di halaman kelola VM nanti), supaya jelas dari mana angka
     * totalnya berasal, bukan cuma satu angka tanpa penjelasan.
     */
    public static function breakdown(Server $server, array $spec): array
    {
        $vcpu = (float) ($spec['vcpu'] ?? 0);
        $ramGb = (float) ($spec['ram'] ?? 0) / 1024;
        $diskGb = (float) ($spec['disk'] ?? 0);
        $snapshotGb = (float) ($spec['snapshot_gb'] ?? 0);
        $backupActive = (bool) ($spec['backup_enabled'] ?? false);
        $isWindows = str_contains(strtolower($spec['os_name'] ?? ''), 'windows');

        $lines = [
            'CPU' => $vcpu * (float) ($server->price_per_vcpu_hour ?? 0),
            'RAM' => $ramGb * (float) ($server->price_per_ram_gb_hour ?? 0),
            'Storage' => $diskGb * (float) ($server->price_per_storage_gb_hour ?? 0),
        ];

        if ($backupActive) {
            $lines['Backup'] = $diskGb * (float) ($server->price_per_backup_gb_hour ?? 0);
        }

        if ($snapshotGb > 0) {
            $lines['Snapshot'] = $snapshotGb * (float) ($server->price_per_snapshot_gb_hour ?? 0);
        }

        if ($isWindows) {
            $lines['Lisensi Windows'] = $vcpu * (float) ($server->price_windows_license_per_vcpu_hour ?? 0);
        }

        return $lines;
    }
}
